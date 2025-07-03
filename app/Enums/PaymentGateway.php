<?php

namespace App\Enums;

enum PaymentGateway: string
{
    case INICIS = 'inicis';
    case KG_INICIS = 'kg_inicis';
    case STRIPE = 'stripe';

    public function label(): string
    {
        return match ($this) {
            self::INICIS => '이니시스',
            self::KG_INICIS => 'KG이니시스',
            self::STRIPE => 'Stripe',
        };
    }

    public function supportedMethods(): array
    {
        return match ($this) {
            self::INICIS => [
                'card' => '신용카드',
                'bank' => '계좌이체',
                'virtual_account' => '가상계좌',
                'phone' => '휴대폰',
            ],
            self::KG_INICIS => [
                'card' => '신용카드',
                'bank' => '계좌이체',
                'virtual_account' => '가상계좌',
                'phone' => '휴대폰',
            ],
            self::STRIPE => [
                'card' => '신용카드',
                'paypal' => 'PayPal',
                'alipay' => 'Alipay',
                'wechat' => 'WeChat Pay',
            ],
        };
    }

    public function isKorean(): bool
    {
        return in_array($this, [self::INICIS, self::KG_INICIS]);
    }

    public function isInternational(): bool
    {
        return $this === self::STRIPE;
    }

    public function getConfigKey(): string
    {
        return 'payment.gateways.' . $this->value;
    }

    public function getWebhookUrl(): string
    {
        return route('payment.webhook', ['gateway' => $this->value]);
    }

    public function getReturnUrl(): string
    {
        return route('payment.return', ['gateway' => $this->value]);
    }

    public function getCancelUrl(): string
    {
        return route('payment.cancel', ['gateway' => $this->value]);
    }
}