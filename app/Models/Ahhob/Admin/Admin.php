<?php

namespace App\Models\Ahhob\Admin;

use App\Enums\AdminRole;
use App\Enums\AdminStatus;
use App\Models\Ahhob\User\LoginHistory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable;

    /*
    |--------------------------------------------------------------------------
    | 모델 속성 (Attributes & Properties)
    |--------------------------------------------------------------------------
    */
    // region --- 모델 속성 (Attributes & Properties) ---

    /**
     * The table associated with the model.
     */
    protected $table = 'admins';

    /**
     * The guard associated with the model.
     */
    protected $guard = 'admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',           // Laravel 기본 - 실명
        'username',       // 추가 - 로그인 아이디  
        'email',          // Laravel 기본
        'password',       // Laravel 기본
        'role',           // 추가 - 관리자 역할
        'permissions',    // 추가 - 권한 배열
        'status',         // 추가 - 관리자 상태
        'memo',           // 추가 - 관리자 메모
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => AdminRole::class,
            'status' => AdminStatus::class,
            'permissions' => 'array',
            'last_login_at' => 'datetime',
        ];
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 관계 (Relationships)
    |--------------------------------------------------------------------------
    */
    // region --- 관계 (Relationships) ---

    /**
     * 관리자의 로그인 기록 (다형적 관계)
     */
    public function loginHistories(): MorphMany
    {
        return $this->morphMany(LoginHistory::class, 'authenticatable');
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 접근자 & 변경자 (Accessors & Mutators)
    |--------------------------------------------------------------------------
    */
    // region --- 접근자 & 변경자 (Accessors & Mutators) ---

    /**
     * 관리자 표시 이름 접근자
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name . " ({$this->username})";
    }

    /**
     * 역할 레벨 접근자
     */
    public function getRoleLevelAttribute(): int
    {
        return $this->role->level();
    }

    /**
     * 권한 목록 접근자
     */
    public function getPermissionListAttribute(): array
    {
        return $this->permissions ?? [];
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 공개 메서드 (Public Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 공개 메서드 (Public Methods) ---

    /**
     * 활성 상태 관리자인지 확인
     */
    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    /**
     * 로그인 가능한 상태인지 확인
     */
    public function canLogin(): bool
    {
        return $this->status->canLogin();
    }

    /**
     * 슈퍼 관리자인지 확인
     */
    public function isSuperAdmin(): bool
    {
        return $this->role->isSuperAdmin();
    }

    /**
     * 특정 역할보다 높은 권한인지 확인
     */
    public function hasHigherLevelThan(AdminRole $role): bool
    {
        return $this->role->hasHigherLevelThan($role);
    }

    /**
     * 하드 삭제 권한 여부 확인
     */
    public function canHardDelete(): bool
    {
        return $this->role->canHardDelete();
    }

    /**
     * 특정 권한을 가지고 있는지 확인
     */
    public function hasPermission(string $permission): bool
    {
        // 슈퍼 관리자는 모든 권한 보유
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($permission, $this->permission_list);
    }

    /**
     * 여러 권한 중 하나라도 가지고 있는지 확인
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 모든 권한을 가지고 있는지 확인
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 권한 추가
     */
    public function givePermission(string $permission): void
    {
        $permissions = $this->permission_list;
        
        if (!in_array($permission, $permissions)) {
            $permissions[] = $permission;
            $this->update(['permissions' => $permissions]);
        }
    }

    /**
     * 권한 제거
     */
    public function revokePermission(string $permission): void
    {
        $permissions = array_filter($this->permission_list, function($p) use ($permission) {
            return $p !== $permission;
        });
        
        $this->update(['permissions' => array_values($permissions)]);
    }

    /**
     * 마지막 로그인 정보 업데이트
     */
    public function updateLastLogin(string $ipAddress): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress,
        ]);
    }

    // endregion
}