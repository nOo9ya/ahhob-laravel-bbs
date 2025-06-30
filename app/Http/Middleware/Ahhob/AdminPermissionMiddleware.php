<?php

namespace App\Http\Middleware\Ahhob;

use App\Services\Ahhob\Auth\AdminAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminPermissionMiddleware
{
    public function __construct(
        private AdminAuthService $adminAuthService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission, string $checkType = 'single'): Response
    {
        // 관리자 인증 확인
        if (!auth()->guard('admin')->check()) {
            return $this->unauthorized($request, '관리자 인증이 필요합니다.');
        }

        $admin = auth()->guard('admin')->user();

        // 슈퍼 관리자는 모든 권한 보유
        if ($admin->isSuperAdmin()) {
            return $next($request);
        }

        // 권한 확인
        $hasPermission = $this->checkPermission($permission, $checkType, $admin);

        if (!$hasPermission) {
            return $this->forbidden($request, '해당 기능에 대한 권한이 없습니다.');
        }

        return $next($request);
    }

    /**
     * 권한 확인 로직
     */
    private function checkPermission(string $permission, string $checkType, $admin): bool
    {
        return match ($checkType) {
            'single' => $this->adminAuthService->checkPermission($permission, $admin),
            'any' => $this->adminAuthService->checkAnyPermission(explode(',', $permission), $admin),
            'all' => $this->adminAuthService->checkAllPermissions(explode(',', $permission), $admin),
            default => $this->adminAuthService->checkPermission($permission, $admin),
        };
    }

    /**
     * 인증되지 않은 경우 응답
     */
    private function unauthorized(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'redirect_url' => route('admin.login'),
            ], 401);
        }

        return redirect()->route('admin.login')->withErrors(['auth' => $message]);
    }

    /**
     * 권한이 없는 경우 응답
     */
    private function forbidden(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 403);
        }

        // 관리자 대시보드로 리다이렉트하면서 오류 메시지 표시
        return redirect()->route('admin.dashboard')->withErrors(['permission' => $message]);
    }
}