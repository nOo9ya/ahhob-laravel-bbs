<?php

namespace App\Jobs\Payment;

use App\Models\Ahhob\Shop\PaymentTransaction;
use App\Services\Payment\PaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RetryFailedPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1; // 작업 자체는 재시도하지 않음 (결제 재시도와 혼동 방지)
    public int $timeout = 120; // 2분 타임아웃

    public function __construct(
        private string $transactionId
    ) {}

    public function handle(PaymentService $paymentService): void
    {
        try {
            Log::info('Starting payment retry job', [
                'transaction_id' => $this->transactionId,
            ]);

            $transaction = PaymentTransaction::where('transaction_id', $this->transactionId)->first();

            if (!$transaction) {
                Log::error('Transaction not found for retry job', [
                    'transaction_id' => $this->transactionId,
                ]);
                return;
            }

            if (!$transaction->canRetry()) {
                Log::info('Transaction cannot be retried', [
                    'transaction_id' => $this->transactionId,
                    'status' => $transaction->status->value,
                    'retry_count' => $transaction->retry_count,
                ]);
                return;
            }

            // 재시도 횟수 증가
            $transaction->incrementRetry();

            // 재시도 실행
            $response = $paymentService->retryPayment($this->transactionId);

            Log::info('Payment retry job completed', [
                'transaction_id' => $this->transactionId,
                'success' => $response->isSuccess(),
                'status' => $response->status->value,
                'retry_count' => $transaction->retry_count,
            ]);

            // 재시도가 다시 실패한 경우, 추가 재시도 스케줄링
            if (!$response->isSuccess() && $transaction->canRetry()) {
                $retryService = app(\App\Services\Payment\PaymentRetryService::class);
                $retryService->scheduleRetry($transaction);
            }

        } catch (\Exception $e) {
            Log::error('Payment retry job failed', [
                'transaction_id' => $this->transactionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // 작업 자체가 실패한 경우 재시도하지 않음
            $this->fail($e);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Payment retry job permanently failed', [
            'transaction_id' => $this->transactionId,
            'error' => $exception->getMessage(),
        ]);
    }
}