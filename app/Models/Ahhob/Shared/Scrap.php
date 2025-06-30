<?php

namespace App\Models\Ahhob\Shared;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Scrap extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'scraps';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'scrapable_type',
        'scrapable_id',
        'user_id',
        'memo',
        'category',
        'sort_order',
        'ip_address',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * 스크랩 대상 모델 (다형적 관계)
     */
    public function scrapable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 스크랩한 사용자
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 쿼리 스코프: 특정 모델의 스크랩
     */
    public function scopeForModel($query, string $type, int $id)
    {
        return $query->where('scrapable_type', $type)->where('scrapable_id', $id);
    }

    /**
     * 쿼리 스코프: 특정 사용자의 스크랩
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 쿼리 스코프: 특정 카테고리의 스크랩
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * 쿼리 스코프: 정렬된 스크랩
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at', 'desc');
    }
}
