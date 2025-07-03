<?php

namespace App\Contracts;

use App\DTOs\PaymentRequest;
use App\DTOs\PaymentResponse;
use App\DTOs\RefundRequest;
use App\DTOs\RefundResponse;

interface PaymentGatewayInterface
{
    /**
     * 결제 요청 처리
     */
    public function processPayment(PaymentRequest $request): PaymentResponse;

    /**
     * 결제 상태 조회
     */
    public function getPaymentStatus(string $transactionId): PaymentResponse;

    /**
     * 결제 취소
     */
    public function cancelPayment(string $transactionId, string $reason = ''): PaymentResponse;

    /**
     * 환불 처리
     */
    public function refundPayment(RefundRequest $request): RefundResponse;

    /**
     * 웹훅 검증
     */
    public function verifyWebhook(array $data, string $signature = ''): bool;

    /**
     * 웹훅 데이터 파싱
     */
    public function parseWebhookData(array $data): PaymentResponse;

    /**
     * 지원하는 결제 수단 목록
     */
    public function getSupportedMethods(): array;

    /**
     * 게이트웨이 설정 검증
     */
    public function validateConfig(): bool;

    /**
     * 테스트 모드 여부
     */
    public function isTestMode(): bool;
}