<?php

namespace Database\Seeders;

use App\Models\Ahhob\System\SystemSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attachmentSettings = [
            [
                'key' => 'attachment.default_max_file_size',
                'value' => '5120',
                'type' => 'integer',
                'group' => 'attachment',
                'label' => '기본 최대 파일 크기',
                'description' => '업로드 가능한 기본 최대 파일 크기 (KB)',
                'input_type' => 'number',
                'validation_rules' => ['required', 'integer', 'min:1', 'max:102400'],
                'sort_order' => 1,
            ],
            [
                'key' => 'attachment.webp.mode',
                'value' => 'optional',
                'type' => 'string',
                'group' => 'attachment',
                'label' => 'WebP 변환 모드',
                'description' => 'WebP 이미지 변환 방식을 설정합니다',
                'input_type' => 'select',
                'options' => [
                    'preserve' => '원본 형식 유지',
                    'optional' => '선택적 변환 (기본: 변환하지 않음)',
                    'auto' => '자동 결정 (파일 크기 기준)',
                    'force' => '무조건 WebP로 변환'
                ],
                'validation_rules' => ['required', 'in:preserve,optional,auto,force'],
                'sort_order' => 2,
            ],
            [
                'key' => 'attachment.webp.quality',
                'value' => '85',
                'type' => 'integer',
                'group' => 'attachment',
                'label' => 'WebP 품질',
                'description' => 'WebP 변환 시 이미지 품질 (1-100)',
                'input_type' => 'number',
                'validation_rules' => ['required', 'integer', 'min:1', 'max:100'],
                'sort_order' => 3,
            ],
            [
                'key' => 'attachment.webp.min_size_for_conversion',
                'value' => '51200',
                'type' => 'integer',
                'group' => 'attachment',
                'label' => 'WebP 자동 변환 최소 크기',
                'description' => 'auto 모드에서 WebP로 변환할 최소 파일 크기 (bytes)',
                'input_type' => 'number',
                'validation_rules' => ['required', 'integer', 'min:1'],
                'sort_order' => 4,
            ],
            [
                'key' => 'attachment.webp.convertible_types',
                'value' => '["image/jpeg","image/png"]',
                'type' => 'json',
                'group' => 'attachment',
                'label' => 'WebP 변환 가능한 MIME 타입',
                'description' => 'WebP로 변환할 수 있는 이미지 MIME 타입들',
                'input_type' => 'textarea',
                'validation_rules' => ['required', 'json'],
                'sort_order' => 5,
            ],
            [
                'key' => 'attachment.webp.keep_original',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'attachment',
                'label' => '원본 파일 보관',
                'description' => 'WebP 변환 시 원본 파일도 함께 보관할지 여부',
                'input_type' => 'checkbox',
                'validation_rules' => ['boolean'],
                'sort_order' => 6,
            ],
            [
                'key' => 'attachment.thumbnail.width',
                'value' => '300',
                'type' => 'integer',
                'group' => 'attachment',
                'label' => '썸네일 가로 크기',
                'description' => '자동 생성되는 썸네일의 최대 가로 크기 (px)',
                'input_type' => 'number',
                'validation_rules' => ['required', 'integer', 'min:50', 'max:1000'],
                'sort_order' => 7,
            ],
            [
                'key' => 'attachment.thumbnail.height',
                'value' => '200',
                'type' => 'integer',
                'group' => 'attachment',
                'label' => '썸네일 세로 크기',
                'description' => '자동 생성되는 썸네일의 최대 세로 크기 (px)',
                'input_type' => 'number',
                'validation_rules' => ['required', 'integer', 'min:50', 'max:1000'],
                'sort_order' => 8,
            ],
            [
                'key' => 'attachment.thumbnail.quality',
                'value' => '85',
                'type' => 'integer',
                'group' => 'attachment',
                'label' => '썸네일 품질',
                'description' => '썸네일 생성 시 이미지 품질 (1-100)',
                'input_type' => 'number',
                'validation_rules' => ['required', 'integer', 'min:1', 'max:100'],
                'sort_order' => 9,
            ],
        ];

        foreach ($attachmentSettings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
