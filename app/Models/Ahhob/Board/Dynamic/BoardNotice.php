<?php

namespace App\Models\Ahhob\Board\Dynamic;

use App\Models\Ahhob\Board\BaseBoardPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoardNotice extends BaseBoardPost
{
    /**
     * The table associated with the model.
     */
    protected $table = 'board_notice';

    /**
     * 게시글 댓글들
     */
    public function comments(): HasMany
    {
        return $this->hasMany(BoardNoticeComment::class, 'post_id');
    }

    /**
     * URL 접근자
     */
    public function getUrlAttribute(): string
    {
        return route('board.view', ['notice', $this->id]);
    }

    /**
     * 게시판 설정 정보
     */
    public function getBoardConfig()
    {
        return \App\Models\Ahhob\Board\Board::where('slug', 'notice')->first();
    }
}