<?php

namespace App\Http\Controllers\Ahhob\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ahhob\Auth\AdminLoginRequest;
use App\Services\Ahhob\Auth\AdminAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function __construct(
        private AdminAuthService $adminAuthService
    ) {}

    /**
     * 관리자 로그인 폼 표시
     */
    public function showLoginForm(): View
    {
        return view('ahhob.admin.auth.login');
    }

    /**
     * 관리자 로그인 처리
     */
    public function login(AdminLoginRequest $request): JsonResponse|RedirectResponse
    {
        // IP 차단 확인
        if ($this->adminAuthService->isIpBlocked($request->ip())) {
            return $this->handleBlockedIp();
        }

        try {
            $result = $this->adminAuthService->login(
                $request->getCredentials(),
                $request->shouldRemember(),
                $request
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'admin' => [
                        'id' => $result['admin']->id,
                        'username' => $result['admin']->username,
                        'display_name' => $result['admin']->display_name,
                        'role' => $result['admin']->role,
                        'permissions' => $result['admin']->permissions,
                        'status' => $result['admin']->status,
                    ],
                    'redirect_url' => $this->getAdminIntendedUrl(),
                ]);
            }

            return redirect()->intended(route('admin.dashboard'))->with('success', $result['message']);

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => method_exists($e, 'errors') ? $e->errors() : [],
                ], 422);
            }

            return back()->withErrors(['username' => $e->getMessage()])->withInput($request->except('password'));
        }
    }

    /**
     * 관리자 로그아웃 처리
     */
    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        $result = $this->adminAuthService->logout($request);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ]);
        }

        return redirect()->route('admin.login')->with('success', $result['message']);
    }


    /**
     * IP 차단 처리
     */
    private function handleBlockedIp(): JsonResponse|RedirectResponse
    {
        $message = '너무 많은 관리자 로그인 시도로 인해 일시적으로 차단되었습니다. 15분 후 다시 시도해주세요.';
        
        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'blocked' => true,
            ], 429);
        }

        return back()->withErrors(['username' => $message]);
    }

    /**
     * 관리자 로그인 후 이동할 URL 결정
     */
    private function getAdminIntendedUrl(): string
    {
        return session()->pull('url.intended', route('admin.dashboard'));
    }
}