<?php

namespace App\Http\Controllers\Ahhob\Admin\Dashboard\Community;

use App\Http\Controllers\Controller;
use App\Models\Ahhob\Board\Board;
use App\Models\Ahhob\Board\BoardGroup;
use App\Models\Ahhob\Board\BoardManager;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class BoardManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /*
    |--------------------------------------------------------------------------
    | 게시판 관리 (Board Management)
    |--------------------------------------------------------------------------
    */

    /**
     * 게시판 목록
     */
    public function index(Request $request): View
    {
        $query = Board::with(['group', 'managers.user']);

        // 검색 조건
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 그룹 필터
        if ($request->filled('group_id')) {
            $query->where('group_id', $request->get('group_id'));
        }

        // 상태 필터
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->get('is_active'));
        }

        // 정렬
        $sortBy = $request->get('sort', 'sort_order');
        $sortOrder = $request->get('order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $boards = $query->paginate(20);
        $groups = BoardGroup::orderBy('sort_order')->get();

        return view('ahhob.admin.dashboard.community.boards.index', compact('boards', 'groups'));
    }

    /**
     * 게시판 상세보기
     */
    public function show(Board $board): View
    {
        $board->load(['group', 'managers.user']);
        
        // 게시판 통계
        $stats = $this->getBoardStats($board);
        
        // 최근 게시글/댓글
        $recentPosts = $this->getRecentPosts($board->slug, 10);
        $recentComments = $this->getRecentComments($board->slug, 10);

        return view('ahhob.admin.dashboard.community.boards.show', compact(
            'board',
            'stats',
            'recentPosts',
            'recentComments'
        ));
    }

    /**
     * 게시판 설정 업데이트
     */
    public function updateSettings(Request $request, Board $board): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'posts_per_page' => 'integer|min:5|max:100',
            'allow_comments' => 'boolean',
            'allow_attachments' => 'boolean',
            'require_login_to_read' => 'boolean',
            'require_login_to_write' => 'boolean',
        ]);

        $board->update($request->only([
            'name',
            'description',
            'is_active',
            'posts_per_page',
            'allow_comments',
            'allow_attachments',
            'require_login_to_read',
            'require_login_to_write',
        ]));

        // 캐시 클리어
        Cache::forget("board.{$board->slug}");
        Cache::forget('boards.active');

        return response()->json([
            'success' => true,
            'message' => '게시판 설정이 업데이트되었습니다.',
        ]);
    }

    /**
     * 게시판 관리자 추가
     */
    public function addManager(Request $request, Board $board): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'permissions' => 'array',
            'permissions.*' => 'string|in:read,write,delete,modify,manage',
        ]);

        // 이미 관리자인지 확인
        $existingManager = BoardManager::where('board_id', $board->id)
            ->where('user_id', $request->user_id)
            ->first();

        if ($existingManager) {
            return response()->json([
                'success' => false,
                'message' => '이미 해당 게시판의 관리자입니다.',
            ], 422);
        }

        BoardManager::create([
            'board_id' => $board->id,
            'user_id' => $request->user_id,
            'permissions' => $request->permissions ?? ['read', 'write', 'modify'],
        ]);

        return response()->json([
            'success' => true,
            'message' => '게시판 관리자가 추가되었습니다.',
        ]);
    }

    /**
     * 게시판 관리자 제거
     */
    public function removeManager(Board $board, User $user): JsonResponse
    {
        $manager = BoardManager::where('board_id', $board->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$manager) {
            return response()->json([
                'success' => false,
                'message' => '해당 사용자는 이 게시판의 관리자가 아닙니다.',
            ], 404);
        }

        $manager->delete();

        return response()->json([
            'success' => true,
            'message' => '게시판 관리자가 제거되었습니다.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 게시글 관리 (Post Management)
    |--------------------------------------------------------------------------
    */

    /**
     * 게시판별 게시글 목록
     */
    public function posts(Request $request, Board $board): View
    {
        $tableName = "board_{$board->slug}";
        
        if (!DB::getSchemaBuilder()->hasTable($tableName)) {
            abort(404, '게시판 테이블을 찾을 수 없습니다.');
        }

        $query = DB::table($tableName)
            ->leftJoin('users', "{$tableName}.user_id", '=', 'users.id')
            ->select(
                "{$tableName}.*",
                'users.name as user_name',
                'users.email as user_email'
            );

        // 검색 조건
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search, $tableName) {
                $q->where("{$tableName}.title", 'like', "%{$search}%")
                  ->orWhere("{$tableName}.content", 'like', "%{$search}%");
            });
        }

        // 상태 필터
        if ($request->filled('status')) {
            $query->where("{$tableName}.status", $request->get('status'));
        }

        // 날짜 필터
        if ($request->filled('date_from')) {
            $query->whereDate("{$tableName}.created_at", '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate("{$tableName}.created_at", '<=', $request->get('date_to'));
        }

        // 정렬
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy("{$tableName}.{$sortBy}", $sortOrder);

        $posts = $query->paginate(20);

        return view('ahhob.admin.dashboard.community.boards.posts', compact('board', 'posts'));
    }

    /**
     * 게시글 상태 업데이트
     */
    public function updatePostStatus(Request $request, Board $board, int $postId): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:published,hidden,deleted',
            'reason' => 'nullable|string|max:255',
        ]);

        $tableName = "board_{$board->slug}";
        
        if (!DB::getSchemaBuilder()->hasTable($tableName)) {
            return response()->json([
                'success' => false,
                'message' => '게시판 테이블을 찾을 수 없습니다.',
            ], 404);
        }

        $updated = DB::table($tableName)
            ->where('id', $postId)
            ->update([
                'status' => $request->status,
                'admin_note' => $request->reason,
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => '게시글을 찾을 수 없습니다.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => '게시글 상태가 업데이트되었습니다.',
        ]);
    }

    /**
     * 게시글 삭제
     */
    public function deletePost(Board $board, int $postId): JsonResponse
    {
        $tableName = "board_{$board->slug}";
        
        if (!DB::getSchemaBuilder()->hasTable($tableName)) {
            return response()->json([
                'success' => false,
                'message' => '게시판 테이블을 찾을 수 없습니다.',
            ], 404);
        }

        $deleted = DB::table($tableName)->where('id', $postId)->delete();

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => '게시글을 찾을 수 없습니다.',
            ], 404);
        }

        // 관련 댓글도 삭제
        $commentTable = "{$tableName}_comments";
        if (DB::getSchemaBuilder()->hasTable($commentTable)) {
            DB::table($commentTable)->where('post_id', $postId)->delete();
        }

        return response()->json([
            'success' => true,
            'message' => '게시글이 삭제되었습니다.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 댓글 관리 (Comment Management)
    |--------------------------------------------------------------------------
    */

    /**
     * 게시판별 댓글 목록
     */
    public function comments(Request $request, Board $board): View
    {
        $tableName = "board_{$board->slug}";
        $commentTable = "{$tableName}_comments";
        
        if (!DB::getSchemaBuilder()->hasTable($commentTable)) {
            abort(404, '댓글 테이블을 찾을 수 없습니다.');
        }

        $query = DB::table($commentTable)
            ->leftJoin('users', "{$commentTable}.user_id", '=', 'users.id')
            ->leftJoin($tableName, "{$commentTable}.post_id", '=', "{$tableName}.id")
            ->select(
                "{$commentTable}.*",
                'users.name as user_name',
                'users.email as user_email',
                "{$tableName}.title as post_title"
            );

        // 검색 조건
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where("{$commentTable}.content", 'like', "%{$search}%");
        }

        // 상태 필터
        if ($request->filled('status')) {
            $query->where("{$commentTable}.status", $request->get('status'));
        }

        // 날짜 필터
        if ($request->filled('date_from')) {
            $query->whereDate("{$commentTable}.created_at", '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate("{$commentTable}.created_at", '<=', $request->get('date_to'));
        }

        // 정렬
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy("{$commentTable}.{$sortBy}", $sortOrder);

        $comments = $query->paginate(20);

        return view('ahhob.admin.dashboard.community.boards.comments', compact('board', 'comments'));
    }

    /**
     * 댓글 상태 업데이트
     */
    public function updateCommentStatus(Request $request, Board $board, int $commentId): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:published,hidden,deleted',
            'reason' => 'nullable|string|max:255',
        ]);

        $commentTable = "board_{$board->slug}_comments";
        
        if (!DB::getSchemaBuilder()->hasTable($commentTable)) {
            return response()->json([
                'success' => false,
                'message' => '댓글 테이블을 찾을 수 없습니다.',
            ], 404);
        }

        $updated = DB::table($commentTable)
            ->where('id', $commentId)
            ->update([
                'status' => $request->status,
                'admin_note' => $request->reason,
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => '댓글을 찾을 수 없습니다.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => '댓글 상태가 업데이트되었습니다.',
        ]);
    }

    /**
     * 댓글 삭제
     */
    public function deleteComment(Board $board, int $commentId): JsonResponse
    {
        $commentTable = "board_{$board->slug}_comments";
        
        if (!DB::getSchemaBuilder()->hasTable($commentTable)) {
            return response()->json([
                'success' => false,
                'message' => '댓글 테이블을 찾을 수 없습니다.',
            ], 404);
        }

        $deleted = DB::table($commentTable)->where('id', $commentId)->delete();

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => '댓글을 찾을 수 없습니다.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => '댓글이 삭제되었습니다.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 커뮤니티 통계 (Community Statistics)
    |--------------------------------------------------------------------------
    */

    /**
     * 커뮤니티 통계 메인
     */
    public function statistics(): View
    {
        $stats = Cache::remember('admin.community.statistics', 1800, function () {
            return [
                'overview' => $this->getCommunityOverview(),
                'board_stats' => $this->getAllBoardsStats(),
                'user_activity' => $this->getUserActivityStats(),
                'content_trends' => $this->getContentTrends(),
            ];
        });

        return view('ahhob.admin.dashboard.community.statistics.index', compact('stats'));
    }

    /**
     * 게시판별 통계
     */
    public function boardStatistics(): View
    {
        $boardStats = Cache::remember('admin.board.statistics', 1800, function () {
            $boards = Board::where('is_active', true)->get();
            $stats = [];

            foreach ($boards as $board) {
                $stats[] = [
                    'board' => $board,
                    'stats' => $this->getBoardStats($board),
                ];
            }

            return $stats;
        });

        return view('ahhob.admin.dashboard.community.statistics.boards', compact('boardStats'));
    }

    /**
     * 활동 통계
     */
    public function activityStatistics(): View
    {
        $activityStats = Cache::remember('admin.activity.statistics', 1800, function () {
            return [
                'daily_activity' => $this->getDailyActivityStats(30),
                'popular_content' => $this->getPopularContent(),
                'user_engagement' => $this->getUserEngagementStats(),
            ];
        });

        return view('ahhob.admin.dashboard.community.statistics.activity', compact('activityStats'));
    }

    /*
    |--------------------------------------------------------------------------
    | 헬퍼 메서드 (Helper Methods)
    |--------------------------------------------------------------------------
    */

    /**
     * 게시판 통계 조회
     */
    private function getBoardStats(Board $board): array
    {
        $tableName = "board_{$board->slug}";
        $commentTable = "{$tableName}_comments";

        $stats = [
            'total_posts' => 0,
            'total_comments' => 0,
            'today_posts' => 0,
            'today_comments' => 0,
            'this_week_posts' => 0,
            'this_month_posts' => 0,
        ];

        if (DB::getSchemaBuilder()->hasTable($tableName)) {
            $today = now()->startOfDay();
            $thisWeek = now()->startOfWeek();
            $thisMonth = now()->startOfMonth();

            $stats['total_posts'] = DB::table($tableName)->count();
            $stats['today_posts'] = DB::table($tableName)->where('created_at', '>=', $today)->count();
            $stats['this_week_posts'] = DB::table($tableName)->where('created_at', '>=', $thisWeek)->count();
            $stats['this_month_posts'] = DB::table($tableName)->where('created_at', '>=', $thisMonth)->count();

            if (DB::getSchemaBuilder()->hasTable($commentTable)) {
                $stats['total_comments'] = DB::table($commentTable)->count();
                $stats['today_comments'] = DB::table($commentTable)->where('created_at', '>=', $today)->count();
            }
        }

        return $stats;
    }

    /**
     * 최근 게시글 조회
     */
    private function getRecentPosts(string $boardSlug, int $limit = 10): array
    {
        $tableName = "board_{$boardSlug}";
        
        if (!DB::getSchemaBuilder()->hasTable($tableName)) {
            return [];
        }

        return DB::table($tableName)
            ->leftJoin('users', "{$tableName}.user_id", '=', 'users.id')
            ->select(
                "{$tableName}.id",
                "{$tableName}.title",
                "{$tableName}.created_at",
                'users.name as user_name'
            )
            ->orderBy("{$tableName}.created_at", 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * 최근 댓글 조회
     */
    private function getRecentComments(string $boardSlug, int $limit = 10): array
    {
        $commentTable = "board_{$boardSlug}_comments";
        
        if (!DB::getSchemaBuilder()->hasTable($commentTable)) {
            return [];
        }

        return DB::table($commentTable)
            ->leftJoin('users', "{$commentTable}.user_id", '=', 'users.id')
            ->select(
                "{$commentTable}.id",
                "{$commentTable}.content",
                "{$commentTable}.created_at",
                'users.name as user_name'
            )
            ->orderBy("{$commentTable}.created_at", 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($comment) {
                $comment->content = str_limit($comment->content, 100);
                return $comment;
            })
            ->toArray();
    }

    /**
     * 커뮤니티 전체 현황
     */
    private function getCommunityOverview(): array
    {
        $boards = Board::where('is_active', true)->get();
        $totalPosts = 0;
        $totalComments = 0;
        $todayPosts = 0;
        $todayComments = 0;

        foreach ($boards as $board) {
            $stats = $this->getBoardStats($board);
            $totalPosts += $stats['total_posts'];
            $totalComments += $stats['total_comments'];
            $todayPosts += $stats['today_posts'];
            $todayComments += $stats['today_comments'];
        }

        return [
            'total_boards' => $boards->count(),
            'total_posts' => $totalPosts,
            'total_comments' => $totalComments,
            'today_posts' => $todayPosts,
            'today_comments' => $todayComments,
        ];
    }

    /**
     * 모든 게시판 통계
     */
    private function getAllBoardsStats(): array
    {
        $boards = Board::where('is_active', true)->get();
        $stats = [];

        foreach ($boards as $board) {
            $stats[] = [
                'board_name' => $board->name,
                'board_slug' => $board->slug,
                'stats' => $this->getBoardStats($board),
            ];
        }

        // 게시글 수 기준으로 정렬
        usort($stats, function ($a, $b) {
            return $b['stats']['total_posts'] <=> $a['stats']['total_posts'];
        });

        return $stats;
    }

    /**
     * 사용자 활동 통계
     */
    private function getUserActivityStats(): array
    {
        return [
            'active_users_today' => $this->getActiveUsersCount('today'),
            'active_users_week' => $this->getActiveUsersCount('week'),
            'active_users_month' => $this->getActiveUsersCount('month'),
        ];
    }

    /**
     * 활성 사용자 수 조회
     */
    private function getActiveUsersCount(string $period): int
    {
        $date = match($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfDay(),
        };

        // 게시글 또는 댓글을 작성한 사용자 수 계산
        $userIds = collect();
        $boards = Board::where('is_active', true)->get();

        foreach ($boards as $board) {
            $tableName = "board_{$board->slug}";
            $commentTable = "{$tableName}_comments";

            if (DB::getSchemaBuilder()->hasTable($tableName)) {
                $postUsers = DB::table($tableName)
                    ->where('created_at', '>=', $date)
                    ->pluck('user_id');
                $userIds = $userIds->merge($postUsers);

                if (DB::getSchemaBuilder()->hasTable($commentTable)) {
                    $commentUsers = DB::table($commentTable)
                        ->where('created_at', '>=', $date)
                        ->pluck('user_id');
                    $userIds = $userIds->merge($commentUsers);
                }
            }
        }

        return $userIds->unique()->filter()->count();
    }

    /**
     * 콘텐츠 트렌드
     */
    private function getContentTrends(): array
    {
        // 최근 30일간의 게시글/댓글 트렌드
        $dates = [];
        $postCounts = [];
        $commentCounts = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dates[] = $date->format('m/d');
            
            $dailyPosts = 0;
            $dailyComments = 0;
            
            $boards = Board::where('is_active', true)->get();
            foreach ($boards as $board) {
                $tableName = "board_{$board->slug}";
                $commentTable = "{$tableName}_comments";

                if (DB::getSchemaBuilder()->hasTable($tableName)) {
                    $dailyPosts += DB::table($tableName)
                        ->whereDate('created_at', $date->format('Y-m-d'))
                        ->count();

                    if (DB::getSchemaBuilder()->hasTable($commentTable)) {
                        $dailyComments += DB::table($commentTable)
                            ->whereDate('created_at', $date->format('Y-m-d'))
                            ->count();
                    }
                }
            }
            
            $postCounts[] = $dailyPosts;
            $commentCounts[] = $dailyComments;
        }

        return [
            'labels' => $dates,
            'posts' => $postCounts,
            'comments' => $commentCounts,
        ];
    }

    /**
     * 인기 콘텐츠
     */
    private function getPopularContent(): array
    {
        // 추후 좋아요, 조회수 기능이 추가되면 구현
        return [
            'popular_posts' => [],
            'trending_topics' => [],
        ];
    }

    /**
     * 사용자 참여도 통계
     */
    private function getUserEngagementStats(): array
    {
        // 추후 상세 사용자 활동 분석 기능 구현
        return [
            'average_posts_per_user' => 0,
            'average_comments_per_user' => 0,
            'engagement_rate' => 0,
        ];
    }

    /**
     * 일일 활동 통계
     */
    private function getDailyActivityStats(int $days): array
    {
        return $this->getContentTrends();
    }
}