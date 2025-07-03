<?php

namespace Database\Seeders;

use App\Enums\PaymentGateway;
use App\Models\Ahhob\Shop\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $paymentMethods = [
            // 이니시스 결제 수단
            [
                'code' => 'inicis_card',
                'name' => '신용카드 (이니시스)',
                'gateway' => PaymentGateway::INICIS,
                'is_active' => true,
                'sort_order' => 1,
                'config' => [
                    'display_name' => '신용카드',
                    'icon' => 'credit-card',
                ],
                'fee_rate' => 2.9,
                'fee_fixed' => 0,
                'min_amount' => 1000,
                'max_amount' => 10000000,
                'allowed_cards' => null,
                'blocked_cards' => null,
                'require_auth' => false,
            ],
            [
                'code' => 'inicis_bank',
                'name' => '계좌이체 (이니시스)',
                'gateway' => PaymentGateway::INICIS,
                'is_active' => true,
                'sort_order' => 2,
                'config' => [
                    'display_name' => '계좌이체',
                    'icon' => 'bank',
                ],
                'fee_rate' => 1.5,
                'fee_fixed' => 0,
                'min_amount' => 1000,
                'max_amount' => 50000000,
                'require_auth' => true,
            ],
            [
                'code' => 'inicis_virtual_account',
                'name' => '가상계좌 (이니시스)',
                'gateway' => PaymentGateway::INICIS,
                'is_active' => true,
                'sort_order' => 3,
                'config' => [
                    'display_name' => '가상계좌',
                    'icon' => 'receipt',
                ],
                'fee_rate' => 1.0,
                'fee_fixed' => 500,
                'min_amount' => 10000,
                'max_amount' => 50000000,
            ],
            [
                'code' => 'inicis_phone',
                'name' => '휴대폰 (이니시스)',
                'gateway' => PaymentGateway::INICIS,
                'is_active' => true,
                'sort_order' => 4,
                'config' => [
                    'display_name' => '휴대폰',
                    'icon' => 'phone',
                ],
                'fee_rate' => 4.5,
                'fee_fixed' => 0,
                'min_amount' => 1000,
                'max_amount' => 300000,
            ],

            // KG이니시스 결제 수단
            [
                'code' => 'kg_inicis_card',
                'name' => '신용카드 (KG이니시스)',
                'gateway' => PaymentGateway::KG_INICIS,
                'is_active' => true,
                'sort_order' => 5,
                'config' => [
                    'display_name' => '신용카드',
                    'icon' => 'credit-card',
                ],
                'fee_rate' => 2.8,
                'fee_fixed' => 0,
                'min_amount' => 1000,
                'max_amount' => 10000000,
            ],
            [
                'code' => 'kg_inicis_bank',
                'name' => '계좌이체 (KG이니시스)',
                'gateway' => PaymentGateway::KG_INICIS,
                'is_active' => true,
                'sort_order' => 6,
                'config' => [
                    'display_name' => '계좌이체',
                    'icon' => 'bank',
                ],
                'fee_rate' => 1.4,
                'fee_fixed' => 0,
                'min_amount' => 1000,
                'max_amount' => 50000000,
                'require_auth' => true,
            ],

            // Stripe 결제 수단
            [
                'code' => 'stripe_card',
                'name' => 'Credit Card (Stripe)',
                'gateway' => PaymentGateway::STRIPE,
                'is_active' => true,
                'sort_order' => 7,
                'config' => [
                    'display_name' => 'Credit Card',
                    'icon' => 'credit-card',
                ],
                'fee_rate' => 3.4,
                'fee_fixed' => 30,
                'min_amount' => 100,
                'max_amount' => 0, // 무제한
            ],
            [
                'code' => 'stripe_paypal',
                'name' => 'PayPal (Stripe)',
                'gateway' => PaymentGateway::STRIPE,
                'is_active' => false, // 기본 비활성화
                'sort_order' => 8,
                'config' => [
                    'display_name' => 'PayPal',
                    'icon' => 'paypal',
                ],
                'fee_rate' => 3.9,
                'fee_fixed' => 30,
                'min_amount' => 100,
                'max_amount' => 0,
            ],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                $method
            );
        }

        $this->command->info('Payment methods seeded successfully!');
    }
}