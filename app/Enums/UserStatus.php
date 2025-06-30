<?php

namespace App\Enums;

enum UserStatus: string
{
    case ACTIVE = 'active';
    case DORMANT = 'dormant';
    case SUSPENDED = 'suspended';
    case BANNED = 'banned';

    /**
     * 사용자 상태 한글 이름 반환
     */
    public function label(): string
    {
        return match($this) {
            self::ACTIVE => '활성',
            self::DORMANT => '휴면',
            self::SUSPENDED => '정지',
            self::BANNED => '차단',
        };
    }

    /**
     * 사용자 상태별 색상 반환 (Tailwind CSS)
     */
    public function color(): string
    {
        return match($this) {
            self::ACTIVE => 'green',
            self::DORMANT => 'yellow',
            self::SUSPENDED => 'orange',
            self::BANNED => 'red',
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
        return in_array($this, [self::ACTIVE, self::DORMANT]);
    }
}