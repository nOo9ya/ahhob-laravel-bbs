<?php

namespace App\Http\Middleware\Ahhob;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNotAuthenticated
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $guard = null): Response
    {
        $guard = $guard ?: 'web';

        if (!Auth::guard($guard)->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '인증이 필요합니다.',
                    'redirect_url' => $this->getLoginRoute($guard),
                ], 401);
            }

            // 로그인 페이지로 리다이렉트하면서 의도된 URL 저장
            return redirect()->guest($this->getLoginRoute($guard));
        }

        return $next($request);
    }

    /**
     * 가드에 따른 로그인 라우트 반환
     */
    private function getLoginRoute(string $guard): string
    {
        return match ($guard) {
            'admin' => route('admin.login'),
            'web' => route('login'),
            default => route('login'),
        };
    }
}