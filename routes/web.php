<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [\App\Http\Controllers\HomeController::class, 'index'])->name('home');

// 테마 변경 라우트 (관리자용)
Route::post('/theme/change', [\App\Http\Controllers\HomeController::class, 'changeTheme'])->name('theme.change');

// 프로필 라우트
Route::middleware('auth:web')->group(function () {
    Route::get('/profile', [\App\Http\Controllers\User\ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [\App\Http\Controllers\User\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\App\Http\Controllers\User\ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/password', [\App\Http\Controllers\User\ProfileController::class, 'editPassword'])->name('profile.password');
    Route::put('/profile/password', [\App\Http\Controllers\User\ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::delete('/profile/avatar', [\App\Http\Controllers\User\ProfileController::class, 'deleteAvatar'])->name('profile.avatar.delete');
});

/*
|--------------------------------------------------------------------------
| Ahhob Feature Routes
|--------------------------------------------------------------------------
|
| ahhob 시스템의 각 도메인별 라우트를 포함합니다.
|
*/

// 인증 라우트
require __DIR__ . '/ahhob/auth.php';

// 게시판 라우트
require __DIR__ . '/ahhob/board.php';

// 관리자 라우트
require __DIR__ . '/ahhob/admin.php';

// 쇼핑몰 라우트
Route::prefix('shop')->name('ahhob.shop.')->group(function () {
    require __DIR__ . '/ahhob/shop.php';
});
