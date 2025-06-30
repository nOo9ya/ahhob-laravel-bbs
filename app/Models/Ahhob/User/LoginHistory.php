<?php

namespace App\Models\Ahhob\User;

use App\Enums\SocialProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LoginHistory extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | 모델 속성 (Attributes & Properties)
    |--------------------------------------------------------------------------
    */
    // region --- 모델 속성 (Attributes & Properties) ---

    /**
     * The table associated with the model.
     */
    protected $table = 'login_histories';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'authenticatable_type',
        'authenticatable_id',
        'ip_address',
        'user_agent',
        'browser',
        'os',
        'device_type',
        'location',
        'login_method',
        'status',
        'failure_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'string', // 'success' or 'failed'
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
     * 로그인한 인증 주체 (User 또는 Admin)
     */
    public function authenticatable(): MorphTo
    {
        return $this->morphTo();
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 접근자 & 변경자 (Accessors & Mutators)
    |--------------------------------------------------------------------------
    */
    // region --- 접근자 & 변경자 (Accessors & Mutators) ---

    /**
     * 로그인 성공 여부 접근자
     */
    public function getIsSuccessAttribute(): bool
    {
        return $this->status === 'success';
    }

    /**
     * 로그인 실패 여부 접근자
     */
    public function getIsFailedAttribute(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * 소셜 로그인 여부 접근자
     */
    public function getIsSocialLoginAttribute(): bool
    {
        return in_array($this->login_method, ['google', 'kakao', 'naver', 'apple']);
    }

    /**
     * 로그인 방법 라벨 접근자
     */
    public function getLoginMethodLabelAttribute(): string
    {
        if ($this->login_method === 'email') {
            return '이메일';
        }

        $provider = SocialProvider::tryFrom($this->login_method);
        return $provider ? $provider->label() : $this->login_method;
    }

    /**
     * 디바이스 아이콘 접근자
     */
    public function getDeviceIconAttribute(): string
    {
        return match($this->device_type) {
            'mobile' => 'fas fa-mobile-alt',
            'tablet' => 'fas fa-tablet-alt',
            'desktop' => 'fas fa-desktop',
            default => 'fas fa-question-circle',
        };
    }

    /**
     * 상태 색상 접근자
     */
    public function getStatusColorAttribute(): string
    {
        return $this->is_success ? 'green' : 'red';
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 쿼리 스코프 (Query Scopes)
    |--------------------------------------------------------------------------
    */
    // region --- 쿼리 스코프 (Query Scopes) ---

    /**
     * 성공한 로그인만 조회
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * 실패한 로그인만 조회
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * 특정 IP 주소로 조회
     */
    public function scopeFromIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * 특정 기간 내 로그인 조회
     */
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * 소셜 로그인만 조회
     */
    public function scopeSocialLogins($query)
    {
        return $query->whereIn('login_method', ['google', 'kakao', 'naver', 'apple']);
    }

    /**
     * 이메일 로그인만 조회
     */
    public function scopeEmailLogins($query)
    {
        return $query->where('login_method', 'email');
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 공개 메서드 (Public Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 공개 메서드 (Public Methods) ---

    /**
     * User-Agent 정보를 파싱하여 브라우저와 OS 정보 추출
     */
    public static function parseUserAgent(string $userAgent): array
    {
        $browser = 'Unknown';
        $os = 'Unknown';
        $deviceType = 'desktop';

        // 간단한 User-Agent 파싱 (실제로는 더 정교한 라이브러리 사용 권장)
        if (preg_match('/Chrome\/[\d.]+/', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox\/[\d.]+/', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari\/[\d.]+/', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Edge\/[\d.]+/', $userAgent)) {
            $browser = 'Edge';
        }

        if (preg_match('/Windows NT/', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/Mac OS X/', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/Linux/', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/iPhone|iPad/', $userAgent)) {
            $os = 'iOS';
            $deviceType = preg_match('/iPad/', $userAgent) ? 'tablet' : 'mobile';
        } elseif (preg_match('/Android/', $userAgent)) {
            $os = 'Android';
            $deviceType = 'mobile';
        }

        // 모바일/태블릿 감지
        if (preg_match('/Mobile|Android|iPhone/', $userAgent) && !preg_match('/iPad/', $userAgent)) {
            $deviceType = 'mobile';
        } elseif (preg_match('/Tablet|iPad/', $userAgent)) {
            $deviceType = 'tablet';
        }

        return [
            'browser' => $browser,
            'os' => $os,
            'device_type' => $deviceType,
        ];
    }

    /**
     * 로그인 기록 생성
     */
    public static function createLoginRecord(
        $authenticatable,
        string $ipAddress,
        string $userAgent,
        string $loginMethod = 'email',
        string $status = 'success',
        string $failureReason = null
    ): self {
        $parsed = self::parseUserAgent($userAgent);

        return self::create([
            'authenticatable_type' => get_class($authenticatable),
            'authenticatable_id' => $authenticatable->id,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'browser' => $parsed['browser'],
            'os' => $parsed['os'],
            'device_type' => $parsed['device_type'],
            'login_method' => $loginMethod,
            'status' => $status,
            'failure_reason' => $failureReason,
        ]);
    }

    // endregion
}