<?php

namespace App\Http\Controllers\Ahhob\Admin\Dashboard\Shop;

use App\Http\Controllers\Controller;
use App\Models\Ahhob\Shop\Order;
use App\Models\Ahhob\Shop\OrderItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class OrderManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /*
    |--------------------------------------------------------------------------
    | 주문 관리 (Order Management)
    |--------------------------------------------------------------------------
    */

    /**
     * 주문 목록
     */
    public function index(Request $request): View
    {
        $query = Order::with(['user', 'items.product']);

        // 검색 조건
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        // 주문 상태 필터
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // 결제 상태 필터
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->get('payment_status'));
        }

        // 배송 상태 필터
        if ($request->filled('shipping_status')) {
            $query->where('shipping_status', $request->get('shipping_status'));
        }

        // 날짜 필터
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        // 금액 범위 필터
        if ($request->filled('amount_min')) {
            $query->where('total_amount', '>=', $request->get('amount_min'));
        }
        if ($request->filled('amount_max')) {
            $query->where('total_amount', '<=', $request->get('amount_max'));
        }

        // 정렬
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $orders = $query->paginate(20);

        // 주문 상태 통계
        $statusStats = $this->getOrderStatusStats();

        return view('ahhob.admin.dashboard.shop.orders.index', compact('orders', 'statusStats'));
    }

    /**
     * 주문 상세보기
     */
    public function show(Order $order): View
    {
        $order->load([
            'user',
            'items.product.attachments',
            'paymentTransactions',
            'shippingInfo'
        ]);

        // 주문 히스토리
        $orderHistory = $this->getOrderHistory($order);

        return view('ahhob.admin.dashboard.shop.orders.show', compact('order', 'orderHistory'));
    }

    /**
     * 주문 상태 업데이트
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled,refunded',
            'note' => 'nullable|string|max:500',
        ]);

        $oldStatus = $order->status;
        $newStatus = $request->status;

        // 상태 변경 유효성 검사
        if (!$this->isValidStatusChange($oldStatus, $newStatus)) {
            return response()->json([
                'success' => false,
                'message' => '유효하지 않은 상태 변경입니다.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $order->update([
                'status' => $newStatus,
                'admin_note' => $request->note,
                'status_updated_at' => now(),
            ]);

            // 배송 상태 자동 업데이트
            if ($newStatus === 'shipped') {
                $order->update(['shipping_status' => 'shipped']);
            } elseif ($newStatus === 'delivered') {
                $order->update(['shipping_status' => 'delivered']);
            }

            // 주문 히스토리 기록
            $this->recordOrderHistory($order, $oldStatus, $newStatus, $request->note);

            // 상태별 추가 처리
            $this->handleStatusChangeActions($order, $newStatus);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '주문 상태가 업데이트되었습니다.',
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => '주문 상태 업데이트 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /**
     * 배송 정보 업데이트
     */
    public function updateShipping(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'shipping_company' => 'nullable|string|max:100',
            'tracking_number' => 'nullable|string|max:100',
            'shipping_date' => 'nullable|date',
            'estimated_delivery' => 'nullable|date|after:shipping_date',
            'shipping_note' => 'nullable|string|max:500',
        ]);

        $order->update([
            'shipping_company' => $request->shipping_company,
            'tracking_number' => $request->tracking_number,
            'shipping_date' => $request->shipping_date,
            'estimated_delivery' => $request->estimated_delivery,
            'shipping_note' => $request->shipping_note,
        ]);

        // 배송 시작 시 상태 자동 업데이트
        if ($request->shipping_date && $order->status === 'processing') {
            $order->update(['status' => 'shipped', 'shipping_status' => 'shipped']);
        }

        return response()->json([
            'success' => true,
            'message' => '배송 정보가 업데이트되었습니다.',
        ]);
    }

    /**
     * 주문 취소
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        // 취소 가능한 상태 확인
        if (!in_array($order->status, ['pending', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => '현재 상태에서는 주문을 취소할 수 없습니다.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $order->update([
                'status' => 'cancelled',
                'cancel_reason' => $request->reason,
                'cancelled_at' => now(),
                'cancelled_by' => auth('admin')->id(),
            ]);

            // 재고 복원
            $this->restoreStock($order);

            // 결제 취소 처리 (필요시)
            if ($order->payment_status === 'paid') {
                $this->processRefund($order, $order->total_amount, '주문 취소');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '주문이 취소되었습니다.',
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => '주문 취소 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /**
     * 환불 처리
     */
    public function refund(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0|max:' . $order->total_amount,
            'reason' => 'required|string|max:500',
        ]);

        if ($order->payment_status !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => '결제가 완료되지 않은 주문은 환불할 수 없습니다.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // 환불 처리
            $this->processRefund($order, $request->amount, $request->reason);

            // 부분 환불인 경우
            if ($request->amount < $order->total_amount) {
                $order->update(['status' => 'partially_refunded']);
            } else {
                $order->update(['status' => 'refunded']);
                $this->restoreStock($order);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '환불이 처리되었습니다.',
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => '환불 처리 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /**
     * 대량 작업
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:confirm,process,ship,cancel',
            'order_ids' => 'required|array',
            'order_ids.*' => 'integer|exists:shop_orders,id',
            'note' => 'nullable|string|max:500',
        ]);

        $orderIds = $request->order_ids;
        $action = $request->action;

        DB::beginTransaction();
        try {
            $orders = Order::whereIn('id', $orderIds)->get();
            $successCount = 0;

            foreach ($orders as $order) {
                $newStatus = match($action) {
                    'confirm' => 'confirmed',
                    'process' => 'processing',
                    'ship' => 'shipped',
                    'cancel' => 'cancelled',
                };

                if ($this->isValidStatusChange($order->status, $newStatus)) {
                    $order->update([
                        'status' => $newStatus,
                        'admin_note' => $request->note,
                        'status_updated_at' => now(),
                    ]);

                    $this->handleStatusChangeActions($order, $newStatus);
                    $successCount++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$successCount}개 주문이 처리되었습니다.",
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => '대량 작업 처리 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 배송 관리 (Shipping Management)
    |--------------------------------------------------------------------------
    */

    /**
     * 배송 관리 페이지
     */
    public function shipping(Request $request): View
    {
        $query = Order::with(['user', 'items.product'])
            ->whereIn('status', ['processing', 'shipped']);

        // 배송 상태 필터
        if ($request->filled('shipping_status')) {
            $query->where('shipping_status', $request->get('shipping_status'));
        }

        // 배송업체 필터
        if ($request->filled('shipping_company')) {
            $query->where('shipping_company', $request->get('shipping_company'));
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        // 배송 통계
        $shippingStats = $this->getShippingStats();

        return view('ahhob.admin.dashboard.shop.orders.shipping', compact('orders', 'shippingStats'));
    }

    /**
     * 대량 배송 업데이트
     */
    public function bulkUpdateShipping(Request $request): JsonResponse
    {
        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'integer|exists:shop_orders,id',
            'shipping_company' => 'required|string|max:100',
            'shipping_date' => 'required|date',
        ]);

        $updated = Order::whereIn('id', $request->order_ids)
            ->update([
                'shipping_company' => $request->shipping_company,
                'shipping_date' => $request->shipping_date,
                'status' => 'shipped',
                'shipping_status' => 'shipped',
            ]);

        return response()->json([
            'success' => true,
            'message' => "{$updated}개 주문의 배송 정보가 업데이트되었습니다.",
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 통계 (Statistics)
    |--------------------------------------------------------------------------
    */

    /**
     * 주문 통계
     */
    public function statistics(): View
    {
        $stats = Cache::remember('admin.order.statistics', 1800, function () {
            return [
                'overview' => $this->getOrderOverview(),
                'daily_trends' => $this->getDailyTrends(30),
                'top_customers' => $this->getTopCustomers(),
                'payment_methods' => $this->getPaymentMethodStats(),
                'regional_stats' => $this->getRegionalStats(),
            ];
        });

        return view('ahhob.admin.dashboard.shop.orders.statistics', compact('stats'));
    }

    /**
     * 매출 통계
     */
    public function salesStatistics(): View
    {
        $salesStats = Cache::remember('admin.sales.statistics', 1800, function () {
            return [
                'revenue_overview' => $this->getRevenueOverview(),
                'monthly_revenue' => $this->getMonthlyRevenue(12),
                'product_performance' => $this->getProductPerformance(),
                'average_order_value' => $this->getAverageOrderValue(),
            ];
        });

        return view('ahhob.admin.dashboard.shop.orders.sales-statistics', compact('salesStats'));
    }

    /**
     * 고객 통계
     */
    public function customerStatistics(): View
    {
        $customerStats = Cache::remember('admin.customer.statistics', 1800, function () {
            return [
                'customer_overview' => $this->getCustomerOverview(),
                'customer_lifetime_value' => $this->getCustomerLifetimeValue(),
                'repeat_customer_rate' => $this->getRepeatCustomerRate(),
                'customer_acquisition' => $this->getCustomerAcquisition(),
            ];
        });

        return view('ahhob.admin.dashboard.shop.orders.customer-statistics', compact('customerStats'));
    }

    /*
    |--------------------------------------------------------------------------
    | 헬퍼 메서드 (Helper Methods)
    |--------------------------------------------------------------------------
    */

    /**
     * 주문 상태 통계
     */
    private function getOrderStatusStats(): array
    {
        return [
            'pending' => Order::where('status', 'pending')->count(),
            'confirmed' => Order::where('status', 'confirmed')->count(),
            'processing' => Order::where('status', 'processing')->count(),
            'shipped' => Order::where('status', 'shipped')->count(),
            'delivered' => Order::where('status', 'delivered')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
            'refunded' => Order::where('status', 'refunded')->count(),
        ];
    }

    /**
     * 주문 히스토리 조회
     */
    private function getOrderHistory(Order $order): array
    {
        // 추후 구현: 주문 이력 테이블에서 조회
        return [
            [
                'action' => 'order_created',
                'description' => '주문이 생성되었습니다.',
                'created_at' => $order->created_at,
                'admin_user' => null,
            ],
        ];
    }

    /**
     * 유효한 상태 변경인지 확인
     */
    private function isValidStatusChange(string $from, string $to): bool
    {
        $validTransitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped' => ['delivered', 'cancelled'],
            'delivered' => ['refunded'],
            'cancelled' => [],
            'refunded' => [],
        ];

        return in_array($to, $validTransitions[$from] ?? []);
    }

    /**
     * 주문 히스토리 기록
     */
    private function recordOrderHistory(Order $order, string $from, string $to, ?string $note): void
    {
        // 추후 구현: 주문 이력 테이블에 기록
    }

    /**
     * 상태 변경에 따른 추가 처리
     */
    private function handleStatusChangeActions(Order $order, string $status): void
    {
        switch ($status) {
            case 'confirmed':
                // 재고 차감
                $this->decreaseStock($order);
                break;
                
            case 'cancelled':
                // 재고 복원
                $this->restoreStock($order);
                break;
                
            case 'delivered':
                // 배송 완료 처리
                $order->update(['delivered_at' => now()]);
                break;
        }
    }

    /**
     * 재고 차감
     */
    private function decreaseStock(Order $order): void
    {
        foreach ($order->items as $item) {
            $product = $item->product;
            if ($product) {
                $product->decrement('stock_quantity', $item->quantity);
                $product->increment('sales_count', $item->quantity);
            }
        }
    }

    /**
     * 재고 복원
     */
    private function restoreStock(Order $order): void
    {
        foreach ($order->items as $item) {
            $product = $item->product;
            if ($product) {
                $product->increment('stock_quantity', $item->quantity);
                $product->decrement('sales_count', $item->quantity);
            }
        }
    }

    /**
     * 환불 처리
     */
    private function processRefund(Order $order, float $amount, string $reason): void
    {
        // 추후 구현: 실제 결제 시스템과 연동하여 환불 처리
    }

    /**
     * 배송 통계
     */
    private function getShippingStats(): array
    {
        return [
            'ready_to_ship' => Order::where('status', 'processing')->count(),
            'shipped_today' => Order::whereDate('shipping_date', today())->count(),
            'in_transit' => Order::where('shipping_status', 'shipped')->count(),
            'delivered_today' => Order::whereDate('delivered_at', today())->count(),
        ];
    }

    /**
     * 주문 개요
     */
    private function getOrderOverview(): array
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();

        return [
            'total_orders' => Order::count(),
            'orders_today' => Order::where('created_at', '>=', $today)->count(),
            'orders_this_month' => Order::where('created_at', '>=', $thisMonth)->count(),
            'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount'),
            'revenue_today' => Order::where('payment_status', 'paid')
                ->where('created_at', '>=', $today)
                ->sum('total_amount'),
            'revenue_this_month' => Order::where('payment_status', 'paid')
                ->where('created_at', '>=', $thisMonth)
                ->sum('total_amount'),
        ];
    }

    /**
     * 일일 트렌드
     */
    private function getDailyTrends(int $days): array
    {
        $dates = [];
        $orderCounts = [];
        $revenues = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dates[] = $date->format('m/d');
            
            $dayOrders = Order::whereDate('created_at', $date)->count();
            $dayRevenue = Order::where('payment_status', 'paid')
                ->whereDate('created_at', $date)
                ->sum('total_amount');
            
            $orderCounts[] = $dayOrders;
            $revenues[] = (float) $dayRevenue;
        }

        return [
            'labels' => $dates,
            'orders' => $orderCounts,
            'revenue' => $revenues,
        ];
    }

    /**
     * 상위 고객
     */
    private function getTopCustomers(): array
    {
        return Order::select('user_id', 'customer_name', 'customer_email')
            ->selectRaw('COUNT(*) as order_count, SUM(total_amount) as total_spent')
            ->where('payment_status', 'paid')
            ->groupBy('user_id', 'customer_name', 'customer_email')
            ->orderBy('total_spent', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * 결제 수단 통계
     */
    private function getPaymentMethodStats(): array
    {
        return Order::select('payment_method')
            ->selectRaw('COUNT(*) as count, SUM(total_amount) as total')
            ->where('payment_status', 'paid')
            ->groupBy('payment_method')
            ->get()
            ->toArray();
    }

    /**
     * 지역별 통계
     */
    private function getRegionalStats(): array
    {
        // 추후 구현: 배송지 기준 지역별 주문 통계
        return [];
    }

    /**
     * 매출 개요
     */
    private function getRevenueOverview(): array
    {
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();
        $lastMonthEnd = $thisMonth->copy()->subSecond();

        $thisMonthRevenue = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $thisMonth)
            ->sum('total_amount');

        $lastMonthRevenue = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$lastMonth, $lastMonthEnd])
            ->sum('total_amount');

        $growthRate = $lastMonthRevenue > 0 
            ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 2)
            : 0;

        return [
            'this_month' => $thisMonthRevenue,
            'last_month' => $lastMonthRevenue,
            'growth_rate' => $growthRate,
        ];
    }

    /**
     * 월별 매출
     */
    private function getMonthlyRevenue(int $months): array
    {
        $data = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();
            
            $revenue = Order::where('payment_status', 'paid')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('total_amount');
            
            $data[] = [
                'month' => $month->format('Y-m'),
                'revenue' => (float) $revenue,
            ];
        }

        return $data;
    }

    /**
     * 상품 성과
     */
    private function getProductPerformance(): array
    {
        return OrderItem::with('product')
            ->select('product_id')
            ->selectRaw('SUM(quantity) as total_sold, SUM(quantity * price) as total_revenue')
            ->groupBy('product_id')
            ->orderBy('total_revenue', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * 평균 주문 금액
     */
    private function getAverageOrderValue(): array
    {
        $dates = [];
        $values = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dates[] = $date->format('m/d');
            
            $avg = Order::where('payment_status', 'paid')
                ->whereDate('created_at', $date)
                ->avg('total_amount');
            
            $values[] = round($avg ?? 0, 2);
        }

        return [
            'labels' => $dates,
            'values' => $values,
        ];
    }

    /**
     * 고객 개요
     */
    private function getCustomerOverview(): array
    {
        return [
            'total_customers' => Order::distinct('user_id')->count(),
            'new_customers_this_month' => Order::where('created_at', '>=', now()->startOfMonth())
                ->distinct('user_id')
                ->count(),
            'repeat_customers' => Order::select('user_id')
                ->groupBy('user_id')
                ->havingRaw('COUNT(*) > 1')
                ->count(),
        ];
    }

    /**
     * 고객 생애 가치
     */
    private function getCustomerLifetimeValue(): float
    {
        return Order::where('payment_status', 'paid')
            ->selectRaw('AVG(customer_total) as clv')
            ->fromSub(function ($query) {
                $query->select('user_id')
                    ->selectRaw('SUM(total_amount) as customer_total')
                    ->from('shop_orders')
                    ->where('payment_status', 'paid')
                    ->groupBy('user_id');
            }, 'customer_totals')
            ->value('clv') ?? 0;
    }

    /**
     * 재구매율
     */
    private function getRepeatCustomerRate(): float
    {
        $totalCustomers = Order::distinct('user_id')->count();
        $repeatCustomers = Order::select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        return $totalCustomers > 0 ? round(($repeatCustomers / $totalCustomers) * 100, 2) : 0;
    }

    /**
     * 고객 획득
     */
    private function getCustomerAcquisition(): array
    {
        // 추후 구현: 마케팅 채널별 고객 획득 분석
        return [];
    }
}