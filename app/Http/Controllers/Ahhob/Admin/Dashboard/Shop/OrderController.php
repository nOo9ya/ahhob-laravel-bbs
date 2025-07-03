<?php

namespace App\Http\Controllers\Ahhob\Admin\Dashboard\Shop;

use App\Http\Controllers\Controller;
use App\Models\Ahhob\Shop\Order;
use App\Models\Ahhob\Shop\OrderItem;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 관리자 주문 관리 컨트롤러 (Admin Order Controller)
    |--------------------------------------------------------------------------
    */
    
    /**
     * 주문 목록
     */
    public function index(Request $request): View
    {
        $query = Order::with(['user', 'items.product']);
        
        // 검색 필터
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }
        
        // 주문 상태 필터
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // 결제 상태 필터
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        
        // 결제 방법 필터
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        
        // 날짜 범위 필터
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // 정렬
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        $query->orderBy($sort, $direction);
        
        $orders = $query->paginate(20);
        
        // 통계 정보
        $stats = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'processing_orders' => Order::where('status', 'processing')->count(),
            'shipped_orders' => Order::where('status', 'shipped')->count(),
            'delivered_orders' => Order::where('status', 'delivered')->count(),
            'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount'),
        ];
        
        return view('ahhob.admin.dashboard.shop.orders.index', compact(
            'orders',
            'stats'
        ));
    }
    
    /**
     * 주문 상세
     */
    public function show(Order $order): View
    {
        $order->load(['user', 'items.product']);
        
        return view('ahhob.admin.dashboard.shop.orders.show', compact('order'));
    }
    
    /**
     * 주문 상태 업데이트
     */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled,refunded',
            'admin_notes' => 'nullable|string|max:500',
        ]);
        
        $oldStatus = $order->status;
        
        $order->updateStatus($request->status, $request->admin_notes);
        
        // 상태별 추가 처리
        switch ($request->status) {
            case 'confirmed':
                // 재고 차감 및 판매량 증가
                $order->markAsCompleted();
                break;
                
            case 'cancelled':
                // 재고 복구
                $this->restoreStock($order);
                break;
        }
        
        return redirect()->back()
            ->with('success', '주문 상태가 업데이트되었습니다.');
    }
    
    /**
     * 결제 상태 업데이트
     */
    public function updatePaymentStatus(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'payment_status' => 'required|in:pending,paid,failed,refunded,partially_refunded',
            'payment_notes' => 'nullable|string|max:500',
        ]);
        
        $order->updatePaymentStatus($request->payment_status);
        
        if ($request->filled('payment_notes')) {
            $order->update(['admin_notes' => $request->payment_notes]);
        }
        
        return redirect()->back()
            ->with('success', '결제 상태가 업데이트되었습니다.');
    }
    
    /**
     * 배송 정보 업데이트
     */
    public function updateShipping(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'tracking_number' => 'required|string|max:100',
            'shipping_company' => 'required|string|max:100',
        ]);
        
        $order->updateShippingInfo(
            $request->tracking_number,
            $request->shipping_company
        );
        
        return redirect()->back()
            ->with('success', '배송 정보가 업데이트되었습니다.');
    }
    
    /**
     * 주문 취소
     */
    public function cancel(Request $request, Order $order): RedirectResponse
    {
        if (!$order->canBeCancelled()) {
            return redirect()->back()
                ->with('error', '취소할 수 없는 주문입니다.');
        }
        
        $request->validate([
            'cancel_reason' => 'required|string|max:500',
        ]);
        
        $order->updateStatus('cancelled', $request->cancel_reason);
        
        // 재고 복구
        $this->restoreStock($order);
        
        return redirect()->back()
            ->with('success', '주문이 취소되었습니다.');
    }
    
    /**
     * 환불 처리
     */
    public function refund(Request $request, Order $order): RedirectResponse
    {
        if (!$order->canBeRefunded()) {
            return redirect()->back()
                ->with('error', '환불할 수 없는 주문입니다.');
        }
        
        $request->validate([
            'refund_amount' => 'required|numeric|min:0|max:' . $order->total_amount,
            'refund_reason' => 'required|string|max:500',
        ]);
        
        // 부분 환불인지 전체 환불인지 확인
        $refundStatus = $request->refund_amount >= $order->total_amount 
            ? 'refunded' 
            : 'partially_refunded';
        
        $order->updatePaymentStatus($refundStatus);
        $order->update(['admin_notes' => $request->refund_reason]);
        
        // 전체 환불인 경우 주문 상태도 변경
        if ($refundStatus === 'refunded') {
            $order->updateStatus('refunded', $request->refund_reason);
            
            // 재고 복구
            $this->restoreStock($order);
        }
        
        return redirect()->back()
            ->with('success', '환불 처리가 완료되었습니다.');
    }
    
    /**
     * 주문 아이템 상태 업데이트
     */
    public function updateItemStatus(Request $request, OrderItem $orderItem): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled,returned,exchanged',
        ]);
        
        $orderItem->updateStatus($request->status);
        
        return response()->json([
            'success' => true,
            'message' => '아이템 상태가 업데이트되었습니다.',
            'status_label' => $orderItem->status_label,
        ]);
    }
    
    /**
     * 대량 작업
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => 'required|in:confirm,ship,deliver,cancel',
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:shop_orders,id',
        ]);
        
        $orders = Order::whereIn('id', $request->order_ids)->get();
        $successCount = 0;
        
        foreach ($orders as $order) {
            switch ($request->action) {
                case 'confirm':
                    if ($order->status === 'pending') {
                        $order->updateStatus('confirmed', '관리자 일괄 확인');
                        $order->markAsCompleted();
                        $successCount++;
                    }
                    break;
                    
                case 'ship':
                    if (in_array($order->status, ['confirmed', 'processing'])) {
                        $order->updateStatus('shipped', '관리자 일괄 배송 처리');
                        $successCount++;
                    }
                    break;
                    
                case 'deliver':
                    if ($order->status === 'shipped') {
                        $order->updateStatus('delivered', '관리자 일괄 배송 완료');
                        $successCount++;
                    }
                    break;
                    
                case 'cancel':
                    if ($order->canBeCancelled()) {
                        $order->updateStatus('cancelled', '관리자 일괄 취소');
                        $this->restoreStock($order);
                        $successCount++;
                    }
                    break;
            }
        }
        
        $actionLabels = [
            'confirm' => '확인',
            'ship' => '배송 처리',
            'deliver' => '배송 완료',
            'cancel' => '취소',
        ];
        
        return redirect()->back()
            ->with('success', "{$successCount}개 주문이 {$actionLabels[$request->action]} 처리되었습니다.");
    }
    
    /**
     * 주문 통계 (AJAX)
     */
    public function statistics(Request $request): JsonResponse
    {
        $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        
        $orders = Order::whereBetween('created_at', [$dateFrom, $dateTo]);
        
        $stats = [
            'total_orders' => $orders->count(),
            'total_revenue' => $orders->where('payment_status', 'paid')->sum('total_amount'),
            'average_order_value' => $orders->avg('total_amount'),
            'status_breakdown' => $orders->groupBy('status')
                ->map(fn($group) => $group->count()),
            'daily_orders' => $orders->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date'),
            'daily_revenue' => $orders->where('payment_status', 'paid')
                ->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('revenue', 'date'),
        ];
        
        return response()->json($stats);
    }
    
    /**
     * 재고 복구
     */
    private function restoreStock(Order $order): void
    {
        foreach ($order->items as $item) {
            if ($item->product && $item->product->track_stock) {
                $item->product->incrementStock($item->quantity);
                $item->product->decrement('sales_count', $item->quantity);
            }
        }
    }
}