<?php

namespace App\Services\Ahhob\Admin\Dashboard;

use App\Models\User;
use App\Models\Ahhob\Shop\Product;
use App\Models\Ahhob\Shop\Order;
use App\Models\Ahhob\Shop\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardStatisticsService
{
    /*
    |--------------------------------------------------------------------------
    | 트렌드 분석 (Trend Analysis)
    |--------------------------------------------------------------------------
    */

    /**
     * 기간별 트렌드 데이터 조회
     */
    public function getTrendData(string $type, int $days = 30): array
    {
        $cacheKey = "dashboard.trend.{$type}.{$days}";
        
        return Cache::remember($cacheKey, 1800, function () use ($type, $days) {
            $dates = $this->generateDateRange($days);
            
            return match($type) {
                'overview' => $this->getOverviewTrend($dates),
                'community' => $this->getCommunityTrend($dates),
                'shop' => $this->getShopTrend($dates),
                'users' => $this->getUsersTrend($dates),
                default => $this->getOverviewTrend($dates),
            };
        });
    }

    /**
     * 전체 현황 트렌드
     */
    private function getOverviewTrend(array $dates): array
    {
        return [
            'labels' => array_map(fn($date) => Carbon::parse($date)->format('m/d'), $dates),
            'datasets' => [
                [
                    'label' => '신규 회원',
                    'data' => $this->getUserRegistrationTrend($dates),
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.4,
                ],
                [
                    'label' => '주문 수',
                    'data' => $this->getOrderCountTrend($dates),
                    'borderColor' => 'rgb(16, 185, 129)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'tension' => 0.4,
                ],
                [
                    'label' => '게시글 수',
                    'data' => $this->getPostCountTrend($dates),
                    'borderColor' => 'rgb(245, 158, 11)',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'tension' => 0.4,
                ],
            ],
        ];
    }

    /**
     * 커뮤니티 트렌드
     */
    private function getCommunityTrend(array $dates): array
    {
        return [
            'labels' => array_map(fn($date) => Carbon::parse($date)->format('m/d'), $dates),
            'datasets' => [
                [
                    'label' => '게시글',
                    'data' => $this->getPostCountTrend($dates),
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
                [
                    'label' => '댓글',
                    'data' => $this->getCommentCountTrend($dates),
                    'borderColor' => 'rgb(16, 185, 129)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
                [
                    'label' => '좋아요',
                    'data' => $this->getLikeCountTrend($dates),
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                ],
            ],
        ];
    }

    /**
     * 쇼핑몰 트렌드
     */
    private function getShopTrend(array $dates): array
    {
        return [
            'labels' => array_map(fn($date) => Carbon::parse($date)->format('m/d'), $dates),
            'datasets' => [
                [
                    'label' => '매출 (만원)',
                    'data' => array_map(fn($amount) => round($amount / 10000, 1), $this->getRevenueTrend($dates)),
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => '주문 수',
                    'data' => $this->getOrderCountTrend($dates),
                    'borderColor' => 'rgb(16, 185, 129)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'yAxisID' => 'y1',
                ],
            ],
            'options' => [
                'scales' => [
                    'y' => [
                        'type' => 'linear',
                        'display' => true,
                        'position' => 'left',
                    ],
                    'y1' => [
                        'type' => 'linear',
                        'display' => true,
                        'position' => 'right',
                        'grid' => [
                            'drawOnChartArea' => false,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * 사용자 트렌드
     */
    private function getUsersTrend(array $dates): array
    {
        return [
            'labels' => array_map(fn($date) => Carbon::parse($date)->format('m/d'), $dates),
            'datasets' => [
                [
                    'label' => '신규 가입',
                    'data' => $this->getUserRegistrationTrend($dates),
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
                [
                    'label' => '활성 사용자',
                    'data' => $this->getActiveUserTrend($dates),
                    'borderColor' => 'rgb(16, 185, 129)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | 개별 지표 트렌드 (Individual Metrics Trends)
    |--------------------------------------------------------------------------
    */

    /**
     * 사용자 가입 트렌드
     */
    private function getUserRegistrationTrend(array $dates): array
    {
        return array_map(function ($date) {
            return User::whereDate('created_at', $date)->count();
        }, $dates);
    }

    /**
     * 활성 사용자 트렌드
     */
    private function getActiveUserTrend(array $dates): array
    {
        return array_map(function ($date) {
            // 해당 날짜에 활동한 사용자 수 (로그인, 게시글 작성, 댓글 등)
            $activeUsers = User::where(function ($query) use ($date) {
                $query->whereHas('loginHistories', function ($q) use ($date) {
                    $q->whereDate('logged_in_at', $date);
                });
            })->count();
            
            return $activeUsers;
        }, $dates);
    }

    /**
     * 게시글 수 트렌드
     */
    private function getPostCountTrend(array $dates): array
    {
        return array_map(function ($date) {
            $count = 0;
            $boards = DB::table('boards')->where('is_active', true)->get();
            
            foreach ($boards as $board) {
                $tableName = "board_{$board->slug}";
                if (DB::getSchemaBuilder()->hasTable($tableName)) {
                    $count += DB::table($tableName)->whereDate('created_at', $date)->count();
                }
            }
            
            return $count;
        }, $dates);
    }

    /**
     * 댓글 수 트렌드
     */
    private function getCommentCountTrend(array $dates): array
    {
        return array_map(function ($date) {
            $count = 0;
            $boards = DB::table('boards')->where('is_active', true)->get();
            
            foreach ($boards as $board) {
                $tableName = "board_{$board->slug}_comments";
                if (DB::getSchemaBuilder()->hasTable($tableName)) {
                    $count += DB::table($tableName)->whereDate('created_at', $date)->count();
                }
            }
            
            return $count;
        }, $dates);
    }

    /**
     * 좋아요 수 트렌드
     */
    private function getLikeCountTrend(array $dates): array
    {
        return array_map(function ($date) {
            return DB::table('post_likes')->whereDate('created_at', $date)->count();
        }, $dates);
    }

    /**
     * 주문 수 트렌드
     */
    private function getOrderCountTrend(array $dates): array
    {
        return array_map(function ($date) {
            return Order::whereDate('created_at', $date)->count();
        }, $dates);
    }

    /**
     * 매출 트렌드
     */
    private function getRevenueTrend(array $dates): array
    {
        return array_map(function ($date) {
            return (float) Order::where('payment_status', 'paid')
                ->whereDate('created_at', $date)
                ->sum('total_amount');
        }, $dates);
    }

    /*
    |--------------------------------------------------------------------------
    | 상세 분석 (Detailed Analysis)
    |--------------------------------------------------------------------------
    */

    /**
     * 게시판별 활동 통계
     */
    public function getBoardActivityStats(): array
    {
        $cacheKey = 'dashboard.board.activity.stats';
        
        return Cache::remember($cacheKey, 1800, function () {
            $boards = DB::table('boards')->where('is_active', true)->get();
            $stats = [];
            
            foreach ($boards as $board) {
                $postTable = "board_{$board->slug}";
                $commentTable = "board_{$board->slug}_comments";
                
                if (DB::getSchemaBuilder()->hasTable($postTable)) {
                    $postCount = DB::table($postTable)->count();
                    $commentCount = 0;
                    
                    if (DB::getSchemaBuilder()->hasTable($commentTable)) {
                        $commentCount = DB::table($commentTable)->count();
                    }
                    
                    $stats[] = [
                        'board_name' => $board->name,
                        'board_slug' => $board->slug,
                        'post_count' => $postCount,
                        'comment_count' => $commentCount,
                        'total_activity' => $postCount + $commentCount,
                    ];
                }
            }
            
            // 활동량 순으로 정렬
            usort($stats, fn($a, $b) => $b['total_activity'] <=> $a['total_activity']);
            
            return $stats;
        });
    }

    /**
     * 상위 사용자 활동 통계
     */
    public function getTopUsersStats(int $limit = 10): array
    {
        $cacheKey = "dashboard.top.users.stats.{$limit}";
        
        return Cache::remember($cacheKey, 1800, function () use ($limit) {
            // 포인트 순위 기반 상위 사용자
            $topUsers = User::orderBy('points', 'desc')
                ->limit($limit)
                ->get(['id', 'name', 'email', 'points', 'created_at']);
            
            return $topUsers->map(function ($user) {
                // 사용자별 활동 통계 계산
                $postCount = $this->getUserPostCount($user->id);
                $commentCount = $this->getUserCommentCount($user->id);
                $orderCount = Order::where('user_id', $user->id)->count();
                
                return [
                    'user' => $user,
                    'post_count' => $postCount,
                    'comment_count' => $commentCount,
                    'order_count' => $orderCount,
                    'total_activity' => $postCount + $commentCount + $orderCount,
                ];
            })->toArray();
        });
    }

    /**
     * 최근 30일 성과 비교
     */
    public function getPerformanceComparison(): array
    {
        $cacheKey = 'dashboard.performance.comparison';
        
        return Cache::remember($cacheKey, 3600, function () {
            $now = now();
            $thisMonth = $now->copy()->startOfMonth();
            $lastMonth = $now->copy()->subMonth()->startOfMonth();
            $lastMonthEnd = $thisMonth->copy()->subSecond();
            
            return [
                'users' => [
                    'this_month' => User::where('created_at', '>=', $thisMonth)->count(),
                    'last_month' => User::whereBetween('created_at', [$lastMonth, $lastMonthEnd])->count(),
                ],
                'orders' => [
                    'this_month' => Order::where('created_at', '>=', $thisMonth)->count(),
                    'last_month' => Order::whereBetween('created_at', [$lastMonth, $lastMonthEnd])->count(),
                ],
                'revenue' => [
                    'this_month' => Order::where('payment_status', 'paid')
                        ->where('created_at', '>=', $thisMonth)
                        ->sum('total_amount'),
                    'last_month' => Order::where('payment_status', 'paid')
                        ->whereBetween('created_at', [$lastMonth, $lastMonthEnd])
                        ->sum('total_amount'),
                ],
                'posts' => [
                    'this_month' => $this->getPostCountForPeriod($thisMonth, $now),
                    'last_month' => $this->getPostCountForPeriod($lastMonth, $lastMonthEnd),
                ],
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | 헬퍼 메서드 (Helper Methods)
    |--------------------------------------------------------------------------
    */

    /**
     * 날짜 범위 생성
     */
    private function generateDateRange(int $days): array
    {
        $dates = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $dates[] = now()->subDays($i)->format('Y-m-d');
        }
        return $dates;
    }

    /**
     * 사용자별 게시글 수 계산
     */
    private function getUserPostCount(int $userId): int
    {
        $count = 0;
        $boards = DB::table('boards')->where('is_active', true)->get();
        
        foreach ($boards as $board) {
            $tableName = "board_{$board->slug}";
            if (DB::getSchemaBuilder()->hasTable($tableName)) {
                $count += DB::table($tableName)->where('user_id', $userId)->count();
            }
        }
        
        return $count;
    }

    /**
     * 사용자별 댓글 수 계산
     */
    private function getUserCommentCount(int $userId): int
    {
        $count = 0;
        $boards = DB::table('boards')->where('is_active', true)->get();
        
        foreach ($boards as $board) {
            $tableName = "board_{$board->slug}_comments";
            if (DB::getSchemaBuilder()->hasTable($tableName)) {
                $count += DB::table($tableName)->where('user_id', $userId)->count();
            }
        }
        
        return $count;
    }

    /**
     * 기간별 게시글 수 계산
     */
    private function getPostCountForPeriod(Carbon $start, Carbon $end): int
    {
        $count = 0;
        $boards = DB::table('boards')->where('is_active', true)->get();
        
        foreach ($boards as $board) {
            $tableName = "board_{$board->slug}";
            if (DB::getSchemaBuilder()->hasTable($tableName)) {
                $count += DB::table($tableName)
                    ->whereBetween('created_at', [$start, $end])
                    ->count();
            }
        }
        
        return $count;
    }

    /**
     * 성장률 계산
     */
    public function calculateGrowthRate(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        
        return round((($current - $previous) / $previous) * 100, 1);
    }
}