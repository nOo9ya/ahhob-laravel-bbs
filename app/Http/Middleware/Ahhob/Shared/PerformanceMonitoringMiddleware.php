<?php

namespace App\Http\Middleware\Ahhob\Shared;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PerformanceMonitoringMiddleware
{
    /*
    |--------------------------------------------------------------------------
    | 성능 모니터링 미들웨어 (Performance Monitoring Middleware)
    |--------------------------------------------------------------------------
    */

    /**
     * 성능 임계치 설정
     */
    private const PERFORMANCE_THRESHOLDS = [
        'response_time' => [
            'warning' => 1000,  // 1초
            'critical' => 3000, // 3초
        ],
        'memory_usage' => [
            'warning' => 64 * 1024 * 1024,   // 64MB
            'critical' => 128 * 1024 * 1024, // 128MB
        ],
        'query_count' => [
            'warning' => 20,
            'critical' => 50,
        ],
        'query_time' => [
            'warning' => 500,  // 0.5초
            'critical' => 1000, // 1초
        ],
    ];

    /**
     * 요청 처리 및 성능 모니터링
     */
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        // 성능 측정 시작
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        $startPeakMemory = memory_get_peak_usage(true);
        
        // 쿼리 카운터 초기화
        $this->enableQueryLogging();
        $queryStartCount = count(DB::getQueryLog());

        // 요청 처리
        $response = $next($request);

        // 성능 측정 종료
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $endPeakMemory = memory_get_peak_usage(true);
        
        // 성능 메트릭 계산
        $metrics = $this->calculateMetrics([
            'start_time' => $startTime,
            'end_time' => $endTime,
            'start_memory' => $startMemory,
            'end_memory' => $endMemory,
            'start_peak_memory' => $startPeakMemory,
            'end_peak_memory' => $endPeakMemory,
            'query_start_count' => $queryStartCount,
        ]);

        // 성능 로깅 및 분석
        $this->logPerformanceMetrics($request, $response, $metrics);
        
        // 실시간 모니터링 데이터 업데이트
        $this->updateRealtimeMetrics($metrics);
        
        // 성능 임계치 확인 및 알림
        $this->checkPerformanceThresholds($request, $metrics);

        // 응답 헤더에 성능 정보 추가 (개발 환경에서만)
        if (config('app.debug', false)) {
            $this->addPerformanceHeaders($response, $metrics);
        }

        return $response;
    }

    /**
     * 쿼리 로깅 활성화
     */
    private function enableQueryLogging(): void
    {
        DB::enableQueryLog();
        
        // 느린 쿼리 감지를 위한 리스너
        DB::listen(function ($query) {
            if ($query->time > self::PERFORMANCE_THRESHOLDS['query_time']['warning']) {
                Log::warning('Slow Query Detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time . 'ms',
                    'threshold' => self::PERFORMANCE_THRESHOLDS['query_time']['warning'] . 'ms'
                ]);
            }

            if ($query->time > self::PERFORMANCE_THRESHOLDS['query_time']['critical']) {
                Log::critical('Critical Slow Query', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time . 'ms',
                    'threshold' => self::PERFORMANCE_THRESHOLDS['query_time']['critical'] . 'ms'
                ]);
            }
        });
    }

    /**
     * 성능 메트릭 계산
     */
    private function calculateMetrics(array $data): array
    {
        $responseTime = ($data['end_time'] - $data['start_time']) * 1000; // ms
        $memoryUsage = $data['end_memory'] - $data['start_memory'];
        $peakMemoryUsage = $data['end_peak_memory'] - $data['start_peak_memory'];
        
        $queryLog = DB::getQueryLog();
        $queryCount = count($queryLog) - $data['query_start_count'];
        $totalQueryTime = collect($queryLog)->sum('time');

        return [
            'response_time' => round($responseTime, 2),
            'memory_usage' => $memoryUsage,
            'peak_memory_usage' => $peakMemoryUsage,
            'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'peak_memory_usage_mb' => round($peakMemoryUsage / 1024 / 1024, 2),
            'query_count' => $queryCount,
            'query_time' => round($totalQueryTime, 2),
            'queries' => array_slice($queryLog, $data['query_start_count']),
            'timestamp' => now(),
        ];
    }

    /**
     * 성능 메트릭 로깅
     */
    private function logPerformanceMetrics(Request $request, SymfonyResponse $response, array $metrics): void
    {
        $logData = [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'status_code' => $response->getStatusCode(),
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'user_id' => auth()->id(),
            'response_time' => $metrics['response_time'],
            'memory_usage_mb' => $metrics['memory_usage_mb'],
            'peak_memory_usage_mb' => $metrics['peak_memory_usage_mb'],
            'query_count' => $metrics['query_count'],
            'query_time' => $metrics['query_time'],
        ];

        // 일반 성능 로그
        Log::channel('performance')->info('Request Performance', $logData);

        // 성능 임계치 초과 시 경고 로그
        if ($this->isPerformanceCritical($metrics)) {
            Log::warning('Critical Performance Issue', array_merge($logData, [
                'critical_metrics' => $this->getCriticalMetrics($metrics),
                'slow_queries' => $this->getSlowQueries($metrics['queries']),
            ]));
        }
    }

    /**
     * 실시간 모니터링 데이터 업데이트
     */
    private function updateRealtimeMetrics(array $metrics): void
    {
        $cacheKey = 'performance.realtime.metrics';
        $ttl = 300; // 5분

        // 현재 시간 기준 1분 단위로 메트릭 집계
        $minuteKey = now()->format('Y-m-d H:i');
        
        $realtimeData = Cache::get($cacheKey, []);
        
        if (!isset($realtimeData[$minuteKey])) {
            $realtimeData[$minuteKey] = [
                'request_count' => 0,
                'total_response_time' => 0,
                'total_memory_usage' => 0,
                'total_query_count' => 0,
                'total_query_time' => 0,
                'error_count' => 0,
            ];
        }

        $realtimeData[$minuteKey]['request_count']++;
        $realtimeData[$minuteKey]['total_response_time'] += $metrics['response_time'];
        $realtimeData[$minuteKey]['total_memory_usage'] += $metrics['memory_usage_mb'];
        $realtimeData[$minuteKey]['total_query_count'] += $metrics['query_count'];
        $realtimeData[$minuteKey]['total_query_time'] += $metrics['query_time'];

        // 5분 이상 된 데이터는 제거
        $cutoffTime = now()->subMinutes(5)->format('Y-m-d H:i');
        $realtimeData = array_filter($realtimeData, function ($key) use ($cutoffTime) {
            return $key >= $cutoffTime;
        }, ARRAY_FILTER_USE_KEY);

        Cache::put($cacheKey, $realtimeData, $ttl);

        // 성능 통계 업데이트
        $this->updatePerformanceStatistics($metrics);
    }

    /**
     * 성능 통계 업데이트
     */
    private function updatePerformanceStatistics(array $metrics): void
    {
        $statsKey = 'performance.daily.stats.' . now()->format('Y-m-d');
        $ttl = 86400; // 24시간

        $stats = Cache::get($statsKey, [
            'total_requests' => 0,
            'total_response_time' => 0,
            'total_memory_usage' => 0,
            'total_query_count' => 0,
            'total_query_time' => 0,
            'max_response_time' => 0,
            'max_memory_usage' => 0,
            'max_query_count' => 0,
            'slow_requests' => 0,
            'critical_requests' => 0,
        ]);

        $stats['total_requests']++;
        $stats['total_response_time'] += $metrics['response_time'];
        $stats['total_memory_usage'] += $metrics['memory_usage_mb'];
        $stats['total_query_count'] += $metrics['query_count'];
        $stats['total_query_time'] += $metrics['query_time'];

        $stats['max_response_time'] = max($stats['max_response_time'], $metrics['response_time']);
        $stats['max_memory_usage'] = max($stats['max_memory_usage'], $metrics['memory_usage_mb']);
        $stats['max_query_count'] = max($stats['max_query_count'], $metrics['query_count']);

        if ($metrics['response_time'] > self::PERFORMANCE_THRESHOLDS['response_time']['warning']) {
            $stats['slow_requests']++;
        }

        if ($this->isPerformanceCritical($metrics)) {
            $stats['critical_requests']++;
        }

        Cache::put($statsKey, $stats, $ttl);
    }

    /**
     * 성능 임계치 확인 및 알림
     */
    private function checkPerformanceThresholds(Request $request, array $metrics): void
    {
        $alerts = [];

        // 응답 시간 확인
        if ($metrics['response_time'] > self::PERFORMANCE_THRESHOLDS['response_time']['critical']) {
            $alerts[] = [
                'type' => 'critical',
                'metric' => 'response_time',
                'value' => $metrics['response_time'],
                'threshold' => self::PERFORMANCE_THRESHOLDS['response_time']['critical'],
                'message' => '응답 시간이 임계치를 초과했습니다.',
            ];
        } elseif ($metrics['response_time'] > self::PERFORMANCE_THRESHOLDS['response_time']['warning']) {
            $alerts[] = [
                'type' => 'warning',
                'metric' => 'response_time',
                'value' => $metrics['response_time'],
                'threshold' => self::PERFORMANCE_THRESHOLDS['response_time']['warning'],
                'message' => '응답 시간이 경고 수준입니다.',
            ];
        }

        // 메모리 사용량 확인
        if ($metrics['memory_usage'] > self::PERFORMANCE_THRESHOLDS['memory_usage']['critical']) {
            $alerts[] = [
                'type' => 'critical',
                'metric' => 'memory_usage',
                'value' => $metrics['memory_usage_mb'],
                'threshold' => self::PERFORMANCE_THRESHOLDS['memory_usage']['critical'] / 1024 / 1024,
                'message' => '메모리 사용량이 임계치를 초과했습니다.',
            ];
        } elseif ($metrics['memory_usage'] > self::PERFORMANCE_THRESHOLDS['memory_usage']['warning']) {
            $alerts[] = [
                'type' => 'warning',
                'metric' => 'memory_usage',
                'value' => $metrics['memory_usage_mb'],
                'threshold' => self::PERFORMANCE_THRESHOLDS['memory_usage']['warning'] / 1024 / 1024,
                'message' => '메모리 사용량이 경고 수준입니다.',
            ];
        }

        // 쿼리 수 확인
        if ($metrics['query_count'] > self::PERFORMANCE_THRESHOLDS['query_count']['critical']) {
            $alerts[] = [
                'type' => 'critical',
                'metric' => 'query_count',
                'value' => $metrics['query_count'],
                'threshold' => self::PERFORMANCE_THRESHOLDS['query_count']['critical'],
                'message' => '쿼리 수가 임계치를 초과했습니다.',
            ];
        } elseif ($metrics['query_count'] > self::PERFORMANCE_THRESHOLDS['query_count']['warning']) {
            $alerts[] = [
                'type' => 'warning',
                'metric' => 'query_count',
                'value' => $metrics['query_count'],
                'threshold' => self::PERFORMANCE_THRESHOLDS['query_count']['warning'],
                'message' => '쿼리 수가 경고 수준입니다.',
            ];
        }

        // 알림 처리
        if (!empty($alerts)) {
            $this->processPerformanceAlerts($request, $alerts, $metrics);
        }
    }

    /**
     * 성능 알림 처리
     */
    private function processPerformanceAlerts(Request $request, array $alerts, array $metrics): void
    {
        foreach ($alerts as $alert) {
            $logLevel = $alert['type'] === 'critical' ? 'critical' : 'warning';
            
            Log::$logLevel('Performance Alert', [
                'alert' => $alert,
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'metrics' => $metrics,
            ]);

            // 캐시에 알림 저장 (관리자 대시보드에서 확인용)
            $this->storeAlert($alert, $request, $metrics);
        }

        // 임계 수준의 성능 문제 발생 시 즉시 알림
        $criticalAlerts = array_filter($alerts, fn($alert) => $alert['type'] === 'critical');
        if (!empty($criticalAlerts)) {
            $this->sendCriticalPerformanceAlert($request, $criticalAlerts, $metrics);
        }
    }

    /**
     * 알림 저장
     */
    private function storeAlert(array $alert, Request $request, array $metrics): void
    {
        $alertKey = 'performance.alerts.' . now()->format('Y-m-d');
        $ttl = 86400; // 24시간

        $alerts = Cache::get($alertKey, []);
        
        $alerts[] = [
            'timestamp' => now()->toISOString(),
            'alert' => $alert,
            'request' => [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
            ],
            'metrics' => $metrics,
        ];

        // 최대 1000개의 알림만 저장
        if (count($alerts) > 1000) {
            $alerts = array_slice($alerts, -1000);
        }

        Cache::put($alertKey, $alerts, $ttl);
    }

    /**
     * 임계 성능 알림 발송
     */
    private function sendCriticalPerformanceAlert(Request $request, array $criticalAlerts, array $metrics): void
    {
        // 중복 알림 방지를 위한 쿨다운 확인
        $cooldownKey = 'performance.alert.cooldown.' . md5($request->fullUrl());
        if (Cache::has($cooldownKey)) {
            return;
        }

        // 5분 쿨다운 설정
        Cache::put($cooldownKey, true, 300);

        // 관리자에게 알림 발송 (이메일, 슬랙 등)
        // 실제 구현에서는 이메일 발송이나 외부 모니터링 도구 연동
        Log::critical('Critical Performance Alert Sent', [
            'alerts' => $criticalAlerts,
            'url' => $request->fullUrl(),
            'metrics' => $metrics,
        ]);
    }

    /**
     * 응답 헤더에 성능 정보 추가
     */
    private function addPerformanceHeaders(SymfonyResponse $response, array $metrics): void
    {
        $response->headers->set('X-Performance-Response-Time', $metrics['response_time'] . 'ms');
        $response->headers->set('X-Performance-Memory-Usage', $metrics['memory_usage_mb'] . 'MB');
        $response->headers->set('X-Performance-Query-Count', (string) $metrics['query_count']);
        $response->headers->set('X-Performance-Query-Time', $metrics['query_time'] . 'ms');
    }

    /**
     * 성능이 임계 수준인지 확인
     */
    private function isPerformanceCritical(array $metrics): bool
    {
        return $metrics['response_time'] > self::PERFORMANCE_THRESHOLDS['response_time']['critical']
            || $metrics['memory_usage'] > self::PERFORMANCE_THRESHOLDS['memory_usage']['critical']
            || $metrics['query_count'] > self::PERFORMANCE_THRESHOLDS['query_count']['critical']
            || $metrics['query_time'] > self::PERFORMANCE_THRESHOLDS['query_time']['critical'];
    }

    /**
     * 임계치를 초과한 메트릭 반환
     */
    private function getCriticalMetrics(array $metrics): array
    {
        $critical = [];

        if ($metrics['response_time'] > self::PERFORMANCE_THRESHOLDS['response_time']['critical']) {
            $critical['response_time'] = [
                'value' => $metrics['response_time'],
                'threshold' => self::PERFORMANCE_THRESHOLDS['response_time']['critical'],
            ];
        }

        if ($metrics['memory_usage'] > self::PERFORMANCE_THRESHOLDS['memory_usage']['critical']) {
            $critical['memory_usage'] = [
                'value' => $metrics['memory_usage_mb'],
                'threshold' => self::PERFORMANCE_THRESHOLDS['memory_usage']['critical'] / 1024 / 1024,
            ];
        }

        if ($metrics['query_count'] > self::PERFORMANCE_THRESHOLDS['query_count']['critical']) {
            $critical['query_count'] = [
                'value' => $metrics['query_count'],
                'threshold' => self::PERFORMANCE_THRESHOLDS['query_count']['critical'],
            ];
        }

        if ($metrics['query_time'] > self::PERFORMANCE_THRESHOLDS['query_time']['critical']) {
            $critical['query_time'] = [
                'value' => $metrics['query_time'],
                'threshold' => self::PERFORMANCE_THRESHOLDS['query_time']['critical'],
            ];
        }

        return $critical;
    }

    /**
     * 느린 쿼리 추출
     */
    private function getSlowQueries(array $queries): array
    {
        return array_filter($queries, function ($query) {
            return $query['time'] > self::PERFORMANCE_THRESHOLDS['query_time']['warning'];
        });
    }

    /**
     * 성능 통계 조회 (관리자 대시보드용)
     */
    public static function getPerformanceStats(?string $date = null): array
    {
        $date = $date ?: now()->format('Y-m-d');
        $statsKey = "performance.daily.stats.{$date}";
        
        $stats = Cache::get($statsKey, [
            'total_requests' => 0,
            'total_response_time' => 0,
            'total_memory_usage' => 0,
            'total_query_count' => 0,
            'total_query_time' => 0,
            'max_response_time' => 0,
            'max_memory_usage' => 0,
            'max_query_count' => 0,
            'slow_requests' => 0,
            'critical_requests' => 0,
        ]);

        // 평균값 계산
        if ($stats['total_requests'] > 0) {
            $stats['avg_response_time'] = round($stats['total_response_time'] / $stats['total_requests'], 2);
            $stats['avg_memory_usage'] = round($stats['total_memory_usage'] / $stats['total_requests'], 2);
            $stats['avg_query_count'] = round($stats['total_query_count'] / $stats['total_requests'], 2);
            $stats['avg_query_time'] = round($stats['total_query_time'] / $stats['total_requests'], 2);
        } else {
            $stats['avg_response_time'] = 0;
            $stats['avg_memory_usage'] = 0;
            $stats['avg_query_count'] = 0;
            $stats['avg_query_time'] = 0;
        }

        return $stats;
    }

    /**
     * 실시간 성능 메트릭 조회
     */
    public static function getRealtimeMetrics(): array
    {
        $cacheKey = 'performance.realtime.metrics';
        $data = Cache::get($cacheKey, []);

        $metrics = [];
        foreach ($data as $minute => $stats) {
            $requestCount = $stats['request_count'];
            
            $metrics[] = [
                'timestamp' => $minute,
                'request_count' => $requestCount,
                'avg_response_time' => $requestCount > 0 ? round($stats['total_response_time'] / $requestCount, 2) : 0,
                'avg_memory_usage' => $requestCount > 0 ? round($stats['total_memory_usage'] / $requestCount, 2) : 0,
                'avg_query_count' => $requestCount > 0 ? round($stats['total_query_count'] / $requestCount, 2) : 0,
                'avg_query_time' => $requestCount > 0 ? round($stats['total_query_time'] / $requestCount, 2) : 0,
                'error_count' => $stats['error_count'],
            ];
        }

        return $metrics;
    }

    /**
     * 성능 알림 목록 조회
     */
    public static function getPerformanceAlerts(?string $date = null): array
    {
        $date = $date ?: now()->format('Y-m-d');
        $alertKey = "performance.alerts.{$date}";
        
        return Cache::get($alertKey, []);
    }
};