<?php

namespace App\Models\Ahhob\Shared;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PostLike extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'post_likes';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'likeable_type',
        'likeable_id',
        'user_id',
        'is_like',
        'ip_address',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'is_like' => 'boolean',
        ];
    }

    /**
     * 좋아요/싫어요 대상 모델 (다형적 관계)
     */
    public function likeable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 좋아요/싫어요를 누른 사용자
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 쿼리 스코프: 좋아요만
     */
    public function scopeLikes($query)
    {
        return $query->where('is_like', true);
    }

    /**
     * 쿼리 스코프: 싫어요만
     */
    public function scopeDislikes($query)
    {
        return $query->where('is_like', false);
    }

    /**
     * 쿼리 스코프: 특정 모델의 좋아요/싫어요
     */
    public function scopeForModel($query, string $type, int $id)
    {
        return $query->where('likeable_type', $type)->where('likeable_id', $id);
    }

    /**
     * 쿼리 스코프: 특정 사용자의 좋아요/싫어요
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
