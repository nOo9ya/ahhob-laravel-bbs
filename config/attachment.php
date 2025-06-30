<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 파일 업로드 기본 설정
    |--------------------------------------------------------------------------
    |
    | 첨부파일 업로드와 관련된 기본 설정들을 정의합니다.
    |
    */

    'default_max_file_size' => env('ATTACHMENT_MAX_FILE_SIZE', 5120), // KB

    'default_allowed_types' => [
        'image/*',
        'application/pdf',
        'text/*',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ],

    /*
    |--------------------------------------------------------------------------
    | 이미지 처리 설정
    |--------------------------------------------------------------------------
    |
    | 이미지 파일 업로드 시 처리 방식을 설정합니다.
    |
    */

    'image' => [
        // 썸네일 생성 기본 설정
        'thumbnail' => [
            'width' => env('ATTACHMENT_THUMBNAIL_WIDTH', 300),
            'height' => env('ATTACHMENT_THUMBNAIL_HEIGHT', 200),
            'quality' => env('ATTACHMENT_THUMBNAIL_QUALITY', 85),
        ],

        // WebP 변환 설정
        'webp' => [
            // WebP 변환 모드
            // 'preserve' : 원본 형식 유지 (변환하지 않음)
            // 'optional' : 옵션으로 제공 (기본값: false)
            // 'auto'     : 파일 크기에 따라 자동 결정
            // 'force'    : 무조건 WebP로 변환
            'mode' => env('ATTACHMENT_WEBP_MODE', 'optional'),

            // WebP 변환 품질 (1-100)
            'quality' => env('ATTACHMENT_WEBP_QUALITY', 85),

            // auto 모드에서 WebP 변환을 위한 최소 파일 크기 (bytes)
            'min_size_for_conversion' => env('ATTACHMENT_WEBP_MIN_SIZE', 50 * 1024), // 50KB

            // WebP로 변환할 MIME 타입들
            'convertible_types' => [
                'image/jpeg',
                'image/png',
                // 'image/gif', // GIF는 애니메이션 때문에 제외
            ],

            // WebP 변환 시 원본 파일도 보관할지 여부
            'keep_original' => env('ATTACHMENT_WEBP_KEEP_ORIGINAL', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 스토리지 설정
    |--------------------------------------------------------------------------
    |
    | 파일 저장과 관련된 설정들입니다.
    |
    */

    'storage' => [
        'disk' => env('ATTACHMENT_DISK', 'public'),
        'path' => env('ATTACHMENT_PATH', 'attachments'),
    ],

];