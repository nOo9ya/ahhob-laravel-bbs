<?php

namespace App\Models\Ahhob\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAssociation extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | 모델 속성 (Attributes & Properties)
    |--------------------------------------------------------------------------
    */
    // region --- 모델 속성 (Attributes & Properties) ---

    protected $table = 'product_associations';

    protected $fillable = [
        'product_a_id',
        'product_b_id',
        'association_type',
        'confidence_score',
        'occurrence_count',
        'last_updated_at',
    ];

    protected $casts = [
        'confidence_score' => 'float',
        'occurrence_count' => 'integer',
        'last_updated_at' => 'datetime',
    ];

    public $timestamps = false;

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 관계 (Relationships)
    |--------------------------------------------------------------------------
    */
    // region --- 관계 (Relationships) ---

    /**
     * 첫 번째 상품 관계
     */
    public function productA(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_a_id');
    }

    /**
     * 두 번째 상품 관계
     */
    public function productB(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_b_id');
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 쿼리 스코프 (Query Scopes)
    |--------------------------------------------------------------------------
    */
    // region --- 쿼리 스코프 (Query Scopes) ---

    /**
     * 특정 연관 타입으로 필터링
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('association_type', $type);
    }

    /**
     * 최소 신뢰도 점수로 필터링
     */
    public function scopeMinConfidence($query, float $minScore)
    {
        return $query->where('confidence_score', '>=', $minScore);
    }

    /**
     * 특정 상품과 연관된 상품들
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where(function ($q) use ($productId) {
            $q->where('product_a_id', $productId)
                ->orWhere('product_b_id', $productId);
        });
    }

    /**
     * 신뢰도 점수 순 정렬
     */
    public function scopeOrderByConfidence($query, string $direction = 'desc')
    {
        return $query->orderBy('confidence_score', $direction);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 접근자 & 변경자 (Accessors & Mutators)
    |--------------------------------------------------------------------------
    */
    // region --- 접근자 & 변경자 (Accessors & Mutators) ---

    /**
     * 연관 타입 라벨 접근자
     */
    public function getAssociationTypeLabelAttribute(): string
    {
        return match ($this->association_type) {
            'frequently_bought_together' => '함께 구매하는 상품',
            'customers_also_viewed' => '다른 고객이 본 상품',
            'similar_products' => '유사한 상품',
            'complementary' => '보완 상품',
            'substitute' => '대체 상품',
            default => '연관 상품',
        };
    }

    /**
     * 신뢰도 백분율 접근자
     */
    public function getConfidencePercentageAttribute(): int
    {
        return (int) round($this->confidence_score * 100);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 공개 메서드 (Public Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 공개 메서드 (Public Methods) ---

    /**
     * 상품 연관 관계 업데이트 또는 생성
     */
    public static function updateOrCreateAssociation(
        int $productAId,
        int $productBId,
        string $type,
        float $confidenceScore = 0.0,
        int $occurrenceCount = 1
    ): self {
        return self::updateOrCreate(
            [
                'product_a_id' => min($productAId, $productBId),
                'product_b_id' => max($productAId, $productBId),
                'association_type' => $type,
            ],
            [
                'confidence_score' => $confidenceScore,
                'occurrence_count' => $occurrenceCount,
                'last_updated_at' => now(),
            ]
        );
    }

    /**
     * 특정 상품의 연관 상품 목록 가져오기
     */
    public static function getAssociatedProducts(
        int $productId,
        string $type = null,
        float $minConfidence = 0.1,
        int $limit = 10
    ): \Illuminate\Database\Eloquent\Collection {
        $query = self::with(['productA', 'productB'])
            ->forProduct($productId)
            ->minConfidence($minConfidence)
            ->orderByConfidence();

        if ($type) {
            $query->byType($type);
        }

        return $query->limit($limit)->get()->map(function ($association) use ($productId) {
            return $association->product_a_id === $productId
                ? $association->productB
                : $association->productA;
        });
    }

    /**
     * 연관 관계 발생 횟수 증가
     */
    public function incrementOccurrence(): self
    {
        $this->increment('occurrence_count');
        $this->update(['last_updated_at' => now()]);
        
        return $this;
    }

    /**
     * 신뢰도 점수 재계산
     */
    public function recalculateConfidence(): self
    {
        // 실제 구현에서는 머신러닝 알고리즘이나 통계적 방법 사용
        $newScore = min(1.0, $this->occurrence_count / 100);
        
        $this->update([
            'confidence_score' => $newScore,
            'last_updated_at' => now(),
        ]);

        return $this;
    }

    // endregion
}