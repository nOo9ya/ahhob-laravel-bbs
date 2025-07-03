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

class InicisGateway implements PaymentGatewayInterface
{
    private array $config;

    public function __construct()
    {
        $this->config = config('payment.gateways.inicis', []);
    }

    public function processPayment(PaymentRequest $request): PaymentResponse
    {
        try {
            // 이니시스 결제 요청 파라미터 구성
            $params = $this->buildPaymentParams($request);
            
            // 테스트 모드에서는 모의 응답 반환
            if ($this->isTestMode()) {
                return $this->mockPaymentResponse($request);
            }

            // 실제 이니시스 API 호출
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ])
                ->post($this->getApiUrl(), $params);

            if (!$response->successful()) {
                Log::error('Inicis API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                
                return PaymentResponse::failure(
                    PaymentStatus::FAILED,
                    $request->transactionId,
                    'API_ERROR',
                    '결제 서버와 통신에 실패했습니다.',
                    ['response' => $response->body()]
                );
            }

            $responseData = $this->parseResponse($response->body());
            
            return $this->buildPaymentResponse($request, $responseData);

        } catch (\Exception $e) {
            Log::error('Inicis payment processing failed', [
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
            $params = [
                'type' => 'inquiry',
                'mid' => $this->config['merchant_id'],
                'tid' => $transactionId,
                'timestamp' => time(),
            ];

            $params['hashdata'] = $this->generateHash($params);

            $response = Http::post($this->getApiUrl() . '/inquiry', $params);
            $responseData = $this->parseResponse($response->body());

            return $this->buildPaymentResponse(null, $responseData);

        } catch (\Exception $e) {
            Log::error('Inicis payment status inquiry failed', [
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
            $params = [
                'type' => 'cancel',
                'mid' => $this->config['merchant_id'],
                'tid' => $transactionId,
                'msg' => $reason ?: '고객 요청',
                'timestamp' => time(),
            ];

            $params['hashdata'] = $this->generateHash($params);

            $response = Http::post($this->getApiUrl() . '/cancel', $params);
            $responseData = $this->parseResponse($response->body());

            if ($responseData['resultcode'] === '00') {
                return PaymentResponse::success(
                    PaymentStatus::CANCELLED,
                    $transactionId,
                    message: '결제가 취소되었습니다.',
                    rawData: $responseData
                );
            }

            return PaymentResponse::failure(
                PaymentStatus::FAILED,
                $transactionId,
                $responseData['resultcode'],
                $responseData['resultmsg'] ?? '결제 취소에 실패했습니다.',
                $responseData
            );

        } catch (\Exception $e) {
            Log::error('Inicis payment cancellation failed', [
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
                'type' => 'refund',
                'mid' => $this->config['merchant_id'],
                'tid' => $request->gatewayTransactionId,
                'price' => $request->amount,
                'msg' => $request->reason,
                'timestamp' => time(),
            ];

            $params['hashdata'] = $this->generateHash($params);

            $response = Http::post($this->getApiUrl() . '/refund', $params);
            $responseData = $this->parseResponse($response->body());

            if ($responseData['resultcode'] === '00') {
                return RefundResponse::success(
                    $request->transactionId,
                    $request->amount,
                    $responseData['tid'] ?? null,
                    '환불이 완료되었습니다.',
                    $responseData
                );
            }

            return RefundResponse::failure(
                $request->transactionId,
                $request->amount,
                $responseData['resultcode'],
                $responseData['resultmsg'] ?? '환불에 실패했습니다.',
                $responseData
            );

        } catch (\Exception $e) {
            Log::error('Inicis refund failed', [
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
        // 이니시스 웹훅 검증 로직
        $expectedHash = $this->generateHash($data);
        return hash_equals($expectedHash, $data['hashdata'] ?? '');
    }

    public function parseWebhookData(array $data): PaymentResponse
    {
        $status = $this->mapStatusFromCode($data['resultcode'] ?? '');
        
        return new PaymentResponse(
            success: $data['resultcode'] === '00',
            status: $status,
            transactionId: $data['oid'] ?? '',
            gatewayTransactionId: $data['tid'] ?? '',
            approvalNumber: $data['authcode'] ?? '',
            message: $data['resultmsg'] ?? '',
            rawData: $data,
            cardInfo: [
                'card_number' => $data['cardnum'] ?? '',
                'card_company' => $data['cardname'] ?? '',
            ]
        );
    }

    public function getSupportedMethods(): array
    {
        return [
            'card' => '신용카드',
            'bank' => '계좌이체',
            'virtual_account' => '가상계좌',
            'phone' => '휴대폰',
        ];
    }

    public function validateConfig(): bool
    {
        return !empty($this->config['merchant_id']) && 
               !empty($this->config['sign_key']);
    }

    public function isTestMode(): bool
    {
        return $this->config['test_mode'] ?? false;
    }

    private function buildPaymentParams(PaymentRequest $request): array
    {
        $params = [
            'version' => '1.0',
            'mid' => $this->config['merchant_id'],
            'oid' => $request->transactionId,
            'price' => (int) $request->amount,
            'currency' => $request->currency,
            'goodname' => $request->productInfo['name'],
            'buyername' => $request->customerInfo['name'],
            'buyertel' => $request->customerInfo['phone'],
            'buyeremail' => $request->customerInfo['email'],
            'returnUrl' => $request->returnUrl,
            'closeUrl' => $request->cancelUrl,
            'acceptmethod' => $this->getAcceptMethod($request->paymentMethod),
            'timestamp' => time(),
        ];

        $params['signature'] = $this->generateHash($params);

        return $params;
    }

    private function buildPaymentResponse(?PaymentRequest $request, array $responseData): PaymentResponse
    {
        $status = $this->mapStatusFromCode($responseData['resultcode'] ?? '');
        $isSuccess = $responseData['resultcode'] === '00';

        if ($isSuccess) {
            return PaymentResponse::success(
                status: $status,
                transactionId: $request ? $request->transactionId : ($responseData['oid'] ?? ''),
                gatewayTransactionId: $responseData['tid'] ?? '',
                approvalNumber: $responseData['authcode'] ?? '',
                message: $responseData['resultmsg'] ?? '결제가 완료되었습니다.',
                rawData: $responseData,
                cardInfo: [
                    'card_number' => $responseData['cardnum'] ?? '',
                    'card_company' => $responseData['cardname'] ?? '',
                ]
            );
        }

        return PaymentResponse::failure(
            status: $status,
            transactionId: $request ? $request->transactionId : ($responseData['oid'] ?? ''),
            errorCode: $responseData['resultcode'] ?? '',
            errorMessage: $responseData['resultmsg'] ?? '결제에 실패했습니다.',
            rawData: $responseData
        );
    }

    private function getApiUrl(): string
    {
        return $this->isTestMode() 
            ? 'https://stgstdpay.inicis.com/api/std/ipay'
            : 'https://stdpay.inicis.com/api/std/ipay';
    }

    private function getAcceptMethod(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'card' => 'CARD',
            'bank' => 'BANK',
            'virtual_account' => 'VBANK',
            'phone' => 'PHONE',
            default => 'CARD',
        };
    }

    private function generateHash(array $params): string
    {
        $hashString = implode('|', [
            $params['oid'] ?? '',
            $params['price'] ?? '',
            $params['mid'] ?? $this->config['merchant_id'],
            $this->config['sign_key'],
        ]);

        return hash('sha256', $hashString);
    }

    private function parseResponse(string $response): array
    {
        parse_str($response, $data);
        return $data;
    }

    private function mapStatusFromCode(string $code): PaymentStatus
    {
        return match ($code) {
            '00' => PaymentStatus::COMPLETED,
            '01', '02' => PaymentStatus::PENDING,
            '03' => PaymentStatus::PROCESSING,
            default => PaymentStatus::FAILED,
        };
    }

    private function mockPaymentResponse(PaymentRequest $request): PaymentResponse
    {
        // 테스트 모드에서의 모의 응답
        return PaymentResponse::success(
            status: PaymentStatus::COMPLETED,
            transactionId: $request->transactionId,
            gatewayTransactionId: 'TEST_' . time(),
            approvalNumber: 'TEST_' . rand(100000, 999999),
            message: '테스트 결제가 완료되었습니다.',
            rawData: ['test_mode' => true],
            cardInfo: [
                'card_number' => '****-****-****-1234',
                'card_company' => '테스트카드',
            ]
        );
    }
}