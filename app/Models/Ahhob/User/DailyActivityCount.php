<?php

namespace App\Models\Ahhob\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\User;
use Carbon\Carbon;

class DailyActivityCount extends Model
{
    /*
    |--------------------------------------------------------------------------
    | 모델 속성 (Attributes & Properties)
    |--------------------------------------------------------------------------
    */
    // region --- 모델 속성 (Attributes & Properties) ---
    
    protected $fillable = [
        'user_id',
        'activity_type',
        'target_id',
        'target_type',
        'activity_date',
        'count',
        'ip_address',
        'user_agent_hash',
        'device_fingerprint',
    ];

    protected $casts = [
        'activity_date' => 'date',
        'count' => 'integer',
        'device_fingerprint' => 'array',
    ];

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 관계 (Relationships)
    |--------------------------------------------------------------------------
    */
    // region --- 관계 (Relationships) ---

    /**
     * 사용자 관계
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 다형적 관계 - 활동 대상 (게시판, 게시글 등)
     */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 쿼리 스코프 (Query Scopes)
    |--------------------------------------------------------------------------
    */
    // region --- 쿼리 스코프 (Query Scopes) ---

    /**
     * 특정 날짜의 활동만 조회
     */
    public function scopeForDate($query, Carbon $date)
    {
        return $query->where('activity_date', $date->toDateString());
    }

    /**
     * 오늘 활동만 조회
     */
    public function scopeToday($query)
    {
        return $query->where('activity_date', today());
    }

    /**
     * 특정 사용자의 활동만 조회
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * 특정 활동 유형만 조회
     */
    public function scopeForActivity($query, string $activityType)
    {
        return $query->where('activity_type', $activityType);
    }

    /**
     * 특정 대상에 대한 활동만 조회
     */
    public function scopeForTarget($query, string $targetType, int $targetId = null)
    {
        $query = $query->where('target_type', $targetType);
        
        if ($targetId !== null) {
            $query->where('target_id', $targetId);
        }
        
        return $query;
    }

    /**
     * 특정 IP 주소의 활동만 조회
     */
    public function scopeForIp($query, string $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * 특정 기기의 활동만 조회
     */
    public function scopeForDevice($query, string $userAgentHash)
    {
        return $query->where('user_agent_hash', $userAgentHash);
    }

    /**
     * 스팸 의심 활동 조회 (동일 IP + 기기에서 여러 계정 활동)
     */
    public function scopeSuspiciousActivity($query, Carbon $date = null)
    {
        $date = $date ?? today();
        
        return $query->select('ip_address', 'user_agent_hash')
            ->selectRaw('COUNT(DISTINCT user_id) as unique_users')
            ->selectRaw('SUM(count) as total_activities')
            ->where('activity_date', $date)
            ->whereNotNull('ip_address')
            ->whereNotNull('user_agent_hash')
            ->groupBy('ip_address', 'user_agent_hash')
            ->havingRaw('COUNT(DISTINCT user_id) > 1')
            ->havingRaw('SUM(count) > 10'); // 임계값 설정
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 공개 메서드 (Public Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 공개 메서드 (Public Methods) ---

    /**
     * 활동 카운트 증가
     */
    public static function incrementActivity(
        User $user,
        string $activityType,
        string $targetType = null,
        int $targetId = null,
        string $ipAddress = null,
        string $userAgent = null
    ): self {
        $userAgentHash = $userAgent ? hash('sha256', $userAgent) : null;
        
        // 기기 핑거프린트 생성
        $deviceFingerprint = [
            'ip' => $ipAddress,
            'user_agent_hash' => $userAgentHash,
            'timestamp' => now()->toISOString(),
        ];

        return self::updateOrCreate(
            [
                'user_id' => $user->id,
                'activity_type' => $activityType,
                'target_id' => $targetId,
                'target_type' => $targetType,
                'activity_date' => today(),
            ],
            [
                'count' => \DB::raw('count + 1'),
                'ip_address' => $ipAddress,
                'user_agent_hash' => $userAgentHash,
                'device_fingerprint' => $deviceFingerprint,
            ]
        );
    }

    /**
     * 사용자의 특정 활동 오늘 카운트 조회
     */
    public static function getTodayCount(
        User $user,
        string $activityType,
        string $targetType = null,
        int $targetId = null
    ): int {
        return self::forUser($user)
            ->forActivity($activityType)
            ->forTarget($targetType, $targetId)
            ->today()
            ->sum('count');
    }

    /**
     * 스팸 의심 활동 감지
     */
    public function isSuspiciousActivity(): bool
    {
        if (!$this->ip_address || !$this->user_agent_hash) {
            return false;
        }

        // 동일 IP + 기기에서 다른 사용자들의 활동 확인
        $suspiciousCount = self::where('ip_address', $this->ip_address)
            ->where('user_agent_hash', $this->user_agent_hash)
            ->where('activity_date', $this->activity_date)
            ->where('user_id', '!=', $this->user_id)
            ->distinct('user_id')
            ->count();

        return $suspiciousCount > 0;
    }

    /**
     * 시간당 카운트 제한 확인
     */
    public static function getHourlyCount(
        User $user,
        string $activityType,
        Carbon $hour = null
    ): int {
        $hour = $hour ?? now();
        
        // 시간당 카운트는 별도 로직이 필요 (현재는 일일 카운트만 저장)
        // 실제 구현시에는 hourly_activity_counts 테이블이 필요할 수 있음
        return 0;
    }

    // endregion
}
