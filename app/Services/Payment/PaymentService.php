<?php

namespace App\Services\Payment;

use App\DTOs\PaymentRequest;
use App\DTOs\PaymentResponse;
use App\DTOs\RefundRequest;
use App\DTOs\RefundResponse;
use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Ahhob\Shop\Order;
use App\Models\Ahhob\Shop\PaymentTransaction;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        private PaymentGatewayManager $gatewayManager
    ) {}

    /**
     * 결제 요청 처리
     */
    public function processPayment(
        Order $order,
        PaymentGateway $gateway,
        string $paymentMethod,
        array $options = []
    ): PaymentResponse {
        try {
            DB::beginTransaction();

            // 주문 상태 확인
            if (!$order->canBePaid()) {
                throw new \InvalidArgumentException('결제할 수 없는 주문 상태입니다.');
            }

            // 결제 트랜잭션 생성
            $transaction = $this->createPaymentTransaction($order, $gateway, $paymentMethod);

            // 결제 요청 객체 생성
            $paymentRequest = PaymentRequest::fromOrder(
                $order,
                $gateway,
                $paymentMethod,
                $transaction->transaction_id
            );

            // 게이트웨이를 통한 결제 처리
            $gatewayInstance = $this->gatewayManager->gateway($gateway);
            $response = $gatewayInstance->processPayment($paymentRequest);

            // 트랜잭션 상태 업데이트
            $this->updateTransactionFromResponse($transaction, $response);

            if ($response->isSuccess()) {
                // 결제 성공 시 주문 상태 업데이트
                if ($response->status === PaymentStatus::COMPLETED) {
                    $order->updatePaymentStatus('paid');
                } else {
                    $order->updatePaymentStatus('processing');
                }
            } else {
                // 결제 실패 시 처리
                $order->updatePaymentStatus('failed');
            }

            DB::commit();

            Log::info('Payment processed', [
                'order_id' => $order->id,
                'transaction_id' => $transaction->transaction_id,
                'gateway' => $gateway->value,
                'success' => $response->isSuccess(),
                'status' => $response->status->value,
            ]);

            return $response;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Payment processing failed', [
                'order_id' => $order->id,
                'gateway' => $gateway->value,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return PaymentResponse::failure(
                PaymentStatus::FAILED,
                $transaction->transaction_id ?? 'unknown',
                'SYSTEM_ERROR',
                '결제 처리 중 시스템 오류가 발생했습니다.'
            );
        }
    }

    /**
     * 결제 상태 조회
     */
    public function getPaymentStatus(string $transactionId): PaymentResponse
    {
        try {
            $transaction = PaymentTransaction::where('transaction_id', $transactionId)->first();

            if (!$transaction) {
                return PaymentResponse::failure(
                    PaymentStatus::FAILED,
                    $transactionId,
                    'TRANSACTION_NOT_FOUND',
                    '결제 트랜잭션을 찾을 수 없습니다.'
                );
            }

            // 게이트웨이에서 최신 상태 조회
            $gatewayInstance = $this->gatewayManager->gateway($transaction->payment_gateway);
            $response = $gatewayInstance->getPaymentStatus($transaction->gateway_transaction_id ?? $transactionId);

            // 트랜잭션 상태 업데이트
            if ($response->isSuccess()) {
                $this->updateTransactionFromResponse($transaction, $response);
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('Payment status inquiry failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return PaymentResponse::failure(
                PaymentStatus::FAILED,
                $transactionId,
                'INQUIRY_ERROR',
                '결제 상태 조회 중 오류가 발생했습니다.'
            );
        }
    }

    /**
     * 결제 취소
     */
    public function cancelPayment(string $transactionId, string $reason = ''): PaymentResponse
    {
        try {
            DB::beginTransaction();

            $transaction = PaymentTransaction::where('transaction_id', $transactionId)->first();

            if (!$transaction) {
                throw new \InvalidArgumentException('결제 트랜잭션을 찾을 수 없습니다.');
            }

            if (!$transaction->canBeCancelled()) {
                throw new \InvalidArgumentException('취소할 수 없는 결제 상태입니다.');
            }

            // 게이트웨이를 통한 결제 취소
            $gatewayInstance = $this->gatewayManager->gateway($transaction->payment_gateway);
            $response = $gatewayInstance->cancelPayment(
                $transaction->gateway_transaction_id ?? $transactionId,
                $reason
            );

            if ($response->isSuccess()) {
                $transaction->cancel($reason);
            }

            DB::commit();

            Log::info('Payment cancelled', [
                'transaction_id' => $transactionId,
                'reason' => $reason,
                'success' => $response->isSuccess(),
            ]);

            return $response;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Payment cancellation failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return PaymentResponse::failure(
                PaymentStatus::FAILED,
                $transactionId,
                'CANCEL_ERROR',
                '결제 취소 처리 중 오류가 발생했습니다.'
            );
        }
    }

    /**
     * 환불 처리
     */
    public function refundPayment(
        string $transactionId,
        float $amount,
        string $reason = ''
    ): RefundResponse {
        try {
            DB::beginTransaction();

            $transaction = PaymentTransaction::where('transaction_id', $transactionId)->first();

            if (!$transaction) {
                throw new \InvalidArgumentException('결제 트랜잭션을 찾을 수 없습니다.');
            }

            if (!$transaction->canBeRefunded()) {
                throw new \InvalidArgumentException('환불할 수 없는 결제 상태입니다.');
            }

            if ($amount > ($transaction->amount - $transaction->refund_amount)) {
                throw new \InvalidArgumentException('환불 가능한 금액을 초과했습니다.');
            }

            // 환불 요청 객체 생성
            $refundRequest = new RefundRequest(
                $transaction->transaction_id,
                $transaction->gateway_transaction_id,
                $amount,
                $reason
            );

            // 게이트웨이를 통한 환불 처리
            $gatewayInstance = $this->gatewayManager->gateway($transaction->payment_gateway);
            $response = $gatewayInstance->refundPayment($refundRequest);

            if ($response->isSuccess()) {
                $transaction->refund($amount, $reason);
            }

            DB::commit();

            Log::info('Payment refunded', [
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'reason' => $reason,
                'success' => $response->isSuccess(),
            ]);

            return $response;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Payment refund failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return RefundResponse::failure(
                $transactionId,
                $amount,
                'REFUND_ERROR',
                '환불 처리 중 오류가 발생했습니다.'
            );
        }
    }

    /**
     * 웹훅 처리
     */
    public function handleWebhook(PaymentGateway $gateway, array $data, string $signature = ''): bool
    {
        try {
            $gatewayInstance = $this->gatewayManager->gateway($gateway);

            // 웹훅 검증
            if (!$gatewayInstance->verifyWebhook($data, $signature)) {
                Log::warning('Invalid webhook signature', [
                    'gateway' => $gateway->value,
                    'data' => $data,
                ]);
                return false;
            }

            // 웹훅 데이터 파싱
            $response = $gatewayInstance->parseWebhookData($data);

            // 트랜잭션 찾기 및 업데이트
            $transaction = PaymentTransaction::where('transaction_id', $response->transactionId)
                ->orWhere('gateway_transaction_id', $response->gatewayTransactionId)
                ->first();

            if ($transaction) {
                $transaction->saveWebhookData($data);
                $this->updateTransactionFromResponse($transaction, $response);

                Log::info('Webhook processed successfully', [
                    'gateway' => $gateway->value,
                    'transaction_id' => $response->transactionId,
                    'status' => $response->status->value,
                ]);
            } else {
                Log::warning('Transaction not found for webhook', [
                    'gateway' => $gateway->value,
                    'transaction_id' => $response->transactionId,
                ]);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'gateway' => $gateway->value,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return false;
        }
    }

    /**
     * 결제 재시도
     */
    public function retryPayment(string $transactionId): PaymentResponse
    {
        try {
            $transaction = PaymentTransaction::where('transaction_id', $transactionId)->first();

            if (!$transaction) {
                throw new \InvalidArgumentException('결제 트랜잭션을 찾을 수 없습니다.');
            }

            if (!$transaction->canRetry()) {
                throw new \InvalidArgumentException('재시도할 수 없는 결제 상태입니다.');
            }

            $transaction->incrementRetry();

            // 원래 주문으로 새로운 결제 시도
            return $this->processPayment(
                $transaction->order,
                $transaction->payment_gateway,
                $transaction->payment_method
            );

        } catch (\Exception $e) {
            Log::error('Payment retry failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return PaymentResponse::failure(
                PaymentStatus::FAILED,
                $transactionId,
                'RETRY_ERROR',
                '결제 재시도 중 오류가 발생했습니다.'
            );
        }
    }

    /**
     * 결제 트랜잭션 생성
     */
    private function createPaymentTransaction(
        Order $order,
        PaymentGateway $gateway,
        string $paymentMethod
    ): PaymentTransaction {
        return PaymentTransaction::create([
            'transaction_id' => $this->generateTransactionId($order),
            'order_id' => $order->id,
            'payment_gateway' => $gateway,
            'payment_method' => $paymentMethod,
            'amount' => $order->total_amount,
            'currency' => 'KRW',
            'status' => PaymentStatus::PENDING,
        ]);
    }

    /**
     * 응답으로부터 트랜잭션 업데이트
     */
    private function updateTransactionFromResponse(
        PaymentTransaction $transaction,
        PaymentResponse $response
    ): void {
        $updateData = [
            'status' => $response->status,
            'gateway_response' => $response->rawData,
        ];

        if ($response->gatewayTransactionId) {
            $updateData['gateway_transaction_id'] = $response->gatewayTransactionId;
        }

        if ($response->approvalNumber) {
            $updateData['approval_number'] = $response->approvalNumber;
        }

        if ($response->cardInfo && !empty($response->cardInfo)) {
            $updateData['card_number'] = $response->cardInfo['card_number'] ?? null;
            $updateData['card_company'] = $response->cardInfo['card_company'] ?? null;
        }

        if ($response->status === PaymentStatus::COMPLETED) {
            $updateData['approval_at'] = now();
        } elseif (!$response->isSuccess()) {
            $updateData['failure_reason'] = $response->errorMessage;
        }

        $transaction->update($updateData);
    }

    /**
     * 트랜잭션 ID 생성
     */
    private function generateTransactionId(Order $order): string
    {
        return 'TXN_' . $order->id . '_' . time() . '_' . Str::random(8);
    }
}