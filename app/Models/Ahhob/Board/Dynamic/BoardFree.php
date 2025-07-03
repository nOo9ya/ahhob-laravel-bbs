<?php

namespace App\Models\Ahhob\Board\Dynamic;

use App\Models\Ahhob\Board\BaseBoardPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoardFree extends BaseBoardPost
{
    /**
     * The table associated with the model.
     */
    protected $table = 'board_free';

    /**
     * 게시글 댓글들
     */
    public function comments(): HasMany
    {
        return $this->hasMany(BoardFreeComment::class, 'post_id');
    }

    /**
     * URL 접근자
     */
    public function getUrlAttribute(): string
    {
        return route('board.view', ['free', $this->id]);
    }

    /**
     * 게시판 설정 정보
     */
    public function getBoardConfig()
    {
        return \App\Models\Ahhob\Board\Board::where('slug', 'free')->first();
    }
}