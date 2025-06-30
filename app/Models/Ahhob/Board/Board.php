<?php

namespace App\Models\Ahhob\Board;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Artisan;

class Board extends Model
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
    protected $table = 'boards';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'board_group_id',
        'name',
        'slug',
        'description',
        'list_template',
        'view_template',
        'write_template',
        'read_permission',
        'write_permission',
        'comment_permission',
        'use_comment',
        'use_attachment',
        'use_editor',
        'use_like',
        'use_secret',
        'use_notice',
        'posts_per_page',
        'max_attachment_size',
        'max_attachment_count',
        'write_point',
        'comment_point',
        'read_point',
        'sort_order',
        'is_active',
        'post_count',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'use_comment' => 'boolean',
            'use_attachment' => 'boolean',
            'use_editor' => 'boolean',
            'use_like' => 'boolean',
            'use_secret' => 'boolean',
            'use_notice' => 'boolean',
            'is_active' => 'boolean',
            'posts_per_page' => 'integer',
            'max_attachment_size' => 'integer',
            'max_attachment_count' => 'integer',
            'write_point' => 'integer',
            'comment_point' => 'integer',
            'read_point' => 'integer',
            'sort_order' => 'integer',
            'post_count' => 'integer',
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
     * 게시판이 속한 그룹
     */
    public function boardGroup(): BelongsTo
    {
        return $this->belongsTo(BoardGroup::class);
    }

    /**
     * 게시판 관리자들
     */
    public function managers(): HasMany
    {
        return $this->hasMany(BoardManager::class);
    }

    /**
     * 활성화된 게시판 관리자들
     */
    public function activeManagers(): HasMany
    {
        return $this->hasMany(BoardManager::class)->where('is_active', true);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 모델 이벤트 (Model Events)
    |--------------------------------------------------------------------------
    */
    // region --- 모델 이벤트 (Model Events) ---

    /**
     * 모델 부팅
     */
    protected static function boot()
    {
        parent::boot();

        // 게시판 생성 시 동적 테이블 생성
        static::created(function ($board) {
            Artisan::call('board:create', ['slug' => $board->slug]);
        });

        // 게시판 삭제 시 동적 테이블 삭제
        static::deleting(function ($board) {
            Artisan::call('board:delete', ['slug' => $board->slug]);
        });
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 쿼리 스코프 (Query Scopes)
    |--------------------------------------------------------------------------
    */
    // region --- 쿼리 스코프 (Query Scopes) ---

    /**
     * 활성화된 게시판만 조회
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 특정 그룹의 게시판들
     */
    public function scopeInGroup($query, $groupId)
    {
        return $query->where('board_group_id', $groupId);
    }

    /**
     * 정렬 순서대로 조회
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 접근자 & 변경자 (Accessors & Mutators)
    |--------------------------------------------------------------------------
    */
    // region --- 접근자 & 변경자 (Accessors & Mutators) ---

    /**
     * 게시글 테이블명 접근자
     */
    public function getPostTableNameAttribute(): string
    {
        return "board_{$this->slug}";
    }

    /**
     * 댓글 테이블명 접근자
     */
    public function getCommentTableNameAttribute(): string
    {
        return "board_{$this->slug}_comments";
    }

    /**
     * URL 접근자
     */
    public function getUrlAttribute(): string
    {
        return route('board.list', $this->slug);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 공개 메서드 (Public Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 공개 메서드 (Public Methods) ---

    /**
     * 동적 게시글 모델 클래스명 생성
     */
    public function getPostModelClass(): string
    {
        $className = 'Board' . ucfirst(camel_case($this->slug));
        return "App\\Models\\Ahhob\\Board\\Dynamic\\{$className}";
    }

    /**
     * 동적 댓글 모델 클래스명 생성
     */
    public function getCommentModelClass(): string
    {
        $className = 'Board' . ucfirst(camel_case($this->slug)) . 'Comment';
        return "App\\Models\\Ahhob\\Board\\Dynamic\\{$className}";
    }

    /**
     * 게시글 수 증가
     */
    public function incrementPostCount(): void
    {
        $this->increment('post_count');
    }

    /**
     * 게시글 수 감소
     */
    public function decrementPostCount(): void
    {
        $this->decrement('post_count');
    }

    /**
     * 활성화 상태 토글
     */
    public function toggleActive(): bool
    {
        $this->is_active = !$this->is_active;
        return $this->save();
    }

    // endregion
}