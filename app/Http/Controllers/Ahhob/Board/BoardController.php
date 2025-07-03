<?php

namespace App\Http\Controllers\Ahhob\Board;

use App\Http\Controllers\Controller;
use App\Models\Ahhob\Board\Board;
use App\Models\Ahhob\Board\BoardGroup;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BoardController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 게시판 목록 및 기본 관리 (Board Management)
    |--------------------------------------------------------------------------
    */
    
    /**
     * 전체 게시판 목록 표시
     *
     * @return View
     */
    public function index(): View
    {
        // 활성화된 게시판 그룹과 게시판 목록 조회
        $boardGroups = BoardGroup::with([
            'boards' => function ($query) {
                $query->where('is_active', true)
                      ->orderBy('sort_order')
                      ->orderBy('created_at');
            }
        ])
        ->where('is_active', true)
        ->orderBy('sort_order')
        ->orderBy('created_at')
        ->get();
        
        return view('ahhob.boards.index', compact('boardGroups'));
    }
}
