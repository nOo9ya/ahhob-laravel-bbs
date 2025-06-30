<?php

namespace App\Models\Ahhob\Board\Dynamic;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BoardNoticeComment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'board_notice_comments';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'post_id',
        'user_id',
        'content',
        'password',
        'is_secret',
        'is_html',
        'parent_id',
        'depth',
        'path',
        'author_name',
        'author_email',
        'author_ip',
        'status',
        'admin_memo',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'is_secret' => 'boolean',
            'is_html' => 'boolean',
            'like_count' => 'integer',
            'reply_count' => 'integer',
            'depth' => 'integer',
        ];
    }

    /**
     * 댓글이 속한 게시글
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(BoardNotice::class, 'post_id');
    }

    /**
     * 댓글 작성자
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 부모 댓글
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * 자식 댓글들
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * 작성자명 표시
     */
    public function getAuthorNameDisplayAttribute(): string
    {
        return $this->user ? $this->user->nickname : ($this->author_name ?? '익명');
    }
}