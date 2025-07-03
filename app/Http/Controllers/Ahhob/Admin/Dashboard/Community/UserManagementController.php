<?php

namespace App\Http\Controllers\Ahhob\Admin\Dashboard\Community;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Ahhob\Community\PointHistory;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class UserManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /*
    |--------------------------------------------------------------------------
    | 회원 관리 (User Management)
    |--------------------------------------------------------------------------
    */

    /**
     * 회원 목록
     */
    public function index(Request $request): View
    {
        $query = User::query();

        // 검색 조건
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // 상태 필터
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // 가입일 필터
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        // 정렬
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $users = $query->paginate(20);

        return view('ahhob.admin.dashboard.community.users.index', compact('users'));
    }

    /**
     * 회원 상세보기
     */
    public function show(User $user): View
    {
        // 활동 통계
        $activityStats = $this->getUserActivityStats($user->id);
        
        // 최근 활동 로그
        $recentActivities = $this->getRecentActivities($user->id);
        
        // 포인트 내역
        $pointHistories = PointHistory::where('user_id', $user->id)
            ->with('pointable')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('ahhob.admin.dashboard.community.users.show', compact(
            'user',
            'activityStats',
            'recentActivities',
            'pointHistories'
        ));
    }

    /**
     * 회원 상태 변경 (활성/비활성/정지)
     */
    public function updateStatus(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:active,inactive,suspended',
            'reason' => 'nullable|string|max:255',
        ]);

        $user->update([
            'status' => $request->status,
            'status_reason' => $request->reason,
            'status_updated_at' => now(),
        ]);

        // 활동 로그 기록
        $this->logUserActivity($user, 'status_updated', [
            'old_status' => $user->getOriginal('status'),
            'new_status' => $request->status,
            'reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => '회원 상태가 변경되었습니다.',
        ]);
    }

    /**
     * 포인트 조정
     */
    public function updatePoints(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:add,subtract,set',
            'points' => 'required|integer|min:0',
            'reason' => 'required|string|max:255',
        ]);

        $oldPoints = $user->points;
        $newPoints = match($request->type) {
            'add' => $oldPoints + $request->points,
            'subtract' => max(0, $oldPoints - $request->points),
            'set' => $request->points,
        };

        $user->update(['points' => $newPoints]);

        // 포인트 내역 기록
        PointHistory::create([
            'user_id' => $user->id,
            'type' => $request->type === 'subtract' ? 'deduction' : 'reward',
            'points' => $request->type === 'subtract' ? -$request->points : $request->points,
            'reason' => $request->reason,
            'pointable_type' => 'admin_adjustment',
            'pointable_id' => auth('admin')->id(),
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => '포인트가 조정되었습니다.',
            'old_points' => $oldPoints,
            'new_points' => $newPoints,
        ]);
    }

    /**
     * 회원 차단
     */
    public function ban(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:255',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $user->update([
            'status' => 'banned',
            'banned_at' => now(),
            'banned_reason' => $request->reason,
            'banned_expires_at' => $request->expires_at,
        ]);

        $this->logUserActivity($user, 'banned', [
            'reason' => $request->reason,
            'expires_at' => $request->expires_at,
        ]);

        return response()->json([
            'success' => true,
            'message' => '회원이 차단되었습니다.',
        ]);
    }

    /**
     * 회원 차단 해제
     */
    public function unban(User $user): JsonResponse
    {
        $user->update([
            'status' => 'active',
            'banned_at' => null,
            'banned_reason' => null,
            'banned_expires_at' => null,
        ]);

        $this->logUserActivity($user, 'unbanned');

        return response()->json([
            'success' => true,
            'message' => '회원 차단이 해제되었습니다.',
        ]);
    }

    /**
     * 회원 활동 로그
     */
    public function activityLog(User $user): JsonResponse
    {
        $activities = collect();

        // 게시글 작성 활동
        $postActivities = $this->getUserPostActivities($user->id);
        $activities = $activities->merge($postActivities);

        // 댓글 작성 활동
        $commentActivities = $this->getUserCommentActivities($user->id);
        $activities = $activities->merge($commentActivities);

        // 포인트 내역
        $pointActivities = PointHistory::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($history) {
                return [
                    'type' => 'point_' . $history->type,
                    'description' => $history->reason,
                    'points' => $history->points,
                    'created_at' => $history->created_at,
                ];
            });
        $activities = $activities->merge($pointActivities);

        // 시간순 정렬
        $activities = $activities->sortByDesc('created_at')->take(100);

        return response()->json($activities->values());
    }

    /*
    |--------------------------------------------------------------------------
    | 회원 통계 (User Statistics)
    |--------------------------------------------------------------------------
    */

    /**
     * 회원 통계
     */
    public function userStatistics(): View
    {
        $stats = Cache::remember('admin.user.statistics', 1800, function () {
            $now = now();
            $today = $now->copy()->startOfDay();
            $yesterday = $now->copy()->subDay()->startOfDay();
            $thisWeek = $now->copy()->startOfWeek();
            $thisMonth = $now->copy()->startOfMonth();

            return [
                'overview' => [
                    'total_users' => User::count(),
                    'active_users' => User::where('status', 'active')->count(),
                    'new_today' => User::whereDate('created_at', $today)->count(),
                    'new_this_week' => User::where('created_at', '>=', $thisWeek)->count(),
                    'new_this_month' => User::where('created_at', '>=', $thisMonth)->count(),
                ],
                'status_distribution' => User::select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray(),
                'registration_trend' => $this->getRegistrationTrend(30),
                'top_users_by_points' => User::orderBy('points', 'desc')->limit(10)->get(),
            ];
        });

        return view('ahhob.admin.dashboard.community.users.statistics', compact('stats'));
    }

    /*
    |--------------------------------------------------------------------------
    | 헬퍼 메서드 (Helper Methods)
    |--------------------------------------------------------------------------
    */

    /**
     * 사용자 활동 통계
     */
    private function getUserActivityStats(int $userId): array
    {
        return Cache::remember("user.activity.stats.{$userId}", 900, function () use ($userId) {
            $postCount = $this->getUserPostCount($userId);
            $commentCount = $this->getUserCommentCount($userId);
            $totalPoints = PointHistory::where('user_id', $userId)->sum('points');

            return [
                'total_posts' => $postCount,
                'total_comments' => $commentCount,
                'total_points_earned' => max(0, $totalPoints),
                'last_activity' => $this->getLastActivity($userId),
            ];
        });
    }

    /**
     * 사용자 게시글 수
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
     * 사용자 댓글 수
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
     * 최근 활동 조회
     */
    private function getRecentActivities(int $userId, int $limit = 10): array
    {
        $activities = collect();

        // 최근 게시글
        $recentPosts = $this->getUserPostActivities($userId, $limit);
        $activities = $activities->merge($recentPosts);

        // 최근 댓글
        $recentComments = $this->getUserCommentActivities($userId, $limit);
        $activities = $activities->merge($recentComments);

        return $activities->sortByDesc('created_at')->take($limit)->values()->toArray();
    }

    /**
     * 사용자 게시글 활동
     */
    private function getUserPostActivities(int $userId, int $limit = 10): array
    {
        $activities = [];
        $boards = DB::table('boards')->where('is_active', true)->get();

        foreach ($boards as $board) {
            $tableName = "board_{$board->slug}";
            if (DB::getSchemaBuilder()->hasTable($tableName)) {
                $posts = DB::table($tableName)
                    ->where('user_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();

                foreach ($posts as $post) {
                    $activities[] = [
                        'type' => 'post_created',
                        'description' => "[{$board->name}] {$post->title}",
                        'board_name' => $board->name,
                        'created_at' => Carbon::parse($post->created_at),
                    ];
                }
            }
        }

        return $activities;
    }

    /**
     * 사용자 댓글 활동
     */
    private function getUserCommentActivities(int $userId, int $limit = 10): array
    {
        $activities = [];
        $boards = DB::table('boards')->where('is_active', true)->get();

        foreach ($boards as $board) {
            $tableName = "board_{$board->slug}_comments";
            if (DB::getSchemaBuilder()->hasTable($tableName)) {
                $comments = DB::table($tableName)
                    ->where('user_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();

                foreach ($comments as $comment) {
                    $activities[] = [
                        'type' => 'comment_created',
                        'description' => "[{$board->name}] 댓글: " . str_limit($comment->content, 50),
                        'board_name' => $board->name,
                        'created_at' => Carbon::parse($comment->created_at),
                    ];
                }
            }
        }

        return $activities;
    }

    /**
     * 마지막 활동 시간
     */
    private function getLastActivity(int $userId): ?Carbon
    {
        // 여러 활동 중 가장 최근 활동 시간 조회
        $lastPost = $this->getLastPostTime($userId);
        $lastComment = $this->getLastCommentTime($userId);
        $lastPoint = PointHistory::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->value('created_at');

        $activities = array_filter([$lastPost, $lastComment, $lastPoint]);
        
        return $activities ? Carbon::parse(max($activities)) : null;
    }

    /**
     * 마지막 게시글 작성 시간
     */
    private function getLastPostTime(int $userId): ?string
    {
        $lastTime = null;
        $boards = DB::table('boards')->where('is_active', true)->get();

        foreach ($boards as $board) {
            $tableName = "board_{$board->slug}";
            if (DB::getSchemaBuilder()->hasTable($tableName)) {
                $time = DB::table($tableName)
                    ->where('user_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->value('created_at');

                if ($time && (!$lastTime || $time > $lastTime)) {
                    $lastTime = $time;
                }
            }
        }

        return $lastTime;
    }

    /**
     * 마지막 댓글 작성 시간
     */
    private function getLastCommentTime(int $userId): ?string
    {
        $lastTime = null;
        $boards = DB::table('boards')->where('is_active', true)->get();

        foreach ($boards as $board) {
            $tableName = "board_{$board->slug}_comments";
            if (DB::getSchemaBuilder()->hasTable($tableName)) {
                $time = DB::table($tableName)
                    ->where('user_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->value('created_at');

                if ($time && (!$lastTime || $time > $lastTime)) {
                    $lastTime = $time;
                }
            }
        }

        return $lastTime;
    }

    /**
     * 가입 트렌드 데이터
     */
    private function getRegistrationTrend(int $days): array
    {
        $dates = [];
        $counts = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dates[] = $date->format('m/d');
            $counts[] = User::whereDate('created_at', $date)->count();
        }

        return [
            'labels' => $dates,
            'data' => $counts,
        ];
    }

    /**
     * 사용자 활동 로그 기록
     */
    private function logUserActivity(User $user, string $action, array $data = []): void
    {
        // 사용자 활동 로그 기록 (향후 구현)
        // UserActivityLog::create([...]);
    }
}