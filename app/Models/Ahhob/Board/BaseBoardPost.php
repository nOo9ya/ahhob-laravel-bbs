<?php

namespace App\Models\Ahhob\Board;

use App\Models\Ahhob\Shared\Attachment;
use App\Models\Ahhob\Shared\PostLike;
use App\Models\Ahhob\Shared\Scrap;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

abstract class BaseBoardPost extends Model
{
    use HasFactory, SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | 모델 속성 (Attributes & Properties)
    |--------------------------------------------------------------------------
    */
    // region --- 모델 속성 (Attributes & Properties) ---

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'content',
        'password',
        'is_notice',
        'is_secret',
        'is_html',
        'author_name',
        'author_email',
        'author_ip',
        'slug',
        'excerpt',
        'meta_data',
        'status',
        'published_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_notice' => 'boolean',
            'is_secret' => 'boolean',
            'is_html' => 'boolean',
            'view_count' => 'integer',
            'like_count' => 'integer',
            'comment_count' => 'integer',
            'attachment_count' => 'integer',
            'meta_data' => 'array',
            'published_at' => 'datetime',
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
     * 게시글 작성자
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 게시글 댓글들 (동적으로 구현됨)
     */
    abstract public function comments(): HasMany;

    /**
     * 첨부파일들
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')
                   ->ordered();
    }

    /**
     * 이미지 첨부파일들만
     */
    public function imageAttachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')
                   ->images()
                   ->completed()
                   ->ordered();
    }

    /**
     * 공개 첨부파일들만
     */
    public function publicAttachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')
                   ->public()
                   ->completed()
                   ->ordered();
    }

    /**
     * 좋아요/싫어요들
     */
    public function likes(): MorphMany
    {
        return $this->morphMany(PostLike::class, 'likeable');
    }

    /**
     * 좋아요들만
     */
    public function likesOnly(): MorphMany
    {
        return $this->morphMany(PostLike::class, 'likeable')
                   ->likes();
    }

    /**
     * 싫어요들만
     */
    public function dislikesOnly(): MorphMany
    {
        return $this->morphMany(PostLike::class, 'likeable')
                   ->dislikes();
    }

    /**
     * 스크랩들
     */
    public function scraps(): MorphMany
    {
        return $this->morphMany(Scrap::class, 'scrapable')
                   ->ordered();
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 쿼리 스코프 (Query Scopes)
    |--------------------------------------------------------------------------
    */
    // region --- 쿼리 스코프 (Query Scopes) ---

    /**
     * 공개된 게시글만
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }

    /**
     * 공지사항
     */
    public function scopeNotices($query)
    {
        return $query->where('is_notice', true);
    }

    /**
     * 일반 게시글 (공지사항 제외)
     */
    public function scopeRegular($query)
    {
        return $query->where('is_notice', false);
    }

    /**
     * 검색
     */
    public function scopeSearch($query, $keyword)
    {
        return $query->where(function($q) use ($keyword) {
            $q->where('title', 'like', "%{$keyword}%")
              ->orWhere('content', 'like', "%{$keyword}%");
        });
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 접근자 & 변경자 (Accessors & Mutators)
    |--------------------------------------------------------------------------
    */
    // region --- 접근자 & 변경자 (Accessors & Mutators) ---

    /**
     * 작성자명 접근자
     */
    public function getAuthorNameDisplayAttribute(): string
    {
        return $this->user ? $this->user->nickname : ($this->author_name ?? '익명');
    }

    /**
     * URL 접근자 (추상 메서드)
     */
    abstract public function getUrlAttribute(): string;

    /**
     * 요약 접근자
     */
    public function getExcerptDisplayAttribute(): string
    {
        if ($this->excerpt) {
            return $this->excerpt;
        }

        return str_limit(strip_tags($this->content), 150);
    }

    /**
     * 좋아요 수 접근자
     */
    public function getLikeCountAttribute(): int
    {
        return $this->likesOnly()->count();
    }

    /**
     * 싫어요 수 접근자
     */
    public function getDislikeCountAttribute(): int
    {
        return $this->dislikesOnly()->count();
    }

    /**
     * 스크랩 수 접근자
     */
    public function getScrapCountAttribute(): int
    {
        return $this->scraps()->count();
    }

    /**
     * 좋아요 비율 접근자
     */
    public function getLikeRatioAttribute(): float
    {
        $totalLikes = $this->likes()->count();
        if ($totalLikes === 0) {
            return 0;
        }
        
        return $this->like_count / $totalLikes;
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 공개 메서드 (Public Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 공개 메서드 (Public Methods) ---

    /**
     * 조회수 증가
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * 좋아요 수 증가
     */
    public function incrementLikeCount(): void
    {
        $this->increment('like_count');
    }

    /**
     * 좋아요 수 감소
     */
    public function decrementLikeCount(): void
    {
        $this->decrement('like_count');
    }

    /**
     * 댓글 수 업데이트
     */
    public function updateCommentCount(): void
    {
        $this->update(['comment_count' => $this->comments()->count()]);
    }

    /**
     * 첨부파일 수 업데이트
     */
    public function updateAttachmentCount(): void
    {
        $this->update(['attachment_count' => $this->attachments()->completed()->count()]);
    }

    /**
     * 사용자가 이 게시글에 좋아요를 눌렀는지 확인
     */
    public function isLikedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->likes()
            ->where('user_id', $user->id)
            ->where('is_like', true)
            ->exists();
    }

    /**
     * 사용자가 이 게시글에 싫어요를 눌렀는지 확인
     */
    public function isDislikedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->likes()
            ->where('user_id', $user->id)
            ->where('is_like', false)
            ->exists();
    }

    /**
     * 사용자가 이 게시글을 스크랩했는지 확인
     */
    public function isScrapedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->scraps()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * 좋아요/싫어요 통계 조회
     */
    public function getLikeStats(): array
    {
        $likes = $this->like_count;
        $dislikes = $this->dislike_count;
        $total = $likes + $dislikes;

        return [
            'likes' => $likes,
            'dislikes' => $dislikes,
            'total' => $total,
            'like_ratio' => $total > 0 ? $likes / $total : 0,
        ];
    }

    /**
     * 비밀글 확인
     */
    public function checkPassword(string $password): bool
    {
        return $this->is_secret && $this->password === $password;
    }

    /**
     * 접근 권한 확인
     */
    public function canAccess(?User $user = null): bool
    {
        // 공개글은 누구나 접근 가능
        if (!$this->is_secret) {
            return true;
        }

        // 작성자는 항상 접근 가능
        if ($user && $this->user_id === $user->id) {
            return true;
        }

        // 관리자는 항상 접근 가능
        if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        return false;
    }

    // endregion
}