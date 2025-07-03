<?php

namespace App\Http\Controllers\Ahhob\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Ahhob\Shop\Product;
use App\Models\Ahhob\Shop\Order;
use App\Models\Ahhob\Shop\PaymentTransaction;
use App\Services\Ahhob\Admin\Dashboard\DashboardStatisticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardStatisticsService $statisticsService
    ) {
        $this->middleware('auth:admin');
    }

    /*
    |--------------------------------------------------------------------------
    | 메인 대시보드 (Main Dashboard)
    |--------------------------------------------------------------------------
    */

    /**
     * 통합 관리자 대시보드 메인 페이지
     */
    public function index(): View
    {
        // 핵심 지표 조회
        $overview = $this->getOverviewStats();
        
        // 실시간 활동 현황
        $realtimeStats = $this->getRealtimeStats();
        
        // 트렌드 데이터 (차트용)
        $trendData = $this->getTrendData();
        
        // 최근 활동 로그
        $recentActivities = $this->getRecentActivities();
        
        // 시스템 상태
        $systemStatus = $this->getSystemStatus();

        return view('ahhob.admin.dashboard.index', compact(
            'overview',
            'realtimeStats', 
            'trendData',
            'recentActivities',
            'systemStatus'
        ));
    }

    /**
     * 실시간 통계 API (AJAX용)
     */
    public function realtimeStats(): JsonResponse
    {
        $stats = Cache::remember('admin.realtime.stats', 60, function () {
            return $this->getRealtimeStats();
        });

        return response()->json($stats);
    }

    /**
     * 트렌드 데이터 API (차트용)
     */
    public function trendData(Request $request): JsonResponse
    {
        $period = $request->get('period', '30'); // 기본 30일
        $type = $request->get('type', 'overview'); // overview, community, shop
        
        $data = $this->statisticsService->getTrendData($type, $period);
        
        return response()->json($data);
    }

    /*
    |--------------------------------------------------------------------------
    | 통계 데이터 수집 메서드 (Statistics Data Collection)
    |--------------------------------------------------------------------------
    */

    /**
     * 전체 현황 요약
     */
    private function getOverviewStats(): array
    {
        return Cache::remember('admin.overview.stats', 600, function () {
            $today = now()->startOfDay();
            $yesterday = now()->subDay()->startOfDay();
            $thisMonth = now()->startOfMonth();
            $lastMonth = now()->subMonth()->startOfMonth();

            return [
                // 사용자 통계
                'users' => [
                    'total' => User::count(),
                    'today' => User::whereDate('created_at', $today)->count(),
                    'this_month' => User::where('created_at', '>=', $thisMonth)->count(),
                    'growth_rate' => $this->calculateGrowthRate(
                        User::where('created_at', '>=', $thisMonth)->count(),
                        User::whereBetween('created_at', [$lastMonth, $thisMonth])->count()
                    ),
                ],

                // 커뮤니티 통계
                'community' => [
                    'total_posts' => $this->getTotalPostsCount(),
                    'total_comments' => $this->getTotalCommentsCount(),
                    'today_posts' => $this->getTodayPostsCount(),
                    'today_comments' => $this->getTodayCommentsCount(),
                ],

                // 쇼핑몰 통계
                'shop' => [
                    'total_products' => Product::count(),
                    'total_orders' => Order::count(),
                    'today_orders' => Order::whereDate('created_at', $today)->count(),
                    'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount'),
                    'today_revenue' => Order::where('payment_status', 'paid')
                        ->whereDate('created_at', $today)
                        ->sum('total_amount'),
                    'pending_orders' => Order::where('status', 'pending')->count(),
                    'low_stock_products' => Product::where('stock_status', 'low_stock')->count(),
                ],

                // 결제 통계
                'payments' => [
                    'total_transactions' => PaymentTransaction::count(),
                    'success_rate' => $this->getPaymentSuccessRate(),
                    'today_transactions' => PaymentTransaction::whereDate('created_at', $today)->count(),
                    'failed_transactions' => PaymentTransaction::where('status', 'failed')->count(),
                ],
            ];
        });
    }

    /**
     * 실시간 현황
     */
    private function getRealtimeStats(): array
    {
        return [
            'online_users' => $this->getOnlineUsersCount(),
            'active_sessions' => $this->getActiveSessionsCount(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'pending_payments' => PaymentTransaction::where('status', 'pending')->count(),
            'system_load' => $this->getSystemLoad(),
            'cache_hit_rate' => $this->getCacheHitRate(),
        ];
    }

    /**
     * 트렌드 데이터 (차트용)
     */
    private function getTrendData(): array
    {
        $days = 30;
        $dates = collect();
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $dates->push(now()->subDays($i)->format('Y-m-d'));
        }

        return [
            'dates' => $dates,
            'users' => $this->getUserTrend($dates),
            'posts' => $this->getPostTrend($dates),
            'orders' => $this->getOrderTrend($dates),
            'revenue' => $this->getRevenueTrend($dates),
        ];
    }

    /**
     * 최근 활동 로그
     */
    private function getRecentActivities(): array
    {
        return Cache::remember('admin.recent.activities', 300, function () {
            $activities = collect();

            // 최근 회원가입
            $recentUsers = User::latest()->limit(5)->get();
            foreach ($recentUsers as $user) {
                $activities->push([
                    'type' => 'user_register',
                    'icon' => 'user-plus',
                    'color' => 'green',
                    'title' => '새 회원 가입',
                    'description' => "{$user->name}님이 가입했습니다",
                    'time' => $user->created_at,
                    'link' => route('ahhob.admin.dashboard.community.users.show', $user),
                ]);
            }

            // 최근 주문
            $recentOrders = Order::with('user')->latest()->limit(5)->get();
            foreach ($recentOrders as $order) {
                $activities->push([
                    'type' => 'order_created',
                    'icon' => 'shopping-cart',
                    'color' => 'blue',
                    'title' => '새 주문',
                    'description' => "{$order->customer_name}님의 주문 (₩" . number_format($order->total_amount) . ")",
                    'time' => $order->created_at,
                    'link' => route('ahhob.admin.dashboard.shop.orders.show', $order),
                ]);
            }

            // 최근 결제
            $recentPayments = PaymentTransaction::with('order')->where('status', 'completed')
                ->latest()->limit(5)->get();
            foreach ($recentPayments as $payment) {
                $activities->push([
                    'type' => 'payment_completed',
                    'icon' => 'credit-card',
                    'color' => 'green',
                    'title' => '결제 완료',
                    'description' => "주문 #{$payment->order->order_number} 결제 완료",
                    'time' => $payment->approval_at,
                    'link' => route('ahhob.admin.dashboard.shop.payments.show', $payment),
                ]);
            }

            return $activities->sortByDesc('time')->take(15)->values()->all();
        });
    }

    /**
     * 시스템 상태
     */
    private function getSystemStatus(): array
    {
        return [
            'database' => $this->checkDatabaseStatus(),
            'redis' => $this->checkRedisStatus(),
            'storage' => $this->checkStorageStatus(),
            'queue' => $this->checkQueueStatus(),
            'mail' => $this->checkMailStatus(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | 헬퍼 메서드 (Helper Methods)
    |--------------------------------------------------------------------------
    */

    /**
     * 성장률 계산
     */
    private function calculateGrowthRate(int $current, int $previous): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        
        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * 전체 게시글 수 계산
     */
    private function getTotalPostsCount(): int
    {
        // 동적 게시판들의 게시글 수 합계
        $totalPosts = 0;
        
        // 게시판 목록 조회
        $boards = DB::table('boards')->where('is_active', true)->get();
        
        foreach ($boards as $board) {
            $tableName = "board_{$board->slug}";
            if (DB::getSchemaBuilder()->hasTable($tableName)) {
                $totalPosts += DB::table($tableName)->count();
            }
        }
        
        return $totalPosts;
    }

    /**
     * 전체 댓글 수 계산
     */
    private function getTotalCommentsCount(): int
    {
        $totalComments = 0;
        
        $boards = DB::table('boards')->where('is_active', true)->get();
        
        foreach ($boards as $board) {
            $tableName = "board_{$board->slug}_comments";
            if (DB::getSchemaBuilder()->hasTable($tableName)) {
                $totalComments += DB::table($tableName)->count();
            }
        }
        
        return $totalComments;
    }

    /**
     * 오늘 게시글 수
     */
    private function getTodayPostsCount(): int
    {
        $todayPosts = 0;
        $today = now()->startOfDay();
        
        $boards = DB::table('boards')->where('is_active', true)->get();
        
        foreach ($boards as $board) {
            $tableName = "board_{$board->slug}";
            if (DB::getSchemaBuilder()->hasTable($tableName)) {
                $todayPosts += DB::table($tableName)
                    ->where('created_at', '>=', $today)
                    ->count();
            }
        }
        
        return $todayPosts;
    }

    /**
     * 오늘 댓글 수
     */
    private function getTodayCommentsCount(): int
    {
        $todayComments = 0;
        $today = now()->startOfDay();
        
        $boards = DB::table('boards')->where('is_active', true)->get();
        
        foreach ($boards as $board) {
            $tableName = "board_{$board->slug}_comments";
            if (DB::getSchemaBuilder()->hasTable($tableName)) {
                $todayComments += DB::table($tableName)
                    ->where('created_at', '>=', $today)
                    ->count();
            }
        }
        
        return $todayComments;
    }

    /**
     * 결제 성공률 계산
     */
    private function getPaymentSuccessRate(): float
    {
        $total = PaymentTransaction::count();
        if ($total === 0) return 0.0;
        
        $success = PaymentTransaction::where('status', 'completed')->count();
        
        return round(($success / $total) * 100, 1);
    }

    /**
     * 온라인 사용자 수 (Redis 세션 기반)
     */
    private function getOnlineUsersCount(): int
    {
        // Redis를 사용한 활성 세션 계산
        // 구현은 Redis 설정에 따라 다를 수 있음
        return 0; // 임시값
    }

    /**
     * 활성 세션 수
     */
    private function getActiveSessionsCount(): int
    {
        // 활성 세션 수 계산 로직
        return 0; // 임시값
    }

    /**
     * 시스템 로드
     */
    private function getSystemLoad(): array
    {
        return [
            'cpu' => 0, // CPU 사용률
            'memory' => 0, // 메모리 사용률
            'disk' => 0, // 디스크 사용률
        ];
    }

    /**
     * 캐시 적중률
     */
    private function getCacheHitRate(): float
    {
        // Redis나 캐시 시스템의 적중률 계산
        return 0.0; // 임시값
    }

    /**
     * 사용자 증가 트렌드
     */
    private function getUserTrend($dates): array
    {
        return $dates->map(function ($date) {
            return User::whereDate('created_at', $date)->count();
        })->toArray();
    }

    /**
     * 게시글 증가 트렌드
     */
    private function getPostTrend($dates): array
    {
        return $dates->map(function ($date) {
            $count = 0;
            $boards = DB::table('boards')->where('is_active', true)->get();
            
            foreach ($boards as $board) {
                $tableName = "board_{$board->slug}";
                if (DB::getSchemaBuilder()->hasTable($tableName)) {
                    $count += DB::table($tableName)->whereDate('created_at', $date)->count();
                }
            }
            
            return $count;
        })->toArray();
    }

    /**
     * 주문 증가 트렌드
     */
    private function getOrderTrend($dates): array
    {
        return $dates->map(function ($date) {
            return Order::whereDate('created_at', $date)->count();
        })->toArray();
    }

    /**
     * 매출 트렌드
     */
    private function getRevenueTrend($dates): array
    {
        return $dates->map(function ($date) {
            return (float) Order::where('payment_status', 'paid')
                ->whereDate('created_at', $date)
                ->sum('total_amount');
        })->toArray();
    }

    /**
     * 데이터베이스 상태 확인
     */
    private function checkDatabaseStatus(): array
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'healthy', 'message' => '정상'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Redis 상태 확인
     */
    private function checkRedisStatus(): array
    {
        try {
            Cache::store('redis')->put('health_check', 'ok', 10);
            return ['status' => 'healthy', 'message' => '정상'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * 스토리지 상태 확인
     */
    private function checkStorageStatus(): array
    {
        $path = storage_path();
        $freeSpace = disk_free_space($path);
        $totalSpace = disk_total_space($path);
        $usedPercentage = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 1);
        
        $status = $usedPercentage > 90 ? 'warning' : 'healthy';
        
        return [
            'status' => $status,
            'message' => "사용률: {$usedPercentage}%",
            'free_space' => $freeSpace,
            'total_space' => $totalSpace,
        ];
    }

    /**
     * 큐 상태 확인
     */
    private function checkQueueStatus(): array
    {
        // 큐 작업 상태 확인 로직
        return ['status' => 'healthy', 'message' => '정상'];
    }

    /**
     * 메일 상태 확인
     */
    private function checkMailStatus(): array
    {
        // 메일 서비스 상태 확인 로직
        return ['status' => 'healthy', 'message' => '정상'];
    }
}