<?php

namespace App\Models\Ahhob\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Ahhob\Board\Board;
use App\Enums\ActivityType;

class PostingLimit extends Model
{
    /*
    |--------------------------------------------------------------------------
    | 모델 속성 (Attributes & Properties)
    |--------------------------------------------------------------------------
    */
    // region --- 모델 속성 (Attributes & Properties) ---
    
    protected $fillable = [
        'name',
        'target_type',
        'target_id',
        'activity_type',
        'daily_limit',
        'hourly_limit',
        'time_restrictions',
        'is_active',
        'description',
    ];

    protected $casts = [
        'time_restrictions' => 'array',
        'is_active' => 'boolean',
        'daily_limit' => 'integer',
        'hourly_limit' => 'integer',
    ];

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 관계 (Relationships)
    |--------------------------------------------------------------------------
    */
    // region --- 관계 (Relationships) ---

    /**
     * 대상이 사용자인 경우 사용자 관계
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_id')->where('target_type', 'user');
    }

    /**
     * 대상이 게시판인 경우 게시판 관계
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class, 'target_id')->where('target_type', 'board');
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 쿼리 스코프 (Query Scopes)
    |--------------------------------------------------------------------------
    */
    // region --- 쿼리 스코프 (Query Scopes) ---

    /**
     * 활성화된 제한 정책만 조회
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 특정 활동 유형에 대한 제한 정책 조회
     */
    public function scopeForActivity($query, string $activityType)
    {
        return $query->where('activity_type', $activityType);
    }

    /**
     * 특정 대상에 대한 제한 정책 조회
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
     * 사용자에게 적용되는 제한 정책 조회
     */
    public function scopeForUser($query, User $user, string $activityType, Board $board = null)
    {
        return $query->active()
            ->where('activity_type', $activityType)
            ->where(function ($q) use ($user, $board) {
                // 전역 제한
                $q->orWhere('target_type', 'global');
                
                // 사용자별 제한
                $q->orWhere(function ($sq) use ($user) {
                    $sq->where('target_type', 'user')
                       ->where('target_id', $user->id);
                });
                
                // 사용자 레벨별 제한
                $q->orWhere(function ($sq) use ($user) {
                    $sq->where('target_type', 'user_level')
                       ->where('target_id', $user->level);
                });
                
                // 게시판별 제한
                if ($board) {
                    $q->orWhere(function ($sq) use ($board) {
                        $sq->where('target_type', 'board')
                           ->where('target_id', $board->id);
                    });
                }
            });
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 공개 메서드 (Public Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 공개 메서드 (Public Methods) ---

    /**
     * 현재 시간대에 제한이 적용되는지 확인
     */
    public function isTimeRestricted(): bool
    {
        if (empty($this->time_restrictions)) {
            return false;
        }

        $currentHour = now()->hour;
        $currentDay = strtolower(now()->format('l')); // monday, tuesday, etc.

        // 시간대별 제한 확인
        if (isset($this->time_restrictions['hours'])) {
            $restrictedHours = $this->time_restrictions['hours'];
            if (in_array($currentHour, $restrictedHours)) {
                return true;
            }
        }

        // 요일별 제한 확인
        if (isset($this->time_restrictions['days'])) {
            $restrictedDays = $this->time_restrictions['days'];
            if (in_array($currentDay, $restrictedDays)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 제한 정책의 우선순위 반환 (낮을수록 높은 우선순위)
     */
    public function getPriority(): int
    {
        return match ($this->target_type) {
            'user' => 1,           // 개별 사용자 제한이 최우선
            'board' => 2,          // 게시판별 제한
            'user_level' => 3,     // 사용자 레벨별 제한
            'global' => 4,         // 전역 제한이 최후순위
            default => 99,
        };
    }

    // endregion
}
