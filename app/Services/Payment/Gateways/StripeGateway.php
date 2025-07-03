<?php

namespace App\Services\Payment\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\PaymentRequest;
use App\DTOs\PaymentResponse;
use App\DTOs\RefundRequest;
use App\DTOs\RefundResponse;
use App\Enums\PaymentStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StripeGateway implements PaymentGatewayInterface
{
    private array $config;

    public function __construct()
    {
        $this->config = config('payment.gateways.stripe', []);
    }

    public function processPayment(PaymentRequest $request): PaymentResponse
    {
        try {
            if ($this->isTestMode()) {
                return $this->mockPaymentResponse($request);
            }

            // Stripe Payment Intent 생성
            $paymentIntent = $this->createPaymentIntent($request);
            
            if (!$paymentIntent) {
                return PaymentResponse::failure(
                    PaymentStatus::FAILED,
                    $request->transactionId,
                    'PAYMENT_INTENT_ERROR',
                    'Payment Intent 생성에 실패했습니다.'
                );
            }

            // 클라이언트 사이드에서 완료될 수 있도록 redirect URL 반환
            return PaymentResponse::redirect(
                $request->transactionId,
                $this->buildCheckoutUrl($paymentIntent),
                'Stripe 결제 페이지로 이동합니다.',
                $paymentIntent
            );

        } catch (\Exception $e) {
            Log::error('Stripe payment processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return PaymentResponse::failure(
                PaymentStatus::FAILED,
                $request->transactionId,
                'SYSTEM_ERROR',
                '결제 처리 중 오류가 발생했습니다.'
            );
        }
    }

    public function getPaymentStatus(string $transactionId): PaymentResponse
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['secret_key'],
                'Stripe-Version' => '2023-10-16',
            ])->get("https://api.stripe.com/v1/payment_intents/{$transactionId}");

            if (!$response->successful()) {
                return PaymentResponse::failure(
                    PaymentStatus::FAILED,
                    $transactionId,
                    'API_ERROR',
                    'Stripe API 호출에 실패했습니다.'
                );
            }

            $paymentIntent = $response->json();
            
            return $this->buildPaymentResponseFromIntent($paymentIntent);

        } catch (\Exception $e) {
            Log::error('Stripe payment status inquiry failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return PaymentResponse::failure(
                PaymentStatus::FAILED,
                $transactionId,
                'INQUIRY_ERROR',
                '결제 상태 조회에 실패했습니다.'
            );
        }
    }

    public function cancelPayment(string $transactionId, string $reason = ''): PaymentResponse
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['secret_key'],
                'Stripe-Version' => '2023-10-16',
            ])->post("https://api.stripe.com/v1/payment_intents/{$transactionId}/cancel", [
                'cancellation_reason' => 'requested_by_customer',
            ]);

            if (!$response->successful()) {
                return PaymentResponse::failure(
                    PaymentStatus::FAILED,
                    $transactionId,
                    'CANCEL_ERROR',
                    'Stripe 결제 취소에 실패했습니다.'
                );
            }

            $paymentIntent = $response->json();

            return PaymentResponse::success(
                PaymentStatus::CANCELLED,
                $transactionId,
                $paymentIntent['id'],
                message: '결제가 취소되었습니다.',
                rawData: $paymentIntent
            );

        } catch (\Exception $e) {
            Log::error('Stripe payment cancellation failed', [
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

    public function refundPayment(RefundRequest $request): RefundResponse
    {
        try {
            $params = [
                'payment_intent' => $request->gatewayTransactionId,
                'amount' => (int) ($request->amount * 100), // Stripe는 센트 단위
                'reason' => 'requested_by_customer',
                'metadata' => [
                    'refund_reason' => $request->reason,
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['secret_key'],
                'Stripe-Version' => '2023-10-16',
            ])->post('https://api.stripe.com/v1/refunds', $params);

            if (!$response->successful()) {
                return RefundResponse::failure(
                    $request->transactionId,
                    $request->amount,
                    'REFUND_ERROR',
                    'Stripe 환불에 실패했습니다.'
                );
            }

            $refund = $response->json();

            return RefundResponse::success(
                $request->transactionId,
                $request->amount,
                $refund['id'],
                '환불이 완료되었습니다.',
                $refund
            );

        } catch (\Exception $e) {
            Log::error('Stripe refund failed', [
                'transaction_id' => $request->transactionId,
                'error' => $e->getMessage(),
            ]);

            return RefundResponse::failure(
                $request->transactionId,
                $request->amount,
                'REFUND_ERROR',
                '환불 처리 중 오류가 발생했습니다.'
            );
        }
    }

    public function verifyWebhook(array $data, string $signature = ''): bool
    {
        $payload = json_encode($data);
        $webhookSecret = $this->config['webhook_secret'];

        if (empty($webhookSecret) || empty($signature)) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
        
        return hash_equals($expectedSignature, $signature);
    }

    public function parseWebhookData(array $data): PaymentResponse
    {
        $eventType = $data['type'] ?? '';
        $paymentIntent = $data['data']['object'] ?? [];

        $status = $this->mapStatusFromStripeStatus($paymentIntent['status'] ?? '');
        
        return new PaymentResponse(
            success: in_array($eventType, ['payment_intent.succeeded', 'charge.succeeded']),
            status: $status,
            transactionId: $paymentIntent['metadata']['order_id'] ?? '',
            gatewayTransactionId: $paymentIntent['id'] ?? '',
            message: "Webhook event: {$eventType}",
            rawData: $data
        );
    }

    public function getSupportedMethods(): array
    {
        return [
            'card' => '신용카드',
            'paypal' => 'PayPal',
            'alipay' => 'Alipay',
            'wechat' => 'WeChat Pay',
        ];
    }

    public function validateConfig(): bool
    {
        return !empty($this->config['publishable_key']) && 
               !empty($this->config['secret_key']);
    }

    public function isTestMode(): bool
    {
        return $this->config['test_mode'] ?? false;
    }

    private function createPaymentIntent(PaymentRequest $request): ?array
    {
        $params = [
            'amount' => (int) ($request->amount * 100), // Stripe는 센트 단위
            'currency' => strtolower($request->currency),
            'payment_method_types' => ['card'],
            'description' => $request->productInfo['name'],
            'metadata' => [
                'order_id' => $request->orderId,
                'transaction_id' => $request->transactionId,
            ],
            'receipt_email' => $request->customerInfo['email'],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config['secret_key'],
            'Stripe-Version' => '2023-10-16',
        ])->post('https://api.stripe.com/v1/payment_intents', $params);

        return $response->successful() ? $response->json() : null;
    }

    private function buildCheckoutUrl(array $paymentIntent): string
    {
        // 실제 구현에서는 Stripe Checkout 세션을 생성하거나
        // 클라이언트 사이드에서 처리할 수 있는 URL을 반환
        return route('payment.stripe.checkout', [
            'payment_intent' => $paymentIntent['id'],
            'client_secret' => $paymentIntent['client_secret'],
        ]);
    }

    private function buildPaymentResponseFromIntent(array $paymentIntent): PaymentResponse
    {
        $status = $this->mapStatusFromStripeStatus($paymentIntent['status']);
        $isSuccess = $paymentIntent['status'] === 'succeeded';

        if ($isSuccess) {
            $charges = $paymentIntent['charges']['data'][0] ?? [];
            
            return PaymentResponse::success(
                status: $status,
                transactionId: $paymentIntent['metadata']['transaction_id'] ?? '',
                gatewayTransactionId: $paymentIntent['id'],
                message: '결제가 완료되었습니다.',
                rawData: $paymentIntent,
                cardInfo: [
                    'card_number' => '**** **** **** ' . ($charges['payment_method_details']['card']['last4'] ?? ''),
                    'card_company' => $charges['payment_method_details']['card']['brand'] ?? '',
                ]
            );
        }

        return PaymentResponse::failure(
            status: $status,
            transactionId: $paymentIntent['metadata']['transaction_id'] ?? '',
            errorMessage: '결제에 실패했습니다.',
            rawData: $paymentIntent
        );
    }

    private function mapStatusFromStripeStatus(string $status): PaymentStatus
    {
        return match ($status) {
            'succeeded' => PaymentStatus::COMPLETED,
            'processing' => PaymentStatus::PROCESSING,
            'requires_payment_method', 'requires_confirmation' => PaymentStatus::PENDING,
            'canceled' => PaymentStatus::CANCELLED,
            default => PaymentStatus::FAILED,
        };
    }

    private function mockPaymentResponse(PaymentRequest $request): PaymentResponse
    {
        return PaymentResponse::success(
            status: PaymentStatus::COMPLETED,
            transactionId: $request->transactionId,
            gatewayTransactionId: 'pi_test_' . time(),
            message: 'Stripe 테스트 결제가 완료되었습니다.',
            rawData: ['test_mode' => true],
            cardInfo: [
                'card_number' => '**** **** **** 4242',
                'card_company' => 'visa',
            ]
        );
    }
}