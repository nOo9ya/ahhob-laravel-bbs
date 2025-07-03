<?php

namespace App\Services\Ahhob\Shared;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class QueryOptimizationService
{
    /*
    |--------------------------------------------------------------------------
    | 동적 게시판 쿼리 최적화 (Dynamic Board Query Optimization)
    |--------------------------------------------------------------------------
    */

    /**
     * 최적화된 게시글 목록 조회
     */
    public static function getOptimizedPostList(
        string $boardSlug,
        int $page = 1,
        int $perPage = 20,
        array $filters = []
    ): LengthAwarePaginator {
        $tableName = "board_{$boardSlug}";
        
        // 테이블 존재 확인
        if (!DB::getSchemaBuilder()->hasTable($tableName)) {
            return new LengthAwarePaginator([], 0, $perPage, $page);
        }

        $cacheKey = "posts.{$boardSlug}.page.{$page}." . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 300, function () use ($tableName, $page, $perPage, $filters) {
            $query = DB::table($tableName)
                ->select([
                    'id',
                    'title',
                    'content_preview',
                    'user_id',
                    'view_count',
                    'like_count',
                    'comment_count',
                    'is_notice',
                    'is_featured',
                    'status',
                    'created_at',
                    'updated_at'
                ])
                ->where('status', 'published');

            // 필터 적용
            self::applyPostFilters($query, $filters);

            // 정렬 (공지사항 우선)
            $query->orderByDesc('is_notice')
                  ->orderByDesc('is_featured')
                  ->orderByDesc('created_at');

            return $query->paginate($perPage, ['*'], 'page', $page);
        });
    }

    /**
     * 최적화된 게시글 상세 조회
     */
    public static function getOptimizedPostDetail(string $boardSlug, int $postId): ?object
    {
        $tableName = "board_{$boardSlug}";
        
        if (!DB::getSchemaBuilder()->hasTable($tableName)) {
            return null;
        }

        $cacheKey = "post.{$boardSlug}.{$postId}";
        
        return Cache::remember($cacheKey, 1800, function () use ($tableName, $postId) {
            return DB::table($tableName)
                ->leftJoin('users', "{$tableName}.user_id", '=', 'users.id')
                ->select([
                    "{$tableName}.*",
                    'users.name as user_name',
                    'users.level as user_level'
                ])
                ->where("{$tableName}.id", $postId)
                ->where("{$tableName}.status", 'published')
                ->first();
        });
    }

    /**
     * 최적화된 댓글 조회 (계층형)
     */
    public static function getOptimizedComments(
        string $boardSlug,
        int $postId,
        int $page = 1,
        int $perPage = 50
    ): LengthAwarePaginator {
        $commentTable = "board_{$boardSlug}_comments";
        
        if (!DB::getSchemaBuilder()->hasTable($commentTable)) {
            return new LengthAwarePaginator([], 0, $perPage, $page);
        }

        $cacheKey = "comments.{$boardSlug}.{$postId}.page.{$page}";
        
        return Cache::remember($cacheKey, 600, function () use ($commentTable, $postId, $page, $perPage) {
            return DB::table($commentTable)
                ->leftJoin('users', "{$commentTable}.user_id", '=', 'users.id')
                ->select([
                    "{$commentTable}.*",
                    'users.name as user_name',
                    'users.level as user_level'
                ])
                ->where("{$commentTable}.post_id", $postId)
                ->where("{$commentTable}.status", 'published')
                ->orderBy("{$commentTable}.parent_id")
                ->orderBy("{$commentTable}.created_at")
                ->paginate($perPage, ['*'], 'page', $page);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | 쇼핑몰 쿼리 최적화 (Shop Query Optimization)
    |--------------------------------------------------------------------------
    */

    /**
     * 최적화된 상품 목록 조회
     */
    public static function getOptimizedProductList(
        int $page = 1,
        int $perPage = 20,
        array $filters = []
    ): LengthAwarePaginator {
        $cacheKey = "products.page.{$page}." . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 900, function () use ($page, $perPage, $filters) {
            $query = DB::table('shop_products')
                ->leftJoin('shop_categories', 'shop_products.category_id', '=', 'shop_categories.id')
                ->select([
                    'shop_products.id',
                    'shop_products.name',
                    'shop_products.slug',
                    'shop_products.price',
                    'shop_products.compare_price',
                    'shop_products.average_rating',
                    'shop_products.reviews_count',
                    'shop_products.sales_count',
                    'shop_products.stock_status',
                    'shop_products.is_featured',
                    'shop_products.created_at',
                    'shop_categories.name as category_name'
                ])
                ->where('shop_products.status', 'active');

            // 필터 적용
            self::applyProductFilters($query, $filters);

            // 정렬
            $sortBy = $filters['sort'] ?? 'created_at';
            $sortOrder = $filters['order'] ?? 'desc';
            
            // 추천 상품 우선 정렬
            $query->orderByDesc('shop_products.is_featured')
                  ->orderBy("shop_products.{$sortBy}", $sortOrder);

            return $query->paginate($perPage, ['*'], 'page', $page);
        });
    }

    /**
     * 최적화된 상품 상세 조회
     */
    public static function getOptimizedProductDetail(int $productId): ?object
    {
        $cacheKey = "product.detail.{$productId}";
        
        return Cache::remember($cacheKey, 1800, function () use ($productId) {
            return DB::table('shop_products')
                ->leftJoin('shop_categories', 'shop_products.category_id', '=', 'shop_categories.id')
                ->select([
                    'shop_products.*',
                    'shop_categories.name as category_name',
                    'shop_categories.slug as category_slug'
                ])
                ->where('shop_products.id', $productId)
                ->where('shop_products.status', 'active')
                ->first();
        });
    }

    /**
     * 최적화된 주문 목록 조회
     */
    public static function getOptimizedOrderList(
        int $page = 1,
        int $perPage = 20,
        array $filters = []
    ): LengthAwarePaginator {
        $cacheKey = "orders.page.{$page}." . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 300, function () use ($page, $perPage, $filters) {
            $query = DB::table('shop_orders')
                ->leftJoin('users', 'shop_orders.user_id', '=', 'users.id')
                ->select([
                    'shop_orders.id',
                    'shop_orders.order_number',
                    'shop_orders.customer_name',
                    'shop_orders.customer_email',
                    'shop_orders.total_amount',
                    'shop_orders.status',
                    'shop_orders.payment_status',
                    'shop_orders.shipping_status',
                    'shop_orders.created_at',
                    'users.name as user_name'
                ]);

            // 필터 적용
            self::applyOrderFilters($query, $filters);

            // 정렬
            $query->orderByDesc('shop_orders.created_at');

            return $query->paginate($perPage, ['*'], 'page', $page);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | 사용자 통계 쿼리 최적화 (User Statistics Optimization)
    |--------------------------------------------------------------------------
    */

    /**
     * 사용자별 활동 통계 (최적화된 집계)
     */
    public static function getUserActivityStats(int $userId): array
    {
        $cacheKey = "user.activity.stats.{$userId}";
        
        return Cache::remember($cacheKey, 900, function () use ($userId) {
            $stats = [
                'total_posts' => 0,
                'total_comments' => 0,
                'total_likes_received' => 0,
                'recent_activities' => []
            ];

            // 활성 게시판 목록 조회
            $boards = CacheService::getBoardList();
            
            foreach ($boards as $board) {
                $postTable = "board_{$board->slug}";
                $commentTable = "board_{$board->slug}_comments";

                // 게시글 수 집계
                if (DB::getSchemaBuilder()->hasTable($postTable)) {
                    $postCount = DB::table($postTable)
                        ->where('user_id', $userId)
                        ->where('status', 'published')
                        ->count();
                    
                    $stats['total_posts'] += $postCount;

                    // 받은 좋아요 수 집계
                    $likesReceived = DB::table($postTable)
                        ->where('user_id', $userId)
                        ->where('status', 'published')
                        ->sum('like_count');
                    
                    $stats['total_likes_received'] += $likesReceived;
                }

                // 댓글 수 집계
                if (DB::getSchemaBuilder()->hasTable($commentTable)) {
                    $commentCount = DB::table($commentTable)
                        ->where('user_id', $userId)
                        ->where('status', 'published')
                        ->count();
                    
                    $stats['total_comments'] += $commentCount;
                }
            }

            return $stats;
        });
    }

    /**
     * 게시판별 인기 게시글 (최적화된 조회)
     */
    public static function getPopularPosts(string $boardSlug, int $limit = 10): array
    {
        $tableName = "board_{$boardSlug}";
        
        if (!DB::getSchemaBuilder()->hasTable($tableName)) {
            return [];
        }

        $cacheKey = "popular.posts.{$boardSlug}.{$limit}";
        
        return Cache::remember($cacheKey, 1800, function () use ($tableName, $limit) {
            return DB::table($tableName)
                ->select(['id', 'title', 'view_count', 'like_count', 'comment_count', 'created_at'])
                ->where('status', 'published')
                ->where('created_at', '>=', now()->subDays(7)) // 최근 7일
                ->orderByRaw('(view_count + like_count * 2 + comment_count * 3) DESC') // 가중치 적용
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | 대시보드 통계 쿼리 최적화 (Dashboard Statistics Optimization)
    |--------------------------------------------------------------------------
    */

    /**
     * 최적화된 대시보드 통계 조회
     */
    public static function getOptimizedDashboardStats(): array
    {
        return Cache::remember('dashboard.optimized.stats', 600, function () {
            return [
                'users' => self::getUserStats(),
                'community' => self::getCommunityStats(),
                'shop' => self::getShopStats(),
                'system' => self::getSystemStats()
            ];
        });
    }

    /**
     * 사용자 통계 (최적화)
     */
    private static function getUserStats(): array
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();
        
        return [
            'total' => DB::table('users')->count(),
            'today' => DB::table('users')->where('created_at', '>=', $today)->count(),
            'this_month' => DB::table('users')->where('created_at', '>=', $thisMonth)->count(),
            'active_today' => DB::table('users')->where('last_login_at', '>=', $today)->count()
        ];
    }

    /**
     * 커뮤니티 통계 (최적화)
     */
    private static function getCommunityStats(): array
    {
        $stats = [
            'total_posts' => 0,
            'total_comments' => 0,
            'today_posts' => 0,
            'today_comments' => 0
        ];

        $boards = CacheService::getBoardList();
        $today = now()->startOfDay();
        
        foreach ($boards as $board) {
            $postTable = "board_{$board->slug}";
            $commentTable = "board_{$board->slug}_comments";

            if (DB::getSchemaBuilder()->hasTable($postTable)) {
                $stats['total_posts'] += DB::table($postTable)->count();
                $stats['today_posts'] += DB::table($postTable)
                    ->where('created_at', '>=', $today)
                    ->count();

                if (DB::getSchemaBuilder()->hasTable($commentTable)) {
                    $stats['total_comments'] += DB::table($commentTable)->count();
                    $stats['today_comments'] += DB::table($commentTable)
                        ->where('created_at', '>=', $today)
                        ->count();
                }
            }
        }

        return $stats;
    }

    /**
     * 쇼핑몰 통계 (최적화)
     */
    private static function getShopStats(): array
    {
        $today = now()->startOfDay();
        
        return [
            'total_products' => DB::table('shop_products')->where('status', 'active')->count(),
            'total_orders' => DB::table('shop_orders')->count(),
            'today_orders' => DB::table('shop_orders')->where('created_at', '>=', $today)->count(),
            'total_revenue' => DB::table('shop_orders')
                ->where('payment_status', 'paid')
                ->sum('total_amount'),
            'today_revenue' => DB::table('shop_orders')
                ->where('payment_status', 'paid')
                ->where('created_at', '>=', $today)
                ->sum('total_amount')
        ];
    }

    /**
     * 시스템 통계 (최적화)
     */
    private static function getSystemStats(): array
    {
        return [
            'cache_stats' => CacheService::getCacheStatistics(),
            'online_users' => CacheService::getOnlineUsersCount(),
            'system_load' => sys_getloadavg()[0] ?? 0
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | 필터 적용 헬퍼 메서드 (Filter Helper Methods)
    |--------------------------------------------------------------------------
    */

    /**
     * 게시글 필터 적용
     */
    private static function applyPostFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
    }

    /**
     * 상품 필터 적용
     */
    private static function applyProductFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['category_id'])) {
            $query->where('shop_products.category_id', $filters['category_id']);
        }

        if (!empty($filters['price_min'])) {
            $query->where('shop_products.price', '>=', $filters['price_min']);
        }

        if (!empty($filters['price_max'])) {
            $query->where('shop_products.price', '<=', $filters['price_max']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('shop_products.name', 'like', "%{$search}%")
                  ->orWhere('shop_products.description', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['stock_status'])) {
            $query->where('shop_products.stock_status', $filters['stock_status']);
        }

        if (isset($filters['is_featured']) && $filters['is_featured']) {
            $query->where('shop_products.is_featured', true);
        }
    }

    /**
     * 주문 필터 적용
     */
    private static function applyOrderFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['status'])) {
            $query->where('shop_orders.status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('shop_orders.payment_status', $filters['payment_status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('shop_orders.created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('shop_orders.created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('shop_orders.order_number', 'like', "%{$search}%")
                  ->orWhere('shop_orders.customer_name', 'like', "%{$search}%")
                  ->orWhere('shop_orders.customer_email', 'like', "%{$search}%");
            });
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 캐시 무효화 헬퍼 (Cache Invalidation Helpers)
    |--------------------------------------------------------------------------
    */

    /**
     * 게시글 관련 캐시 무효화
     */
    public static function invalidatePostCaches(string $boardSlug, int $postId = null): void
    {
        // 목록 캐시 무효화
        CacheService::forgetByPattern("posts.{$boardSlug}.page.*");
        
        // 상세 캐시 무효화
        if ($postId) {
            Cache::forget("post.{$boardSlug}.{$postId}");
            CacheService::forgetByPattern("comments.{$boardSlug}.{$postId}.*");
        }
        
        // 게시판 통계 무효화
        CacheService::invalidateBoardCache($boardSlug);
        
        // 대시보드 통계 무효화
        Cache::forget('dashboard.optimized.stats');
    }

    /**
     * 상품 관련 캐시 무효화
     */
    public static function invalidateProductCaches(int $productId = null): void
    {
        // 목록 캐시 무효화
        CacheService::forgetByPattern('products.page.*');
        
        // 상세 캐시 무효화
        if ($productId) {
            Cache::forget("product.detail.{$productId}");
        }
        
        // 추천 상품 캐시 무효화
        Cache::forget(CacheService::FEATURED_PRODUCTS);
        
        // 대시보드 통계 무효화
        Cache::forget('dashboard.optimized.stats');
    }

    /**
     * 사용자 관련 캐시 무효화
     */
    public static function invalidateUserCaches(int $userId): void
    {
        Cache::forget("user.activity.stats.{$userId}");
        CacheService::invalidateUserCache($userId);
        
        // 대시보드 통계 무효화
        Cache::forget('dashboard.optimized.stats');
    }

    /*
    |--------------------------------------------------------------------------
    | 성능 모니터링 (Performance Monitoring)
    |--------------------------------------------------------------------------
    */

    /**
     * 느린 쿼리 로깅
     */
    public static function enableSlowQueryLogging(): void
    {
        DB::listen(function ($query) {
            if ($query->time > 1000) { // 1초 이상인 쿼리
                \Log::warning('Slow Query Detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time . 'ms'
                ]);
            }
        });
    }

    /**
     * 쿼리 실행 계획 분석
     */
    public static function explainQuery(string $sql, array $bindings = []): array
    {
        try {
            $explainSql = "EXPLAIN " . $sql;
            return DB::select($explainSql, $bindings);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}