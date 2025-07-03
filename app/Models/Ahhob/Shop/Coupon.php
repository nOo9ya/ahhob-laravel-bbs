<?php

namespace App\Models\Ahhob\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

class Coupon extends Model
{
    use HasFactory;

    protected $table = 'shop_coupons';

    /*
    |--------------------------------------------------------------------------
    | 모델 속성 (Attributes & Properties)
    |--------------------------------------------------------------------------
    */
    // region --- 모델 속성 (Attributes & Properties) ---

    protected $fillable = [
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'minimum_order_amount',
        'maximum_discount',
        'maximum_uses',
        'maximum_uses_per_user',
        'used_count',
        'valid_from',
        'valid_until',
        'is_active',
        'is_public',
        'created_by',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'minimum_order_amount' => 'decimal:2',
        'maximum_discount' => 'decimal:2',
        'maximum_uses' => 'integer',
        'maximum_uses_per_user' => 'integer',
        'used_count' => 'integer',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
    ];

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 관계 (Relationships)
    |--------------------------------------------------------------------------
    */
    // region --- 관계 (Relationships) ---

    /**
     * 생성자
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 쿼리 스코프 (Query Scopes)
    |--------------------------------------------------------------------------
    */
    // region --- 쿼리 스코프 (Query Scopes) ---

    /**
     * 활성화된 쿠폰만 조회
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * 공개된 쿠폰만 조회
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * 유효한 쿠폰만 조회
     */
    public function scopeValid(Builder $query): Builder
    {
        $now = now();
        return $query->where('valid_from', '<=', $now)
                    ->where('valid_until', '>=', $now);
    }

    /**
     * 사용 가능한 쿠폰만 조회
     */
    public function scopeUsable(Builder $query): Builder
    {
        return $query->active()
                    ->valid()
                    ->where(function ($query) {
                        $query->whereNull('maximum_uses')
                              ->orWhereColumn('used_count', '<', 'maximum_uses');
                    });
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 접근자 & 변경자 (Accessors & Mutators)
    |--------------------------------------------------------------------------
    */
    // region --- 접근자 & 변경자 (Accessors & Mutators) ---

    /**
     * 할인 타입 레이블
     */
    public function getDiscountTypeLabelAttribute(): string
    {
        return match($this->discount_type) {
            'fixed' => '정액 할인',
            'percentage' => '비율 할인',
            default => '알 수 없음',
        };
    }

    /**
     * 포맷된 할인값
     */
    public function getFormattedDiscountValueAttribute(): string
    {
        if ($this->discount_type === 'percentage') {
            return $this->discount_value . '%';
        }

        return '₩' . number_format($this->discount_value);
    }

    /**
     * 포맷된 최소 주문 금액
     */
    public function getFormattedMinimumOrderAmountAttribute(): ?string
    {
        return $this->minimum_order_amount ? '₩' . number_format($this->minimum_order_amount) : null;
    }

    /**
     * 사용 가능 여부
     */
    public function getIsUsableAttribute(): bool
    {
        return $this->is_active
            && $this->valid_from <= now()
            && $this->valid_until >= now()
            && ($this->maximum_uses === null || $this->used_count < $this->maximum_uses);
    }

    /**
     * 남은 사용 횟수
     */
    public function getRemainingUsageAttribute(): ?int
    {
        if ($this->maximum_uses === null) {
            return null;
        }

        return max(0, $this->maximum_uses - $this->used_count);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 공개 메서드 (Public Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 공개 메서드 (Public Methods) ---

    /**
     * 쿠폰 적용 가능 여부 확인
     */
    public function canBeUsed(?User $user = null, float $orderAmount = 0): array
    {
        // 기본 유효성 검사
        if (!$this->is_usable) {
            return ['can_use' => false, 'reason' => '사용할 수 없는 쿠폰입니다.'];
        }

        // 최소 주문 금액 확인
        if ($this->minimum_order_amount && $orderAmount < $this->minimum_order_amount) {
            return [
                'can_use' => false, 
                'reason' => "최소 주문 금액 ₩" . number_format($this->minimum_order_amount) . " 이상이어야 합니다."
            ];
        }

        // 사용자별 사용 제한 확인
        if ($user && $this->maximum_uses_per_user) {
            $userUsageCount = Order::where('user_id', $user->id)
                                  ->where('coupon_code', $this->code)
                                  ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
                                  ->count();

            if ($userUsageCount >= $this->maximum_uses_per_user) {
                return ['can_use' => false, 'reason' => '사용자별 사용 제한을 초과했습니다.'];
            }
        }

        return ['can_use' => true, 'reason' => null];
    }

    /**
     * 할인 금액 계산
     */
    public function calculateDiscount(float $amount): float
    {
        if ($this->discount_type === 'percentage') {
            $discount = ($amount * $this->discount_value) / 100;
        } else {
            $discount = $this->discount_value;
        }

        // 최대 할인 금액 제한
        if ($this->maximum_discount && $discount > $this->maximum_discount) {
            $discount = $this->maximum_discount;
        }

        return min($discount, $amount); // 주문 금액을 초과할 수 없음
    }

    /**
     * 모든 상품에 적용 가능 (단순화)
     */
    public function isApplicableToProduct(Product $product): bool
    {
        return true;
    }

    /**
     * 사용 횟수 증가
     */
    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }

    /**
     * 사용 횟수 감소 (주문 취소 시)
     */
    public function decrementUsage(): void
    {
        if ($this->used_count > 0) {
            $this->decrement('used_count');
        }
    }

    /**
     * 쿠폰 코드 생성
     */
    public static function generateCode(int $length = 8): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }

        // 중복 확인
        while (static::where('code', $code)->exists()) {
            $code = static::generateCode($length);
        }

        return $code;
    }

    // endregion
}