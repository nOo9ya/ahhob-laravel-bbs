<?php

namespace App\Services\Ahhob\Auth;

use App\Enums\ActivityType;
use App\Enums\UserStatus;
use App\Models\User;
use App\Models\Ahhob\User\LoginHistory;
use App\Models\Ahhob\User\UserActivityLog;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * 사용자 로그인 처리
     */
    public function login(array $credentials, bool $remember = false, Request $request = null): array
    {
        $request = $request ?? request();
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        // 로그인 시도
        if (!Auth::guard('web')->attempt($credentials, $remember)) {
            // 실패한 로그인 기록
            $this->recordFailedLogin($credentials['username'], $ipAddress, $userAgent, 'email', '잘못된 인증 정보');
            
            throw ValidationException::withMessages([
                'username' => ['제공된 인증 정보가 우리 기록과 일치하지 않습니다.'],
            ]);
        }

        $user = Auth::guard('web')->user();

        // 사용자 상태 확인
        if (!$user->canLogin()) {
            Auth::guard('web')->logout();
            
            $this->recordFailedLogin($credentials['username'], $ipAddress, $userAgent, 'email', '계정 상태: ' . $user->status->label());
            
            throw ValidationException::withMessages([
                'username' => ['계정이 ' . $user->status->label() . ' 상태입니다. 관리자에게 문의해주세요.'],
            ]);
        }

        // 성공한 로그인 기록
        $this->recordSuccessfulLogin($user, $ipAddress, $userAgent, 'email');
        
        // 마지막 로그인 정보 업데이트
        $user->updateLastLogin($ipAddress);
        
        // 활동 로그 기록
        UserActivityLog::createActivity(
            $user,
            ActivityType::LOGIN,
            null,
            ['ip_address' => $ipAddress, 'user_agent' => $userAgent],
            $ipAddress,
            $userAgent
        );

        return [
            'user' => $user,
            'message' => '로그인이 완료되었습니다.',
        ];
    }

    /**
     * 사용자 회원가입 처리
     */
    public function register(array $userData, Request $request = null): array
    {
        $request = $request ?? request();
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        // 비밀번호 해싱
        $userData['password'] = Hash::make($userData['password']);

        // 사용자 생성
        $user = User::create($userData);

        // 회원가입 이벤트 발생
        event(new Registered($user));

        // 활동 로그 기록
        UserActivityLog::createActivity(
            $user,
            ActivityType::REGISTER,
            null,
            ['registration_ip' => $ipAddress],
            $ipAddress,
            $userAgent
        );

        // 자동 로그인
        Auth::guard('web')->login($user);

        // 로그인 기록
        $this->recordSuccessfulLogin($user, $ipAddress, $userAgent, 'email');

        return [
            'user' => $user,
            'message' => '회원가입이 완료되었습니다. 환영합니다!',
        ];
    }

    /**
     * 사용자 로그아웃 처리
     */
    public function logout(Request $request = null): array
    {
        $request = $request ?? request();
        $user = Auth::guard('web')->user();

        if ($user) {
            // 활동 로그 기록
            UserActivityLog::createActivity(
                $user,
                ActivityType::LOGOUT,
                null,
                ['logout_ip' => $request->ip()],
                $request->ip(),
                $request->userAgent()
            );
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return [
            'message' => '로그아웃이 완료되었습니다.',
        ];
    }

    /**
     * 이메일 인증 재발송
     */
    public function resendEmailVerification(User $user): array
    {
        if ($user->hasVerifiedEmail()) {
            return [
                'message' => '이미 인증된 이메일입니다.',
                'already_verified' => true,
            ];
        }

        $user->sendEmailVerificationNotification();

        // 활동 로그 기록
        UserActivityLog::createActivity(
            $user,
            ActivityType::EMAIL_VERIFY,
            null,
            ['action' => 'resend_verification']
        );

        return [
            'message' => '인증 이메일이 재발송되었습니다.',
            'already_verified' => false,
        ];
    }

    /**
     * 비밀번호 재설정 요청
     */
    public function requestPasswordReset(string $email): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            // 보안상 이유로 사용자 존재 여부를 노출하지 않음
            return [
                'message' => '비밀번호 재설정 링크가 이메일로 발송되었습니다.',
            ];
        }

        // 비밀번호 재설정 링크 발송 (Laravel 기본 기능 활용)
        $status = Password::sendResetLink(['email' => $email]);

        if ($status === Password::RESET_LINK_SENT) {
            // 활동 로그 기록
            UserActivityLog::createActivity(
                $user,
                ActivityType::PASSWORD_RESET,
                null,
                ['action' => 'request_reset']
            );
        }

        return [
            'message' => '비밀번호 재설정 링크가 이메일로 발송되었습니다.',
        ];
    }

    /**
     * 성공한 로그인 기록
     */
    private function recordSuccessfulLogin($user, string $ipAddress, string $userAgent, string $loginMethod): void
    {
        LoginHistory::createLoginRecord(
            $user,
            $ipAddress,
            $userAgent,
            $loginMethod,
            'success'
        );
    }

    /**
     * 실패한 로그인 기록
     */
    private function recordFailedLogin(string $username, string $ipAddress, string $userAgent, string $loginMethod, string $reason): void
    {
        // 실패한 로그인도 기록 (사용자 정보 없이)
        LoginHistory::create([
            'authenticatable_type' => User::class,
            'authenticatable_id' => null, // 실패했으므로 null
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'login_method' => $loginMethod,
            'status' => 'failed',
            'failure_reason' => "Username: {$username}, Reason: {$reason}",
        ] + LoginHistory::parseUserAgent($userAgent));
    }

    /**
     * 특정 IP의 최근 로그인 실패 횟수 확인
     */
    public function getRecentLoginFailures(string $ipAddress, int $minutes = 15): int
    {
        return LoginHistory::where('ip_address', $ipAddress)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    /**
     * IP 기반 로그인 시도 제한 확인
     */
    public function isIpBlocked(string $ipAddress, int $maxAttempts = 5, int $minutes = 15): bool
    {
        return $this->getRecentLoginFailures($ipAddress, $minutes) >= $maxAttempts;
    }

    /**
     * 사용자 상태 변경
     */
    public function changeUserStatus(User $user, UserStatus $newStatus, string $reason = null): array
    {
        $oldStatus = $user->status;
        $user->update(['status' => $newStatus]);

        // 활동 로그 기록
        UserActivityLog::createActivity(
            $user,
            ActivityType::ADMIN_ACTION,
            null,
            [
                'action' => 'status_change',
                'old_status' => $oldStatus->value,
                'new_status' => $newStatus->value,
                'reason' => $reason,
                'changed_by' => auth()->id(),
            ]
        );

        return [
            'message' => "사용자 상태가 {$oldStatus->label()}에서 {$newStatus->label()}로 변경되었습니다.",
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ];
    }
}