<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Ahhob\Board\BoardController;
use App\Http\Controllers\Ahhob\Board\PostController;

/*
|--------------------------------------------------------------------------
| Board Routes
|--------------------------------------------------------------------------
|
| 게시판 관련 라우트입니다.
| 동적 게시판 시스템을 지원하며, {boardSlug} 파라미터를 통해 각 게시판에 접근합니다.
|
*/

// 게시판 목록
Route::get('/boards', [BoardController::class, 'index'])->name('boards.index');

// 특정 게시판 라우트 그룹
Route::prefix('boards/{boardSlug}')->name('boards.')->group(function () {
    
    // 게시판 메인 (게시글 목록)
    Route::get('/', [PostController::class, 'index'])->name('posts.index');
    
    // 게시글 작성 폼 (로그인 필요)
    Route::get('/create', [PostController::class, 'create'])
        ->middleware('auth:web')
        ->name('posts.create');
    
    // 게시글 저장 (로그인 필요)
    Route::post('/', [PostController::class, 'store'])
        ->middleware('auth:web')
        ->name('posts.store');
    
    // 게시글 상세보기
    Route::get('/{post}', [PostController::class, 'show'])->name('posts.show');
    
    // 게시글 수정 폼 (로그인 필요, 권한 체크)
    Route::get('/{post}/edit', [PostController::class, 'edit'])
        ->middleware(['auth:web', 'can:update,post'])
        ->name('posts.edit');
    
    // 게시글 업데이트 (로그인 필요, 권한 체크)
    Route::put('/{post}', [PostController::class, 'update'])
        ->middleware(['auth:web', 'can:update,post'])
        ->name('posts.update');
    
    // 게시글 삭제 (로그인 필요, 권한 체크)
    Route::delete('/{post}', [PostController::class, 'destroy'])
        ->middleware(['auth:web', 'can:delete,post'])
        ->name('posts.destroy');
});