<?php

namespace App\Http\Controllers\Ahhob\Admin\Dashboard\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;

class MonitoringController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /*
    |--------------------------------------------------------------------------
    | 시스템 모니터링 메인 (Main System Monitoring)
    |--------------------------------------------------------------------------
    */

    /**
     * 모니터링 대시보드
     */
    public function index(): View
    {
        $systemOverview = $this->getSystemOverview();
        $alerts = $this->getSystemAlerts();
        $recentMetrics = $this->getRecentMetrics();
        
        return view('ahhob.admin.dashboard.system.monitoring.index', compact(
            'systemOverview',
            'alerts',
            'recentMetrics'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | 서버 상태 (Server Status)
    |--------------------------------------------------------------------------
    */

    /**
     * 서버 상태 모니터링
     */
    public function server(): View
    {
        $serverStats = $this->getServerStats();
        $processInfo = $this->getProcessInfo();
        $networkStats = $this->getNetworkStats();
        
        return view('ahhob.admin.dashboard.system.monitoring.server', compact(
            'serverStats',
            'processInfo',
            'networkStats'
        ));
    }

    /**
     * 서버 메트릭 API
     */
    public function serverMetrics(): JsonResponse
    {
        $metrics = Cache::remember('system.server.metrics', 60, function () {
            return [
                'cpu' => $this->getCpuUsage(),
                'memory' => $this->getMemoryUsage(),
                'disk' => $this->getDiskUsage(),
                'load_average' => $this->getLoadAverage(),
                'uptime' => $this->getSystemUptime(),
                'timestamp' => now()->timestamp,
            ];
        });

        return response()->json($metrics);
    }

    /*
    |--------------------------------------------------------------------------
    | 에러 로그 (Error Logs)
    |--------------------------------------------------------------------------
    */

    /**
     * 에러 로그 관리
     */
    public function logs(Request $request): View
    {
        $logFiles = $this->getLogFiles();
        $selectedLog = $request->get('log', 'laravel.log');
        $logEntries = $this->parseLogFile($selectedLog, $request->get('lines', 100));
        
        return view('ahhob.admin.dashboard.system.monitoring.logs', compact(
            'logFiles',
            'selectedLog',
            'logEntries'
        ));
    }

    /**
     * 로그 파일 다운로드
     */
    public function downloadLogs(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $logFile = $request->get('log', 'laravel.log');
        $logPath = storage_path("logs/{$logFile}");
        
        if (!File::exists($logPath)) {
            abort(404, '로그 파일을 찾을 수 없습니다.');
        }

        return response()->download($logPath);
    }

    /**
     * 로그 클리어
     */
    public function clearLogs(Request $request): JsonResponse
    {
        $request->validate([
            'log_files' => 'required|array',
            'log_files.*' => 'string',
        ]);

        try {
            $clearedFiles = [];

            foreach ($request->log_files as $logFile) {
                $logPath = storage_path("logs/{$logFile}");
                
                if (File::exists($logPath)) {
                    File::put($logPath, '');
                    $clearedFiles[] = $logFile;
                }
            }

            return response()->json([
                'success' => true,
                'message' => count($clearedFiles) . '개의 로그 파일이 클리어되었습니다.',
                'cleared_files' => $clearedFiles,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '로그 클리어 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 성능 모니터링 (Performance Monitoring)
    |--------------------------------------------------------------------------
    */

    /**
     * 성능 모니터링
     */
    public function performance(): View
    {
        $performanceStats = $this->getPerformanceStats();
        $slowQueries = $this->getSlowQueries();
        $cacheStats = $this->getCacheStats();
        
        return view('ahhob.admin.dashboard.system.monitoring.performance', compact(
            'performanceStats',
            'slowQueries',
            'cacheStats'
        ));
    }

    /**
     * 성능 메트릭 API
     */
    public function performanceMetrics(): JsonResponse
    {
        $metrics = Cache::remember('system.performance.metrics', 300, function () {
            return [
                'response_time' => $this->getAverageResponseTime(),
                'throughput' => $this->getRequestThroughput(),
                'error_rate' => $this->getErrorRate(),
                'database_connections' => $this->getDatabaseConnections(),
                'cache_hit_rate' => $this->getCacheHitRate(),
                'memory_usage' => $this->getMemoryUsage(),
                'timestamp' => now()->timestamp,
            ];
        });

        return response()->json($metrics);
    }

    /*
    |--------------------------------------------------------------------------
    | 데이터베이스 상태 (Database Status)
    |--------------------------------------------------------------------------
    */

    /**
     * 데이터베이스 모니터링
     */
    public function database(): View
    {
        $databaseStats = $this->getDatabaseStats();
        $connectionStats = $this->getConnectionStats();
        $tableStats = $this->getTableStats();
        
        return view('ahhob.admin.dashboard.system.monitoring.database', compact(
            'databaseStats',
            'connectionStats',
            'tableStats'
        ));
    }

    /**
     * 데이터베이스 메트릭 API
     */
    public function databaseMetrics(): JsonResponse
    {
        $metrics = Cache::remember('system.database.metrics', 60, function () {
            return [
                'active_connections' => $this->getActiveConnections(),
                'slow_queries' => $this->getSlowQueryCount(),
                'query_cache_hit_rate' => $this->getQueryCacheHitRate(),
                'table_locks' => $this->getTableLocks(),
                'innodb_buffer_pool' => $this->getInnodbBufferPoolStats(),
                'timestamp' => now()->timestamp,
            ];
        });

        return response()->json($metrics);
    }

    /*
    |--------------------------------------------------------------------------
    | 큐 모니터링 (Queue Monitoring)
    |--------------------------------------------------------------------------
    */

    /**
     * 큐 모니터링
     */
    public function queue(): View
    {
        $queueStats = $this->getQueueStats();
        $failedJobs = $this->getFailedJobs();
        $jobStats = $this->getJobStats();
        
        return view('ahhob.admin.dashboard.system.monitoring.queue', compact(
            'queueStats',
            'failedJobs',
            'jobStats'
        ));
    }

    /**
     * 큐 메트릭 API
     */
    public function queueMetrics(): JsonResponse
    {
        $metrics = [
            'pending_jobs' => $this->getPendingJobsCount(),
            'processing_jobs' => $this->getProcessingJobsCount(),
            'failed_jobs' => $this->getFailedJobsCount(),
            'completed_jobs_today' => $this->getCompletedJobsToday(),
            'average_processing_time' => $this->getAverageProcessingTime(),
            'timestamp' => now()->timestamp,
        ];

        return response()->json($metrics);
    }

    /**
     * 실패한 작업 재시도
     */
    public function retryFailedJobs(Request $request): JsonResponse
    {
        $request->validate([
            'job_ids' => 'nullable|array',
            'job_ids.*' => 'string',
            'retry_all' => 'boolean',
        ]);

        try {
            if ($request->boolean('retry_all')) {
                // 모든 실패한 작업 재시도
                $this->retryAllFailedJobs();
                $message = '모든 실패한 작업이 재시도되었습니다.';
            } else {
                // 선택된 작업만 재시도
                $retryCount = $this->retrySelectedJobs($request->job_ids ?? []);
                $message = "{$retryCount}개의 작업이 재시도되었습니다.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '작업 재시도 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /**
     * 실패한 작업 클리어
     */
    public function clearFailedJobs(Request $request): JsonResponse
    {
        $request->validate([
            'job_ids' => 'nullable|array',
            'job_ids.*' => 'string',
            'clear_all' => 'boolean',
        ]);

        try {
            if ($request->boolean('clear_all')) {
                // 모든 실패한 작업 삭제
                $this->clearAllFailedJobs();
                $message = '모든 실패한 작업이 삭제되었습니다.';
            } else {
                // 선택된 작업만 삭제
                $deleteCount = $this->clearSelectedJobs($request->job_ids ?? []);
                $message = "{$deleteCount}개의 작업이 삭제되었습니다.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '작업 삭제 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 헬퍼 메서드 (Helper Methods)
    |--------------------------------------------------------------------------
    */

    /**
     * 시스템 개요
     */
    private function getSystemOverview(): array
    {
        return [
            'server_status' => $this->getServerStatus(),
            'database_status' => $this->getDatabaseStatus(),
            'cache_status' => $this->getCacheStatus(),
            'queue_status' => $this->getQueueStatus(),
            'storage_status' => $this->getStorageStatus(),
        ];
    }

    /**
     * 시스템 알림
     */
    private function getSystemAlerts(): array
    {
        $alerts = [];

        // CPU 사용률 체크
        $cpuUsage = $this->getCpuUsage();
        if ($cpuUsage > 80) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'CPU 사용률 높음',
                'message' => "현재 CPU 사용률이 {$cpuUsage}%입니다.",
            ];
        }

        // 메모리 사용률 체크
        $memoryUsage = $this->getMemoryUsage();
        if ($memoryUsage['usage_percentage'] > 90) {
            $alerts[] = [
                'type' => 'critical',
                'title' => '메모리 부족',
                'message' => "메모리 사용률이 {$memoryUsage['usage_percentage']}%입니다.",
            ];
        }

        // 디스크 사용률 체크
        $diskUsage = $this->getDiskUsage();
        if ($diskUsage['usage_percentage'] > 85) {
            $alerts[] = [
                'type' => 'warning',
                'title' => '디스크 공간 부족',
                'message' => "디스크 사용률이 {$diskUsage['usage_percentage']}%입니다.",
            ];
        }

        // 실패한 작업 체크
        $failedJobs = $this->getFailedJobsCount();
        if ($failedJobs > 10) {
            $alerts[] = [
                'type' => 'warning',
                'title' => '실패한 작업 다수',
                'message' => "{$failedJobs}개의 작업이 실패했습니다.",
            ];
        }

        return $alerts;
    }

    /**
     * 최근 메트릭
     */
    private function getRecentMetrics(): array
    {
        return [
            'response_time_trend' => $this->getResponseTimeTrend(24), // 24시간
            'error_rate_trend' => $this->getErrorRateTrend(24),
            'throughput_trend' => $this->getThroughputTrend(24),
        ];
    }

    /**
     * 서버 상태
     */
    private function getServerStatus(): string
    {
        $cpuUsage = $this->getCpuUsage();
        $memoryUsage = $this->getMemoryUsage();
        
        if ($cpuUsage > 90 || $memoryUsage['usage_percentage'] > 95) {
            return 'critical';
        } elseif ($cpuUsage > 70 || $memoryUsage['usage_percentage'] > 80) {
            return 'warning';
        }
        
        return 'healthy';
    }

    /**
     * 데이터베이스 상태
     */
    private function getDatabaseStatus(): string
    {
        try {
            DB::connection()->getPdo();
            
            $connections = $this->getActiveConnections();
            $maxConnections = $this->getMaxConnections();
            
            if ($connections / $maxConnections > 0.9) {
                return 'warning';
            }
            
            return 'healthy';
        } catch (\Exception $e) {
            return 'error';
        }
    }

    /**
     * 캐시 상태
     */
    private function getCacheStatus(): string
    {
        try {
            Cache::put('health_check', 'ok', 10);
            return Cache::get('health_check') === 'ok' ? 'healthy' : 'error';
        } catch (\Exception $e) {
            return 'error';
        }
    }

    /**
     * 큐 상태
     */
    private function getQueueStatus(): string
    {
        $failedJobs = $this->getFailedJobsCount();
        $pendingJobs = $this->getPendingJobsCount();
        
        if ($failedJobs > 50) {
            return 'critical';
        } elseif ($failedJobs > 10 || $pendingJobs > 1000) {
            return 'warning';
        }
        
        return 'healthy';
    }

    /**
     * 스토리지 상태
     */
    private function getStorageStatus(): string
    {
        $diskUsage = $this->getDiskUsage();
        
        if ($diskUsage['usage_percentage'] > 95) {
            return 'critical';
        } elseif ($diskUsage['usage_percentage'] > 85) {
            return 'warning';
        }
        
        return 'healthy';
    }

    /**
     * CPU 사용률 조회
     */
    private function getCpuUsage(): float
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return round($load[0] * 100 / $this->getCpuCoreCount(), 2);
        }
        
        return 0.0;
    }

    /**
     * CPU 코어 수 조회
     */
    private function getCpuCoreCount(): int
    {
        if (is_file('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            preg_match_all('/^processor/m', $cpuinfo, $matches);
            return count($matches[0]);
        }
        
        return 1;
    }

    /**
     * 메모리 사용량 조회
     */
    private function getMemoryUsage(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->getMemoryLimit();
        
        return [
            'used' => $memoryUsage,
            'limit' => $memoryLimit,
            'usage_percentage' => round(($memoryUsage / $memoryLimit) * 100, 2),
        ];
    }

    /**
     * 메모리 제한 조회
     */
    private function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        
        if ($limit === '-1') {
            return PHP_INT_MAX;
        }
        
        return $this->convertToBytes($limit);
    }

    /**
     * 바이트 변환
     */
    private function convertToBytes(string $value): int
    {
        $unit = strtolower(substr($value, -1));
        $number = (int) substr($value, 0, -1);
        
        switch ($unit) {
            case 'g':
                return $number * 1024 * 1024 * 1024;
            case 'm':
                return $number * 1024 * 1024;
            case 'k':
                return $number * 1024;
            default:
                return (int) $value;
        }
    }

    /**
     * 디스크 사용량 조회
     */
    private function getDiskUsage(): array
    {
        $path = storage_path();
        $totalSpace = disk_total_space($path);
        $freeSpace = disk_free_space($path);
        $usedSpace = $totalSpace - $freeSpace;
        
        return [
            'total' => $totalSpace,
            'used' => $usedSpace,
            'free' => $freeSpace,
            'usage_percentage' => round(($usedSpace / $totalSpace) * 100, 2),
        ];
    }

    /**
     * 로드 평균 조회
     */
    private function getLoadAverage(): array
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return [
                '1min' => $load[0],
                '5min' => $load[1],
                '15min' => $load[2],
            ];
        }
        
        return ['1min' => 0, '5min' => 0, '15min' => 0];
    }

    /**
     * 시스템 업타임 조회
     */
    private function getSystemUptime(): int
    {
        if (is_file('/proc/uptime')) {
            $uptime = file_get_contents('/proc/uptime');
            return (int) floatval(explode(' ', $uptime)[0]);
        }
        
        return 0;
    }

    /**
     * 서버 통계
     */
    private function getServerStats(): array
    {
        return [
            'cpu_usage' => $this->getCpuUsage(),
            'memory_usage' => $this->getMemoryUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'load_average' => $this->getLoadAverage(),
            'uptime' => $this->getSystemUptime(),
        ];
    }

    /**
     * 프로세스 정보
     */
    private function getProcessInfo(): array
    {
        // 추후 구현: 프로세스 정보 조회
        return [];
    }

    /**
     * 네트워크 통계
     */
    private function getNetworkStats(): array
    {
        // 추후 구현: 네트워크 통계 조회
        return [];
    }

    /**
     * 로그 파일 목록 조회
     */
    private function getLogFiles(): array
    {
        $logPath = storage_path('logs');
        
        if (!is_dir($logPath)) {
            return [];
        }
        
        $files = [];
        foreach (scandir($logPath) as $file) {
            if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'log') {
                $filePath = $logPath . '/' . $file;
                $files[] = [
                    'name' => $file,
                    'size' => filesize($filePath),
                    'modified' => filemtime($filePath),
                ];
            }
        }
        
        return $files;
    }

    /**
     * 로그 파일 파싱
     */
    private function parseLogFile(string $logFile, int $lines): array
    {
        $logPath = storage_path("logs/{$logFile}");
        
        if (!File::exists($logPath)) {
            return [];
        }
        
        $content = File::get($logPath);
        $logLines = array_slice(array_reverse(explode("\n", $content)), 0, $lines);
        
        $entries = [];
        foreach ($logLines as $line) {
            if (empty($line)) continue;
            
            // 간단한 로그 파싱 (실제로는 더 정교한 파싱 필요)
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.+)/', $line, $matches)) {
                $entries[] = [
                    'timestamp' => $matches[1],
                    'environment' => $matches[2],
                    'level' => $matches[3],
                    'message' => $matches[4],
                ];
            } else {
                $entries[] = [
                    'timestamp' => '',
                    'environment' => '',
                    'level' => 'info',
                    'message' => $line,
                ];
            }
        }
        
        return $entries;
    }

    // 추가 헬퍼 메서드들은 실제 구현에서 각각의 기능에 맞게 구현 필요
    private function getPerformanceStats(): array { return []; }
    private function getSlowQueries(): array { return []; }
    private function getCacheStats(): array { return []; }
    private function getAverageResponseTime(): float { return 0.0; }
    private function getRequestThroughput(): int { return 0; }
    private function getErrorRate(): float { return 0.0; }
    private function getDatabaseConnections(): int { return 0; }
    private function getCacheHitRate(): float { return 0.0; }
    private function getDatabaseStats(): array { return []; }
    private function getConnectionStats(): array { return []; }
    private function getTableStats(): array { return []; }
    private function getActiveConnections(): int { return 0; }
    private function getMaxConnections(): int { return 100; }
    private function getSlowQueryCount(): int { return 0; }
    private function getQueryCacheHitRate(): float { return 0.0; }
    private function getTableLocks(): int { return 0; }
    private function getInnodbBufferPoolStats(): array { return []; }
    private function getQueueStats(): array { return []; }
    private function getFailedJobs(): array { return []; }
    private function getJobStats(): array { return []; }
    private function getPendingJobsCount(): int { return 0; }
    private function getProcessingJobsCount(): int { return 0; }
    private function getFailedJobsCount(): int { return 0; }
    private function getCompletedJobsToday(): int { return 0; }
    private function getAverageProcessingTime(): float { return 0.0; }
    private function retryAllFailedJobs(): void {}
    private function retrySelectedJobs(array $jobIds): int { return 0; }
    private function clearAllFailedJobs(): void {}
    private function clearSelectedJobs(array $jobIds): int { return 0; }
    private function getResponseTimeTrend(int $hours): array { return []; }
    private function getErrorRateTrend(int $hours): array { return []; }
    private function getThroughputTrend(int $hours): array { return []; }
}