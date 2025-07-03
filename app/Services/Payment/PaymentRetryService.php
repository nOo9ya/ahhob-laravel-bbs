<?php

namespace App\Services\Payment;

use App\Models\Ahhob\Shop\PaymentTransaction;
use App\Services\Payment\PaymentService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use App\Jobs\Payment\RetryFailedPaymentJob;

class PaymentRetryService
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * 실패한 결제 재시도 스케줄링
     */
    public function scheduleRetry(PaymentTransaction $transaction, int $delaySeconds = null): bool
    {
        try {
            if (!$transaction->canRetry()) {
                Log::info('Payment cannot be retried', [
                    'transaction_id' => $transaction->transaction_id,
                    'status' => $transaction->status->value,
                    'retry_count' => $transaction->retry_count,
                ]);
                return false;
            }

            $delay = $delaySeconds ?? $this->calculateRetryDelay($transaction->retry_count);

            // 재시도 작업을 큐에 추가
            RetryFailedPaymentJob::dispatch($transaction->transaction_id)
                ->delay(now()->addSeconds($delay));

            Log::info('Payment retry scheduled', [
                'transaction_id' => $transaction->transaction_id,
                'retry_count' => $transaction->retry_count,
                'delay_seconds' => $delay,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to schedule payment retry', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 재시도 지연 시간 계산 (백오프 알고리즘)
     */
    private function calculateRetryDelay(int $retryCount): int
    {
        $baseDelay = config('payment.retry.delay_seconds', 60);
        $backoffMultiplier = config('payment.retry.backoff_multiplier', 2);

        return $baseDelay * pow($backoffMultiplier, $retryCount);
    }

    /**
     * 모든 재시도 가능한 실패 결제 처리
     */
    public function processRetryablePayments(): int
    {
        $failedTransactions = PaymentTransaction::where('status', 'failed')
            ->where('retry_count', '<', config('payment.retry.max_attempts', 3))
            ->where(function ($query) {
                $query->whereNull('last_retry_at')
                    ->orWhere('last_retry_at', '<', now()->subMinutes(30));
            })
            ->get();

        $scheduledCount = 0;

        foreach ($failedTransactions as $transaction) {
            if ($this->scheduleRetry($transaction)) {
                $scheduledCount++;
            }
        }

        Log::info('Scheduled retryable payments', [
            'total_failed' => $failedTransactions->count(),
            'scheduled_count' => $scheduledCount,
        ]);

        return $scheduledCount;
    }

    /**
     * 재시도 한도 초과 결제 정리
     */
    public function cleanupExhaustedRetries(): int
    {
        $maxAttempts = config('payment.retry.max_attempts', 3);
        
        $exhaustedCount = PaymentTransaction::where('status', 'failed')
            ->where('retry_count', '>=', $maxAttempts)
            ->update([
                'status' => 'expired',
                'failure_reason' => '재시도 한도 초과',
                'updated_at' => now(),
            ]);

        if ($exhaustedCount > 0) {
            Log::info('Cleaned up exhausted retry payments', [
                'count' => $exhaustedCount,
            ]);
        }

        return $exhaustedCount;
    }

    /**
     * 특정 결제 수동 재시도
     */
    public function manualRetry(string $transactionId): bool
    {
        try {
            $transaction = PaymentTransaction::where('transaction_id', $transactionId)->first();

            if (!$transaction) {
                Log::error('Transaction not found for manual retry', [
                    'transaction_id' => $transactionId,
                ]);
                return false;
            }

            // 수동 재시도는 재시도 횟수 제한을 무시
            $response = $this->paymentService->retryPayment($transactionId);

            Log::info('Manual payment retry completed', [
                'transaction_id' => $transactionId,
                'success' => $response->isSuccess(),
                'status' => $response->status->value,
            ]);

            return $response->isSuccess();

        } catch (\Exception $e) {
            Log::error('Manual payment retry failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 재시도 통계 조회
     */
    public function getRetryStatistics(): array
    {
        return [
            'pending_retries' => PaymentTransaction::where('status', 'failed')
                ->where('retry_count', '<', config('payment.retry.max_attempts', 3))
                ->count(),
            'exhausted_retries' => PaymentTransaction::where('status', 'expired')
                ->where('failure_reason', 'like', '%재시도%')
                ->count(),
            'total_failed' => PaymentTransaction::where('status', 'failed')->count(),
            'success_rate_after_retry' => $this->calculateRetrySuccessRate(),
        ];
    }

    /**
     * 재시도 성공률 계산
     */
    private function calculateRetrySuccessRate(): float
    {
        $totalRetries = PaymentTransaction::where('retry_count', '>', 0)->count();
        
        if ($totalRetries === 0) {
            return 0;
        }

        $successfulRetries = PaymentTransaction::where('retry_count', '>', 0)
            ->where('status', 'completed')
            ->count();

        return round(($successfulRetries / $totalRetries) * 100, 2);
    }

    /**
     * 특정 게이트웨이의 재시도 패턴 분석
     */
    public function analyzeRetryPatterns(string $gateway = null): array
    {
        $query = PaymentTransaction::where('retry_count', '>', 0);
        
        if ($gateway) {
            $query->where('payment_gateway', $gateway);
        }

        $transactions = $query->get();

        $patterns = [
            'total_retries' => $transactions->count(),
            'avg_retry_count' => $transactions->avg('retry_count'),
            'max_retry_count' => $transactions->max('retry_count'),
            'common_failure_reasons' => $transactions
                ->whereNotNull('failure_reason')
                ->groupBy('failure_reason')
                ->map->count()
                ->sortDesc()
                ->take(5)
                ->toArray(),
            'retry_success_by_attempt' => $this->getRetrySuccessByAttempt($transactions),
        ];

        return $patterns;
    }

    /**
     * 재시도 횟수별 성공률 분석
     */
    private function getRetrySuccessByAttempt($transactions): array
    {
        $attempts = [];

        for ($i = 1; $i <= 3; $i++) {
            $attemptTransactions = $transactions->where('retry_count', '>=', $i);
            $successfulAttempts = $attemptTransactions->where('status', 'completed');

            $attempts[$i] = [
                'total' => $attemptTransactions->count(),
                'successful' => $successfulAttempts->count(),
                'success_rate' => $attemptTransactions->count() > 0 
                    ? round(($successfulAttempts->count() / $attemptTransactions->count()) * 100, 2)
                    : 0,
            ];
        }

        return $attempts;
    }
}