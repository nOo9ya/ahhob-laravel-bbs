<?php

namespace App\Models\Ahhob\User;

use App\Enums\ActivityType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserActivityLog extends Model
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
    protected $table = 'user_activity_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'activity_type',
        'related_type',
        'related_id',
        'activity_data',
        'ip_address',
        'user_agent',
        'referer_url',
        'session_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activity_type' => ActivityType::class,
            'activity_data' => 'array',
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
     * 활동을 수행한 사용자
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 관련된 모델 (다형적 관계)
     */
    public function related(): MorphTo
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
     * 활동 타입 라벨 접근자
     */
    public function getActivityTypeLabelAttribute(): string
    {
        return $this->activity_type->label();
    }

    /**
     * 활동 카테고리 접근자
     */
    public function getActivityCategoryAttribute(): string
    {
        return $this->activity_type->category();
    }

    /**
     * 중요한 활동인지 확인 접근자
     */
    public function getIsImportantAttribute(): bool
    {
        return $this->activity_type->isImportant();
    }

    /**
     * 브라우저 정보 접근자
     */
    public function getBrowserInfoAttribute(): string
    {
        if (empty($this->user_agent)) {
            return 'Unknown';
        }

        $parsed = LoginHistory::parseUserAgent($this->user_agent);
        return $parsed['browser'] . ' on ' . $parsed['os'];
    }

    /**
     * 활동 설명 접근자
     */
    public function getActivityDescriptionAttribute(): string
    {
        $description = $this->activity_type->label();
        
        if ($this->related) {
            $relatedName = method_exists($this->related, 'getDisplayName') 
                ? $this->related->getDisplayName()
                : (isset($this->related->title) ? $this->related->title : $this->related->name ?? '#' . $this->related->id);
            
            $description .= ": {$relatedName}";
        }

        return $description;
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 쿼리 스코프 (Query Scopes)
    |--------------------------------------------------------------------------
    */
    // region --- 쿼리 스코프 (Query Scopes) ---

    /**
     * 특정 활동 타입으로 조회
     */
    public function scopeOfType($query, ActivityType $type)
    {
        return $query->where('activity_type', $type);
    }

    /**
     * 특정 카테고리의 활동으로 조회
     */
    public function scopeOfCategory($query, string $category)
    {
        $types = ActivityType::getByCategory($category);
        return $query->whereIn('activity_type', $types);
    }

    /**
     * 중요한 활동만 조회
     */
    public function scopeImportant($query)
    {
        $importantTypes = array_filter(ActivityType::cases(), function($type) {
            return $type->isImportant();
        });
        
        return $query->whereIn('activity_type', $importantTypes);
    }

    /**
     * 특정 IP에서의 활동 조회
     */
    public function scopeFromIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * 특정 기간 내 활동 조회
     */
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * 오늘의 활동 조회
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * 이번 주 활동 조회
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 공개 메서드 (Public Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 공개 메서드 (Public Methods) ---

    /**
     * 사용자 활동 로그 생성
     */
    public static function createActivity(
        User $user,
        ActivityType $activityType,
        $related = null,
        array $activityData = [],
        string $ipAddress = null,
        string $userAgent = null,
        string $refererUrl = null
    ): self {
        return self::create([
            'user_id' => $user->id,
            'activity_type' => $activityType,
            'related_type' => $related ? get_class($related) : null,
            'related_id' => $related?->id,
            'activity_data' => $activityData,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
            'referer_url' => $refererUrl ?? request()->headers->get('referer'),
            'session_id' => session()->getId(),
        ]);
    }

    /**
     * 익명 활동 로그 생성 (비회원)
     */
    public static function createAnonymousActivity(
        ActivityType $activityType,
        $related = null,
        array $activityData = [],
        string $ipAddress = null,
        string $userAgent = null,
        string $refererUrl = null
    ): self {
        return self::create([
            'user_id' => null,
            'activity_type' => $activityType,
            'related_type' => $related ? get_class($related) : null,
            'related_id' => $related?->id,
            'activity_data' => $activityData,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
            'referer_url' => $refererUrl ?? request()->headers->get('referer'),
            'session_id' => session()->getId(),
        ]);
    }

    /**
     * 특정 사용자의 활동 통계
     */
    public static function getUserActivityStats(User $user, int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        return [
            'total_activities' => self::where('user_id', $user->id)
                ->where('created_at', '>=', $startDate)
                ->count(),
            'by_category' => self::where('user_id', $user->id)
                ->where('created_at', '>=', $startDate)
                ->get()
                ->groupBy('activity_category')
                ->map->count(),
            'by_day' => self::where('user_id', $user->id)
                ->where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->pluck('count', 'date'),
        ];
    }

    /**
     * IP 기반 의심스러운 활동 감지
     */
    public static function detectSuspiciousActivity(string $ipAddress, int $hours = 24): array
    {
        $startDate = now()->subHours($hours);
        
        $activities = self::where('ip_address', $ipAddress)
            ->where('created_at', '>=', $startDate)
            ->get();

        $userCount = $activities->pluck('user_id')->filter()->unique()->count();
        $activityCount = $activities->count();
        
        return [
            'is_suspicious' => $userCount > 5 || $activityCount > 100, // 임계값 설정
            'unique_users' => $userCount,
            'total_activities' => $activityCount,
            'activities_by_type' => $activities->groupBy('activity_type')->map->count(),
        ];
    }

    // endregion
}