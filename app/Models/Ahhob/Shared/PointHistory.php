<?php

namespace App\Models\Ahhob\Shared;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PointHistory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'point_histories';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'pointable_type',
        'pointable_id',
        'points',
        'balance_before',
        'balance_after',
        'type',
        'description',
        'admin_memo',
        'admin_id',
        'expires_at',
        'is_expired',
        'user_ip',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'balance_before' => 'integer',
            'balance_after' => 'integer',
            'is_expired' => 'boolean',
            'expires_at' => 'date',
        ];
    }

    /**
     * 포인트를 받은/잃은 사용자
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 포인트를 조정한 관리자
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * 관련 모델 (다형적 관계)
     */
    public function pointable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 쿼리 스코프: 특정 사용자의 히스토리
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 쿼리 스코프: 획득한 포인트만
     */
    public function scopeEarned($query)
    {
        return $query->where('type', 'earned');
    }

    /**
     * 쿼리 스코프: 사용한 포인트만
     */
    public function scopeSpent($query)
    {
        return $query->where('type', 'spent');
    }

    /**
     * 쿼리 스코프: 만료된 포인트만
     */
    public function scopeExpired($query)
    {
        return $query->where('type', 'expired');
    }

    /**
     * 쿼리 스코프: 특정 사유의 히스토리
     */
    public function scopeByReason($query, string $reason)
    {
        return $query->where('reason', $reason);
    }

    /**
     * 쿼리 스코프: 만료되지 않은 포인트
     */
    public function scopeNotExpired($query)
    {
        return $query->whereNull('expired_at');
    }

    /**
     * 쿼리 스코프: 특정 기간의 히스토리
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * 쿼리 스코프: 오늘의 히스토리
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * 쿼리 스코프: 이번 달 히스토리
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    /**
     * 포인트 타입 상수
     */
    public const TYPE_EARNED = 'earned';
    public const TYPE_SPENT = 'spent';
    public const TYPE_EXPIRED = 'expired';

    /**
     * 포인트 사유 상수
     */
    public const REASON_POST_CREATE = 'post_create';
    public const REASON_COMMENT_CREATE = 'comment_create';
    public const REASON_LIKE_RECEIVED = 'like_received';
    public const REASON_DAILY_ATTENDANCE = 'daily_attendance';
    public const REASON_TRANSFER_SENT = 'transfer_sent';
    public const REASON_TRANSFER_RECEIVED = 'transfer_received';
    public const REASON_TRANSFER_FEE = 'transfer_fee';
    public const REASON_ADMIN_ADJUSTMENT = 'admin_adjustment';
    public const REASON_POINT_EXPIRY = 'point_expiry';

    /**
     * 포인트 사유별 한국어 설명
     */
    public function getReasonDisplayAttribute(): string
    {
        return match($this->reason) {
            self::REASON_POST_CREATE => '게시글 작성',
            self::REASON_COMMENT_CREATE => '댓글 작성',
            self::REASON_LIKE_RECEIVED => '좋아요 받음',
            self::REASON_DAILY_ATTENDANCE => '출석 체크',
            self::REASON_TRANSFER_SENT => '포인트 전송',
            self::REASON_TRANSFER_RECEIVED => '포인트 받음',
            self::REASON_TRANSFER_FEE => '전송 수수료',
            self::REASON_ADMIN_ADJUSTMENT => '관리자 조정',
            self::REASON_POINT_EXPIRY => '포인트 만료',
            default => $this->reason,
        };
    }

    /**
     * 포인트 타입별 한국어 설명
     */
    public function getTypeDisplayAttribute(): string
    {
        return match($this->type) {
            self::TYPE_EARNED => '획득',
            self::TYPE_SPENT => '사용',
            self::TYPE_EXPIRED => '만료',
            default => $this->type,
        };
    }

    /**
     * 포인트 변화량 표시 (+ 또는 - 기호 포함)
     */
    public function getAmountWithSignAttribute(): string
    {
        return ($this->amount > 0 ? '+' : '') . number_format($this->amount);
    }

    /**
     * 만료 여부 확인
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expired_at !== null;
    }

    /**
     * 만료 예정 여부 확인
     */
    public function getWillExpireSoonAttribute(): bool
    {
        if (!$this->expires_at || $this->is_expired) {
            return false;
        }

        return $this->expires_at->diffInDays(now()) <= 7;
    }
}