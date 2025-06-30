<?php

namespace App\Enums;

enum AdminStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';

    /**
     * 관리자 상태 한글 이름 반환
     */
    public function label(): string
    {
        return match($this) {
            self::ACTIVE => '활성',
            self::INACTIVE => '비활성',
            self::SUSPENDED => '정지',
        };
    }

    /**
     * 상태별 색상 반환 (Tailwind CSS)
     */
    public function color(): string
    {
        return match($this) {
            self::ACTIVE => 'green',
            self::INACTIVE => 'gray',
            self::SUSPENDED => 'red',
        };
    }

    /**
     * 활성 상태 여부 확인
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * 로그인 가능 여부 확인
     */
    public function canLogin(): bool
    {
        return $this === self::ACTIVE;
    }
}