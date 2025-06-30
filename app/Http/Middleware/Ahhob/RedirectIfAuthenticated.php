<?php

namespace App\Http\Middleware\Ahhob;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $guard = null): Response
    {
        $guard = $guard ?: 'web';

        if (Auth::guard($guard)->check()) {
            $redirectRoute = $this->getRedirectRoute($guard);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '이미 로그인된 상태입니다.',
                    'redirect_url' => $redirectRoute,
                ], 403);
            }

            return redirect($redirectRoute);
        }

        return $next($request);
    }

    /**
     * 가드에 따른 리다이렉트 라우트 반환
     */
    private function getRedirectRoute(string $guard): string
    {
        return match ($guard) {
            'admin' => route('admin.dashboard'),
            'web' => route('home'),
            default => route('home'),
        };
    }
}