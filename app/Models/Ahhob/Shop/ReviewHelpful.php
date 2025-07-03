<?php

namespace App\Models\Ahhob\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class ReviewHelpful extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_id',
        'user_id',
    ];

    /*
    |--------------------------------------------------------------------------
    | 관계 (Relationships)
    |--------------------------------------------------------------------------
    */

    /**
     * 리뷰
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * 사용자
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | 정적 메서드 (Static Methods)
    |--------------------------------------------------------------------------
    */

    /**
     * 도움됨 토글
     */
    public static function toggle(Review $review, User $user): bool
    {
        $helpful = static::where([
            'review_id' => $review->id,
            'user_id' => $user->id,
        ])->first();

        if ($helpful) {
            $helpful->delete();
            $review->decrement('helpful_count');
            return false; // 도움됨 취소
        } else {
            static::create([
                'review_id' => $review->id,
                'user_id' => $user->id,
            ]);
            $review->increment('helpful_count');
            return true; // 도움됨 추가
        }
    }

    /**
     * 사용자가 리뷰를 도움됨으로 표시했는지 확인
     */
    public static function isHelpful(Review $review, User $user): bool
    {
        return static::where([
            'review_id' => $review->id,
            'user_id' => $user->id,
        ])->exists();
    }
}