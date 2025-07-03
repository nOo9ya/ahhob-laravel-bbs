<?php

namespace App\Services\Ahhob\Shared;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CacheService
{
    /*
    |--------------------------------------------------------------------------
    | 캐시 키 상수 (Cache Key Constants)
    |--------------------------------------------------------------------------
    */
    
    // 게시판 관련 캐시 키
    const BOARD_LIST = 'boards.active';
    const BOARD_DETAIL = 'board.{slug}';
    const BOARD_STATS = 'board.stats.{slug}';
    
    // 사용자 관련 캐시 키
    const USER_PROFILE = 'user.profile.{id}';
    const USER_ACTIVITY = 'user.activity.{id}';
    const USER_POINTS = 'user.points.{id}';
    
    // 상품 관련 캐시 키
    const PRODUCT_LIST = 'shop.products.page.{page}';
    const PRODUCT_DETAIL = 'shop.product.{id}';
    const PRODUCT_CATEGORY = 'shop.category.{id}.products';
    const FEATURED_PRODUCTS = 'shop.products.featured';
    
    // 시스템 통계 캐시 키
    const DASHBOARD_STATS = 'admin.dashboard.stats';
    const REALTIME_STATS = 'admin.realtime.stats';
    const TREND_DATA = 'admin.trend.{type}.{days}';
    
    // 설정 관련 캐시 키
    const SYSTEM_SETTINGS = 'system.settings';
    const EMAIL_SETTINGS = 'system.email.settings';
    const CACHE_SETTINGS = 'system.cache.settings';

    /*
    |--------------------------------------------------------------------------
    | 기본 캐시 TTL 설정 (Default Cache TTL Settings)
    |--------------------------------------------------------------------------
    */
    
    const TTL_MINUTE = 60;         // 1분
    const TTL_FIVE_MINUTES = 300;  // 5분
    const TTL_FIFTEEN_MINUTES = 900; // 15분
    const TTL_THIRTY_MINUTES = 1800; // 30분
    const TTL_HOUR = 3600;         // 1시간
    const TTL_TWELVE_HOURS = 43200; // 12시간
    const TTL_DAY = 86400;         // 24시간
    const TTL_WEEK = 604800;       // 1주일

    /*
    |--------------------------------------------------------------------------
    | 게시판 캐시 관리 (Board Cache Management)
    |--------------------------------------------------------------------------
    */

    /**
     * 활성 게시판 목록 캐시
     */
    public static function getBoardList(): array
    {
        return Cache::remember(self::BOARD_LIST, self::TTL_HOUR, function () {
            return DB::table('boards')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->toArray();
        });
    }

    /**
     * 게시판 상세 정보 캐시
     */
    public static function getBoardDetail(string $slug): ?object
    {
        $key = str_replace('{slug}', $slug, self::BOARD_DETAIL);
        
        return Cache::remember($key, self::TTL_HOUR, function () use ($slug) {
            return DB::table('boards')
                ->where('slug', $slug)
                ->where('is_active', true)
                ->first();
        });
    }

    /**
     * 게시판 통계 캐시
     */
    public static function getBoardStats(string $slug): array
    {
        $key = str_replace('{slug}', $slug, self::BOARD_STATS);
        
        return Cache::remember($key, self::TTL_FIFTEEN_MINUTES, function () use ($slug) {
            $tableName = "board_{$slug}";
            $commentTable = "{$tableName}_comments";
            
            $stats = [
                'total_posts' => 0,
                'total_comments' => 0,
                'today_posts' => 0,
                'latest_post' => null,
            ];

            if (DB::getSchemaBuilder()->hasTable($tableName)) {
                $stats['total_posts'] = DB::table($tableName)->count();
                $stats['today_posts'] = DB::table($tableName)
                    ->whereDate('created_at', today())
                    ->count();
                
                $stats['latest_post'] = DB::table($tableName)
                    ->select('id', 'title', 'created_at', 'user_id')
                    ->orderBy('created_at', 'desc')
                    ->first();

                if (DB::getSchemaBuilder()->hasTable($commentTable)) {
                    $stats['total_comments'] = DB::table($commentTable)->count();
                }
            }

            return $stats;
        });
    }

    /**
     * 게시판 캐시 무효화
     */
    public static function invalidateBoardCache(string $slug): void
    {
        Cache::forget(self::BOARD_LIST);
        Cache::forget(str_replace('{slug}', $slug, self::BOARD_DETAIL));
        Cache::forget(str_replace('{slug}', $slug, self::BOARD_STATS));
        
        // 관련 통계 캐시도 무효화
        Cache::forget(self::DASHBOARD_STATS);
        Cache::forget(self::REALTIME_STATS);
    }

    /*
    |--------------------------------------------------------------------------
    | 사용자 캐시 관리 (User Cache Management)
    |--------------------------------------------------------------------------
    */

    /**
     * 사용자 프로필 캐시
     */
    public static function getUserProfile(int $userId): ?object
    {
        $key = str_replace('{id}', $userId, self::USER_PROFILE);
        
        return Cache::remember($key, self::TTL_THIRTY_MINUTES, function () use ($userId) {
            return DB::table('users')
                ->select('id', 'name', 'email', 'level', 'points', 'created_at', 'last_login_at')
                ->where('id', $userId)
                ->first();
        });
    }

    /**
     * 사용자 활동 통계 캐시
     */
    public static function getUserActivity(int $userId): array
    {
        $key = str_replace('{id}', $userId, self::USER_ACTIVITY);
        
        return Cache::remember($key, self::TTL_FIFTEEN_MINUTES, function () use ($userId) {
            $stats = [
                'total_posts' => 0,
                'total_comments' => 0,
                'recent_activities' => [],
            ];

            // 모든 활성 게시판에서 사용자 활동 집계
            $boards = self::getBoardList();
            
            foreach ($boards as $board) {
                $tableName = "board_{$board->slug}";
                $commentTable = "{$tableName}_comments";

                if (DB::getSchemaBuilder()->hasTable($tableName)) {
                    $posts = DB::table($tableName)
                        ->where('user_id', $userId)
                        ->count();
                    
                    $stats['total_posts'] += $posts;

                    if (DB::getSchemaBuilder()->hasTable($commentTable)) {
                        $comments = DB::table($commentTable)
                            ->where('user_id', $userId)
                            ->count();
                        
                        $stats['total_comments'] += $comments;
                    }
                }
            }

            return $stats;
        });
    }

    /**
     * 사용자 캐시 무효화
     */
    public static function invalidateUserCache(int $userId): void
    {
        Cache::forget(str_replace('{id}', $userId, self::USER_PROFILE));
        Cache::forget(str_replace('{id}', $userId, self::USER_ACTIVITY));
        Cache::forget(str_replace('{id}', $userId, self::USER_POINTS));
    }

    /*
    |--------------------------------------------------------------------------
    | 상품 캐시 관리 (Product Cache Management)
    |--------------------------------------------------------------------------
    */

    /**
     * 상품 목록 캐시 (페이지별)
     */
    public static function getProductList(int $page = 1, array $filters = []): array
    {
        $cacheKey = self::generateProductListKey($page, $filters);
        
        return Cache::remember($cacheKey, self::TTL_FIFTEEN_MINUTES, function () use ($page, $filters) {
            $query = DB::table('shop_products')
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->orderBy('created_at', 'desc');

            // 필터 적용
            if (!empty($filters['category_id'])) {
                $query->where('category_id', $filters['category_id']);
            }

            if (!empty($filters['price_min'])) {
                $query->where('price', '>=', $filters['price_min']);
            }

            if (!empty($filters['price_max'])) {
                $query->where('price', '<=', $filters['price_max']);
            }

            return $query->paginate(20, ['*'], 'page', $page);
        });
    }

    /**
     * 상품 상세 정보 캐시
     */
    public static function getProductDetail(int $productId): ?object
    {
        $key = str_replace('{id}', $productId, self::PRODUCT_DETAIL);
        
        return Cache::remember($key, self::TTL_THIRTY_MINUTES, function () use ($productId) {
            return DB::table('shop_products')
                ->where('id', $productId)
                ->where('status', 'active')
                ->first();
        });
    }

    /**
     * 추천 상품 캐시
     */
    public static function getFeaturedProducts(int $limit = 10): array
    {
        return Cache::remember(self::FEATURED_PRODUCTS, self::TTL_HOUR, function () use ($limit) {
            return DB::table('shop_products')
                ->where('status', 'active')
                ->where('is_featured', true)
                ->orderBy('sort_order')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }

    /**
     * 상품 캐시 무효화
     */
    public static function invalidateProductCache(int $productId = null): void
    {
        if ($productId) {
            Cache::forget(str_replace('{id}', $productId, self::PRODUCT_DETAIL));
        }
        
        // 목록 캐시 전체 무효화 (태그 기반으로 개선 가능)
        for ($page = 1; $page <= 10; $page++) {
            Cache::forget(str_replace('{page}', $page, self::PRODUCT_LIST));
        }
        
        Cache::forget(self::FEATURED_PRODUCTS);
    }

    /*
    |--------------------------------------------------------------------------
    | 시스템 설정 캐시 (System Settings Cache)
    |--------------------------------------------------------------------------
    */

    /**
     * 시스템 설정 캐시
     */
    public static function getSystemSettings(): array
    {
        return Cache::remember(self::SYSTEM_SETTINGS, self::TTL_HOUR, function () {
            // 실제로는 settings 테이블에서 조회
            return [
                'site_name' => config('app.name', '사이트명'),
                'site_description' => '사이트 설명',
                'timezone' => config('app.timezone', 'Asia/Seoul'),
                'locale' => config('app.locale', 'ko'),
                'maintenance_mode' => false,
                'registration_enabled' => true,
            ];
        });
    }

    /**
     * 설정 캐시 무효화
     */
    public static function invalidateSettingsCache(): void
    {
        Cache::forget(self::SYSTEM_SETTINGS);
        Cache::forget(self::EMAIL_SETTINGS);
        Cache::forget(self::CACHE_SETTINGS);
    }

    /*
    |--------------------------------------------------------------------------
    | Redis 전용 캐시 관리 (Redis Specific Cache)
    |--------------------------------------------------------------------------
    */

    /**
     * 실시간 온라인 사용자 수 (Redis)
     */
    public static function getOnlineUsersCount(): int
    {
        try {
            return (int) Redis::scard('online_users');
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * 온라인 사용자 추가
     */
    public static function addOnlineUser(int $userId): void
    {
        try {
            Redis::sadd('online_users', $userId);
            Redis::expire('online_users', self::TTL_FIFTEEN_MINUTES);
        } catch (\Exception $e) {
            // Redis 오류 시 무시
        }
    }

    /**
     * 활동 제한 캐시 (Redis)
     */
    public static function getUserActivityCount(int $userId, string $date): array
    {
        $key = "activity_count:{$userId}:{$date}";
        
        try {
            $data = Redis::get($key);
            return $data ? json_decode($data, true) : [
                'post_count' => 0,
                'comment_count' => 0,
                'like_count' => 0,
            ];
        } catch (\Exception $e) {
            return ['post_count' => 0, 'comment_count' => 0, 'like_count' => 0];
        }
    }

    /**
     * 활동 카운트 증가
     */
    public static function incrementActivityCount(int $userId, string $type): void
    {
        $date = now()->format('Y-m-d');
        $key = "activity_count:{$userId}:{$date}";
        
        try {
            $data = self::getUserActivityCount($userId, $date);
            $data["{$type}_count"]++;
            
            Redis::setex($key, self::TTL_DAY, json_encode($data));
        } catch (\Exception $e) {
            // Redis 오류 시 무시
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 캐시 유틸리티 메서드 (Cache Utility Methods)
    |--------------------------------------------------------------------------
    */

    /**
     * 상품 목록 캐시 키 생성
     */
    private static function generateProductListKey(int $page, array $filters): string
    {
        $baseKey = str_replace('{page}', $page, self::PRODUCT_LIST);
        
        if (!empty($filters)) {
            $filterHash = md5(serialize($filters));
            $baseKey .= ".{$filterHash}";
        }
        
        return $baseKey;
    }

    /**
     * 캐시 키 패턴으로 일괄 삭제
     */
    public static function forgetByPattern(string $pattern): int
    {
        $deleted = 0;
        
        try {
            $keys = Redis::keys($pattern);
            if (!empty($keys)) {
                $deleted = Redis::del($keys);
            }
        } catch (\Exception $e) {
            // Redis 오류 시 Laravel 캐시에서 시도
            // 패턴 기반 삭제는 Laravel 캐시에서 직접 지원하지 않음
        }
        
        return $deleted;
    }

    /**
     * 캐시 통계 조회
     */
    public static function getCacheStatistics(): array
    {
        $stats = [
            'redis_memory' => 0,
            'redis_keys' => 0,
            'file_cache_size' => 0,
        ];

        try {
            $info = Redis::info('memory');
            $stats['redis_memory'] = $info['used_memory'] ?? 0;
            
            $stats['redis_keys'] = Redis::dbsize();
        } catch (\Exception $e) {
            // Redis 오류 시 기본값 유지
        }

        // 파일 캐시 크기 계산
        $cachePath = storage_path('framework/cache');
        if (is_dir($cachePath)) {
            $stats['file_cache_size'] = self::calculateDirectorySize($cachePath);
        }

        return $stats;
    }

    /**
     * 디렉토리 크기 계산
     */
    private static function calculateDirectorySize(string $path): int
    {
        $size = 0;
        
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        } catch (\Exception $e) {
            // 오류 시 0 반환
        }

        return $size;
    }

    /**
     * 캐시 워밍업 (자주 사용되는 데이터 미리 로드)
     */
    public static function warmupCache(): array
    {
        $warmed = [];

        // 게시판 목록 워밍업
        self::getBoardList();
        $warmed[] = 'boards.list';

        // 추천 상품 워밍업
        self::getFeaturedProducts();
        $warmed[] = 'featured.products';

        // 시스템 설정 워밍업
        self::getSystemSettings();
        $warmed[] = 'system.settings';

        return $warmed;
    }

    /**
     * 전체 캐시 클리어
     */
    public static function clearAllCache(): bool
    {
        try {
            // Laravel 캐시 클리어
            Cache::flush();
            
            // Redis 캐시 클리어
            Redis::flushdb();
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}