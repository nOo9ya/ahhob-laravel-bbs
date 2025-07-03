<?php

namespace App\Models\Ahhob\Shop;

use App\Enums\PaymentGateway;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'gateway',
        'is_active',
        'sort_order',
        'config',
        'fee_rate',
        'fee_fixed',
        'min_amount',
        'max_amount',
        'allowed_cards',
        'blocked_cards',
        'require_auth',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array',
        'fee_rate' => 'decimal:4',
        'allowed_cards' => 'array',
        'blocked_cards' => 'array',
        'require_auth' => 'boolean',
        'gateway' => PaymentGateway::class,
    ];

    /**
     * 활성화된 결제 수단만 조회
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * 특정 게이트웨이의 결제 수단만 조회
     */
    public function scopeByGateway(Builder $query, PaymentGateway $gateway): Builder
    {
        return $query->where('gateway', $gateway);
    }

    /**
     * 정렬된 결제 수단 조회
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * 특정 금액에 사용 가능한 결제 수단인지 확인
     */
    public function isAvailableForAmount(int $amount): bool
    {
        if ($this->min_amount > 0 && $amount < $this->min_amount) {
            return false;
        }

        if ($this->max_amount > 0 && $amount > $this->max_amount) {
            return false;
        }

        return true;
    }

    /**
     * 특정 카드사가 허용되는지 확인
     */
    public function isCardAllowed(string $cardCompany): bool
    {
        // 차단된 카드사 확인
        if ($this->blocked_cards && in_array($cardCompany, $this->blocked_cards)) {
            return false;
        }

        // 허용된 카드사가 설정되어 있다면 확인
        if ($this->allowed_cards && !empty($this->allowed_cards)) {
            return in_array($cardCompany, $this->allowed_cards);
        }

        return true;
    }

    /**
     * 결제 수수료 계산
     */
    public function calculateFee(int $amount): int
    {
        $fee = $this->fee_fixed;
        
        if ($this->fee_rate > 0) {
            $fee += (int) round($amount * ($this->fee_rate / 100));
        }

        return $fee;
    }

    /**
     * 수수료 포함 총 금액 계산
     */
    public function calculateTotalAmount(int $amount): int
    {
        return $amount + $this->calculateFee($amount);
    }

    /**
     * 게이트웨이 라벨 반환
     */
    public function getGatewayLabelAttribute(): string
    {
        return match ($this->gateway) {
            PaymentGateway::INICIS => '이니시스',
            PaymentGateway::KG_INICIS => 'KG이니시스',
            PaymentGateway::STRIPE => 'Stripe',
            default => $this->gateway,
        };
    }

    /**
     * 포맷된 수수료율 반환
     */
    public function getFormattedFeeRateAttribute(): string
    {
        return number_format($this->fee_rate, 2) . '%';
    }

    /**
     * 포맷된 고정 수수료 반환
     */
    public function getFormattedFeeFixedAttribute(): string
    {
        return '₩' . number_format($this->fee_fixed);
    }

    /**
     * 포맷된 최소 금액 반환
     */
    public function getFormattedMinAmountAttribute(): string
    {
        return $this->min_amount > 0 ? '₩' . number_format($this->min_amount) : '제한 없음';
    }

    /**
     * 포맷된 최대 금액 반환
     */
    public function getFormattedMaxAmountAttribute(): string
    {
        return $this->max_amount > 0 ? '₩' . number_format($this->max_amount) : '제한 없음';
    }
}