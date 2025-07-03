<?php

namespace App\Http\Controllers\Ahhob\Admin\Dashboard\Shop;

use App\Http\Controllers\Controller;
use App\Models\Ahhob\Shop\PaymentTransaction;
use App\Models\Ahhob\Shop\PaymentMethod;
use App\Models\Ahhob\Shop\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class PaymentManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /*
    |--------------------------------------------------------------------------
    | 결제 관리 (Payment Management)
    |--------------------------------------------------------------------------
    */

    /**
     * 결제 내역 목록
     */
    public function index(Request $request): View
    {
        $query = PaymentTransaction::with(['order.user', 'paymentMethod']);

        // 검색 조건
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                  ->orWhere('payment_id', 'like', "%{$search}%")
                  ->orWhereHas('order', function ($orderQuery) use ($search) {
                      $orderQuery->where('order_number', 'like', "%{$search}%")
                          ->orWhere('customer_name', 'like', "%{$search}%");
                  });
            });
        }

        // 결제 상태 필터
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // 결제 수단 필터
        if ($request->filled('payment_method_id')) {
            $query->where('payment_method_id', $request->get('payment_method_id'));
        }

        // 결제 게이트웨이 필터
        if ($request->filled('gateway')) {
            $query->where('gateway', $request->get('gateway'));
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
            $query->where('amount', '>=', $request->get('amount_min'));
        }
        if ($request->filled('amount_max')) {
            $query->where('amount', '<=', $request->get('amount_max'));
        }

        // 정렬
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $transactions = $query->paginate(20);
        $paymentMethods = PaymentMethod::where('is_active', true)->get();

        // 결제 통계
        $paymentStats = $this->getPaymentStats();

        return view('ahhob.admin.dashboard.shop.payments.index', compact(
            'transactions',
            'paymentMethods',
            'paymentStats'
        ));
    }

    /**
     * 결제 상세보기
     */
    public function show(PaymentTransaction $transaction): View
    {
        $transaction->load([
            'order.user',
            'order.items.product',
            'paymentMethod',
            'refunds'
        ]);

        // 결제 로그 (추후 구현)
        $paymentLogs = $this->getPaymentLogs($transaction);

        return view('ahhob.admin.dashboard.shop.payments.show', compact('transaction', 'paymentLogs'));
    }

    /**
     * 결제 취소
     */
    public function cancel(Request $request, PaymentTransaction $transaction): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        // 취소 가능한 상태 확인
        if (!in_array($transaction->status, ['pending', 'processing'])) {
            return response()->json([
                'success' => false,
                'message' => '현재 상태에서는 결제를 취소할 수 없습니다.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $transaction->update([
                'status' => 'cancelled',
                'cancel_reason' => $request->reason,
                'cancelled_at' => now(),
                'cancelled_by' => auth('admin')->id(),
            ]);

            // 주문 상태도 업데이트
            if ($transaction->order) {
                $transaction->order->update([
                    'payment_status' => 'cancelled',
                    'status' => 'cancelled',
                ]);
            }

            // 결제 로그 기록
            $this->logPaymentAction($transaction, 'cancelled', $request->reason);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '결제가 취소되었습니다.',
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => '결제 취소 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /**
     * 환불 처리
     */
    public function refund(Request $request, PaymentTransaction $transaction): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0|max:' . $transaction->amount,
            'reason' => 'required|string|max:500',
        ]);

        if ($transaction->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => '완료된 결제만 환불할 수 있습니다.',
            ], 422);
        }

        // 이미 환불된 금액 확인
        $refundedAmount = $transaction->refunds()->sum('amount');
        $availableAmount = $transaction->amount - $refundedAmount;

        if ($request->amount > $availableAmount) {
            return response()->json([
                'success' => false,
                'message' => '환불 가능 금액을 초과했습니다.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // 환불 레코드 생성
            $refund = $transaction->refunds()->create([
                'amount' => $request->amount,
                'reason' => $request->reason,
                'status' => 'processing',
                'processed_by' => auth('admin')->id(),
            ]);

            // 실제 환불 처리 (게이트웨이 API 호출)
            $this->processGatewayRefund($transaction, $request->amount, $refund->id);

            // 전액 환불인 경우 상태 업데이트
            if (($refundedAmount + $request->amount) >= $transaction->amount) {
                $transaction->update(['status' => 'refunded']);
                
                if ($transaction->order) {
                    $transaction->order->update(['payment_status' => 'refunded']);
                }
            }

            $refund->update(['status' => 'completed']);

            // 결제 로그 기록
            $this->logPaymentAction($transaction, 'refunded', $request->reason, $request->amount);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '환불이 처리되었습니다.',
                'refund_amount' => $request->amount,
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
     * 결제 재시도
     */
    public function retry(Request $request, PaymentTransaction $transaction): JsonResponse
    {
        if (!in_array($transaction->status, ['failed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => '재시도할 수 없는 결제 상태입니다.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // 새로운 결제 트랜잭션 생성
            $newTransaction = PaymentTransaction::create([
                'order_id' => $transaction->order_id,
                'payment_method_id' => $transaction->payment_method_id,
                'gateway' => $transaction->gateway,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'status' => 'pending',
                'retry_of' => $transaction->id,
            ]);

            // 게이트웨이 결제 요청
            $result = $this->initiateGatewayPayment($newTransaction);

            if ($result['success']) {
                $newTransaction->update([
                    'payment_id' => $result['payment_id'],
                    'status' => 'processing',
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => '결제가 재시도되었습니다.',
                    'transaction_id' => $newTransaction->id,
                ]);
            } else {
                throw new \Exception($result['message']);
            }

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => '결제 재시도 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 분석 및 리포트 (Analytics & Reports)
    |--------------------------------------------------------------------------
    */

    /**
     * 결제 대시보드
     */
    public function dashboard(): View
    {
        $dashboardData = Cache::remember('admin.payment.dashboard', 900, function () {
            return [
                'overview' => $this->getPaymentOverview(),
                'success_rate_trend' => $this->getSuccessRateTrend(30),
                'gateway_performance' => $this->getGatewayPerformance(),
                'payment_method_stats' => $this->getPaymentMethodStats(),
                'recent_failures' => $this->getRecentFailures(10),
            ];
        });

        return view('ahhob.admin.dashboard.shop.payments.dashboard', compact('dashboardData'));
    }

    /**
     * 결제 분석
     */
    public function analytics(Request $request): View
    {
        $period = $request->get('period', '30'); // 기본 30일
        $analytics = Cache::remember("admin.payment.analytics.{$period}", 1800, function () use ($period) {
            return [
                'revenue_analysis' => $this->getRevenueAnalysis($period),
                'failure_analysis' => $this->getFailureAnalysis($period),
                'gateway_comparison' => $this->getGatewayComparison($period),
                'hourly_patterns' => $this->getHourlyPatterns($period),
                'fraud_detection' => $this->getFraudDetectionStats($period),
            ];
        });

        return view('ahhob.admin.dashboard.shop.payments.analytics', compact('analytics', 'period'));
    }

    /*
    |--------------------------------------------------------------------------
    | 게이트웨이 관리 (Gateway Management)
    |--------------------------------------------------------------------------
    */

    /**
     * 게이트웨이 목록
     */
    public function gateways(): View
    {
        $gateways = PaymentMethod::with(['transactions' => function ($query) {
            $query->whereDate('created_at', '>=', now()->subDays(30));
        }])->get();

        $gatewayStats = $this->getGatewayStats();

        return view('ahhob.admin.dashboard.shop.payments.gateways', compact('gateways', 'gatewayStats'));
    }

    /**
     * 게이트웨이 설정 업데이트
     */
    public function updateGatewaySettings(Request $request, PaymentMethod $gateway): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
            'fee_percentage' => 'nullable|numeric|min:0|max:100',
            'fee_fixed' => 'nullable|numeric|min:0',
            'settings' => 'nullable|array',
        ]);

        $gateway->update($request->all());

        return response()->json([
            'success' => true,
            'message' => '게이트웨이 설정이 업데이트되었습니다.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 헬퍼 메서드 (Helper Methods)
    |--------------------------------------------------------------------------
    */

    /**
     * 결제 통계
     */
    private function getPaymentStats(): array
    {
        $today = now()->startOfDay();
        
        return [
            'total_transactions' => PaymentTransaction::count(),
            'completed_today' => PaymentTransaction::where('status', 'completed')
                ->where('created_at', '>=', $today)
                ->count(),
            'failed_today' => PaymentTransaction::where('status', 'failed')
                ->where('created_at', '>=', $today)
                ->count(),
            'pending_count' => PaymentTransaction::where('status', 'pending')->count(),
            'today_revenue' => PaymentTransaction::where('status', 'completed')
                ->where('created_at', '>=', $today)
                ->sum('amount'),
            'success_rate' => $this->calculateSuccessRate(),
        ];
    }

    /**
     * 결제 로그 조회
     */
    private function getPaymentLogs(PaymentTransaction $transaction): array
    {
        // 추후 구현: 결제 로그 테이블에서 조회
        return [
            [
                'action' => 'created',
                'description' => '결제 트랜잭션이 생성되었습니다.',
                'created_at' => $transaction->created_at,
                'details' => [],
            ],
        ];
    }

    /**
     * 결제 액션 로그 기록
     */
    private function logPaymentAction(
        PaymentTransaction $transaction, 
        string $action, 
        ?string $reason = null, 
        ?float $amount = null
    ): void {
        // 추후 구현: 결제 로그 테이블에 기록
    }

    /**
     * 게이트웨이 환불 처리
     */
    private function processGatewayRefund(PaymentTransaction $transaction, float $amount, int $refundId): void
    {
        // 추후 구현: 실제 게이트웨이 API 호출하여 환불 처리
        // 각 게이트웨이별로 다른 처리 방식 적용
    }

    /**
     * 게이트웨이 결제 시작
     */
    private function initiateGatewayPayment(PaymentTransaction $transaction): array
    {
        // 추후 구현: 실제 게이트웨이 API 호출하여 결제 시작
        return [
            'success' => true,
            'payment_id' => 'test_payment_' . time(),
            'message' => 'Payment initiated successfully',
        ];
    }

    /**
     * 성공률 계산
     */
    private function calculateSuccessRate(): float
    {
        $total = PaymentTransaction::count();
        if ($total === 0) return 0;

        $completed = PaymentTransaction::where('status', 'completed')->count();
        return round(($completed / $total) * 100, 2);
    }

    /**
     * 결제 개요
     */
    private function getPaymentOverview(): array
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();

        return [
            'total_revenue' => PaymentTransaction::where('status', 'completed')->sum('amount'),
            'revenue_today' => PaymentTransaction::where('status', 'completed')
                ->where('created_at', '>=', $today)
                ->sum('amount'),
            'revenue_this_month' => PaymentTransaction::where('status', 'completed')
                ->where('created_at', '>=', $thisMonth)
                ->sum('amount'),
            'total_transactions' => PaymentTransaction::count(),
            'successful_transactions' => PaymentTransaction::where('status', 'completed')->count(),
            'failed_transactions' => PaymentTransaction::where('status', 'failed')->count(),
            'success_rate' => $this->calculateSuccessRate(),
        ];
    }

    /**
     * 성공률 트렌드
     */
    private function getSuccessRateTrend(int $days): array
    {
        $dates = [];
        $rates = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dates[] = $date->format('m/d');
            
            $total = PaymentTransaction::whereDate('created_at', $date)->count();
            $successful = PaymentTransaction::whereDate('created_at', $date)
                ->where('status', 'completed')
                ->count();
            
            $rate = $total > 0 ? round(($successful / $total) * 100, 2) : 0;
            $rates[] = $rate;
        }

        return [
            'labels' => $dates,
            'rates' => $rates,
        ];
    }

    /**
     * 게이트웨이 성능
     */
    private function getGatewayPerformance(): array
    {
        return PaymentTransaction::select('gateway')
            ->selectRaw('
                COUNT(*) as total_transactions,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as successful,
                ROUND(AVG(CASE WHEN status = "completed" THEN 1 ELSE 0 END) * 100, 2) as success_rate,
                SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as total_revenue
            ')
            ->groupBy('gateway')
            ->get()
            ->toArray();
    }

    /**
     * 결제수단 통계
     */
    private function getPaymentMethodStats(): array
    {
        return PaymentMethod::withCount(['transactions' => function ($query) {
            $query->whereDate('created_at', '>=', now()->subDays(30));
        }])
        ->with(['transactions' => function ($query) {
            $query->where('status', 'completed')
                ->whereDate('created_at', '>=', now()->subDays(30));
        }])
        ->get()
        ->map(function ($method) {
            $revenue = $method->transactions->sum('amount');
            return [
                'name' => $method->name,
                'transaction_count' => $method->transactions_count,
                'revenue' => $revenue,
            ];
        })
        ->toArray();
    }

    /**
     * 최근 실패 내역
     */
    private function getRecentFailures(int $limit): array
    {
        return PaymentTransaction::with(['order'])
            ->where('status', 'failed')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($transaction) {
                return [
                    'transaction_id' => $transaction->transaction_id,
                    'order_number' => $transaction->order->order_number ?? 'N/A',
                    'amount' => $transaction->amount,
                    'gateway' => $transaction->gateway,
                    'failure_reason' => $transaction->failure_reason,
                    'created_at' => $transaction->created_at,
                ];
            })
            ->toArray();
    }

    /**
     * 매출 분석
     */
    private function getRevenueAnalysis(int $days): array
    {
        // 추후 구현: 상세 매출 분석
        return [
            'daily_revenue' => [],
            'average_transaction_value' => 0,
            'peak_hours' => [],
        ];
    }

    /**
     * 실패 분석
     */
    private function getFailureAnalysis(int $days): array
    {
        $failures = PaymentTransaction::where('status', 'failed')
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        $reasonCounts = $failures->groupBy('failure_reason')
            ->map(function ($group) {
                return $group->count();
            })
            ->sortDesc();

        return [
            'total_failures' => $failures->count(),
            'failure_reasons' => $reasonCounts->toArray(),
            'failure_rate_by_gateway' => $this->getFailureRateByGateway($days),
        ];
    }

    /**
     * 게이트웨이 비교
     */
    private function getGatewayComparison(int $days): array
    {
        return PaymentTransaction::select('gateway')
            ->selectRaw('
                COUNT(*) as total,
                AVG(amount) as avg_amount,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as successful,
                AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_processing_time
            ')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('gateway')
            ->get()
            ->toArray();
    }

    /**
     * 시간대별 패턴
     */
    private function getHourlyPatterns(int $days): array
    {
        $patterns = [];
        
        for ($hour = 0; $hour < 24; $hour++) {
            $count = PaymentTransaction::where('created_at', '>=', now()->subDays($days))
                ->whereRaw('HOUR(created_at) = ?', [$hour])
                ->count();
            
            $patterns[] = [
                'hour' => $hour,
                'count' => $count,
            ];
        }

        return $patterns;
    }

    /**
     * 사기 탐지 통계
     */
    private function getFraudDetectionStats(int $days): array
    {
        // 추후 구현: 사기 탐지 관련 통계
        return [
            'suspicious_transactions' => 0,
            'blocked_transactions' => 0,
            'false_positives' => 0,
        ];
    }

    /**
     * 게이트웨이별 실패율
     */
    private function getFailureRateByGateway(int $days): array
    {
        return PaymentTransaction::select('gateway')
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                ROUND(AVG(CASE WHEN status = "failed" THEN 1 ELSE 0 END) * 100, 2) as failure_rate
            ')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('gateway')
            ->get()
            ->toArray();
    }

    /**
     * 게이트웨이 통계
     */
    private function getGatewayStats(): array
    {
        return [
            'total_gateways' => PaymentMethod::count(),
            'active_gateways' => PaymentMethod::where('is_active', true)->count(),
            'total_volume_today' => PaymentTransaction::where('status', 'completed')
                ->whereDate('created_at', today())
                ->sum('amount'),
            'transaction_count_today' => PaymentTransaction::whereDate('created_at', today())->count(),
        ];
    }
}