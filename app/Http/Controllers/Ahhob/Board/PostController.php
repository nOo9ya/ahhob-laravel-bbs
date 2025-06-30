<?php

namespace App\Http\Controllers\Ahhob\Board;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Models\Ahhob\Board\Board;
use App\Models\User;
use App\Models\Ahhob\User\DailyActivityCount;
use App\Services\Ahhob\Board\PostService;
use App\Services\Ahhob\User\ActivityLimitService;

class PostController extends Controller
{
    public function __construct(
        private PostService $postService,
        private ActivityLimitService $activityLimitService
    ) {
        $this->middleware('auth');
    }

    /**
     * @OA\Get(
     *     path="/boards/{board}/posts",
     *     summary="게시판 게시글 목록 조회",
     *     tags={"Board"},
     *     @OA\Parameter(
     *         name="board",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="게시판 슬러그"
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         @OA\Schema(type="integer", default=1),
     *         description="페이지 번호"
     *     ),
     *     @OA\Response(response="200", description="게시글 목록 조회 성공")
     * )
     */
    public function index(Request $request, string $boardSlug): View
    {
        $board = Board::where('slug', $boardSlug)->firstOrFail();
        
        // 게시판 접근 권한 확인
        $this->authorize('view', $board);
        
        $posts = $this->postService->getPaginatedPosts($board, $request->all());
        
        return view('ahhob.board.posts.index', compact('board', 'posts'));
    }

    /**
     * @OA\Get(
     *     path="/boards/{board}/posts/create",
     *     summary="게시글 작성 폼",
     *     tags={"Board"},
     *     @OA\Response(response="200", description="작성 폼 표시")
     * )
     */
    public function create(string $boardSlug): View
    {
        $board = Board::where('slug', $boardSlug)->firstOrFail();
        
        // 게시글 작성 권한 확인
        $this->authorize('create', [$this->postService->getPostModel($board), $board]);
        
        // 활동 제한 확인
        $user = auth()->user();
        $canPost = $this->activityLimitService->canPerformActivity(
            $user, 
            'post', 
            $board
        );
        
        if (!$canPost['allowed']) {
            return redirect()->route('boards.posts.index', $board->slug)
                ->with('error', $canPost['message']);
        }
        
        return view('ahhob.board.posts.create', compact('board'));
    }

    /**
     * @OA\Post(
     *     path="/boards/{board}/posts",
     *     summary="게시글 작성",
     *     tags={"Board"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="게시글 제목"),
     *             @OA\Property(property="content", type="string", example="게시글 내용"),
     *             @OA\Property(property="is_notice", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response="201", description="게시글 작성 성공"),
     *     @OA\Response(response="403", description="권한 없음"),
     *     @OA\Response(response="429", description="활동 제한 초과")
     * )
     */
    public function store(Request $request, string $boardSlug): RedirectResponse
    {
        $board = Board::where('slug', $boardSlug)->firstOrFail();
        $user = auth()->user();
        
        // 게시글 작성 권한 확인
        $this->authorize('create', [$this->postService->getPostModel($board), $board]);
        
        // 활동 제한 확인
        $canPost = $this->activityLimitService->canPerformActivity(
            $user, 
            'post', 
            $board
        );
        
        if (!$canPost['allowed']) {
            return redirect()->back()
                ->withInput()
                ->with('error', $canPost['message']);
        }
        
        // 입력 데이터 검증
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_notice' => 'boolean',
            'attachments' => 'array',
            'attachments.*' => 'file|max:10240', // 10MB 제한
        ]);
        
        try {
            // 게시글 생성
            $post = $this->postService->createPost($board, $user, $validated);
            
            // 활동 카운트 증가
            DailyActivityCount::incrementActivity(
                $user,
                'post',
                'board',
                $board->id,
                $request->ip(),
                $request->userAgent()
            );
            
            return redirect()->route('boards.posts.show', [$board->slug, $post->id])
                ->with('success', '게시글이 성공적으로 작성되었습니다.');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', '게시글 작성 중 오류가 발생했습니다.');
        }
    }

    /**
     * @OA\Get(
     *     path="/boards/{board}/posts/{post}",
     *     summary="게시글 상세 조회",
     *     tags={"Board"},
     *     @OA\Response(response="200", description="게시글 상세 조회 성공")
     * )
     */
    public function show(string $boardSlug, int $postId): View
    {
        $board = Board::where('slug', $boardSlug)->firstOrFail();
        $post = $this->postService->getPost($board, $postId);
        
        // 게시글 조회 권한 확인
        $this->authorize('view', $post);
        
        // 조회수 증가
        $this->postService->incrementViews($post);
        
        return view('ahhob.board.posts.show', compact('board', 'post'));
    }

    /**
     * @OA\Get(
     *     path="/boards/{board}/posts/{post}/edit",
     *     summary="게시글 수정 폼",
     *     tags={"Board"},
     *     @OA\Response(response="200", description="수정 폼 표시")
     * )
     */
    public function edit(string $boardSlug, int $postId): View
    {
        $board = Board::where('slug', $boardSlug)->firstOrFail();
        $post = $this->postService->getPost($board, $postId);
        
        // 게시글 수정 권한 확인
        $this->authorize('update', $post);
        
        return view('ahhob.board.posts.edit', compact('board', 'post'));
    }

    /**
     * @OA\Put(
     *     path="/boards/{board}/posts/{post}",
     *     summary="게시글 수정",
     *     tags={"Board"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="content", type="string")
     *         )
     *     ),
     *     @OA\Response(response="200", description="게시글 수정 성공")
     * )
     */
    public function update(Request $request, string $boardSlug, int $postId): RedirectResponse
    {
        $board = Board::where('slug', $boardSlug)->firstOrFail();
        $post = $this->postService->getPost($board, $postId);
        
        // 게시글 수정 권한 확인
        $this->authorize('update', $post);
        
        // 입력 데이터 검증
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_notice' => 'boolean',
        ]);
        
        try {
            $this->postService->updatePost($post, $validated);
            
            return redirect()->route('boards.posts.show', [$board->slug, $post->id])
                ->with('success', '게시글이 성공적으로 수정되었습니다.');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', '게시글 수정 중 오류가 발생했습니다.');
        }
    }

    /**
     * @OA\Delete(
     *     path="/boards/{board}/posts/{post}",
     *     summary="게시글 삭제",
     *     tags={"Board"},
     *     @OA\Response(response="200", description="게시글 삭제 성공")
     * )
     */
    public function destroy(string $boardSlug, int $postId): RedirectResponse
    {
        $board = Board::where('slug', $boardSlug)->firstOrFail();
        $post = $this->postService->getPost($board, $postId);
        
        // 게시글 삭제 권한 확인
        $this->authorize('delete', $post);
        
        try {
            $this->postService->deletePost($post);
            
            return redirect()->route('boards.posts.index', $board->slug)
                ->with('success', '게시글이 성공적으로 삭제되었습니다.');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', '게시글 삭제 중 오류가 발생했습니다.');
        }
    }
}
