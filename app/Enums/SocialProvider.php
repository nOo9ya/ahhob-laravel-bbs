<?php

namespace App\Enums;

enum SocialProvider: string
{
    case GOOGLE = 'google';
    case KAKAO = 'kakao';
    case NAVER = 'naver';
    case APPLE = 'apple';

    /**
     * 소셜 제공자 한글 이름 반환
     */
    public function label(): string
    {
        return match($this) {
            self::GOOGLE => '구글',
            self::KAKAO => '카카오',
            self::NAVER => '네이버',
            self::APPLE => '애플',
        };
    }

    /**
     * 제공자별 아이콘 클래스 반환
     */
    public function iconClass(): string
    {
        return match($this) {
            self::GOOGLE => 'fab fa-google',
            self::KAKAO => 'fab fa-kakao',
            self::NAVER => 'fab fa-naver',
            self::APPLE => 'fab fa-apple',
        };
    }

    /**
     * 제공자별 브랜드 색상 반환
     */
    public function brandColor(): string
    {
        return match($this) {
            self::GOOGLE => '#DB4437',
            self::KAKAO => '#FEE500',
            self::NAVER => '#03C75A',
            self::APPLE => '#000000',
        };
    }

    /**
     * OAuth 설정 키 반환
     */
    public function configKey(): string
    {
        return "services.{$this->value}";
    }

    /**
     * 활성화된 소셜 제공자 목록 반환
     */
    public static function getActive(): array
    {
        return array_filter(self::cases(), function($provider) {
            return config("{$provider->configKey()}.client_id") !== null;
        });
    }
}