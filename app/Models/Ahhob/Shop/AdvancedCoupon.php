<?php

namespace App\Models\Ahhob\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use Carbon\Carbon;

class AdvancedCoupon extends Model
{
    use HasFactory;

    protected $table = 'coupons';

    protected $fillable = [
        'code', 'name', 'description', 'type', 'value',
        'min_order_amount', 'max_discount_amount',
        'usage_limit', 'usage_limit_per_user', 'used_count',
        'applicable_products', 'applicable_categories',
        'excluded_products', 'excluded_categories',
        'user_type', 'user_level_min', 'first_order_only', 'user_tags',
        'starts_at', 'expires_at', 'is_active', 'is_public', 'created_by'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'applicable_products' => 'array',
        'applicable_categories' => 'array',
        'excluded_products' => 'array',
        'excluded_categories' => 'array',
        'user_tags' => 'array',
        'first_order_only' => 'boolean',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | 관계 (Relationships)
    |--------------------------------------------------------------------------
    */

    /**
     * 생성자
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 쿠폰 사용 내역
     */
    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * 자동 발급 규칙
     */
    public function autoIssueRules(): HasMany
    {
        return $this->hasMany(CouponAutoIssueRule::class);
    }

    /**
     * 사용자별 쿠폰 보유
     */
    public function userCoupons(): HasMany
    {
        return $this->hasMany(UserCoupon::class);
    }

    /**
     * 쿠폰을 보유한 사용자들
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_coupons')
                    ->withPivot(['issued_at', 'expires_at', 'is_used', 'used_at'])
                    ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | 쿼리 스코프 (Query Scopes)
    |--------------------------------------------------------------------------
    */

    /**
     * 활성화된 쿠폰만
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * 공개된 쿠폰만
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * 유효한 쿠폰만 (기간 내)
     */
    public function scopeValid(Builder $query): Builder
    {
        $now = now();
        return $query->where(function ($q) use ($now) {
            $q->where('starts_at', '<=', $now)
              ->where('expires_at', '>=', $now)
              ->orWhereNull('starts_at')
              ->orWhereNull('expires_at');
        });
    }

    /**
     * 사용 가능한 쿠폰만
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->active()
                    ->valid()
                    ->where(function ($q) {
                        $q->whereNull('usage_limit')
                          ->orWhereColumn('used_count', '<', 'usage_limit');
                    });
    }

    /*
    |--------------------------------------------------------------------------
    | 접근자 & 변경자 (Accessors & Mutators)
    |--------------------------------------------------------------------------
    */

    /**
     * 할인 타입 레이블
     */
    public function getTypeLabellAttribute(): string
    {
        return match($this->type) {
            'fixed' => '고정 할인',
            'percentage' => '비율 할인',
            default => '알 수 없음',
        };
    }

    /**
     * 사용자 타입 레이블
     */
    public function getUserTypeLabelAttribute(): string
    {
        return match($this->user_type) {
            'all' => '모든 사용자',
            'new' => '신규 사용자',
            'existing' => '기존 사용자',
            default => '알 수 없음',
        };
    }

    /**
     * 쿠폰 상태
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'expired';
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'scheduled';
        }

        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return 'exhausted';
        }

        return 'active';
    }

    /**
     * 쿠폰 상태 레이블
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'active' => '사용 가능',
            'inactive' => '비활성화',
            'expired' => '만료됨',
            'scheduled' => '예약됨',
            'exhausted' => '소진됨',
            default => '알 수 없음',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | 공개 메서드 (Public Methods)
    |--------------------------------------------------------------------------
    */

    /**
     * 쿠폰 유효성 검사
     */
    public function isValid(User $user = null, array $orderItems = []): bool
    {
        // 기본 조건 확인
        if (!$this->is_active || $this->status !== 'active') {
            return false;
        }

        // 사용자별 조건 확인
        if ($user) {
            if (!$this->isValidForUser($user)) {
                return false;
            }
        }

        // 상품별 조건 확인
        if (!empty($orderItems) && !$this->isValidForProducts($orderItems)) {
            return false;
        }

        return true;
    }

    /**
     * 사용자에 대한 유효성 검사
     */
    public function isValidForUser(User $user): bool
    {
        // 사용자 타입 확인
        if ($this->user_type !== 'all') {
            $isNewUser = $user->orders()->count() === 0;
            
            if ($this->user_type === 'new' && !$isNewUser) {
                return false;
            }
            
            if ($this->user_type === 'existing' && $isNewUser) {
                return false;
            }
        }

        // 첫 주문만 가능한 쿠폰
        if ($this->first_order_only && $user->orders()->count() > 0) {
            return false;
        }

        // 사용자 레벨 확인
        if ($this->user_level_min && ($user->level ?? 0) < $this->user_level_min) {
            return false;
        }

        // 사용자 태그 확인
        if ($this->user_tags && !empty($this->user_tags)) {
            $userTags = $user->tags ?? [];
            if (empty(array_intersect($this->user_tags, $userTags))) {
                return false;
            }
        }

        // 사용자별 사용 제한 확인
        if ($this->usage_limit_per_user) {
            $userUsageCount = $this->usages()->where('user_id', $user->id)->count();
            if ($userUsageCount >= $this->usage_limit_per_user) {
                return false;
            }
        }

        return true;
    }

    /**
     * 상품에 대한 유효성 검사
     */
    public function isValidForProducts(array $orderItems): bool
    {
        $applicableFound = false;

        foreach ($orderItems as $item) {
            $product = $item['product'] ?? $item;
            $productId = $product->id ?? $product['id'];
            $categoryId = $product->category_id ?? $product['category_id'];

            // 제외 상품 확인
            if ($this->excluded_products && in_array($productId, $this->excluded_products)) {
                continue;
            }

            // 제외 카테고리 확인
            if ($this->excluded_categories && in_array($categoryId, $this->excluded_categories)) {
                continue;
            }

            // 적용 가능 상품이 지정된 경우
            if ($this->applicable_products) {
                if (in_array($productId, $this->applicable_products)) {
                    $applicableFound = true;
                }
                continue;
            }

            // 적용 가능 카테고리가 지정된 경우
            if ($this->applicable_categories) {
                if (in_array($categoryId, $this->applicable_categories)) {
                    $applicableFound = true;
                }
                continue;
            }

            // 특별한 제한이 없으면 모든 상품에 적용
            if (!$this->applicable_products && !$this->applicable_categories) {
                $applicableFound = true;
            }
        }

        return $applicableFound;
    }

    /**
     * 할인 금액 계산
     */
    public function calculateDiscount(float $orderAmount, array $orderItems = []): float
    {
        // 최소 주문 금액 확인
        if ($this->min_order_amount && $orderAmount < $this->min_order_amount) {
            return 0;
        }

        // 적용 가능한 상품들의 금액만 계산
        $applicableAmount = $this->calculateApplicableAmount($orderAmount, $orderItems);

        if ($applicableAmount <= 0) {
            return 0;
        }

        // 할인 계산
        $discount = match($this->type) {
            'fixed' => $this->value,
            'percentage' => $applicableAmount * ($this->value / 100),
            default => 0,
        };

        // 최대 할인 금액 제한
        if ($this->max_discount_amount && $discount > $this->max_discount_amount) {
            $discount = $this->max_discount_amount;
        }

        // 적용 가능 금액을 초과할 수 없음
        if ($discount > $applicableAmount) {
            $discount = $applicableAmount;
        }

        return round($discount, 2);
    }

    /**
     * 적용 가능한 금액 계산
     */
    private function calculateApplicableAmount(float $orderAmount, array $orderItems): float
    {
        // 특정 상품/카테고리 제한이 없으면 전체 주문 금액
        if (!$this->applicable_products && !$this->applicable_categories && 
            !$this->excluded_products && !$this->excluded_categories) {
            return $orderAmount;
        }

        // 주문 아이템별로 적용 가능 금액 계산
        $applicableAmount = 0;

        foreach ($orderItems as $item) {
            $product = $item['product'] ?? $item;
            $productId = $product->id ?? $product['id'];
            $categoryId = $product->category_id ?? $product['category_id'];
            $itemAmount = ($item['price'] ?? $product->price) * ($item['quantity'] ?? 1);

            // 제외 상품/카테고리 확인
            if ($this->excluded_products && in_array($productId, $this->excluded_products)) {
                continue;
            }
            if ($this->excluded_categories && in_array($categoryId, $this->excluded_categories)) {
                continue;
            }

            // 적용 가능 상품/카테고리 확인
            $isApplicable = false;

            if ($this->applicable_products && in_array($productId, $this->applicable_products)) {
                $isApplicable = true;
            } elseif ($this->applicable_categories && in_array($categoryId, $this->applicable_categories)) {
                $isApplicable = true;
            } elseif (!$this->applicable_products && !$this->applicable_categories) {
                $isApplicable = true;
            }

            if ($isApplicable) {
                $applicableAmount += $itemAmount;
            }
        }

        return $applicableAmount;
    }

    /**
     * 쿠폰 사용
     */
    public function use(User $user, Order $order, float $discountAmount): CouponUsage
    {
        // 사용 횟수 증가
        $this->increment('used_count');

        // 사용 내역 생성
        return $this->usages()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'discount_amount' => $discountAmount,
            'order_amount' => $order->total_amount,
        ]);
    }

    /**
     * 사용자에게 쿠폰 발급
     */
    public function issueToUser(User $user, ?Carbon $expiresAt = null): UserCoupon
    {
        return UserCoupon::create([
            'user_id' => $user->id,
            'coupon_id' => $this->id,
            'issued_at' => now(),
            'expires_at' => $expiresAt ?? $this->expires_at,
        ]);
    }

    /**
     * 쿠폰 코드 생성
     */
    public static function generateCode(int $length = 10): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // 중복 확인
        if (static::where('code', $code)->exists()) {
            return static::generateCode($length);
        }
        
        return $code;
    }
}