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

class KgInicisGateway implements PaymentGatewayInterface
{
    private array $config;

    public function __construct()
    {
        $this->config = config('payment.gateways.kg_inicis', []);
    }

    public function processPayment(PaymentRequest $request): PaymentResponse
    {
        try {
            $params = $this->buildPaymentParams($request);
            
            if ($this->isTestMode()) {
                return $this->mockPaymentResponse($request);
            }

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ])
                ->post($this->getApiUrl(), $params);

            if (!$response->successful()) {
                Log::error('KG Inicis API request failed', [
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
            Log::error('KG Inicis payment processing failed', [
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
                'svcCd' => 'PAY',
                'ver' => '1.0',
                'method' => 'inquiry',
                'mid' => $this->config['merchant_id'],
                'tid' => $transactionId,
                'authDt' => date('YmdHis'),
            ];

            $params['authKey'] = $this->generateAuthKey($params);

            $response = Http::post($this->getApiUrl() . '/inquiry', $params);
            $responseData = $this->parseResponse($response->body());

            return $this->buildPaymentResponse(null, $responseData);

        } catch (\Exception $e) {
            Log::error('KG Inicis payment status inquiry failed', [
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
                'svcCd' => 'PAY',
                'ver' => '1.0',
                'method' => 'cancel',
                'mid' => $this->config['merchant_id'],
                'tid' => $transactionId,
                'cancMsg' => $reason ?: '고객 요청',
                'authDt' => date('YmdHis'),
            ];

            $params['authKey'] = $this->generateAuthKey($params);

            $response = Http::post($this->getApiUrl() . '/cancel', $params);
            $responseData = $this->parseResponse($response->body());

            if ($responseData['resCd'] === '0000') {
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
                $responseData['resCd'],
                $responseData['resMsg'] ?? '결제 취소에 실패했습니다.',
                $responseData
            );

        } catch (\Exception $e) {
            Log::error('KG Inicis payment cancellation failed', [
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
                'svcCd' => 'PAY',
                'ver' => '1.0',
                'method' => 'refund',
                'mid' => $this->config['merchant_id'],
                'tid' => $request->gatewayTransactionId,
                'refundAmt' => (int) $request->amount,
                'refundMsg' => $request->reason,
                'authDt' => date('YmdHis'),
            ];

            $params['authKey'] = $this->generateAuthKey($params);

            $response = Http::post($this->getApiUrl() . '/refund', $params);
            $responseData = $this->parseResponse($response->body());

            if ($responseData['resCd'] === '0000') {
                return RefundResponse::success(
                    $request->transactionId,
                    $request->amount,
                    $responseData['refundTid'] ?? null,
                    '환불이 완료되었습니다.',
                    $responseData
                );
            }

            return RefundResponse::failure(
                $request->transactionId,
                $request->amount,
                $responseData['resCd'],
                $responseData['resMsg'] ?? '환불에 실패했습니다.',
                $responseData
            );

        } catch (\Exception $e) {
            Log::error('KG Inicis refund failed', [
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
        $expectedAuthKey = $this->generateAuthKey($data);
        return hash_equals($expectedAuthKey, $data['authKey'] ?? '');
    }

    public function parseWebhookData(array $data): PaymentResponse
    {
        $status = $this->mapStatusFromCode($data['resCd'] ?? '');
        
        return new PaymentResponse(
            success: $data['resCd'] === '0000',
            status: $status,
            transactionId: $data['moid'] ?? '',
            gatewayTransactionId: $data['tid'] ?? '',
            approvalNumber: $data['authNo'] ?? '',
            message: $data['resMsg'] ?? '',
            rawData: $data,
            cardInfo: [
                'card_number' => $data['CARD_Num'] ?? '',
                'card_company' => $data['CARD_Cl'] ?? '',
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
               !empty($this->config['merchant_key']);
    }

    public function isTestMode(): bool
    {
        return $this->config['test_mode'] ?? false;
    }

    private function buildPaymentParams(PaymentRequest $request): array
    {
        $params = [
            'svcCd' => 'PAY',
            'ver' => '1.0',
            'method' => 'auth',
            'mid' => $this->config['merchant_id'],
            'moid' => $request->transactionId,
            'price' => (int) $request->amount,
            'currency' => $request->currency,
            'goodsName' => $request->productInfo['name'],
            'buyerName' => $request->customerInfo['name'],
            'buyerTel' => $request->customerInfo['phone'],
            'buyerEmail' => $request->customerInfo['email'],
            'returnUrl' => $request->returnUrl,
            'cancelUrl' => $request->cancelUrl,
            'payMethod' => $this->getPayMethod($request->paymentMethod),
            'authDt' => date('YmdHis'),
        ];

        $params['authKey'] = $this->generateAuthKey($params);

        return $params;
    }

    private function buildPaymentResponse(?PaymentRequest $request, array $responseData): PaymentResponse
    {
        $status = $this->mapStatusFromCode($responseData['resCd'] ?? '');
        $isSuccess = $responseData['resCd'] === '0000';

        if ($isSuccess) {
            return PaymentResponse::success(
                status: $status,
                transactionId: $request ? $request->transactionId : ($responseData['moid'] ?? ''),
                gatewayTransactionId: $responseData['tid'] ?? '',
                approvalNumber: $responseData['authNo'] ?? '',
                message: $responseData['resMsg'] ?? '결제가 완료되었습니다.',
                rawData: $responseData,
                cardInfo: [
                    'card_number' => $responseData['CARD_Num'] ?? '',
                    'card_company' => $responseData['CARD_Cl'] ?? '',
                ]
            );
        }

        return PaymentResponse::failure(
            status: $status,
            transactionId: $request ? $request->transactionId : ($responseData['moid'] ?? ''),
            errorCode: $responseData['resCd'] ?? '',
            errorMessage: $responseData['resMsg'] ?? '결제에 실패했습니다.',
            rawData: $responseData
        );
    }

    private function getApiUrl(): string
    {
        return $this->isTestMode() 
            ? 'https://tbnpg.kcp.co.kr'
            : 'https://npg.kcp.co.kr';
    }

    private function getPayMethod(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'card' => '100000000000',
            'bank' => '010000000000',
            'virtual_account' => '001000000000',
            'phone' => '000010000000',
            default => '100000000000',
        };
    }

    private function generateAuthKey(array $params): string
    {
        $authString = implode('', [
            $params['mid'] ?? $this->config['merchant_id'],
            $params['moid'] ?? $params['tid'] ?? '',
            $params['price'] ?? $params['refundAmt'] ?? '',
            $this->config['merchant_key'],
        ]);

        return hash('sha256', $authString);
    }

    private function parseResponse(string $response): array
    {
        parse_str($response, $data);
        return $data;
    }

    private function mapStatusFromCode(string $code): PaymentStatus
    {
        return match ($code) {
            '0000' => PaymentStatus::COMPLETED,
            '0001', '0002' => PaymentStatus::PENDING,
            '0003' => PaymentStatus::PROCESSING,
            default => PaymentStatus::FAILED,
        };
    }

    private function mockPaymentResponse(PaymentRequest $request): PaymentResponse
    {
        return PaymentResponse::success(
            status: PaymentStatus::COMPLETED,
            transactionId: $request->transactionId,
            gatewayTransactionId: 'KGTEST_' . time(),
            approvalNumber: 'KGTEST_' . rand(100000, 999999),
            message: 'KG이니시스 테스트 결제가 완료되었습니다.',
            rawData: ['test_mode' => true],
            cardInfo: [
                'card_number' => '****-****-****-5678',
                'card_company' => 'KG테스트카드',
            ]
        );
    }
}