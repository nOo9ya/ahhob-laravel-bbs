<?php

use App\Http\Controllers\Ahhob\Admin\SystemSettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| 관리자 전용 라우트들입니다.
|
*/

Route::prefix('admin')->name('admin.')->middleware(['web', 'auth', 'admin'])->group(function () {
    
    // 시스템 설정 관리
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SystemSettingController::class, 'index'])->name('index');
        Route::get('/attachment', [SystemSettingController::class, 'attachment'])->name('attachment');
        Route::post('/store', [SystemSettingController::class, 'store'])->name('store');
        Route::post('/reset', [SystemSettingController::class, 'reset'])->name('reset');
        Route::post('/clear-cache', [SystemSettingController::class, 'clearCache'])->name('clear-cache');
        Route::get('/export', [SystemSettingController::class, 'export'])->name('export');
        Route::post('/import', [SystemSettingController::class, 'import'])->name('import');
    });
    
});