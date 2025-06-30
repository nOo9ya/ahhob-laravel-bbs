<?php

namespace App\Models\Ahhob\Shared;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'search_logs';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'keyword',
        'results_count',
        'user_id',
        'ip_address',
        'user_agent',
        'execution_time',
        'filters_used',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'results_count' => 'integer',
            'execution_time' => 'float',
            'filters_used' => 'array',
        ];
    }

    /**
     * 검색한 사용자
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 쿼리 스코프: 특정 사용자의 검색 로그
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 쿼리 스코프: 특정 키워드의 검색 로그
     */
    public function scopeByKeyword($query, string $keyword)
    {
        return $query->where('keyword', $keyword);
    }

    /**
     * 쿼리 스코프: 특정 기간의 검색 로그
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * 쿼리 스코프: 오늘의 검색 로그
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * 쿼리 스코프: 이번 달 검색 로그
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    /**
     * 인기 검색어 통계
     */
    public static function getPopularKeywords(int $limit = 10, int $days = 30): \Illuminate\Support\Collection
    {
        return static::where('created_at', '>=', now()->subDays($days))
            ->select('keyword')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('AVG(results_count) as avg_results')
            ->groupBy('keyword')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    /**
     * 검색 통계
     */
    public static function getSearchStats(int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        $totalSearches = static::where('created_at', '>=', $startDate)->count();
        $uniqueKeywords = static::where('created_at', '>=', $startDate)
            ->distinct('keyword')
            ->count('keyword');
        $avgResultsCount = static::where('created_at', '>=', $startDate)
            ->avg('results_count');
        $avgExecutionTime = static::where('created_at', '>=', $startDate)
            ->avg('execution_time');

        return [
            'total_searches' => $totalSearches,
            'unique_keywords' => $uniqueKeywords,
            'avg_results_count' => round($avgResultsCount, 2),
            'avg_execution_time' => round($avgExecutionTime, 4),
            'period_days' => $days,
        ];
    }
}