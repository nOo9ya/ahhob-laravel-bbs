<?php

namespace App\Models\Ahhob\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PopularKeyword extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | 모델 속성 (Attributes & Properties)
    |--------------------------------------------------------------------------
    */
    // region --- 모델 속성 (Attributes & Properties) ---

    protected $table = 'popular_keywords';

    protected $fillable = [
        'keyword',
        'search_count',
        'result_count',
        'click_count',
        'trend_score',
        'category_id',
        'last_searched_at',
        'period_type',
    ];

    protected $casts = [
        'search_count' => 'integer',
        'result_count' => 'integer',
        'click_count' => 'integer',
        'trend_score' => 'float',
        'last_searched_at' => 'datetime',
    ];

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 관계 (Relationships)
    |--------------------------------------------------------------------------
    */
    // region --- 관계 (Relationships) ---

    /**
     * 카테고리 관계
     */
    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
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
     * 특정 기간 타입으로 필터링
     */
    public function scopeByPeriod($query, string $period)
    {
        return $query->where('period_type', $period);
    }

    /**
     * 특정 카테고리로 필터링
     */
    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * 검색 횟수 순 정렬
     */
    public function scopeOrderBySearchCount($query, string $direction = 'desc')
    {
        return $query->orderBy('search_count', $direction);
    }

    /**
     * 트렌드 점수 순 정렬
     */
    public function scopeOrderByTrend($query, string $direction = 'desc')
    {
        return $query->orderBy('trend_score', $direction);
    }

    /**
     * 최소 검색 횟수로 필터링
     */
    public function scopeMinSearchCount($query, int $minCount)
    {
        return $query->where('search_count', '>=', $minCount);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 접근자 & 변경자 (Accessors & Mutators)
    |--------------------------------------------------------------------------
    */
    // region --- 접근자 & 변경자 (Accessors & Mutators) ---

    /**
     * 클릭률 접근자
     */
    public function getClickThroughRateAttribute(): float
    {
        return $this->search_count > 0 ? $this->click_count / $this->search_count : 0;
    }

    /**
     * 평균 결과 수 접근자
     */
    public function getAverageResultCountAttribute(): float
    {
        return $this->search_count > 0 ? $this->result_count / $this->search_count : 0;
    }

    /**
     * 트렌드 등급 접근자
     */
    public function getTrendGradeAttribute(): string
    {
        return match (true) {
            $this->trend_score >= 0.9 => 'HOT',
            $this->trend_score >= 0.7 => 'RISING',
            $this->trend_score >= 0.5 => 'STABLE',
            $this->trend_score >= 0.3 => 'DECLINING',
            default => 'COLD',
        };
    }

    /**
     * 기간 타입 라벨 접근자
     */
    public function getPeriodTypeLabelAttribute(): string
    {
        return match ($this->period_type) {
            'daily' => '일간',
            'weekly' => '주간',
            'monthly' => '월간',
            'yearly' => '연간',
            default => '전체',
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
     * 인기 키워드 업데이트 또는 생성
     */
    public static function updateOrCreateKeyword(
        string $keyword,
        string $period = 'daily',
        ?int $categoryId = null,
        int $searchCount = 1,
        int $resultCount = 0,
        int $clickCount = 0
    ): self {
        $existing = self::where('keyword', $keyword)
            ->where('period_type', $period)
            ->where('category_id', $categoryId)
            ->first();

        if ($existing) {
            $existing->increment('search_count', $searchCount);
            $existing->increment('result_count', $resultCount);
            $existing->increment('click_count', $clickCount);
            $existing->last_searched_at = now();
            $existing->recalculateTrendScore();
            $existing->save();
            
            return $existing;
        }

        return self::create([
            'keyword' => $keyword,
            'period_type' => $period,
            'category_id' => $categoryId,
            'search_count' => $searchCount,
            'result_count' => $resultCount,
            'click_count' => $clickCount,
            'trend_score' => 0.5,
            'last_searched_at' => now(),
        ]);
    }

    /**
     * 기간별 인기 키워드 가져오기
     */
    public static function getPopularByPeriod(
        string $period = 'daily',
        ?int $categoryId = null,
        int $limit = 10
    ): \Illuminate\Database\Eloquent\Collection {
        $query = self::byPeriod($period)
            ->minSearchCount(2)
            ->orderBySearchCount();

        if ($categoryId) {
            $query->byCategory($categoryId);
        }

        return $query->limit($limit)->get();
    }

    /**
     * 트렌딩 키워드 가져오기
     */
    public static function getTrendingKeywords(
        string $period = 'daily',
        ?int $categoryId = null,
        int $limit = 10
    ): \Illuminate\Database\Eloquent\Collection {
        $query = self::byPeriod($period)
            ->where('trend_score', '>=', 0.7)
            ->orderByTrend();

        if ($categoryId) {
            $query->byCategory($categoryId);
        }

        return $query->limit($limit)->get();
    }

    /**
     * 키워드 제안 (자동완성용)
     */
    public static function getSuggestions(
        string $partialKeyword,
        ?int $categoryId = null,
        int $limit = 5
    ): \Illuminate\Database\Eloquent\Collection {
        $query = self::where('keyword', 'LIKE', $partialKeyword . '%')
            ->orderBySearchCount();

        if ($categoryId) {
            $query->byCategory($categoryId);
        }

        return $query->limit($limit)->get();
    }

    /**
     * 트렌드 점수 재계산
     */
    public function recalculateTrendScore(): self
    {
        $daysSinceLastSearch = $this->last_searched_at ? 
            $this->last_searched_at->diffInDays(now()) : 999;

        $recencyScore = match (true) {
            $daysSinceLastSearch <= 1 => 1.0,
            $daysSinceLastSearch <= 7 => 0.8,
            $daysSinceLastSearch <= 30 => 0.6,
            $daysSinceLastSearch <= 90 => 0.4,
            default => 0.2,
        };

        $frequencyScore = min(1.0, $this->search_count / 100);
        $clickScore = $this->click_through_rate;

        $this->trend_score = ($recencyScore * 0.4) + ($frequencyScore * 0.4) + ($clickScore * 0.2);
        
        return $this;
    }

    /**
     * 오래된 키워드 정리
     */
    public static function cleanupOldKeywords(int $daysToKeep = 90): int
    {
        return self::where('last_searched_at', '<', now()->subDays($daysToKeep))
            ->where('search_count', '<', 5)
            ->delete();
    }

    // endregion
}