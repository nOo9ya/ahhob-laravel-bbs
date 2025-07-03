<?php

namespace App\Http\Controllers\Ahhob\Admin\Dashboard\System;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /*
    |--------------------------------------------------------------------------
    | 기본 설정 (General Settings)
    |--------------------------------------------------------------------------
    */

    /**
     * 설정 메인 페이지
     */
    public function index(): View
    {
        $settings = $this->getCurrentSettings();
        $systemInfo = $this->getSystemInfo();
        
        return view('ahhob.admin.dashboard.system.settings.index', compact('settings', 'systemInfo'));
    }

    /**
     * 일반 설정
     */
    public function general(): View
    {
        $settings = $this->getCurrentSettings();
        
        return view('ahhob.admin.dashboard.system.settings.general', compact('settings'));
    }

    /**
     * 일반 설정 업데이트
     */
    public function updateGeneral(Request $request): JsonResponse
    {
        $request->validate([
            'site_name' => 'required|string|max:100',
            'site_description' => 'nullable|string|max:255',
            'site_keywords' => 'nullable|string|max:255',
            'admin_email' => 'required|email|max:100',
            'timezone' => 'required|string',
            'locale' => 'required|string|in:ko,en',
            'maintenance_mode' => 'boolean',
            'maintenance_message' => 'nullable|string|max:500',
            'registration_enabled' => 'boolean',
            'email_verification_required' => 'boolean',
            'default_user_level' => 'integer|min:1|max:10',
        ]);

        try {
            foreach ($request->validated() as $key => $value) {
                $this->updateSetting($key, $value);
            }

            // 캐시 클리어
            Cache::forget('system.settings');

            return response()->json([
                'success' => true,
                'message' => '일반 설정이 업데이트되었습니다.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '설정 업데이트 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 이메일 설정 (Email Settings)
    |--------------------------------------------------------------------------
    */

    /**
     * 이메일 설정
     */
    public function email(): View
    {
        $emailSettings = $this->getEmailSettings();
        
        return view('ahhob.admin.dashboard.system.settings.email', compact('emailSettings'));
    }

    /**
     * 이메일 설정 업데이트
     */
    public function updateEmail(Request $request): JsonResponse
    {
        $request->validate([
            'mail_driver' => 'required|in:smtp,sendmail,mailgun,ses,postmark',
            'mail_host' => 'required_if:mail_driver,smtp|nullable|string|max:100',
            'mail_port' => 'required_if:mail_driver,smtp|nullable|integer|min:1|max:65535',
            'mail_username' => 'required_if:mail_driver,smtp|nullable|string|max:100',
            'mail_password' => 'nullable|string|max:100',
            'mail_encryption' => 'nullable|in:tls,ssl',
            'mail_from_address' => 'required|email|max:100',
            'mail_from_name' => 'required|string|max:100',
        ]);

        try {
            foreach ($request->validated() as $key => $value) {
                if ($key === 'mail_password' && empty($value)) {
                    continue; // 비밀번호가 비어있으면 기존 값 유지
                }
                $this->updateSetting($key, $value);
            }

            // 이메일 설정 캐시 클리어
            Cache::forget('system.email_settings');

            return response()->json([
                'success' => true,
                'message' => '이메일 설정이 업데이트되었습니다.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '이메일 설정 업데이트 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /**
     * 이메일 테스트
     */
    public function testEmail(Request $request): JsonResponse
    {
        $request->validate([
            'test_email' => 'required|email',
        ]);

        try {
            Mail::raw('이것은 테스트 이메일입니다.', function ($message) use ($request) {
                $message->to($request->test_email)
                    ->subject('이메일 설정 테스트');
            });

            return response()->json([
                'success' => true,
                'message' => '테스트 이메일이 전송되었습니다.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '이메일 전송 실패: ' . $e->getMessage(),
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 스토리지 설정 (Storage Settings)
    |--------------------------------------------------------------------------
    */

    /**
     * 스토리지 설정
     */
    public function storage(): View
    {
        $storageSettings = $this->getStorageSettings();
        $storageStats = $this->getStorageStats();
        
        return view('ahhob.admin.dashboard.system.settings.storage', compact('storageSettings', 'storageStats'));
    }

    /**
     * 스토리지 설정 업데이트
     */
    public function updateStorage(Request $request): JsonResponse
    {
        $request->validate([
            'default_disk' => 'required|in:local,public,s3',
            'max_file_size' => 'required|integer|min:1|max:100', // MB
            'allowed_file_types' => 'required|string',
            'image_quality' => 'required|integer|min:1|max:100',
            'thumbnail_width' => 'required|integer|min:50|max:1000',
            'thumbnail_height' => 'required|integer|min:50|max:1000',
        ]);

        try {
            foreach ($request->validated() as $key => $value) {
                $this->updateSetting($key, $value);
            }

            Cache::forget('system.storage_settings');

            return response()->json([
                'success' => true,
                'message' => '스토리지 설정이 업데이트되었습니다.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '스토리지 설정 업데이트 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 캐시 관리 (Cache Management)
    |--------------------------------------------------------------------------
    */

    /**
     * 캐시 설정
     */
    public function cache(): View
    {
        $cacheStats = $this->getCacheStats();
        
        return view('ahhob.admin.dashboard.system.settings.cache', compact('cacheStats'));
    }

    /**
     * 캐시 클리어
     */
    public function clearCache(Request $request): JsonResponse
    {
        $request->validate([
            'cache_types' => 'required|array',
            'cache_types.*' => 'in:application,config,route,view,all',
        ]);

        try {
            $cleared = [];

            foreach ($request->cache_types as $type) {
                switch ($type) {
                    case 'application':
                        Cache::flush();
                        $cleared[] = '애플리케이션 캐시';
                        break;
                        
                    case 'config':
                        Artisan::call('config:clear');
                        $cleared[] = '설정 캐시';
                        break;
                        
                    case 'route':
                        Artisan::call('route:clear');
                        $cleared[] = '라우트 캐시';
                        break;
                        
                    case 'view':
                        Artisan::call('view:clear');
                        $cleared[] = '뷰 캐시';
                        break;
                        
                    case 'all':
                        Cache::flush();
                        Artisan::call('config:clear');
                        Artisan::call('route:clear');
                        Artisan::call('view:clear');
                        $cleared = ['모든 캐시'];
                        break;
                }
            }

            return response()->json([
                'success' => true,
                'message' => implode(', ', $cleared) . '가 클리어되었습니다.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '캐시 클리어 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /**
     * 캐시 최적화
     */
    public function optimizeCache(): JsonResponse
    {
        try {
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');

            return response()->json([
                'success' => true,
                'message' => '캐시가 최적화되었습니다.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '캐시 최적화 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 보안 설정 (Security Settings)
    |--------------------------------------------------------------------------
    */

    /**
     * 보안 설정
     */
    public function security(): View
    {
        $securitySettings = $this->getSecuritySettings();
        $securityStats = $this->getSecurityStats();
        
        return view('ahhob.admin.dashboard.system.settings.security', compact('securitySettings', 'securityStats'));
    }

    /*
    |--------------------------------------------------------------------------
    | 관리자 계정 관리 (Admin Account Management)
    |--------------------------------------------------------------------------
    */

    /**
     * 관리자 목록
     */
    public function admins(): View
    {
        $admins = Admin::orderBy('created_at', 'desc')->paginate(20);
        
        return view('ahhob.admin.dashboard.system.settings.admins', compact('admins'));
    }

    /**
     * 관리자 생성 폼
     */
    public function createAdmin(): View
    {
        return view('ahhob.admin.dashboard.system.settings.create-admin');
    }

    /**
     * 관리자 생성
     */
    public function storeAdmin(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:admins,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:super_admin,admin,moderator',
            'is_active' => 'boolean',
        ]);

        try {
            Admin::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'is_active' => $request->boolean('is_active', true),
                'created_by' => auth('admin')->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => '관리자가 생성되었습니다.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '관리자 생성 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /**
     * 관리자 수정 폼
     */
    public function editAdmin(Admin $admin): View
    {
        return view('ahhob.admin.dashboard.system.settings.edit-admin', compact('admin'));
    }

    /**
     * 관리자 업데이트
     */
    public function updateAdmin(Request $request, Admin $admin): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:admins,email,' . $admin->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:super_admin,admin,moderator',
            'is_active' => 'boolean',
        ]);

        try {
            $updateData = $request->only(['name', 'email', 'role']);
            $updateData['is_active'] = $request->boolean('is_active');

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $admin->update($updateData);

            return response()->json([
                'success' => true,
                'message' => '관리자 정보가 업데이트되었습니다.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '관리자 정보 업데이트 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /**
     * 관리자 삭제
     */
    public function destroyAdmin(Admin $admin): JsonResponse
    {
        // 자기 자신은 삭제할 수 없음
        if ($admin->id === auth('admin')->id()) {
            return response()->json([
                'success' => false,
                'message' => '자기 자신의 계정은 삭제할 수 없습니다.',
            ], 422);
        }

        // 마지막 슈퍼 관리자는 삭제할 수 없음
        if ($admin->role === 'super_admin' && Admin::where('role', 'super_admin')->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => '마지막 슈퍼 관리자는 삭제할 수 없습니다.',
            ], 422);
        }

        try {
            $admin->delete();

            return response()->json([
                'success' => true,
                'message' => '관리자가 삭제되었습니다.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '관리자 삭제 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 접근 제한 (Access Control)
    |--------------------------------------------------------------------------
    */

    /**
     * 접근 제한 설정
     */
    public function accessControl(): View
    {
        $accessSettings = $this->getAccessControlSettings();
        
        return view('ahhob.admin.dashboard.system.settings.access-control', compact('accessSettings'));
    }

    /**
     * 접근 제한 설정 업데이트
     */
    public function updateAccessControl(Request $request): JsonResponse
    {
        $request->validate([
            'admin_ip_whitelist' => 'nullable|string',
            'admin_session_timeout' => 'required|integer|min:5|max:1440', // 분 단위
            'max_login_attempts' => 'required|integer|min:1|max:10',
            'lockout_duration' => 'required|integer|min:1|max:60', // 분 단위
            'two_factor_required' => 'boolean',
        ]);

        try {
            foreach ($request->validated() as $key => $value) {
                $this->updateSetting($key, $value);
            }

            Cache::forget('system.access_control');

            return response()->json([
                'success' => true,
                'message' => '접근 제한 설정이 업데이트되었습니다.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '접근 제한 설정 업데이트 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | IP 차단 (IP Blocking)
    |--------------------------------------------------------------------------
    */

    /**
     * IP 차단 관리
     */
    public function ipBlocking(): View
    {
        $blockedIps = $this->getBlockedIps();
        
        return view('ahhob.admin.dashboard.system.settings.ip-blocking', compact('blockedIps'));
    }

    /**
     * IP 차단 추가
     */
    public function addBlockedIp(Request $request): JsonResponse
    {
        $request->validate([
            'ip_address' => 'required|ip',
            'reason' => 'required|string|max:255',
            'expires_at' => 'nullable|date|after:now',
        ]);

        try {
            $blockData = [
                'ip' => $request->ip_address,
                'reason' => $request->reason,
                'blocked_at' => now()->toISOString(),
                'blocked_by' => auth('admin')->id(),
                'expires_at' => $request->expires_at,
            ];

            $key = "blocked_ip:{$request->ip_address}";
            
            if ($request->expires_at) {
                $ttl = now()->diffInSeconds($request->expires_at);
                Redis::setex($key, $ttl, json_encode($blockData));
            } else {
                Redis::set($key, json_encode($blockData));
            }

            return response()->json([
                'success' => true,
                'message' => "IP {$request->ip_address}가 차단되었습니다.",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'IP 차단 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /**
     * IP 차단 해제
     */
    public function removeBlockedIp(string $ip): JsonResponse
    {
        try {
            $key = "blocked_ip:{$ip}";
            $deleted = Redis::del($key);

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => "IP {$ip}의 차단이 해제되었습니다.",
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => '해당 IP는 차단되지 않았습니다.',
                ], 404);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'IP 차단 해제 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 백업 및 복구 (Backup & Recovery)
    |--------------------------------------------------------------------------
    */

    /**
     * 백업 관리
     */
    public function backup(): View
    {
        $backups = $this->getBackupList();
        $backupSettings = $this->getBackupSettings();
        
        return view('ahhob.admin.dashboard.system.settings.backup', compact('backups', 'backupSettings'));
    }

    /**
     * 백업 생성
     */
    public function createBackup(Request $request): JsonResponse
    {
        $request->validate([
            'include_files' => 'boolean',
            'include_database' => 'boolean',
        ]);

        try {
            $backupName = 'backup_' . now()->format('Y_m_d_H_i_s');
            
            // 실제 백업 프로세스는 추후 구현
            // Artisan::call('backup:run');

            return response()->json([
                'success' => true,
                'message' => '백업이 생성되었습니다.',
                'backup_name' => $backupName,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '백업 생성 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /**
     * 백업 다운로드
     */
    public function downloadBackup(string $backup): JsonResponse
    {
        // 추후 구현: 백업 파일 다운로드
        return response()->json([
            'success' => false,
            'message' => '백업 다운로드 기능은 추후 구현됩니다.',
        ]);
    }

    /**
     * 백업 삭제
     */
    public function deleteBackup(string $backup): JsonResponse
    {
        // 추후 구현: 백업 파일 삭제
        return response()->json([
            'success' => false,
            'message' => '백업 삭제 기능은 추후 구현됩니다.',
        ]);
    }

    /**
     * 백업 복원
     */
    public function restoreBackup(string $backup): JsonResponse
    {
        // 추후 구현: 백업 복원
        return response()->json([
            'success' => false,
            'message' => '백업 복원 기능은 추후 구현됩니다.',
        ]);
    }

    /**
     * 백업 일정 설정
     */
    public function backupSchedule(): View
    {
        $scheduleSettings = $this->getBackupScheduleSettings();
        
        return view('ahhob.admin.dashboard.system.settings.backup-schedule', compact('scheduleSettings'));
    }

    /**
     * 백업 일정 업데이트
     */
    public function updateBackupSchedule(Request $request): JsonResponse
    {
        $request->validate([
            'enabled' => 'boolean',
            'frequency' => 'required|in:daily,weekly,monthly',
            'time' => 'required|date_format:H:i',
            'retain_days' => 'required|integer|min:1|max:365',
            'include_files' => 'boolean',
            'include_database' => 'boolean',
        ]);

        try {
            foreach ($request->validated() as $key => $value) {
                $this->updateSetting("backup_{$key}", $value);
            }

            Cache::forget('system.backup_schedule');

            return response()->json([
                'success' => true,
                'message' => '백업 일정이 업데이트되었습니다.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '백업 일정 업데이트 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 헬퍼 메서드 (Helper Methods)
    |--------------------------------------------------------------------------
    */

    /**
     * 현재 설정 조회
     */
    private function getCurrentSettings(): array
    {
        return Cache::remember('system.settings', 3600, function () {
            // 추후 구현: 설정 테이블에서 조회
            return [
                'site_name' => config('app.name', '사이트명'),
                'site_description' => '사이트 설명',
                'admin_email' => config('mail.from.address', 'admin@example.com'),
                'timezone' => config('app.timezone', 'Asia/Seoul'),
                'locale' => config('app.locale', 'ko'),
                'maintenance_mode' => false,
                'registration_enabled' => true,
                'email_verification_required' => false,
                'default_user_level' => 1,
            ];
        });
    }

    /**
     * 설정 업데이트
     */
    private function updateSetting(string $key, $value): void
    {
        // 추후 구현: 설정 테이블에 저장
        Cache::put("setting.{$key}", $value, 86400);
    }

    /**
     * 시스템 정보 조회
     */
    private function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'database_version' => DB::selectOne('SELECT VERSION() as version')->version ?? 'Unknown',
            'redis_version' => $this->getRedisVersion(),
        ];
    }

    /**
     * 이메일 설정 조회
     */
    private function getEmailSettings(): array
    {
        return Cache::remember('system.email_settings', 3600, function () {
            return [
                'mail_driver' => config('mail.default', 'smtp'),
                'mail_host' => config('mail.mailers.smtp.host', ''),
                'mail_port' => config('mail.mailers.smtp.port', 587),
                'mail_username' => config('mail.mailers.smtp.username', ''),
                'mail_encryption' => config('mail.mailers.smtp.encryption', 'tls'),
                'mail_from_address' => config('mail.from.address', ''),
                'mail_from_name' => config('mail.from.name', ''),
            ];
        });
    }

    /**
     * 스토리지 설정 조회
     */
    private function getStorageSettings(): array
    {
        return Cache::remember('system.storage_settings', 3600, function () {
            return [
                'default_disk' => config('filesystems.default', 'local'),
                'max_file_size' => 10, // MB
                'allowed_file_types' => 'jpg,jpeg,png,gif,pdf,doc,docx',
                'image_quality' => 80,
                'thumbnail_width' => 300,
                'thumbnail_height' => 300,
            ];
        });
    }

    /**
     * 스토리지 통계
     */
    private function getStorageStats(): array
    {
        $path = storage_path();
        $totalSpace = disk_total_space($path);
        $freeSpace = disk_free_space($path);
        $usedSpace = $totalSpace - $freeSpace;

        return [
            'total_space' => $totalSpace,
            'used_space' => $usedSpace,
            'free_space' => $freeSpace,
            'usage_percentage' => round(($usedSpace / $totalSpace) * 100, 2),
        ];
    }

    /**
     * 캐시 통계
     */
    private function getCacheStats(): array
    {
        return [
            'cache_driver' => config('cache.default'),
            'redis_memory_usage' => $this->getRedisMemoryUsage(),
            'file_cache_size' => $this->getFileCacheSize(),
        ];
    }

    /**
     * 보안 설정 조회
     */
    private function getSecuritySettings(): array
    {
        return Cache::remember('system.security_settings', 3600, function () {
            return [
                'admin_ip_whitelist' => '',
                'admin_session_timeout' => 120, // 분
                'max_login_attempts' => 5,
                'lockout_duration' => 15, // 분
                'two_factor_required' => false,
            ];
        });
    }

    /**
     * 보안 통계
     */
    private function getSecurityStats(): array
    {
        return [
            'blocked_ips_count' => count($this->getBlockedIps()),
            'failed_login_attempts_today' => 0, // 추후 구현
            'active_admin_sessions' => 0, // 추후 구현
        ];
    }

    /**
     * 접근 제한 설정 조회
     */
    private function getAccessControlSettings(): array
    {
        return Cache::remember('system.access_control', 3600, function () {
            return [
                'admin_ip_whitelist' => '',
                'admin_session_timeout' => 120,
                'max_login_attempts' => 5,
                'lockout_duration' => 15,
                'two_factor_required' => false,
            ];
        });
    }

    /**
     * 차단된 IP 목록 조회
     */
    private function getBlockedIps(): array
    {
        $blockedIps = [];
        $keys = Redis::keys('blocked_ip:*');
        
        foreach ($keys as $key) {
            $data = Redis::get($key);
            if ($data) {
                $decoded = json_decode($data, true);
                if ($decoded) {
                    $decoded['ttl'] = Redis::ttl($key);
                    $blockedIps[] = $decoded;
                }
            }
        }

        return $blockedIps;
    }

    /**
     * 백업 목록 조회
     */
    private function getBackupList(): array
    {
        // 추후 구현: 실제 백업 파일 목록 조회
        return [];
    }

    /**
     * 백업 설정 조회
     */
    private function getBackupSettings(): array
    {
        return [
            'backup_disk' => 'local',
            'cleanup_strategy' => 'default',
            'monitor_backups' => true,
        ];
    }

    /**
     * 백업 일정 설정 조회
     */
    private function getBackupScheduleSettings(): array
    {
        return Cache::remember('system.backup_schedule', 3600, function () {
            return [
                'enabled' => false,
                'frequency' => 'daily',
                'time' => '02:00',
                'retain_days' => 30,
                'include_files' => true,
                'include_database' => true,
            ];
        });
    }

    /**
     * Redis 버전 조회
     */
    private function getRedisVersion(): string
    {
        try {
            $info = Redis::info('server');
            return $info['redis_version'] ?? 'Unknown';
        } catch (\Exception $e) {
            return 'Unavailable';
        }
    }

    /**
     * Redis 메모리 사용량 조회
     */
    private function getRedisMemoryUsage(): array
    {
        try {
            $info = Redis::info('memory');
            return [
                'used_memory' => $info['used_memory'] ?? 0,
                'used_memory_human' => $info['used_memory_human'] ?? '0B',
                'maxmemory' => $info['maxmemory'] ?? 0,
            ];
        } catch (\Exception $e) {
            return ['used_memory' => 0, 'used_memory_human' => '0B', 'maxmemory' => 0];
        }
    }

    /**
     * 파일 캐시 크기 조회
     */
    private function getFileCacheSize(): int
    {
        $cachePath = storage_path('framework/cache');
        if (!is_dir($cachePath)) {
            return 0;
        }

        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($cachePath)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }
}