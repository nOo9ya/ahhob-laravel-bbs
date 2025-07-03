<?php

namespace App\Models\Ahhob\Shop;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProductPreference extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | 모델 속성 (Attributes & Properties)
    |--------------------------------------------------------------------------
    */
    // region --- 모델 속성 (Attributes & Properties) ---

    protected $table = 'user_product_preferences';

    protected $fillable = [
        'user_id',
        'product_id',
        'category_id',
        'preference_type',
        'preference_score',
        'interaction_count',
        'last_interaction_at',
    ];

    protected $casts = [
        'preference_score' => 'float',
        'interaction_count' => 'integer',
        'last_interaction_at' => 'datetime',
    ];

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 관계 (Relationships)
    |--------------------------------------------------------------------------
    */
    // region --- 관계 (Relationships) ---

    /**
     * 사용자 관계
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 상품 관계
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 카테고리 관계
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 쿼리 스코프 (Query Scopes)
    |--------------------------------------------------------------------------
    */
    // region --- 쿼리 스코프 (Query Scopes) ---

    /**
     * 특정 사용자의 선호도
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 특정 선호도 타입으로 필터링
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('preference_type', $type);
    }

    /**
     * 최소 선호도 점수로 필터링
     */
    public function scopeMinScore($query, float $minScore)
    {
        return $query->where('preference_score', '>=', $minScore);
    }

    /**
     * 선호도 점수 순 정렬
     */
    public function scopeOrderByPreference($query, string $direction = 'desc')
    {
        return $query->orderBy('preference_score', $direction);
    }

    /**
     * 최근 상호작용 순 정렬
     */
    public function scopeOrderByRecentInteraction($query)
    {
        return $query->orderBy('last_interaction_at', 'desc');
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 접근자 & 변경자 (Accessors & Mutators)
    |--------------------------------------------------------------------------
    */
    // region --- 접근자 & 변경자 (Accessors & Mutators) ---

    /**
     * 선호도 타입 라벨 접근자
     */
    public function getPreferenceTypeLabelAttribute(): string
    {
        return match ($this->preference_type) {
            'view' => '조회',
            'purchase' => '구매',
            'cart' => '장바구니',
            'wishlist' => '위시리스트',
            'review' => '리뷰',
            'rating' => '평점',
            'search' => '검색',
            default => '기타',
        };
    }

    /**
     * 선호도 등급 접근자
     */
    public function getPreferenceGradeAttribute(): string
    {
        return match (true) {
            $this->preference_score >= 0.8 => 'A+',
            $this->preference_score >= 0.7 => 'A',
            $this->preference_score >= 0.6 => 'B+',
            $this->preference_score >= 0.5 => 'B',
            $this->preference_score >= 0.4 => 'C+',
            $this->preference_score >= 0.3 => 'C',
            default => 'D',
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
     * 사용자 선호도 기록 또는 업데이트
     */
    public static function recordPreference(
        int $userId,
        ?int $productId = null,
        ?int $categoryId = null,
        string $type = 'view',
        float $scoreIncrement = 0.1
    ): self {
        $key = [
            'user_id' => $userId,
            'preference_type' => $type,
        ];

        if ($productId) {
            $key['product_id'] = $productId;
        }

        if ($categoryId) {
            $key['category_id'] = $categoryId;
        }

        $preference = self::firstOrCreate($key, [
            'preference_score' => 0,
            'interaction_count' => 0,
        ]);

        $preference->increment('interaction_count');
        $preference->preference_score = min(1.0, $preference->preference_score + $scoreIncrement);
        $preference->last_interaction_at = now();
        $preference->save();

        return $preference;
    }

    /**
     * 사용자의 상위 선호 카테고리 가져오기
     */
    public static function getTopCategories(int $userId, int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return self::with('category')
            ->forUser($userId)
            ->whereNotNull('category_id')
            ->orderByPreference()
            ->limit($limit)
            ->get();
    }

    /**
     * 사용자의 선호 상품 가져오기
     */
    public static function getPreferredProducts(
        int $userId,
        string $type = null,
        float $minScore = 0.3,
        int $limit = 20
    ): \Illuminate\Database\Eloquent\Collection {
        $query = self::with('product')
            ->forUser($userId)
            ->whereNotNull('product_id')
            ->minScore($minScore)
            ->orderByPreference();

        if ($type) {
            $query->byType($type);
        }

        return $query->limit($limit)->get();
    }

    /**
     * 선호도 점수 재계산
     */
    public function recalculateScore(): self
    {
        // 상호작용 빈도와 최근성을 고려한 점수 계산
        $frequencyScore = min(1.0, $this->interaction_count / 10);
        $recencyScore = $this->calculateRecencyScore();
        
        $this->preference_score = ($frequencyScore * 0.7) + ($recencyScore * 0.3);
        $this->save();

        return $this;
    }

    /**
     * 최근성 점수 계산
     */
    private function calculateRecencyScore(): float
    {
        if (!$this->last_interaction_at) {
            return 0;
        }

        $daysSinceLastInteraction = $this->last_interaction_at->diffInDays(now());
        
        return match (true) {
            $daysSinceLastInteraction <= 1 => 1.0,
            $daysSinceLastInteraction <= 7 => 0.8,
            $daysSinceLastInteraction <= 30 => 0.6,
            $daysSinceLastInteraction <= 90 => 0.4,
            default => 0.2,
        };
    }

    // endregion
}