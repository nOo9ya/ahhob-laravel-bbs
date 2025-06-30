<?php

namespace App\Models\Ahhob\System;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'system_settings';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'validation_rules',
        'options',
        'input_type',
        'sort_order',
        'is_public',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'validation_rules' => 'array',
            'options' => 'array',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * 캐시 키 생성
     */
    private static function getCacheKey(string $key): string
    {
        return "system_setting:{$key}";
    }

    /**
     * 설정값 조회 (캐시 사용)
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = self::getCacheKey($key);
        
        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)
                ->where('is_active', true)
                ->first();
            
            if (!$setting) {
                return $default;
            }
            
            return self::castValue($setting->value, $setting->type);
        });
    }

    /**
     * 설정값 저장
     */
    public static function set(string $key, mixed $value): bool
    {
        $setting = self::where('key', $key)->first();
        
        if ($setting) {
            $setting->value = self::formatValue($value, $setting->type);
            $result = $setting->save();
        } else {
            // 새 설정 생성 시 기본 타입 자동 감지
            $type = self::detectType($value);
            $result = self::create([
                'key' => $key,
                'value' => self::formatValue($value, $type),
                'type' => $type,
                'label' => ucwords(str_replace(['_', '-'], ' ', $key)),
                'group' => 'general',
            ]);
        }
        
        // 캐시 무효화
        Cache::forget(self::getCacheKey($key));
        
        return (bool) $result;
    }

    /**
     * 여러 설정값을 한 번에 저장
     */
    public static function setMultiple(array $settings): bool
    {
        $success = true;
        
        foreach ($settings as $key => $value) {
            if (!self::set($key, $value)) {
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * 그룹별 설정 조회
     */
    public static function getByGroup(string $group): array
    {
        $settings = self::where('group', $group)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();
        
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = self::castValue($setting->value, $setting->type);
        }
        
        return $result;
    }

    /**
     * 값 타입 변환
     */
    private static function castValue(mixed $value, string $type): mixed
    {
        return match($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => json_decode($value, true),
            'array' => is_string($value) ? json_decode($value, true) : $value,
            default => $value,
        };
    }

    /**
     * 값 포맷팅
     */
    private static function formatValue(mixed $value, string $type): string
    {
        return match($type) {
            'boolean' => $value ? '1' : '0',
            'json', 'array' => json_encode($value),
            default => (string) $value,
        };
    }

    /**
     * 값 타입 자동 감지
     */
    private static function detectType(mixed $value): string
    {
        return match(true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_array($value) => 'array',
            default => 'string',
        };
    }

    /**
     * 쿼리 스코프: 활성화된 설정만
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 쿼리 스코프: 그룹별
     */
    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * 쿼리 스코프: 공개 설정만
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * 쿼리 스코프: 정렬된
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('label');
    }
}
