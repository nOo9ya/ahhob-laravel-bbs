<?php

namespace App\Helpers;

use App\Models\Ahhob\System\SystemSetting;

class SystemSettingHelper
{
    /**
     * 첨부파일 설정 조회
     */
    public static function getAttachmentSettings(): array
    {
        return [
            'max_file_size' => SystemSetting::get('attachment.default_max_file_size', 5120),
            'webp' => [
                'mode' => SystemSetting::get('attachment.webp.mode', 'optional'),
                'quality' => SystemSetting::get('attachment.webp.quality', 85),
                'min_size_for_conversion' => SystemSetting::get('attachment.webp.min_size_for_conversion', 51200),
                'convertible_types' => SystemSetting::get('attachment.webp.convertible_types', ['image/jpeg', 'image/png']),
                'keep_original' => SystemSetting::get('attachment.webp.keep_original', false),
            ],
            'thumbnail' => [
                'width' => SystemSetting::get('attachment.thumbnail.width', 300),
                'height' => SystemSetting::get('attachment.thumbnail.height', 200),
                'quality' => SystemSetting::get('attachment.thumbnail.quality', 85),
            ],
        ];
    }

    /**
     * WebP 설정만 조회
     */
    public static function getWebPSettings(): array
    {
        return [
            'mode' => SystemSetting::get('attachment.webp.mode', 'optional'),
            'quality' => SystemSetting::get('attachment.webp.quality', 85),
            'min_size_for_conversion' => SystemSetting::get('attachment.webp.min_size_for_conversion', 51200),
            'convertible_types' => SystemSetting::get('attachment.webp.convertible_types', ['image/jpeg', 'image/png']),
            'keep_original' => SystemSetting::get('attachment.webp.keep_original', false),
        ];
    }

    /**
     * 썸네일 설정만 조회
     */
    public static function getThumbnailSettings(): array
    {
        return [
            'width' => SystemSetting::get('attachment.thumbnail.width', 300),
            'height' => SystemSetting::get('attachment.thumbnail.height', 200),
            'quality' => SystemSetting::get('attachment.thumbnail.quality', 85),
        ];
    }

    /**
     * 특정 그룹의 모든 설정 조회
     */
    public static function getSettingsByGroup(string $group): array
    {
        return SystemSetting::getByGroup($group);
    }

    /**
     * 설정값 조회 (단축 함수)
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return SystemSetting::get($key, $default);
    }

    /**
     * 설정값 저장 (단축 함수)
     */
    public static function set(string $key, mixed $value): bool
    {
        return SystemSetting::set($key, $value);
    }

    /**
     * 여러 설정값 한 번에 저장
     */
    public static function setMultiple(array $settings): bool
    {
        return SystemSetting::setMultiple($settings);
    }
}