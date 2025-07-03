<?php

namespace App\Models\Ahhob\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

class Cart extends Model
{
    use HasFactory;

    protected $table = 'shop_carts';

    /*
    |--------------------------------------------------------------------------
    | 모델 속성 (Attributes & Properties)
    |--------------------------------------------------------------------------
    */
    // region --- 모델 속성 (Attributes & Properties) ---

    protected $fillable = [
        'user_id',
        'session_id',
        'product_id',
        'product_options',
        'quantity',
        'unit_price',
        'total_price',
        'product_name',
        'product_image',
        'product_sku',
    ];

    protected $casts = [
        'product_options' => 'array',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 관계 (Relationships)
    |--------------------------------------------------------------------------
    */
    // region --- 관계 (Relationships) ---

    /**
     * 사용자
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 상품
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 쿼리 스코프 (Query Scopes)
    |--------------------------------------------------------------------------
    */
    // region --- 쿼리 스코프 (Query Scopes) ---

    /**
     * 특정 사용자의 장바구니
     */
    public function scopeForUser(Builder $query, ?int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 특정 세션의 장바구니
     */
    public function scopeForSession(Builder $query, string $sessionId): Builder
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * 최신 순으로 정렬
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 접근자 & 변경자 (Accessors & Mutators)
    |--------------------------------------------------------------------------
    */
    // region --- 접근자 & 변경자 (Accessors & Mutators) ---

    /**
     * 상품 이미지 URL 반환
     */
    public function getProductImageUrlAttribute(): ?string
    {
        return $this->product_image ? asset('storage/' . $this->product_image) : null;
    }

    /**
     * 포맷된 단가
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return '₩' . number_format($this->unit_price);
    }

    /**
     * 포맷된 총 가격
     */
    public function getFormattedTotalPriceAttribute(): string
    {
        return '₩' . number_format($this->total_price);
    }

    /**
     * 옵션 문자열 반환
     */
    public function getOptionsStringAttribute(): ?string
    {
        if (!$this->product_options) {
            return null;
        }

        return collect($this->product_options)
            ->map(fn ($value, $key) => "{$key}: {$value}")
            ->implode(', ');
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 공개 메서드 (Public Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 공개 메서드 (Public Methods) ---

    /**
     * 수량 업데이트
     */
    public function updateQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
        $this->total_price = $this->unit_price * $quantity;
        $this->save();
    }

    /**
     * 총 가격 재계산
     */
    public function recalculateTotal(): void
    {
        $this->total_price = $this->unit_price * $this->quantity;
        $this->save();
    }

    /**
     * 상품이 여전히 유효한지 확인
     */
    public function isValid(): bool
    {
        $product = $this->product;
        
        if (!$product || !$product->is_purchasable) {
            return false;
        }

        // 재고 확인
        if (!$product->is_in_stock) {
            return false;
        }

        // 옵션 유효성 확인
        if ($this->product_options) {
            foreach ($this->product_options as $optionName => $optionValue) {
                $option = $product->options()
                    ->where('name', $optionName)
                    ->where('value', $optionValue)
                    ->where('is_active', true)
                    ->first();
                
                if (!$option || !$option->hasStock($this->quantity)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 장바구니 아이템을 주문 아이템으로 변환
     */
    public function toOrderItem(): array
    {
        return [
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'product_sku' => $this->product_sku,
            'product_image' => $this->product_image,
            'product_options' => $this->product_options,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total_price' => $this->total_price,
        ];
    }

    // endregion
}