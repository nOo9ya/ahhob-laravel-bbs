<?php

namespace App\Models\Ahhob\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

class Wishlist extends Model
{
    use HasFactory;

    protected $table = 'shop_wishlists';

    /*
    |--------------------------------------------------------------------------
    | 모델 속성 (Attributes & Properties)
    |--------------------------------------------------------------------------
    */
    // region --- 모델 속성 (Attributes & Properties) ---

    protected $fillable = [
        'user_id',
        'product_id',
        'notes',
        'priority',
        'notify_price_drop',
        'notify_back_in_stock',
    ];

    protected $casts = [
        'priority' => 'integer',
        'notify_price_drop' => 'boolean',
        'notify_back_in_stock' => 'boolean',
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
     * 특정 사용자의 위시리스트
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 우선순위별 정렬
     */
    public function scopeOrderByPriority(Builder $query): Builder
    {
        return $query->orderBy('priority', 'desc')->orderBy('created_at', 'desc');
    }

    /**
     * 가격 하락 알림을 원하는 항목들
     */
    public function scopeWantsPriceDropNotification(Builder $query): Builder
    {
        return $query->where('notify_price_drop', true);
    }

    /**
     * 재입고 알림을 원하는 항목들
     */
    public function scopeWantsBackInStockNotification(Builder $query): Builder
    {
        return $query->where('notify_back_in_stock', true);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 접근자 & 변경자 (Accessors & Mutators)
    |--------------------------------------------------------------------------
    */
    // region --- 접근자 & 변경자 (Accessors & Mutators) ---

    /**
     * 우선순위 레이블
     */
    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            2 => '높음',
            1 => '보통',
            0 => '낮음',
            default => '알 수 없음',
        };
    }

    /**
     * 우선순위 색상 (UI용)
     */
    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            2 => 'red',
            1 => 'yellow',
            0 => 'green',
            default => 'gray',
        };
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 공개 메서드 (Public Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 공개 메서드 (Public Methods) ---

    /**
     * 장바구니에 추가
     */
    public function moveToCart(int $quantity = 1): ?Cart
    {
        // 상품이 구매 가능한지 확인
        if (!$this->product->is_purchasable) {
            return null;
        }

        // 장바구니에 추가
        $cartItem = Cart::create([
            'user_id' => $this->user_id,
            'product_id' => $this->product_id,
            'quantity' => $quantity,
            'unit_price' => $this->product->price,
            'total_price' => $this->product->price * $quantity,
            'product_name' => $this->product->name,
            'product_image' => $this->product->featured_image,
            'product_sku' => $this->product->sku,
        ]);

        // 위시리스트에서 제거 (선택사항)
        // $this->delete();

        return $cartItem;
    }

    /**
     * 가격 변동 확인 (간단한 현재 가격만 확인)
     */
    public function getCurrentPrice(): float
    {
        return $this->product->price;
    }

    /**
     * 재입고 확인
     */
    public function checkBackInStock(): bool
    {
        return $this->product->is_in_stock;
    }

    // endregion
}