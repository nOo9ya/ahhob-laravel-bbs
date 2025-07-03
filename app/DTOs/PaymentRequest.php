<?php

namespace App\DTOs;

use App\Enums\PaymentGateway;

class PaymentRequest
{
    public function __construct(
        public readonly string $transactionId,
        public readonly int $orderId,
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $paymentMethod,
        public readonly PaymentGateway $gateway,
        public readonly array $customerInfo,
        public readonly array $productInfo,
        public readonly string $returnUrl,
        public readonly string $cancelUrl,
        public readonly string $webhookUrl,
        public readonly array $extraData = [],
    ) {}

    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'order_id' => $this->orderId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_method' => $this->paymentMethod,
            'gateway' => $this->gateway->value,
            'customer_info' => $this->customerInfo,
            'product_info' => $this->productInfo,
            'return_url' => $this->returnUrl,
            'cancel_url' => $this->cancelUrl,
            'webhook_url' => $this->webhookUrl,
            'extra_data' => $this->extraData,
        ];
    }

    public static function fromOrder(
        object $order,
        PaymentGateway $gateway,
        string $paymentMethod,
        string $transactionId = null
    ): self {
        return new self(
            transactionId: $transactionId ?? 'TXN_' . time() . '_' . $order->id,
            orderId: $order->id,
            amount: $order->total_amount,
            currency: 'KRW',
            paymentMethod: $paymentMethod,
            gateway: $gateway,
            customerInfo: [
                'name' => $order->customer_name,
                'email' => $order->customer_email,
                'phone' => $order->customer_phone,
            ],
            productInfo: [
                'name' => $order->items->count() > 1 
                    ? $order->items->first()->product_name . ' 외 ' . ($order->items->count() - 1) . '건'
                    : $order->items->first()->product_name,
                'items' => $order->items->map(fn($item) => [
                    'name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ])->toArray(),
            ],
            returnUrl: route('ahhob.shop.orders.payment.return', $order),
            cancelUrl: route('ahhob.shop.orders.payment.cancel', $order),
            webhookUrl: route('payment.webhook', ['gateway' => $gateway->value]),
        );
    }
}