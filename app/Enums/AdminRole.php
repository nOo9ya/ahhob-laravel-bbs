<?php

namespace App\Enums;

enum AdminRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case MANAGER = 'manager';

    /**
     * 관리자 역할 한글 이름 반환
     */
    public function label(): string
    {
        return match($this) {
            self::SUPER_ADMIN => '슈퍼 관리자',
            self::ADMIN => '관리자',
            self::MANAGER => '매니저',
        };
    }

    /**
     * 권한 레벨 반환 (높을수록 강한 권한)
     */
    public function level(): int
    {
        return match($this) {
            self::SUPER_ADMIN => 100,
            self::ADMIN => 50,
            self::MANAGER => 10,
        };
    }

    /**
     * 슈퍼 관리자 여부 확인
     */
    public function isSuperAdmin(): bool
    {
        return $this === self::SUPER_ADMIN;
    }

    /**
     * 특정 역할보다 높은 권한인지 확인
     */
    public function hasHigherLevelThan(AdminRole $role): bool
    {
        return $this->level() > $role->level();
    }

    /**
     * 하드 삭제 권한 여부 확인
     */
    public function canHardDelete(): bool
    {
        return $this === self::SUPER_ADMIN;
    }

    /**
     * 역할별 색상 반환 (Tailwind CSS)
     */
    public function color(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'red',
            self::ADMIN => 'blue',
            self::MANAGER => 'green',
        };
    }
}