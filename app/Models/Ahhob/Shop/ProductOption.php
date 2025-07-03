<?php

namespace App\Models\Ahhob\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ProductOption extends Model
{
    use HasFactory;

    protected $table = 'shop_product_options';

    /*
    |--------------------------------------------------------------------------
    | 모델 속성 (Attributes & Properties)
    |--------------------------------------------------------------------------
    */
    // region --- 모델 속성 (Attributes & Properties) ---

    protected $fillable = [
        'product_id',
        'name',
        'value',
        'type',
        'price_adjustment',
        'price_type',
        'stock_quantity',
        'sku_suffix',
        'sort_order',
        'is_active',
        'image',
        'description',
    ];

    protected $casts = [
        'price_adjustment' => 'decimal:2',
        'stock_quantity' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 관계 (Relationships)
    |--------------------------------------------------------------------------
    */
    // region --- 관계 (Relationships) ---

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
     * 활성화된 옵션만 조회
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * 옵션명으로 그룹화
     */
    public function scopeGroupByName(Builder $query): Builder
    {
        return $query->orderBy('name')->orderBy('sort_order');
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 접근자 & 변경자 (Accessors & Mutators)
    |--------------------------------------------------------------------------
    */
    // region --- 접근자 & 변경자 (Accessors & Mutators) ---

    /**
     * 옵션 이미지 URL 반환
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    /**
     * 최종 가격 조정값 계산
     */
    public function getFinalPriceAdjustmentAttribute(): float
    {
        if ($this->price_type === 'percentage') {
            return ($this->product->price * $this->price_adjustment) / 100;
        }

        return $this->price_adjustment;
    }

    /**
     * 옵션이 적용된 최종 가격
     */
    public function getFinalPriceAttribute(): float
    {
        return $this->product->price + $this->final_price_adjustment;
    }

    /**
     * 포맷된 가격 조정값
     */
    public function getFormattedPriceAdjustmentAttribute(): string
    {
        if ($this->price_adjustment == 0) {
            return '';
        }

        $sign = $this->price_adjustment > 0 ? '+' : '';
        $value = $this->price_type === 'percentage' 
            ? $this->price_adjustment . '%'
            : '₩' . number_format(abs($this->price_adjustment));

        return $sign . $value;
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 공개 메서드 (Public Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 공개 메서드 (Public Methods) ---

    /**
     * 재고가 있는지 확인
     */
    public function hasStock(int $quantity = 1): bool
    {
        if ($this->stock_quantity === null) {
            return $this->product->is_in_stock;
        }

        return $this->stock_quantity >= $quantity;
    }

    /**
     * 재고 감소
     */
    public function decrementStock(int $quantity): bool
    {
        if ($this->stock_quantity === null) {
            return $this->product->decrementStock($quantity);
        }

        if ($this->stock_quantity < $quantity) {
            return false;
        }

        $this->decrement('stock_quantity', $quantity);
        return true;
    }

    /**
     * 재고 증가
     */
    public function incrementStock(int $quantity): void
    {
        if ($this->stock_quantity !== null) {
            $this->increment('stock_quantity', $quantity);
        }
    }

    // endregion
}