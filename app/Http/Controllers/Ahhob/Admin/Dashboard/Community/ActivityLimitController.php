<?php

namespace App\Http\Controllers\Ahhob\Admin\Dashboard\Community;

use App\Http\Controllers\Controller;
use App\Models\Ahhob\Community\PostingLimit;
use App\Models\Ahhob\Community\DailyActivityCount;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class ActivityLimitController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /*
    |--------------------------------------------------------------------------
    | 활동 제한 관리 (Activity Limit Management)
    |--------------------------------------------------------------------------
    */

    /**
     * 활동 제한 메인 대시보드
     */
    public function index(): View
    {
        // 현재 활성 정책 수
        $activePolicies = PostingLimit::where('is_active', true)->count();
        
        // 오늘의 위반 건수
        $todayViolations = DailyActivityCount::whereDate('date', today())
            ->where('violations_count', '>', 0)
            ->count();

        // 활동 제한 적용 중인 사용자 수
        $limitedUsers = DailyActivityCount::whereDate('date', today())
            ->where('is_limited', true)
            ->count();

        // 최근 위반 사례
        $recentViolations = $this->getRecentViolations(10);

        // 제한 정책 요약
        $policyStats = $this->getPolicyStats();

        return view('ahhob.admin.dashboard.community.activity-limits.index', compact(
            'activePolicies',
            'todayViolations',
            'limitedUsers',
            'recentViolations',
            'policyStats'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | 정책 관리 (Policy Management)
    |--------------------------------------------------------------------------
    */

    /**
     * 제한 정책 목록
     */
    public function policies(Request $request): View
    {
        $query = PostingLimit::query();

        // 검색 조건
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 상태 필터
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->get('is_active'));
        }

        // 타입 필터
        if ($request->filled('target_type')) {
            $query->where('target_type', $request->get('target_type'));
        }

        // 정렬
        $sortBy = $request->get('sort', 'priority');
        $sortOrder = $request->get('order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $policies = $query->paginate(20);

        return view('ahhob.admin.dashboard.community.activity-limits.policies', compact('policies'));
    }

    /**
     * 제한 정책 생성
     */
    public function storePolicy(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'target_type' => 'required|in:user_level,board_specific,global,ip_based',
            'target_value' => 'nullable|string|max:100',
            'action_type' => 'required|in:post_create,comment_create,both',
            'limit_count' => 'required|integer|min:0',
            'time_window' => 'required|integer|min:1',
            'time_unit' => 'required|in:minute,hour,day',
            'priority' => 'required|integer|min:1|max:100',
            'is_active' => 'boolean',
        ]);

        $policy = PostingLimit::create($request->all());

        // 캐시 클리어
        Cache::forget('posting_limits.active');

        return response()->json([
            'success' => true,
            'message' => '제한 정책이 생성되었습니다.',
            'policy' => $policy,
        ]);
    }

    /**
     * 제한 정책 업데이트
     */
    public function updatePolicy(Request $request, PostingLimit $policy): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'target_type' => 'required|in:user_level,board_specific,global,ip_based',
            'target_value' => 'nullable|string|max:100',
            'action_type' => 'required|in:post_create,comment_create,both',
            'limit_count' => 'required|integer|min:0',
            'time_window' => 'required|integer|min:1',
            'time_unit' => 'required|in:minute,hour,day',
            'priority' => 'required|integer|min:1|max:100',
            'is_active' => 'boolean',
        ]);

        $policy->update($request->all());

        // 캐시 클리어
        Cache::forget('posting_limits.active');

        return response()->json([
            'success' => true,
            'message' => '제한 정책이 업데이트되었습니다.',
        ]);
    }

    /**
     * 제한 정책 삭제
     */
    public function deletePolicy(PostingLimit $policy): JsonResponse
    {
        $policy->delete();

        // 캐시 클리어
        Cache::forget('posting_limits.active');

        return response()->json([
            'success' => true,
            'message' => '제한 정책이 삭제되었습니다.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 위반 관리 (Violation Management)
    |--------------------------------------------------------------------------
    */

    /**
     * 위반 내역 조회
     */
    public function violations(Request $request): View
    {
        $query = DailyActivityCount::with('user')
            ->where('violations_count', '>', 0);

        // 날짜 필터
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->get('date_to'));
        }

        // 사용자 검색
        if ($request->filled('user_search')) {
            $search = $request->get('user_search');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // 정렬
        $sortBy = $request->get('sort', 'date');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $violations = $query->paginate(20);

        // 위반 통계
        $violationStats = $this->getViolationStats($request);

        return view('ahhob.admin.dashboard.community.activity-limits.violations', compact(
            'violations',
            'violationStats'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | IP 추적 관리 (IP Tracking Management)
    |--------------------------------------------------------------------------
    */

    /**
     * IP 추적 현황
     */
    public function ipTracking(Request $request): View
    {
        // Redis에서 활성 IP 추적 데이터 조회
        $suspiciousIps = $this->getSuspiciousIps();
        
        // IP별 활동 통계
        $ipStats = $this->getIpActivityStats($request);
        
        // 차단된 IP 목록
        $blockedIps = $this->getBlockedIps();

        return view('ahhob.admin.dashboard.community.activity-limits.ip-tracking', compact(
            'suspiciousIps',
            'ipStats',
            'blockedIps'
        ));
    }

    /**
     * IP 차단
     */
    public function blockIp(Request $request, string $ip): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:255',
            'duration' => 'nullable|integer|min:1', // 시간 단위
        ]);

        // Redis에 차단 정보 저장
        $blockData = [
            'ip' => $ip,
            'reason' => $request->reason,
            'blocked_at' => now()->toISOString(),
            'blocked_by' => auth('admin')->id(),
            'duration' => $request->duration,
        ];

        $key = "blocked_ip:{$ip}";
        $ttl = $request->duration ? $request->duration * 3600 : null; // 시간을 초로 변환

        if ($ttl) {
            Redis::setex($key, $ttl, json_encode($blockData));
        } else {
            Redis::set($key, json_encode($blockData));
        }

        return response()->json([
            'success' => true,
            'message' => "IP {$ip}가 차단되었습니다.",
        ]);
    }

    /**
     * IP 차단 해제
     */
    public function unblockIp(string $ip): JsonResponse
    {
        $key = "blocked_ip:{$ip}";
        $deleted = Redis::del($key);

        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => "IP {$ip}의 차단이 해제되었습니다.",
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => '해당 IP는 차단되지 않았습니다.',
            ], 404);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 헬퍼 메서드 (Helper Methods)
    |--------------------------------------------------------------------------
    */

    /**
     * 최근 위반 사례 조회
     */
    private function getRecentViolations(int $limit): array
    {
        return DailyActivityCount::with('user')
            ->where('violations_count', '>', 0)
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($violation) {
                return [
                    'user_name' => $violation->user->name,
                    'user_email' => $violation->user->email,
                    'violations_count' => $violation->violations_count,
                    'date' => $violation->date,
                    'last_violation_at' => $violation->updated_at,
                ];
            })
            ->toArray();
    }

    /**
     * 정책 통계
     */
    private function getPolicyStats(): array
    {
        return [
            'total_policies' => PostingLimit::count(),
            'active_policies' => PostingLimit::where('is_active', true)->count(),
            'global_policies' => PostingLimit::where('target_type', 'global')->where('is_active', true)->count(),
            'board_specific_policies' => PostingLimit::where('target_type', 'board_specific')->where('is_active', true)->count(),
            'user_level_policies' => PostingLimit::where('target_type', 'user_level')->where('is_active', true)->count(),
            'ip_based_policies' => PostingLimit::where('target_type', 'ip_based')->where('is_active', true)->count(),
        ];
    }

    /**
     * 위반 통계
     */
    private function getViolationStats(Request $request): array
    {
        $query = DailyActivityCount::where('violations_count', '>', 0);

        // 날짜 필터 적용
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->get('date_to'));
        }

        return [
            'total_violations' => $query->sum('violations_count'),
            'unique_violators' => $query->distinct('user_id')->count(),
            'daily_average' => round($query->avg('violations_count'), 2),
            'max_daily_violations' => $query->max('violations_count'),
        ];
    }

    /**
     * 의심스러운 IP 조회
     */
    private function getSuspiciousIps(): array
    {
        $suspiciousIps = [];
        
        // Redis에서 다중 계정 탐지 데이터 조회
        $keys = Redis::keys('multi_account_detection:*');
        
        foreach ($keys as $key) {
            $data = Redis::get($key);
            if ($data) {
                $decoded = json_decode($data, true);
                if ($decoded && isset($decoded['risk_score']) && $decoded['risk_score'] > 70) {
                    $suspiciousIps[] = $decoded;
                }
            }
        }

        // 위험도 순으로 정렬
        usort($suspiciousIps, function ($a, $b) {
            return $b['risk_score'] <=> $a['risk_score'];
        });

        return array_slice($suspiciousIps, 0, 20); // 상위 20개만 반환
    }

    /**
     * IP별 활동 통계
     */
    private function getIpActivityStats(Request $request): array
    {
        // 실제 구현에서는 더 복잡한 IP 활동 분석 로직 필요
        // 여기서는 기본 구조만 제공
        
        $dateFrom = $request->get('date_from', now()->subDays(7)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        // 활동이 많은 IP 상위 목록 (실제로는 로그 테이블에서 조회)
        return [
            'top_active_ips' => [],
            'new_ips_today' => 0,
            'total_unique_ips' => 0,
        ];
    }

    /**
     * 차단된 IP 목록 조회
     */
    private function getBlockedIps(): array
    {
        $blockedIps = [];
        $keys = Redis::keys('blocked_ip:*');
        
        foreach ($keys as $key) {
            $data = Redis::get($key);
            if ($data) {
                $decoded = json_decode($data, true);
                if ($decoded) {
                    $decoded['ttl'] = Redis::ttl($key); // 남은 시간
                    $blockedIps[] = $decoded;
                }
            }
        }

        // 차단 시간 순으로 정렬
        usort($blockedIps, function ($a, $b) {
            return $b['blocked_at'] <=> $a['blocked_at'];
        });

        return $blockedIps;
    }
}