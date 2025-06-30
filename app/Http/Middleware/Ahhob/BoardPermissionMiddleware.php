<?php

namespace App\Http\Middleware\Ahhob;

use App\Models\Ahhob\Board\Board;
use App\Models\Ahhob\Board\BoardManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BoardPermissionMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission = 'read'): Response
    {
        // 게시판 슬러그 확인
        $boardSlug = $request->route('boardSlug');
        
        if (!$boardSlug) {
            return $this->notFound($request, '게시판을 찾을 수 없습니다.');
        }

        // 게시판 조회
        $board = Board::where('slug', $boardSlug)->first();
        
        if (!$board) {
            return $this->notFound($request, '게시판을 찾을 수 없습니다.');
        }

        // 비활성화된 게시판 확인
        if (!$board->is_active) {
            return $this->forbidden($request, '비활성화된 게시판입니다.');
        }

        // 권한 확인
        if (!$this->checkPermission($board, $permission, $request)) {
            return $this->unauthorized($request, '해당 기능에 대한 권한이 없습니다.');
        }

        // 요청에 게시판 정보 추가
        $request->attributes->set('board', $board);

        return $next($request);
    }

    /**
     * 권한 확인 로직
     */
    private function checkPermission(Board $board, string $permission, Request $request): bool
    {
        // 슈퍼 관리자는 모든 권한 보유
        if (auth()->guard('admin')->check() && auth()->guard('admin')->user()->isSuperAdmin()) {
            return true;
        }

        $permissionField = $permission . '_permission';
        $requiredPermission = $board->{$permissionField} ?? 'all';

        return match ($requiredPermission) {
            'all' => true,
            'member' => auth()->check(),
            'admin' => $this->isBoardManagerOrAdmin($board, $request),
            default => false,
        };
    }

    /**
     * 게시판 매니저 또는 관리자 권한 확인
     */
    private function isBoardManagerOrAdmin(Board $board, Request $request): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $user = auth()->user();

        // 게시판 매니저 권한 확인
        return BoardManager::where('board_id', $board->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * 404 응답
     */
    private function notFound(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 404);
        }

        return response($message, 404);
    }

    /**
     * 403 응답
     */
    private function forbidden(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 403);
        }

        return response($message, 403);
    }

    /**
     * 401 응답
     */
    private function unauthorized(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 401);
        }

        return response($message, 401);
    }
}