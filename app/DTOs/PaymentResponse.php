<?php

namespace App\DTOs;

use App\Enums\PaymentStatus;

class PaymentResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly PaymentStatus $status,
        public readonly string $transactionId,
        public readonly ?string $gatewayTransactionId = null,
        public readonly ?string $approvalNumber = null,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $message = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
        public readonly array $rawData = [],
        public readonly array $cardInfo = [],
    ) {}

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isRedirectRequired(): bool
    {
        return !empty($this->redirectUrl);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'status' => $this->status->value,
            'transaction_id' => $this->transactionId,
            'gateway_transaction_id' => $this->gatewayTransactionId,
            'approval_number' => $this->approvalNumber,
            'redirect_url' => $this->redirectUrl,
            'message' => $this->message,
            'error_code' => $this->errorCode,
            'error_message' => $this->errorMessage,
            'raw_data' => $this->rawData,
            'card_info' => $this->cardInfo,
        ];
    }

    public static function success(
        PaymentStatus $status,
        string $transactionId,
        ?string $gatewayTransactionId = null,
        ?string $approvalNumber = null,
        ?string $redirectUrl = null,
        string $message = '결제가 성공했습니다.',
        array $rawData = [],
        array $cardInfo = []
    ): self {
        return new self(
            success: true,
            status: $status,
            transactionId: $transactionId,
            gatewayTransactionId: $gatewayTransactionId,
            approvalNumber: $approvalNumber,
            redirectUrl: $redirectUrl,
            message: $message,
            rawData: $rawData,
            cardInfo: $cardInfo,
        );
    }

    public static function failure(
        PaymentStatus $status,
        string $transactionId,
        string $errorCode = '',
        string $errorMessage = '결제에 실패했습니다.',
        array $rawData = []
    ): self {
        return new self(
            success: false,
            status: $status,
            transactionId: $transactionId,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            rawData: $rawData,
        );
    }

    public static function redirect(
        string $transactionId,
        string $redirectUrl,
        string $message = '결제 페이지로 이동합니다.',
        array $rawData = []
    ): self {
        return new self(
            success: true,
            status: PaymentStatus::PROCESSING,
            transactionId: $transactionId,
            redirectUrl: $redirectUrl,
            message: $message,
            rawData: $rawData,
        );
    }
}