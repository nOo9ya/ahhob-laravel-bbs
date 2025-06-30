<?php

namespace App\Models\Ahhob\Board;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardManager extends Model
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
    protected $table = 'board_managers';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'board_id',
        'user_id',
        'can_edit_posts',
        'can_delete_posts',
        'can_move_posts',
        'can_manage_comments',
        'can_manage_attachments',
        'can_set_notice',
        'can_manage_secret',
        'memo',
        'is_active',
        'assigned_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'can_edit_posts' => 'boolean',
            'can_delete_posts' => 'boolean',
            'can_move_posts' => 'boolean',
            'can_manage_comments' => 'boolean',
            'can_manage_attachments' => 'boolean',
            'can_set_notice' => 'boolean',
            'can_manage_secret' => 'boolean',
            'is_active' => 'boolean',
            'assigned_at' => 'datetime',
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
     * 관리하는 게시판
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * 관리자 사용자
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 쿼리 스코프 (Query Scopes)
    |--------------------------------------------------------------------------
    */
    // region --- 쿼리 스코프 (Query Scopes) ---

    /**
     * 활성화된 관리자만
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 특정 게시판의 관리자들
     */
    public function scopeForBoard($query, $boardId)
    {
        return $query->where('board_id', $boardId);
    }

    /**
     * 특정 사용자의 관리 게시판들
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 접근자 & 변경자 (Accessors & Mutators)
    |--------------------------------------------------------------------------
    */
    // region --- 접근자 & 변경자 (Accessors & Mutators) ---

    /**
     * 권한 목록 접근자
     */
    public function getPermissionsAttribute(): array
    {
        return [
            'edit_posts' => $this->can_edit_posts,
            'delete_posts' => $this->can_delete_posts,
            'move_posts' => $this->can_move_posts,
            'manage_comments' => $this->can_manage_comments,
            'manage_attachments' => $this->can_manage_attachments,
            'set_notice' => $this->can_set_notice,
            'manage_secret' => $this->can_manage_secret,
        ];
    }

    /**
     * 관리자 표시명 접근자
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->user ? $this->user->nickname : '알 수 없는 사용자';
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 공개 메서드 (Public Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 공개 메서드 (Public Methods) ---

    /**
     * 특정 권한이 있는지 확인
     */
    public function hasPermission(string $permission): bool
    {
        $permissionField = "can_{$permission}";
        return $this->is_active && ($this->{$permissionField} ?? false);
    }

    /**
     * 권한 부여
     */
    public function grantPermission(string $permission): void
    {
        $permissionField = "can_{$permission}";
        if (in_array($permissionField, $this->fillable)) {
            $this->update([$permissionField => true]);
        }
    }

    /**
     * 권한 제거
     */
    public function revokePermission(string $permission): void
    {
        $permissionField = "can_{$permission}";
        if (in_array($permissionField, $this->fillable)) {
            $this->update([$permissionField => false]);
        }
    }

    /**
     * 모든 권한 부여
     */
    public function grantAllPermissions(): void
    {
        $this->update([
            'can_edit_posts' => true,
            'can_delete_posts' => true,
            'can_move_posts' => true,
            'can_manage_comments' => true,
            'can_manage_attachments' => true,
            'can_set_notice' => true,
            'can_manage_secret' => true,
        ]);
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