<?php

namespace App\DTOs;

class RefundRequest
{
    public function __construct(
        public readonly string $transactionId,
        public readonly string $gatewayTransactionId,
        public readonly float $amount,
        public readonly string $reason,
        public readonly array $extraData = [],
    ) {}

    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'gateway_transaction_id' => $this->gatewayTransactionId,
            'amount' => $this->amount,
            'reason' => $this->reason,
            'extra_data' => $this->extraData,
        ];
    }
}