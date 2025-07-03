<?php

namespace App\Models\Ahhob\Shop;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductView extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | 모델 속성 (Attributes & Properties)
    |--------------------------------------------------------------------------
    */
    // region --- 모델 속성 (Attributes & Properties) ---

    protected $table = 'product_views';

    protected $fillable = [
        'user_id',
        'product_id',
        'ip_address',
        'user_agent',
        'session_id',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
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

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 쿼리 스코프 (Query Scopes)
    |--------------------------------------------------------------------------
    */
    // region --- 쿼리 스코프 (Query Scopes) ---

    /**
     * 특정 기간 내 조회 기록
     */
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('viewed_at', [$startDate, $endDate]);
    }

    /**
     * 특정 사용자의 조회 기록
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 특정 상품의 조회 기록
     */
    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * 오늘 조회 기록
     */
    public function scopeToday($query)
    {
        return $query->whereDate('viewed_at', today());
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 공개 메서드 (Public Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 공개 메서드 (Public Methods) ---

    /**
     * 상품 조회 기록 추가
     */
    public static function recordView(int $productId, ?int $userId = null, ?string $ipAddress = null, ?string $userAgent = null, ?string $sessionId = null): self
    {
        return self::create([
            'product_id' => $productId,
            'user_id' => $userId,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
            'session_id' => $sessionId ?? session()->getId(),
            'viewed_at' => now(),
        ]);
    }

    /**
     * 특정 상품의 조회수 계산
     */
    public static function getProductViewCount(int $productId, ?string $period = null): int
    {
        $query = self::where('product_id', $productId);

        if ($period) {
            match ($period) {
                'today' => $query->today(),
                'week' => $query->where('viewed_at', '>=', now()->subWeek()),
                'month' => $query->where('viewed_at', '>=', now()->subMonth()),
                default => null,
            };
        }

        return $query->count();
    }

    /**
     * 사용자별 최근 조회한 상품 목록
     */
    public static function getRecentViewedProducts(int $userId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return self::with('product')
            ->where('user_id', $userId)
            ->orderBy('viewed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    // endregion
}