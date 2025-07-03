<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\PaymentGateway;
use App\Services\Payment\Gateways\InicisGateway;
use App\Services\Payment\Gateways\KgInicisGateway;
use App\Services\Payment\Gateways\StripeGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    private array $gateways = [];

    public function __construct()
    {
        $this->registerGateways();
    }

    /**
     * 게이트웨이 등록
     */
    private function registerGateways(): void
    {
        $this->gateways = [
            PaymentGateway::INICIS->value => InicisGateway::class,
            PaymentGateway::KG_INICIS->value => KgInicisGateway::class,
            PaymentGateway::STRIPE->value => StripeGateway::class,
        ];
    }

    /**
     * 특정 게이트웨이 인스턴스 반환
     */
    public function gateway(PaymentGateway $gateway): PaymentGatewayInterface
    {
        if (!isset($this->gateways[$gateway->value])) {
            throw new InvalidArgumentException("Unsupported payment gateway: {$gateway->value}");
        }

        $gatewayClass = $this->gateways[$gateway->value];
        
        return app($gatewayClass);
    }

    /**
     * 활성화된 게이트웨이 목록 반환
     */
    public function getActiveGateways(): array
    {
        $activeGateways = [];

        foreach (PaymentGateway::cases() as $gateway) {
            if ($this->isGatewayActive($gateway)) {
                $activeGateways[] = $gateway;
            }
        }

        return $activeGateways;
    }

    /**
     * 게이트웨이 활성화 상태 확인
     */
    public function isGatewayActive(PaymentGateway $gateway): bool
    {
        try {
            $gatewayInstance = $this->gateway($gateway);
            return $gatewayInstance->validateConfig();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 특정 금액과 결제 수단에 사용 가능한 게이트웨이 목록
     */
    public function getAvailableGateways(int $amount, string $paymentMethod): array
    {
        $availableGateways = [];

        foreach ($this->getActiveGateways() as $gateway) {
            $gatewayInstance = $this->gateway($gateway);
            $supportedMethods = $gatewayInstance->getSupportedMethods();

            if (array_key_exists($paymentMethod, $supportedMethods)) {
                $availableGateways[] = $gateway;
            }
        }

        return $availableGateways;
    }

    /**
     * 기본 게이트웨이 반환
     */
    public function getDefaultGateway(): PaymentGateway
    {
        $defaultGateway = config('payment.default_gateway', 'inicis');
        
        return PaymentGateway::from($defaultGateway);
    }

    /**
     * 모든 지원 결제 수단 반환
     */
    public function getAllSupportedMethods(): array
    {
        $allMethods = [];

        foreach ($this->getActiveGateways() as $gateway) {
            $gatewayInstance = $this->gateway($gateway);
            $methods = $gatewayInstance->getSupportedMethods();

            foreach ($methods as $method => $label) {
                if (!isset($allMethods[$method])) {
                    $allMethods[$method] = [
                        'label' => $label,
                        'gateways' => [],
                    ];
                }
                $allMethods[$method]['gateways'][] = $gateway;
            }
        }

        return $allMethods;
    }

    /**
     * 게이트웨이별 수수료 정보
     */
    public function getGatewayFees(): array
    {
        $fees = [];

        foreach ($this->getActiveGateways() as $gateway) {
            $config = config("payment.gateways.{$gateway->value}");
            $fees[$gateway->value] = [
                'rate' => $config['fee_rate'] ?? 0,
                'fixed' => $config['fee_fixed'] ?? 0,
            ];
        }

        return $fees;
    }

    /**
     * 최적 게이트웨이 추천
     */
    public function recommendGateway(int $amount, string $paymentMethod, array $preferences = []): PaymentGateway
    {
        $availableGateways = $this->getAvailableGateways($amount, $paymentMethod);

        if (empty($availableGateways)) {
            throw new InvalidArgumentException('No available gateway for the specified criteria');
        }

        // 우선순위: 수수료 -> 안정성 -> 기본값
        $fees = $this->getGatewayFees();
        
        usort($availableGateways, function ($a, $b) use ($fees, $amount) {
            $feeA = $fees[$a->value]['fixed'] + ($amount * $fees[$a->value]['rate'] / 100);
            $feeB = $fees[$b->value]['fixed'] + ($amount * $fees[$b->value]['rate'] / 100);
            
            return $feeA <=> $feeB;
        });

        return $availableGateways[0];
    }
}