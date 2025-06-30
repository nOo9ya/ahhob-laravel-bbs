<?php

namespace App\Http\Controllers\Ahhob\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ahhob\Auth\LoginRequest;
use App\Http\Requests\Ahhob\Auth\RegisterRequest;
use App\Services\Ahhob\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * 로그인 폼 표시
     */
    public function showLoginForm(): View
    {
        return view('ahhob.auth.login');
    }

    /**
     * 로그인 처리
     */
    public function login(LoginRequest $request): JsonResponse|RedirectResponse
    {
        // IP 차단 확인
        if ($this->authService->isIpBlocked($request->ip())) {
            return $this->handleBlockedIp();
        }

        try {
            $result = $this->authService->login(
                $request->getCredentials(),
                $request->shouldRemember(),
                $request
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'user' => $result['user'],
                    'redirect_url' => $this->getIntendedUrl(),
                ]);
            }

            return redirect()->intended(route('home'))->with('success', $result['message']);

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
     * 회원가입 폼 표시
     */
    public function showRegisterForm(): View
    {
        return view('ahhob.auth.register');
    }

    /**
     * 회원가입 처리
     */
    public function register(RegisterRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $result = $this->authService->register(
                $request->getUserData(),
                $request
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'user' => $result['user'],
                    'redirect_url' => route('email.verify'),
                ]);
            }

            return redirect()->route('email.verify')->with('success', $result['message']);

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => method_exists($e, 'errors') ? $e->errors() : [],
                ], 422);
            }

            return back()->withErrors(['general' => $e->getMessage()])->withInput();
        }
    }

    /**
     * 로그아웃 처리
     */
    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        $result = $this->authService->logout($request);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ]);
        }

        return redirect()->route('home')->with('success', $result['message']);
    }

    /**
     * 이메일 인증 안내 페이지
     */
    public function showEmailVerifyNotice(): View
    {
        return view('ahhob.auth.verify-email');
    }

    /**
     * 이메일 인증 재발송
     */
    public function resendEmailVerification(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        $result = $this->authService->resendEmailVerification($user);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'already_verified' => $result['already_verified'],
            ]);
        }

        $messageType = $result['already_verified'] ? 'info' : 'success';
        return back()->with($messageType, $result['message']);
    }

    /**
     * 비밀번호 재설정 요청 폼
     */
    public function showForgotPasswordForm(): View
    {
        return view('ahhob.auth.forgot-password');
    }

    /**
     * 비밀번호 재설정 요청 처리
     */
    public function sendResetLinkEmail(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => '이메일을 입력해주세요.',
            'email.email' => '올바른 이메일 형식이 아닙니다.',
        ]);

        $result = $this->authService->requestPasswordReset($request->email);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ]);
        }

        return back()->with('status', $result['message']);
    }

    /**
     * IP 차단 처리
     */
    private function handleBlockedIp(): JsonResponse|RedirectResponse
    {
        $message = '너무 많은 로그인 시도로 인해 일시적으로 차단되었습니다. 15분 후 다시 시도해주세요.';
        
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
     * 로그인 후 이동할 URL 결정
     */
    private function getIntendedUrl(): string
    {
        return session()->pull('url.intended', route('home'));
    }

}