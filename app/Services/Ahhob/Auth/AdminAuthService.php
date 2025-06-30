<?php

namespace App\Services\Ahhob\Auth;

use App\Enums\ActivityType;
use App\Enums\AdminStatus;
use App\Models\Ahhob\Admin\Admin;
use App\Models\Ahhob\User\LoginHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminAuthService
{
    /**
     * 관리자 로그인 처리
     */
    public function login(array $credentials, bool $remember = false, Request $request = null): array
    {
        $request = $request ?? request();
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        // 로그인 시도
        if (!Auth::guard('admin')->attempt($credentials, $remember)) {
            // 실패한 로그인 기록
            $this->recordFailedLogin($credentials['username'], $ipAddress, $userAgent, 'email', '잘못된 인증 정보');
            
            throw ValidationException::withMessages([
                'username' => ['제공된 관리자 인증 정보가 우리 기록과 일치하지 않습니다.'],
            ]);
        }

        $admin = Auth::guard('admin')->user();

        // 관리자 상태 확인
        if (!$admin->canLogin()) {
            Auth::guard('admin')->logout();
            
            $this->recordFailedLogin($credentials['username'], $ipAddress, $userAgent, 'email', '계정 상태: ' . $admin->status->label());
            
            throw ValidationException::withMessages([
                'username' => ['관리자 계정이 ' . $admin->status->label() . ' 상태입니다. 슈퍼 관리자에게 문의해주세요.'],
            ]);
        }

        // 성공한 로그인 기록
        $this->recordSuccessfulLogin($admin, $ipAddress, $userAgent, 'email');
        
        // 마지막 로그인 정보 업데이트
        $admin->updateLastLogin($ipAddress);

        return [
            'admin' => $admin,
            'message' => '관리자 로그인이 완료되었습니다.',
        ];
    }

    /**
     * 관리자 로그아웃 처리
     */
    public function logout(Request $request = null): array
    {
        $request = $request ?? request();
        $admin = Auth::guard('admin')->user();

        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return [
            'message' => '관리자 로그아웃이 완료되었습니다.',
        ];
    }

    /**
     * 관리자 권한 확인
     */
    public function checkPermission(string $permission, Admin $admin = null): bool
    {
        $admin = $admin ?? Auth::guard('admin')->user();
        
        if (!$admin) {
            return false;
        }

        return $admin->hasPermission($permission);
    }

    /**
     * 여러 권한 중 하나라도 가지고 있는지 확인
     */
    public function checkAnyPermission(array $permissions, Admin $admin = null): bool
    {
        $admin = $admin ?? Auth::guard('admin')->user();
        
        if (!$admin) {
            return false;
        }

        return $admin->hasAnyPermission($permissions);
    }

    /**
     * 모든 권한을 가지고 있는지 확인
     */
    public function checkAllPermissions(array $permissions, Admin $admin = null): bool
    {
        $admin = $admin ?? Auth::guard('admin')->user();
        
        if (!$admin) {
            return false;
        }

        return $admin->hasAllPermissions($permissions);
    }

    /**
     * 관리자 권한 부여
     */
    public function givePermission(Admin $admin, string $permission): array
    {
        $currentAdmin = Auth::guard('admin')->user();
        
        // 슈퍼 관리자만 권한 부여 가능
        if (!$currentAdmin || !$currentAdmin->isSuperAdmin()) {
            throw new \Exception('권한 부여는 슈퍼 관리자만 가능합니다.');
        }

        $admin->givePermission($permission);

        return [
            'message' => "'{$permission}' 권한이 {$admin->display_name}에게 부여되었습니다.",
        ];
    }

    /**
     * 관리자 권한 제거
     */
    public function revokePermission(Admin $admin, string $permission): array
    {
        $currentAdmin = Auth::guard('admin')->user();
        
        // 슈퍼 관리자만 권한 제거 가능
        if (!$currentAdmin || !$currentAdmin->isSuperAdmin()) {
            throw new \Exception('권한 제거는 슈퍼 관리자만 가능합니다.');
        }

        $admin->revokePermission($permission);

        return [
            'message' => "'{$permission}' 권한이 {$admin->display_name}에서 제거되었습니다.",
        ];
    }

    /**
     * 성공한 로그인 기록
     */
    private function recordSuccessfulLogin($admin, string $ipAddress, string $userAgent, string $loginMethod): void
    {
        LoginHistory::createLoginRecord(
            $admin,
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
        // 실패한 로그인도 기록 (관리자 정보 없이)
        LoginHistory::create([
            'authenticatable_type' => Admin::class,
            'authenticatable_id' => null, // 실패했으므로 null
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'login_method' => $loginMethod,
            'status' => 'failed',
            'failure_reason' => "Admin Username: {$username}, Reason: {$reason}",
        ] + LoginHistory::parseUserAgent($userAgent));
    }

    /**
     * 특정 IP의 최근 관리자 로그인 실패 횟수 확인
     */
    public function getRecentLoginFailures(string $ipAddress, int $minutes = 15): int
    {
        return LoginHistory::where('ip_address', $ipAddress)
            ->where('authenticatable_type', Admin::class)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    /**
     * IP 기반 관리자 로그인 시도 제한 확인
     */
    public function isIpBlocked(string $ipAddress, int $maxAttempts = 3, int $minutes = 15): bool
    {
        return $this->getRecentLoginFailures($ipAddress, $minutes) >= $maxAttempts;
    }

    /**
     * 관리자 계정 상태 변경
     */
    public function changeAdminStatus(Admin $admin, AdminStatus $newStatus, string $reason = null): array
    {
        $currentAdmin = Auth::guard('admin')->user();
        
        // 슈퍼 관리자만 상태 변경 가능
        if (!$currentAdmin || !$currentAdmin->isSuperAdmin()) {
            throw new \Exception('관리자 상태 변경은 슈퍼 관리자만 가능합니다.');
        }

        $oldStatus = $admin->status;
        $admin->update(['status' => $newStatus]);

        return [
            'message' => "관리자 {$admin->display_name}의 상태가 {$oldStatus->label()}에서 {$newStatus->label()}로 변경되었습니다.",
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ];
    }

    /**
     * 관리자 로그인 기록 조회
     */
    public function getAdminLoginHistory(Admin $admin = null, int $limit = 10): array
    {
        $admin = $admin ?? Auth::guard('admin')->user();
        
        if (!$admin) {
            return [];
        }

        return $admin->loginHistories()
            ->latest()
            ->limit($limit)
            ->get()
            ->toArray();
    }
}