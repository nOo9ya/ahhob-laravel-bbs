<?php

namespace App\DTOs;

class RefundResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $transactionId,
        public readonly float $refundAmount,
        public readonly ?string $refundTransactionId = null,
        public readonly ?string $message = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
        public readonly array $rawData = [],
    ) {}

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'transaction_id' => $this->transactionId,
            'refund_amount' => $this->refundAmount,
            'refund_transaction_id' => $this->refundTransactionId,
            'message' => $this->message,
            'error_code' => $this->errorCode,
            'error_message' => $this->errorMessage,
            'raw_data' => $this->rawData,
        ];
    }

    public static function success(
        string $transactionId,
        float $refundAmount,
        ?string $refundTransactionId = null,
        string $message = '환불이 완료되었습니다.',
        array $rawData = []
    ): self {
        return new self(
            success: true,
            transactionId: $transactionId,
            refundAmount: $refundAmount,
            refundTransactionId: $refundTransactionId,
            message: $message,
            rawData: $rawData,
        );
    }

    public static function failure(
        string $transactionId,
        float $refundAmount,
        string $errorCode = '',
        string $errorMessage = '환불에 실패했습니다.',
        array $rawData = []
    ): self {
        return new self(
            success: false,
            transactionId: $transactionId,
            refundAmount: $refundAmount,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            rawData: $rawData,
        );
    }
}