<?php

namespace App\Models\Ahhob\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

class Review extends Model
{
    use HasFactory;

    protected $table = 'shop_reviews';

    /*
    |--------------------------------------------------------------------------
    | 모델 속성 (Attributes & Properties)
    |--------------------------------------------------------------------------
    */
    // region --- 모델 속성 (Attributes & Properties) ---

    protected $fillable = [
        'product_id',
        'user_id',
        'order_item_id',
        'title',
        'content',
        'rating',
        'images',
        'is_verified_purchase',
        'status',
        'admin_notes',
        'helpful_count',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'images' => 'array',
        'is_verified_purchase' => 'boolean',
        'helpful_count' => 'integer',
        'approved_at' => 'datetime',
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

    /**
     * 작성자
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 주문 상품
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * 승인자
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 쿼리 스코프 (Query Scopes)
    |--------------------------------------------------------------------------
    */
    // region --- 쿼리 스코프 (Query Scopes) ---

    /**
     * 승인된 리뷰만 조회
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /**
     * 특정 상태의 리뷰만 조회
     */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * 구매 인증된 리뷰만 조회
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified_purchase', true);
    }

    /**
     * 특정 평점의 리뷰만 조회
     */
    public function scopeRating(Builder $query, int $rating): Builder
    {
        return $query->where('rating', $rating);
    }

    /**
     * 평점 범위로 필터링
     */
    public function scopeRatingRange(Builder $query, int $min, int $max): Builder
    {
        return $query->whereBetween('rating', [$min, $max]);
    }

    /**
     * 최신순 정렬
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * 도움됨 순 정렬
     */
    public function scopeOrderByHelpful(Builder $query): Builder
    {
        return $query->orderBy('helpful_count', 'desc');
    }

    /**
     * 평점 순 정렬
     */
    public function scopeOrderByRating(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('rating', $direction);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 접근자 & 변경자 (Accessors & Mutators)
    |--------------------------------------------------------------------------
    */
    // region --- 접근자 & 변경자 (Accessors & Mutators) ---

    /**
     * 상태 레이블
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => '승인 대기',
            'approved' => '승인됨',
            'rejected' => '거부됨',
            default => '알 수 없음',
        };
    }

    /**
     * 리뷰 이미지 URL들 반환
     */
    public function getImageUrlsAttribute(): array
    {
        if (!$this->images) {
            return [];
        }

        return collect($this->images)
            ->map(fn ($image) => asset('storage/' . $image))
            ->toArray();
    }

    /**
     * 별점 표시용 배열
     */
    public function getStarsAttribute(): array
    {
        $stars = [];
        for ($i = 1; $i <= 5; $i++) {
            $stars[] = $i <= $this->rating;
        }
        return $stars;
    }

    /**
     * 도움됨 표시
     */
    public function getHelpfulDisplayAttribute(): string
    {
        return $this->helpful_count > 0 ? "도움됨 {$this->helpful_count}" : '';
    }

    /**
     * 마스킹된 사용자명
     */
    public function getMaskedUserNameAttribute(): string
    {
        $name = $this->user->name ?? $this->user->nickname;
        $length = mb_strlen($name);
        
        if ($length <= 2) {
            return $name[0] . '*';
        }
        
        return $name[0] . str_repeat('*', $length - 2) . $name[$length - 1];
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 공개 메서드 (Public Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 공개 메서드 (Public Methods) ---

    /**
     * 리뷰 승인
     */
    public function approve(?User $approver = null): void
    {
        $this->status = 'approved';
        $this->approved_by = $approver?->id;
        $this->approved_at = now();
        $this->save();

        // 상품의 평균 평점 업데이트
        $this->product->updateAverageRating();

        // 주문 아이템의 리뷰 제출 상태 업데이트
        if ($this->orderItem) {
            $this->orderItem->markReviewSubmitted();
        }
    }

    /**
     * 리뷰 거부
     */
    public function reject(string $reason, ?User $approver = null): void
    {
        $this->status = 'rejected';
        $this->admin_notes = $reason;
        $this->approved_by = $approver?->id;
        $this->save();
    }

    /**
     * 도움됨 증가
     */
    public function incrementHelpful(): void
    {
        $this->increment('helpful_count');
    }


    /**
     * 구매 인증 여부 확인 및 설정
     */
    public function verifyPurchase(): void
    {
        if ($this->order_item_id) {
            $this->is_verified_purchase = true;
            $this->save();
        }
    }

    /**
     * 수정 가능 여부 확인
     */
    public function canBeEdited(): bool
    {
        return $this->status === 'pending' 
            && $this->created_at->diffInHours(now()) <= 24; // 24시간 내 수정 가능
    }

    /**
     * 삭제 가능 여부 확인
     */
    public function canBeDeleted(): bool
    {
        return $this->user_id === auth()->id() 
            && $this->created_at->diffInDays(now()) <= 7; // 7일 내 삭제 가능
    }

    // endregion
}