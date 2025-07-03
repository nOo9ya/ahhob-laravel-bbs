<?php

namespace App\Http\Controllers\Ahhob\Admin\Dashboard\Shop;

use App\Http\Controllers\Controller;
use App\Models\Ahhob\Shop\PaymentTransaction;
use App\Models\Ahhob\Shop\Order;
use App\Services\Payment\PaymentService;
use App\Services\Payment\PaymentRetryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private PaymentRetryService $retryService
    ) {}

    /**
     * 결제 내역 목록
     */
    public function index(Request $request): View
    {
        $query = PaymentTransaction::with(['order', 'order.user'])
            ->orderBy('created_at', 'desc');

        // 검색 필터
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                  ->orWhere('gateway_transaction_id', 'like', "%{$search}%")
                  ->orWhereHas('order', function ($orderQuery) use ($search) {
                      $orderQuery->where('order_number', 'like', "%{$search}%")
                                 ->orWhere('customer_name', 'like', "%{$search}%")
                                 ->orWhere('customer_email', 'like', "%{$search}%");
                  });
            });
        }

        // 상태 필터
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 게이트웨이 필터
        if ($request->filled('gateway')) {
            $query->where('payment_gateway', $request->gateway);
        }

        // 결제 수단 필터
        if ($request->filled('method')) {
            $query->where('payment_method', $request->method);
        }

        // 날짜 범위 필터
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // 금액 범위 필터
        if ($request->filled('amount_from')) {
            $query->where('amount', '>=', $request->amount_from);
        }
        if ($request->filled('amount_to')) {
            $query->where('amount', '<=', $request->amount_to);
        }

        $transactions = $query->paginate(20);

        // 통계 데이터
        $stats = $this->getPaymentStats($request);

        return view('ahhob.admin.dashboard.shop.payments.index', compact(
            'transactions',
            'stats'
        ));
    }

    /**
     * 결제 상세 정보
     */
    public function show(PaymentTransaction $transaction): View
    {
        $transaction->load(['order', 'order.user', 'order.items']);

        return view('ahhob.admin.dashboard.shop.payments.show', compact('transaction'));
    }

    /**
     * 결제 취소
     */
    public function cancel(Request $request, PaymentTransaction $transaction): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        try {
            $response = $this->paymentService->cancelPayment(
                $transaction->transaction_id,
                $request->reason
            );

            return response()->json([
                'success' => $response->isSuccess(),
                'message' => $response->message,
            ]);

        } catch (\Exception $e) {
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
            'amount' => 'required|numeric|min:0|max:' . ($transaction->amount - $transaction->refund_amount),
            'reason' => 'required|string|max:255',
        ]);

        try {
            $response = $this->paymentService->refundPayment(
                $transaction->transaction_id,
                $request->amount,
                $request->reason
            );

            return response()->json([
                'success' => $response->isSuccess(),
                'message' => $response->message,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '환불 처리 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /**
     * 결제 재시도
     */
    public function retry(PaymentTransaction $transaction): JsonResponse
    {
        try {
            $success = $this->retryService->manualRetry($transaction->transaction_id);

            return response()->json([
                'success' => $success,
                'message' => $success ? '결제 재시도가 완료되었습니다.' : '결제 재시도에 실패했습니다.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '결제 재시도 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /**
     * 대량 작업 처리
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => 'required|in:cancel,refund,retry',
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:payment_transactions,id',
            'reason' => 'required_if:action,cancel,refund|string|max:255',
            'amount' => 'required_if:action,refund|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $transactions = PaymentTransaction::whereIn('id', $request->transaction_ids)->get();
            $successCount = 0;
            $errors = [];

            foreach ($transactions as $transaction) {
                try {
                    $result = match ($request->action) {
                        'cancel' => $this->paymentService->cancelPayment(
                            $transaction->transaction_id,
                            $request->reason
                        ),
                        'refund' => $this->paymentService->refundPayment(
                            $transaction->transaction_id,
                            $request->amount,
                            $request->reason
                        ),
                        'retry' => $this->retryService->manualRetry($transaction->transaction_id),
                    };

                    if (($result === true) || (is_object($result) && $result->isSuccess())) {
                        $successCount++;
                    } else {
                        $errors[] = "트랜잭션 {$transaction->transaction_id}: 처리 실패";
                    }

                } catch (\Exception $e) {
                    $errors[] = "트랜잭션 {$transaction->transaction_id}: {$e->getMessage()}";
                }
            }

            DB::commit();

            $message = "{$successCount}건이 성공적으로 처리되었습니다.";
            if (!empty($errors)) {
                $message .= " " . count($errors) . "건 실패.";
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', '대량 작업 처리 중 오류가 발생했습니다.');
        }
    }

    /**
     * 결제 상태 동기화
     */
    public function syncStatus(PaymentTransaction $transaction): JsonResponse
    {
        try {
            $response = $this->paymentService->getPaymentStatus($transaction->transaction_id);

            return response()->json([
                'success' => true,
                'status' => $response->status->value,
                'message' => '결제 상태가 동기화되었습니다.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '상태 동기화 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /**
     * 결제 통계 대시보드
     */
    public function dashboard(Request $request): View
    {
        $period = $request->get('period', '30'); // 기본 30일

        $stats = [
            'total_transactions' => PaymentTransaction::count(),
            'successful_payments' => PaymentTransaction::where('status', 'completed')->count(),
            'failed_payments' => PaymentTransaction::where('status', 'failed')->count(),
            'pending_payments' => PaymentTransaction::where('status', 'pending')->count(),
            'total_amount' => PaymentTransaction::where('status', 'completed')->sum('amount'),
            'refunded_amount' => PaymentTransaction::sum('refund_amount'),
        ];

        // 기간별 결제 현황
        $chartData = $this->getPaymentChartData($period);

        // 게이트웨이별 통계
        $gatewayStats = PaymentTransaction::select('payment_gateway')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as total_amount')
            ->selectRaw('AVG(CASE WHEN status = "completed" THEN amount END) as avg_amount')
            ->groupBy('payment_gateway')
            ->get();

        // 재시도 통계
        $retryStats = $this->retryService->getRetryStatistics();

        return view('ahhob.admin.dashboard.shop.payments.dashboard', compact(
            'stats',
            'chartData',
            'gatewayStats',
            'retryStats'
        ));
    }

    /**
     * 결제 분석 리포트
     */
    public function analytics(Request $request): View
    {
        $period = $request->get('period', '30');
        $gateway = $request->get('gateway');

        // 성공률 분석
        $successRateData = $this->getSuccessRateAnalysis($period, $gateway);

        // 실패 원인 분석
        $failureAnalysis = $this->getFailureAnalysis($period, $gateway);

        // 재시도 패턴 분석
        $retryPatterns = $this->retryService->analyzeRetryPatterns($gateway);

        // 시간대별 분석
        $hourlyAnalysis = $this->getHourlyAnalysis($period, $gateway);

        return view('ahhob.admin.dashboard.shop.payments.analytics', compact(
            'successRateData',
            'failureAnalysis',
            'retryPatterns',
            'hourlyAnalysis'
        ));
    }

    /**
     * 결제 통계 데이터 조회
     */
    private function getPaymentStats(Request $request): array
    {
        $query = PaymentTransaction::query();

        // 필터 적용
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return [
            'total_count' => $query->count(),
            'completed_count' => (clone $query)->where('status', 'completed')->count(),
            'failed_count' => (clone $query)->where('status', 'failed')->count(),
            'pending_count' => (clone $query)->where('status', 'pending')->count(),
            'total_amount' => (clone $query)->where('status', 'completed')->sum('amount'),
            'refunded_amount' => (clone $query)->sum('refund_amount'),
            'success_rate' => $this->calculateSuccessRate($query),
        ];
    }

    /**
     * 성공률 계산
     */
    private function calculateSuccessRate($query): float
    {
        $total = (clone $query)->count();
        if ($total === 0) return 0;

        $successful = (clone $query)->where('status', 'completed')->count();
        return round(($successful / $total) * 100, 2);
    }

    /**
     * 차트 데이터 생성
     */
    private function getPaymentChartData(string $period): array
    {
        $days = (int) $period;
        $startDate = now()->subDays($days);

        $data = PaymentTransaction::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total_count'),
            DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as success_count'),
            DB::raw('SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as total_amount')
        )
        ->where('created_at', '>=', $startDate)
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        return $data->map(function ($item) {
            return [
                'date' => $item->date,
                'total_count' => $item->total_count,
                'success_count' => $item->success_count,
                'success_rate' => $item->total_count > 0 ? round(($item->success_count / $item->total_count) * 100, 2) : 0,
                'total_amount' => $item->total_amount,
            ];
        })->toArray();
    }

    /**
     * 성공률 분석 데이터
     */
    private function getSuccessRateAnalysis(string $period, ?string $gateway): array
    {
        $query = PaymentTransaction::query();
        
        if ($gateway) {
            $query->where('payment_gateway', $gateway);
        }

        $query->where('created_at', '>=', now()->subDays((int) $period));

        return [
            'overall' => $this->calculateSuccessRate($query),
            'by_method' => $query->select('payment_method')
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as successful')
                ->groupBy('payment_method')
                ->get()
                ->map(function ($item) {
                    return [
                        'method' => $item->payment_method,
                        'total' => $item->total,
                        'successful' => $item->successful,
                        'rate' => $item->total > 0 ? round(($item->successful / $item->total) * 100, 2) : 0,
                    ];
                }),
        ];
    }

    /**
     * 실패 원인 분석
     */
    private function getFailureAnalysis(string $period, ?string $gateway): array
    {
        $query = PaymentTransaction::where('status', 'failed')
            ->where('created_at', '>=', now()->subDays((int) $period));

        if ($gateway) {
            $query->where('payment_gateway', $gateway);
        }

        return $query->select('failure_reason')
            ->selectRaw('COUNT(*) as count')
            ->whereNotNull('failure_reason')
            ->groupBy('failure_reason')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * 시간대별 분석
     */
    private function getHourlyAnalysis(string $period, ?string $gateway): array
    {
        $query = PaymentTransaction::query()
            ->where('created_at', '>=', now()->subDays((int) $period));

        if ($gateway) {
            $query->where('payment_gateway', $gateway);
        }

        return $query->select(
            DB::raw('HOUR(created_at) as hour'),
            DB::raw('COUNT(*) as total_count'),
            DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as success_count')
        )
        ->groupBy('hour')
        ->orderBy('hour')
        ->get()
        ->map(function ($item) {
            return [
                'hour' => $item->hour,
                'total_count' => $item->total_count,
                'success_count' => $item->success_count,
                'success_rate' => $item->total_count > 0 ? round(($item->success_count / $item->total_count) * 100, 2) : 0,
            ];
        })
        ->toArray();
    }
}