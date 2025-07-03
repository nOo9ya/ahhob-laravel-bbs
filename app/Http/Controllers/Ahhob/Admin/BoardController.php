<?php

namespace App\Http\Controllers\Ahhob\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ahhob\Board\Board;
use App\Services\Ahhob\Board\PostService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BoardController extends Controller
{
    public function __construct(
        private PostService $postService
    ) {}

    /**
     * 게시판 목록 조회
     */
    public function index(Request $request): View
    {
        $boards = Board::with(['group'])
                      ->withCount(['posts'])
                      ->orderBy('order')
                      ->get();
        
        return view('ahhob.admin.boards.index', compact('boards'));
    }
    
    /**
     * 특정 게시판의 게시글 목록
     */
    public function posts(Request $request, Board $board): View
    {
        $modelClass = $this->postService->getPostModel($board);
        
        if (!class_exists($modelClass)) {
            return redirect()->route('admin.boards.index')
                           ->with('error', '게시판 모델이 존재하지 않습니다.');
        }
        
        $query = $modelClass::query()->with(['user']);
        
        // 검색 기능
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }
        
        // 상태 필터
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        
        $posts = $query->orderBy('created_at', 'desc')
                      ->paginate(20)
                      ->withQueryString();
        
        return view('ahhob.admin.boards.posts', compact('board', 'posts'));
    }
    
    /**
     * 게시글 삭제
     */
    public function deletePost(Request $request, $postId): RedirectResponse
    {
        // 어떤 게시판의 게시글인지 확인하기 위해 board_id를 찾아야 함
        $board = null;
        $post = null;
        
        // 모든 게시판을 확인해서 해당 게시글 찾기
        $boards = Board::all();
        foreach ($boards as $b) {
            $modelClass = $this->postService->getPostModel($b);
            if (class_exists($modelClass)) {
                $foundPost = $modelClass::find($postId);
                if ($foundPost) {
                    $board = $b;
                    $post = $foundPost;
                    break;
                }
            }
        }
        
        if (!$post) {
            return back()->with('error', '게시글을 찾을 수 없습니다.');
        }
        
        $post->delete();
        
        return back()->with('success', '게시글이 삭제되었습니다.');
    }
}