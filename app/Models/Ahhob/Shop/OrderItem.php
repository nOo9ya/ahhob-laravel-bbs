<?php

namespace App\Models\Ahhob\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

class OrderItem extends Model
{
    use HasFactory;

    protected $table = 'shop_order_items';

    /*
    |--------------------------------------------------------------------------
    | 모델 속성 (Attributes & Properties)
    |--------------------------------------------------------------------------
    */
    // region --- 모델 속성 (Attributes & Properties) ---

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_sku',
        'product_image',
        'product_options',
        'quantity',
        'unit_price',
        'total_price',
        'status',
        'review_submitted',
        'review_deadline',
    ];

    protected $casts = [
        'product_options' => 'array',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'review_submitted' => 'boolean',
        'review_deadline' => 'datetime',
    ];

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 관계 (Relationships)
    |--------------------------------------------------------------------------
    */
    // region --- 관계 (Relationships) ---

    /**
     * 주문
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * 상품
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 리뷰
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 쿼리 스코프 (Query Scopes)
    |--------------------------------------------------------------------------
    */
    // region --- 쿼리 스코프 (Query Scopes) ---

    /**
     * 특정 상태의 아이템만 조회
     */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * 리뷰 가능한 아이템들
     */
    public function scopeReviewable(Builder $query): Builder
    {
        return $query->where('status', 'delivered')
                    ->where('review_submitted', false)
                    ->where('review_deadline', '>', now());
    }

    /**
     * 리뷰 기한이 지난 아이템들
     */
    public function scopeReviewExpired(Builder $query): Builder
    {
        return $query->where('review_submitted', false)
                    ->where('review_deadline', '<', now());
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
     * 상태 레이블
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => '처리 대기',
            'processing' => '처리 중',
            'shipped' => '배송 중',
            'delivered' => '배송 완료',
            'cancelled' => '취소',
            'returned' => '반품',
            'exchanged' => '교환',
            default => '알 수 없음',
        };
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

    /**
     * 리뷰 작성 가능 여부
     */
    public function getCanReviewAttribute(): bool
    {
        return $this->status === 'delivered'
            && !$this->review_submitted
            && $this->review_deadline > now();
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 공개 메서드 (Public Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 공개 메서드 (Public Methods) ---

    /**
     * 상태 업데이트
     */
    public function updateStatus(string $status): void
    {
        $this->status = $status;
        
        // 배송 완료 시 리뷰 기한 설정 (30일)
        if ($status === 'delivered' && !$this->review_deadline) {
            $this->review_deadline = now()->addDays(30);
        }
        
        $this->save();
    }

    /**
     * 리뷰 제출 처리
     */
    public function markReviewSubmitted(): void
    {
        $this->review_submitted = true;
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
     * 반품/교환 가능 여부 확인
     */
    public function canBeReturned(): bool
    {
        return $this->status === 'delivered'
            && $this->created_at->diffInDays(now()) <= 7; // 7일 내 반품 가능
    }

    /**
     * 취소 가능 여부 확인
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    // endregion
}