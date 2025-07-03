<?php

namespace App\Console\Commands\Ahhob\Performance;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use App\Services\Ahhob\Shared\CacheService;
use App\Services\Ahhob\Shared\QueryOptimizationService;

class OptimizePerformanceCommand extends Command
{
    /*
    |--------------------------------------------------------------------------
    | 성능 최적화 명령어 (Performance Optimization Command)
    |--------------------------------------------------------------------------
    */

    /**
     * 명령어 이름 및 시그니처
     */
    protected $signature = 'ahhob:optimize
                            {--cache : 캐시 최적화만 실행}
                            {--database : 데이터베이스 최적화만 실행}
                            {--indexes : 인덱스 최적화만 실행}
                            {--cleanup : 정리 작업만 실행}
                            {--all : 모든 최적화 작업 실행 (기본값)}
                            {--warmup : 캐시 워밍업 실행}
                            {--analyze : 성능 분석 실행}';

    /**
     * 명령어 설명
     */
    protected $description = 'Ahhob 애플리케이션 성능 최적화 작업을 실행합니다';

    /**
     * 최적화 작업 실행
     */
    public function handle(): int
    {
        $this->info('🚀 Ahhob 성능 최적화 시작...');
        $this->newLine();

        $startTime = microtime(true);

        try {
            // 옵션에 따른 작업 실행
            if ($this->option('cache') || $this->option('all')) {
                $this->optimizeCache();
            }

            if ($this->option('database') || $this->option('all')) {
                $this->optimizeDatabase();
            }

            if ($this->option('indexes') || $this->option('all')) {
                $this->optimizeIndexes();
            }

            if ($this->option('cleanup') || $this->option('all')) {
                $this->performCleanup();
            }

            if ($this->option('warmup')) {
                $this->warmupCache();
            }

            if ($this->option('analyze')) {
                $this->analyzePerformance();
            }

            // 기본 동작 (옵션이 없을 때)
            if (!$this->hasAnyOption()) {
                $this->optimizeCache();
                $this->optimizeDatabase();
                $this->performCleanup();
                $this->warmupCache();
            }

            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);

            $this->newLine();
            $this->info("✅ 성능 최적화가 완료되었습니다! (소요 시간: {$duration}ms)");

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ 최적화 중 오류가 발생했습니다: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * 캐시 최적화
     */
    private function optimizeCache(): void
    {
        $this->line('🗄️  캐시 최적화 중...');

        // Laravel 설정 캐시
        $this->task('설정 캐시 생성', function () {
            Artisan::call('config:cache');
            return true;
        });

        // Laravel 라우트 캐시
        $this->task('라우트 캐시 생성', function () {
            Artisan::call('route:cache');
            return true;
        });

        // Laravel 뷰 캐시
        $this->task('뷰 캐시 생성', function () {
            Artisan::call('view:cache');
            return true;
        });

        // 이벤트 캐시
        if (version_compare(app()->version(), '8.0', '>=')) {
            $this->task('이벤트 캐시 생성', function () {
                Artisan::call('event:cache');
                return true;
            });
        }

        // 오래된 캐시 정리
        $this->task('오래된 캐시 정리', function () {
            $this->cleanupExpiredCache();
            return true;
        });

        $this->info('✅ 캐시 최적화 완료');
        $this->newLine();
    }

    /**
     * 데이터베이스 최적화
     */
    private function optimizeDatabase(): void
    {
        $this->line('🗃️  데이터베이스 최적화 중...');

        // 테이블 최적화
        $this->task('테이블 최적화', function () {
            $this->optimizeTables();
            return true;
        });

        // 통계 업데이트
        $this->task('테이블 통계 업데이트', function () {
            $this->updateTableStatistics();
            return true;
        });

        // 쿼리 캐시 최적화
        $this->task('쿼리 캐시 최적화', function () {
            $this->optimizeQueryCache();
            return true;
        });

        $this->info('✅ 데이터베이스 최적화 완료');
        $this->newLine();
    }

    /**
     * 인덱스 최적화
     */
    private function optimizeIndexes(): void
    {
        $this->line('📊 인덱스 최적화 중...');

        // 인덱스 사용률 분석
        $this->task('인덱스 사용률 분석', function () {
            $this->analyzeIndexUsage();
            return true;
        });

        // 중복 인덱스 확인
        $this->task('중복 인덱스 확인', function () {
            $this->checkDuplicateIndexes();
            return true;
        });

        // 미사용 인덱스 확인
        $this->task('미사용 인덱스 확인', function () {
            $this->checkUnusedIndexes();
            return true;
        });

        $this->info('✅ 인덱스 최적화 완료');
        $this->newLine();
    }

    /**
     * 정리 작업
     */
    private function performCleanup(): void
    {
        $this->line('🧹 정리 작업 중...');

        // 오래된 로그 파일 정리
        $this->task('오래된 로그 파일 정리', function () {
            $this->cleanupOldLogs();
            return true;
        });

        // 임시 파일 정리
        $this->task('임시 파일 정리', function () {
            $this->cleanupTempFiles();
            return true;
        });

        // 실패한 작업 정리
        $this->task('실패한 작업 정리', function () {
            Artisan::call('queue:flush');
            return true;
        });

        // 세션 정리
        $this->task('만료된 세션 정리', function () {
            $this->cleanupExpiredSessions();
            return true;
        });

        $this->info('✅ 정리 작업 완료');
        $this->newLine();
    }

    /**
     * 캐시 워밍업
     */
    private function warmupCache(): void
    {
        $this->line('🔥 캐시 워밍업 중...');

        $this->task('애플리케이션 캐시 워밍업', function () {
            $warmed = CacheService::warmupCache();
            $this->line('   워밍업된 캐시: ' . implode(', ', $warmed));
            return true;
        });

        $this->task('게시판 데이터 워밍업', function () {
            $this->warmupBoardData();
            return true;
        });

        $this->task('상품 데이터 워밍업', function () {
            $this->warmupProductData();
            return true;
        });

        $this->info('✅ 캐시 워밍업 완료');
        $this->newLine();
    }

    /**
     * 성능 분석
     */
    private function analyzePerformance(): void
    {
        $this->line('📈 성능 분석 중...');

        // 느린 쿼리 분석
        $this->task('느린 쿼리 분석', function () {
            $this->analyzeSlowQueries();
            return true;
        });

        // 캐시 적중률 분석
        $this->task('캐시 적중률 분석', function () {
            $this->analyzeCacheHitRate();
            return true;
        });

        // 메모리 사용량 분석
        $this->task('메모리 사용량 분석', function () {
            $this->analyzeMemoryUsage();
            return true;
        });

        $this->info('✅ 성능 분석 완료');
        $this->newLine();
    }

    /**
     * 옵션이 설정되었는지 확인
     */
    private function hasAnyOption(): bool
    {
        return $this->option('cache') 
            || $this->option('database') 
            || $this->option('indexes') 
            || $this->option('cleanup')
            || $this->option('warmup')
            || $this->option('analyze');
    }

    /**
     * 만료된 캐시 정리
     */
    private function cleanupExpiredCache(): void
    {
        // Redis 캐시 정리
        try {
            $redis = app('redis');
            $redis->eval("
                for i=1,#KEYS do
                    if redis.call('ttl', KEYS[i]) == -1 then
                        redis.call('del', KEYS[i])
                    end
                end
            ", 0);
        } catch (\Exception $e) {
            $this->warn('Redis 캐시 정리 실패: ' . $e->getMessage());
        }

        // 파일 캐시 정리
        $cachePath = storage_path('framework/cache/data');
        if (is_dir($cachePath)) {
            $files = glob($cachePath . '/*');
            $cleaned = 0;

            foreach ($files as $file) {
                if (is_file($file) && (time() - filemtime($file)) > 86400) { // 24시간 이상
                    unlink($file);
                    $cleaned++;
                }
            }

            $this->line("   정리된 파일 캐시: {$cleaned}개");
        }
    }

    /**
     * 테이블 최적화
     */
    private function optimizeTables(): void
    {
        $tables = DB::select('SHOW TABLES');
        $databaseName = DB::getDatabaseName();
        $optimized = 0;

        foreach ($tables as $table) {
            $tableName = $table->{"Tables_in_{$databaseName}"};
            
            try {
                DB::statement("OPTIMIZE TABLE `{$tableName}`");
                $optimized++;
            } catch (\Exception $e) {
                $this->warn("테이블 {$tableName} 최적화 실패: " . $e->getMessage());
            }
        }

        $this->line("   최적화된 테이블: {$optimized}개");
    }

    /**
     * 테이블 통계 업데이트
     */
    private function updateTableStatistics(): void
    {
        $tables = DB::select('SHOW TABLES');
        $databaseName = DB::getDatabaseName();
        $updated = 0;

        foreach ($tables as $table) {
            $tableName = $table->{"Tables_in_{$databaseName}"};
            
            try {
                DB::statement("ANALYZE TABLE `{$tableName}`");
                $updated++;
            } catch (\Exception $e) {
                $this->warn("테이블 {$tableName} 통계 업데이트 실패: " . $e->getMessage());
            }
        }

        $this->line("   업데이트된 테이블: {$updated}개");
    }

    /**
     * 쿼리 캐시 최적화
     */
    private function optimizeQueryCache(): void
    {
        try {
            // MySQL 쿼리 캐시 리셋
            DB::statement('RESET QUERY CACHE');
            $this->line('   쿼리 캐시가 리셋되었습니다');
        } catch (\Exception $e) {
            $this->warn('쿼리 캐시 리셋 실패: ' . $e->getMessage());
        }
    }

    /**
     * 인덱스 사용률 분석
     */
    private function analyzeIndexUsage(): void
    {
        try {
            $indexStats = DB::select("
                SELECT 
                    OBJECT_SCHEMA as database_name,
                    OBJECT_NAME as table_name,
                    INDEX_NAME as index_name,
                    COUNT_FETCH as index_fetches,
                    COUNT_INSERT as index_inserts,
                    COUNT_UPDATE as index_updates,
                    COUNT_DELETE as index_deletes
                FROM performance_schema.table_io_waits_summary_by_index_usage 
                WHERE OBJECT_SCHEMA = ? 
                AND COUNT_FETCH > 0
                ORDER BY COUNT_FETCH DESC
                LIMIT 10
            ", [DB::getDatabaseName()]);

            if (!empty($indexStats)) {
                $this->line('   상위 10개 사용 인덱스:');
                foreach ($indexStats as $stat) {
                    $this->line("     {$stat->table_name}.{$stat->index_name}: {$stat->index_fetches} 조회");
                }
            }
        } catch (\Exception $e) {
            $this->warn('인덱스 사용률 분석 실패: ' . $e->getMessage());
        }
    }

    /**
     * 중복 인덱스 확인
     */
    private function checkDuplicateIndexes(): void
    {
        try {
            $duplicates = DB::select("
                SELECT 
                    TABLE_NAME,
                    GROUP_CONCAT(INDEX_NAME) as duplicate_indexes,
                    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as columns
                FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = ?
                GROUP BY TABLE_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX)
                HAVING COUNT(*) > 1
            ", [DB::getDatabaseName()]);

            if (!empty($duplicates)) {
                $this->warn('   중복 인덱스 발견:');
                foreach ($duplicates as $duplicate) {
                    $this->line("     {$duplicate->TABLE_NAME}: {$duplicate->duplicate_indexes}");
                }
            } else {
                $this->line('   중복 인덱스가 없습니다');
            }
        } catch (\Exception $e) {
            $this->warn('중복 인덱스 확인 실패: ' . $e->getMessage());
        }
    }

    /**
     * 미사용 인덱스 확인
     */
    private function checkUnusedIndexes(): void
    {
        try {
            $unusedIndexes = DB::select("
                SELECT 
                    OBJECT_SCHEMA as database_name,
                    OBJECT_NAME as table_name,
                    INDEX_NAME as index_name
                FROM performance_schema.table_io_waits_summary_by_index_usage 
                WHERE OBJECT_SCHEMA = ? 
                AND INDEX_NAME IS NOT NULL
                AND INDEX_NAME != 'PRIMARY'
                AND COUNT_FETCH = 0
                AND COUNT_INSERT = 0
                AND COUNT_UPDATE = 0
                AND COUNT_DELETE = 0
                ORDER BY OBJECT_NAME, INDEX_NAME
            ", [DB::getDatabaseName()]);

            if (!empty($unusedIndexes)) {
                $this->warn('   미사용 인덱스 발견:');
                foreach ($unusedIndexes as $index) {
                    $this->line("     {$index->table_name}.{$index->index_name}");
                }
            } else {
                $this->line('   미사용 인덱스가 없습니다');
            }
        } catch (\Exception $e) {
            $this->warn('미사용 인덱스 확인 실패: ' . $e->getMessage());
        }
    }

    /**
     * 오래된 로그 파일 정리
     */
    private function cleanupOldLogs(): void
    {
        $logPath = storage_path('logs');
        $cutoffDate = now()->subDays(30);
        $cleaned = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($logPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'log') {
                if ($file->getMTime() < $cutoffDate->timestamp) {
                    unlink($file->getRealPath());
                    $cleaned++;
                }
            }
        }

        $this->line("   정리된 로그 파일: {$cleaned}개");
    }

    /**
     * 임시 파일 정리
     */
    private function cleanupTempFiles(): void
    {
        $tempPaths = [
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
        ];

        $cleaned = 0;

        foreach ($tempPaths as $path) {
            if (is_dir($path)) {
                $files = glob($path . '/*');
                
                foreach ($files as $file) {
                    if (is_file($file) && (time() - filemtime($file)) > 86400) { // 24시간 이상
                        unlink($file);
                        $cleaned++;
                    }
                }
            }
        }

        $this->line("   정리된 임시 파일: {$cleaned}개");
    }

    /**
     * 만료된 세션 정리
     */
    private function cleanupExpiredSessions(): void
    {
        try {
            $sessionHandler = config('session.driver');
            
            if ($sessionHandler === 'redis') {
                // Redis 세션 정리
                $redis = app('redis');
                $keys = $redis->keys('laravel_session:*');
                $cleaned = 0;

                foreach ($keys as $key) {
                    if ($redis->ttl($key) == -1) { // 만료 시간이 없는 키
                        $redis->del($key);
                        $cleaned++;
                    }
                }

                $this->line("   정리된 Redis 세션: {$cleaned}개");
            } elseif ($sessionHandler === 'file') {
                // 파일 세션 정리
                $sessionPath = storage_path('framework/sessions');
                $files = glob($sessionPath . '/*');
                $cleaned = 0;

                foreach ($files as $file) {
                    if (is_file($file) && (time() - filemtime($file)) > config('session.lifetime') * 60) {
                        unlink($file);
                        $cleaned++;
                    }
                }

                $this->line("   정리된 파일 세션: {$cleaned}개");
            }
        } catch (\Exception $e) {
            $this->warn('세션 정리 실패: ' . $e->getMessage());
        }
    }

    /**
     * 게시판 데이터 워밍업
     */
    private function warmupBoardData(): void
    {
        try {
            $boards = CacheService::getBoardList();
            
            foreach ($boards as $board) {
                CacheService::getBoardStats($board->slug);
                QueryOptimizationService::getPopularPosts($board->slug, 5);
            }

            $this->line("   워밍업된 게시판: " . count($boards) . "개");
        } catch (\Exception $e) {
            $this->warn('게시판 데이터 워밍업 실패: ' . $e->getMessage());
        }
    }

    /**
     * 상품 데이터 워밍업
     */
    private function warmupProductData(): void
    {
        try {
            // 추천 상품 캐시
            CacheService::getFeaturedProducts(10);
            
            // 상품 목록 캐시 (첫 3페이지)
            for ($page = 1; $page <= 3; $page++) {
                QueryOptimizationService::getOptimizedProductList($page, 20);
            }

            $this->line('   워밍업된 상품 데이터: 추천 상품, 상품 목록 3페이지');
        } catch (\Exception $e) {
            $this->warn('상품 데이터 워밍업 실패: ' . $e->getMessage());
        }
    }

    /**
     * 느린 쿼리 분석
     */
    private function analyzeSlowQueries(): void
    {
        try {
            $slowQueries = DB::select("
                SELECT 
                    sql_text,
                    exec_count,
                    avg_timer_wait / 1000000000 as avg_time_seconds,
                    max_timer_wait / 1000000000 as max_time_seconds
                FROM performance_schema.events_statements_summary_by_digest 
                WHERE avg_timer_wait > 1000000000 -- 1초 이상
                ORDER BY avg_timer_wait DESC 
                LIMIT 5
            ");

            if (!empty($slowQueries)) {
                $this->line('   상위 5개 느린 쿼리:');
                foreach ($slowQueries as $query) {
                    $sql = substr($query->sql_text, 0, 100) . '...';
                    $avgTime = round($query->avg_time_seconds, 3);
                    $this->line("     평균 {$avgTime}초: {$sql}");
                }
            } else {
                $this->line('   느린 쿼리가 없습니다');
            }
        } catch (\Exception $e) {
            $this->warn('느린 쿼리 분석 실패: ' . $e->getMessage());
        }
    }

    /**
     * 캐시 적중률 분석
     */
    private function analyzeCacheHitRate(): void
    {
        try {
            $cacheStats = CacheService::getCacheStatistics();
            
            $this->line('   캐시 통계:');
            $this->line("     Redis 메모리 사용량: " . round($cacheStats['redis_memory'] / 1024 / 1024, 2) . "MB");
            $this->line("     Redis 키 개수: {$cacheStats['redis_keys']}개");
            $this->line("     파일 캐시 크기: " . round($cacheStats['file_cache_size'] / 1024 / 1024, 2) . "MB");
        } catch (\Exception $e) {
            $this->warn('캐시 적중률 분석 실패: ' . $e->getMessage());
        }
    }

    /**
     * 메모리 사용량 분석
     */
    private function analyzeMemoryUsage(): void
    {
        $memoryUsage = memory_get_usage(true);
        $peakMemoryUsage = memory_get_peak_usage(true);
        
        $this->line('   메모리 사용량:');
        $this->line("     현재 사용량: " . round($memoryUsage / 1024 / 1024, 2) . "MB");
        $this->line("     최대 사용량: " . round($peakMemoryUsage / 1024 / 1024, 2) . "MB");
        
        // 시스템 메모리 정보 (Linux만)
        if (file_exists('/proc/meminfo')) {
            $meminfo = file_get_contents('/proc/meminfo');
            if (preg_match('/MemTotal:\s+(\d+)\s+kB/', $meminfo, $matches)) {
                $totalMemory = $matches[1] * 1024;
                $usagePercent = round(($memoryUsage / $totalMemory) * 100, 2);
                $this->line("     시스템 메모리 사용률: {$usagePercent}%");
            }
        }
    }
};