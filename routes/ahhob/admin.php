<?php

use App\Http\Controllers\Ahhob\Admin\SystemSettingController;
use App\Http\Controllers\Ahhob\Admin\AdminDashboardController;
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
    
    // 대시보드 (기존 dashboard.php 파일 사용)
    require __DIR__ . '/admin/dashboard.php';
    
    // SEO 관리
    Route::get('/sitemap', [AdminDashboardController::class, 'sitemap'])->name('sitemap');
    Route::post('/sitemap/generate', [AdminDashboardController::class, 'generateSitemap'])->name('sitemap.generate');
    Route::get('/sitemap/download', [AdminDashboardController::class, 'downloadSitemap'])->name('sitemap.download');
    Route::delete('/sitemap', [AdminDashboardController::class, 'deleteSitemap'])->name('sitemap.delete');
    
    // 쇼핑몰 관리
    Route::prefix('shop')->name('shop.')->group(function () {
        require __DIR__ . '/admin/shop.php';
    });
    
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