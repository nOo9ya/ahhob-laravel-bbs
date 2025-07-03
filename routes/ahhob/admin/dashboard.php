<?php

use App\Http\Controllers\Ahhob\Admin\Dashboard\DashboardController;
use App\Http\Controllers\Ahhob\Admin\Dashboard\Community\UserManagementController;
use App\Http\Controllers\Ahhob\Admin\Dashboard\Community\BoardManagementController;
use App\Http\Controllers\Ahhob\Admin\Dashboard\Community\ActivityLimitController;
use App\Http\Controllers\Ahhob\Admin\Dashboard\Shop\ProductManagementController;
use App\Http\Controllers\Ahhob\Admin\Dashboard\Shop\OrderManagementController;
use App\Http\Controllers\Ahhob\Admin\Dashboard\Shop\PaymentManagementController;
use App\Http\Controllers\Ahhob\Admin\Dashboard\System\SettingsController;
use App\Http\Controllers\Ahhob\Admin\Dashboard\System\MonitoringController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 통합 관리자 대시보드 라우트 (Integrated Admin Dashboard Routes)
|--------------------------------------------------------------------------
*/

// 메인 대시보드
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// 실시간 API
Route::get('/realtime-stats', [DashboardController::class, 'realtimeStats'])->name('realtime-stats');
Route::get('/trend-data', [DashboardController::class, 'trendData'])->name('trend-data');

/*
|--------------------------------------------------------------------------
| 커뮤니티 관리 (Community Management)
|--------------------------------------------------------------------------
*/
Route::prefix('community')->name('community.')->group(function () {
    
    // 회원 관리
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('index');
        Route::get('/{user}', [UserManagementController::class, 'show'])->name('show');
        Route::patch('/{user}/status', [UserManagementController::class, 'updateStatus'])->name('update-status');
        Route::patch('/{user}/points', [UserManagementController::class, 'updatePoints'])->name('update-points');
        Route::post('/{user}/ban', [UserManagementController::class, 'ban'])->name('ban');
        Route::delete('/{user}/ban', [UserManagementController::class, 'unban'])->name('unban');
        Route::get('/{user}/activity-log', [UserManagementController::class, 'activityLog'])->name('activity-log');
    });

    // 게시판 관리
    Route::prefix('boards')->name('boards.')->group(function () {
        Route::get('/', [BoardManagementController::class, 'index'])->name('index');
        Route::get('/{board}', [BoardManagementController::class, 'show'])->name('show');
        Route::patch('/{board}/settings', [BoardManagementController::class, 'updateSettings'])->name('update-settings');
        Route::post('/{board}/managers', [BoardManagementController::class, 'addManager'])->name('add-manager');
        Route::delete('/{board}/managers/{user}', [BoardManagementController::class, 'removeManager'])->name('remove-manager');
        
        // 게시글 관리
        Route::get('/{board}/posts', [BoardManagementController::class, 'posts'])->name('posts');
        Route::patch('/{board}/posts/{post}/status', [BoardManagementController::class, 'updatePostStatus'])->name('update-post-status');
        Route::delete('/{board}/posts/{post}', [BoardManagementController::class, 'deletePost'])->name('delete-post');
        
        // 댓글 관리
        Route::get('/{board}/comments', [BoardManagementController::class, 'comments'])->name('comments');
        Route::patch('/{board}/comments/{comment}/status', [BoardManagementController::class, 'updateCommentStatus'])->name('update-comment-status');
        Route::delete('/{board}/comments/{comment}', [BoardManagementController::class, 'deleteComment'])->name('delete-comment');
    });

    // 활동 제한 관리
    Route::prefix('activity-limits')->name('activity-limits.')->group(function () {
        Route::get('/', [ActivityLimitController::class, 'index'])->name('index');
        Route::get('/policies', [ActivityLimitController::class, 'policies'])->name('policies');
        Route::post('/policies', [ActivityLimitController::class, 'storePolicy'])->name('store-policy');
        Route::patch('/policies/{policy}', [ActivityLimitController::class, 'updatePolicy'])->name('update-policy');
        Route::delete('/policies/{policy}', [ActivityLimitController::class, 'deletePolicy'])->name('delete-policy');
        
        Route::get('/violations', [ActivityLimitController::class, 'violations'])->name('violations');
        Route::get('/ip-tracking', [ActivityLimitController::class, 'ipTracking'])->name('ip-tracking');
        Route::post('/ip-tracking/{ip}/block', [ActivityLimitController::class, 'blockIp'])->name('block-ip');
        Route::delete('/ip-tracking/{ip}/unblock', [ActivityLimitController::class, 'unblockIp'])->name('unblock-ip');
    });

    // 커뮤니티 통계
    Route::prefix('statistics')->name('statistics.')->group(function () {
        Route::get('/', [BoardManagementController::class, 'statistics'])->name('index');
        Route::get('/boards', [BoardManagementController::class, 'boardStatistics'])->name('boards');
        Route::get('/users', [UserManagementController::class, 'userStatistics'])->name('users');
        Route::get('/activity', [BoardManagementController::class, 'activityStatistics'])->name('activity');
    });
});

/*
|--------------------------------------------------------------------------
| 쇼핑몰 관리 (Shop Management)
|--------------------------------------------------------------------------
*/
Route::prefix('shop')->name('shop.')->group(function () {
    
    // 상품 관리
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductManagementController::class, 'index'])->name('index');
        Route::get('/create', [ProductManagementController::class, 'create'])->name('create');
        Route::post('/', [ProductManagementController::class, 'store'])->name('store');
        Route::get('/{product}', [ProductManagementController::class, 'show'])->name('show');
        Route::get('/{product}/edit', [ProductManagementController::class, 'edit'])->name('edit');
        Route::patch('/{product}', [ProductManagementController::class, 'update'])->name('update');
        Route::delete('/{product}', [ProductManagementController::class, 'destroy'])->name('destroy');
        
        // 대량 작업
        Route::post('/bulk-action', [ProductManagementController::class, 'bulkAction'])->name('bulk-action');
        
        // 재고 관리
        Route::get('/{product}/inventory', [ProductManagementController::class, 'inventory'])->name('inventory');
        Route::patch('/{product}/inventory', [ProductManagementController::class, 'updateInventory'])->name('update-inventory');
        
        // 카테고리 관리
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [ProductManagementController::class, 'categories'])->name('index');
            Route::post('/', [ProductManagementController::class, 'storeCategory'])->name('store');
            Route::patch('/{category}', [ProductManagementController::class, 'updateCategory'])->name('update');
            Route::delete('/{category}', [ProductManagementController::class, 'destroyCategory'])->name('destroy');
        });
    });

    // 주문 관리
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderManagementController::class, 'index'])->name('index');
        Route::get('/{order}', [OrderManagementController::class, 'show'])->name('show');
        Route::patch('/{order}/status', [OrderManagementController::class, 'updateStatus'])->name('update-status');
        Route::patch('/{order}/shipping', [OrderManagementController::class, 'updateShipping'])->name('update-shipping');
        Route::post('/{order}/cancel', [OrderManagementController::class, 'cancel'])->name('cancel');
        Route::post('/{order}/refund', [OrderManagementController::class, 'refund'])->name('refund');
        
        // 대량 작업
        Route::post('/bulk-action', [OrderManagementController::class, 'bulkAction'])->name('bulk-action');
        
        // 배송 관리
        Route::get('/shipping', [OrderManagementController::class, 'shipping'])->name('shipping');
        Route::post('/shipping/bulk-update', [OrderManagementController::class, 'bulkUpdateShipping'])->name('bulk-update-shipping');
    });

    // 결제 관리
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [PaymentManagementController::class, 'index'])->name('index');
        Route::get('/{transaction}', [PaymentManagementController::class, 'show'])->name('show');
        Route::post('/{transaction}/cancel', [PaymentManagementController::class, 'cancel'])->name('cancel');
        Route::post('/{transaction}/refund', [PaymentManagementController::class, 'refund'])->name('refund');
        Route::post('/{transaction}/retry', [PaymentManagementController::class, 'retry'])->name('retry');
        
        // 분석 및 리포트
        Route::get('/analytics/dashboard', [PaymentManagementController::class, 'dashboard'])->name('dashboard');
        Route::get('/analytics/report', [PaymentManagementController::class, 'analytics'])->name('analytics');
        
        // 게이트웨이 관리
        Route::get('/gateways', [PaymentManagementController::class, 'gateways'])->name('gateways');
        Route::patch('/gateways/{gateway}/settings', [PaymentManagementController::class, 'updateGatewaySettings'])->name('update-gateway-settings');
    });

    // 마케팅 관리
    Route::prefix('marketing')->name('marketing.')->group(function () {
        // 쿠폰 관리
        Route::prefix('coupons')->name('coupons.')->group(function () {
            Route::get('/', [ProductManagementController::class, 'coupons'])->name('index');
            Route::get('/create', [ProductManagementController::class, 'createCoupon'])->name('create');
            Route::post('/', [ProductManagementController::class, 'storeCoupon'])->name('store');
            Route::get('/{coupon}/edit', [ProductManagementController::class, 'editCoupon'])->name('edit');
            Route::patch('/{coupon}', [ProductManagementController::class, 'updateCoupon'])->name('update');
            Route::delete('/{coupon}', [ProductManagementController::class, 'destroyCoupon'])->name('destroy');
            
            // 쿠폰 발급
            Route::post('/{coupon}/issue', [ProductManagementController::class, 'issueCoupon'])->name('issue');
            Route::get('/{coupon}/usage', [ProductManagementController::class, 'couponUsage'])->name('usage');
        });
        
        // 이벤트 관리
        Route::prefix('events')->name('events.')->group(function () {
            Route::get('/', [ProductManagementController::class, 'events'])->name('index');
            Route::get('/create', [ProductManagementController::class, 'createEvent'])->name('create');
            Route::post('/', [ProductManagementController::class, 'storeEvent'])->name('store');
            Route::get('/{event}/edit', [ProductManagementController::class, 'editEvent'])->name('edit');
            Route::patch('/{event}', [ProductManagementController::class, 'updateEvent'])->name('update');
            Route::delete('/{event}', [ProductManagementController::class, 'destroyEvent'])->name('destroy');
        });
    });

    // 쇼핑몰 통계
    Route::prefix('statistics')->name('statistics.')->group(function () {
        Route::get('/', [OrderManagementController::class, 'statistics'])->name('index');
        Route::get('/sales', [OrderManagementController::class, 'salesStatistics'])->name('sales');
        Route::get('/products', [ProductManagementController::class, 'productStatistics'])->name('products');
        Route::get('/customers', [OrderManagementController::class, 'customerStatistics'])->name('customers');
        Route::get('/inventory', [ProductManagementController::class, 'inventoryStatistics'])->name('inventory');
    });
});

/*
|--------------------------------------------------------------------------
| 시스템 설정 및 모니터링 (System Settings & Monitoring)
|--------------------------------------------------------------------------
*/
Route::prefix('system')->name('system.')->group(function () {
    
    // 기본 설정
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::get('/general', [SettingsController::class, 'general'])->name('general');
        Route::patch('/general', [SettingsController::class, 'updateGeneral'])->name('update-general');
        
        Route::get('/email', [SettingsController::class, 'email'])->name('email');
        Route::patch('/email', [SettingsController::class, 'updateEmail'])->name('update-email');
        Route::post('/email/test', [SettingsController::class, 'testEmail'])->name('test-email');
        
        Route::get('/storage', [SettingsController::class, 'storage'])->name('storage');
        Route::patch('/storage', [SettingsController::class, 'updateStorage'])->name('update-storage');
        
        Route::get('/cache', [SettingsController::class, 'cache'])->name('cache');
        Route::post('/cache/clear', [SettingsController::class, 'clearCache'])->name('clear-cache');
        Route::post('/cache/optimize', [SettingsController::class, 'optimizeCache'])->name('optimize-cache');
    });

    // 권한 및 보안
    Route::prefix('security')->name('security.')->group(function () {
        Route::get('/', [SettingsController::class, 'security'])->name('index');
        
        // 관리자 계정 관리
        Route::get('/admins', [SettingsController::class, 'admins'])->name('admins');
        Route::get('/admins/create', [SettingsController::class, 'createAdmin'])->name('create-admin');
        Route::post('/admins', [SettingsController::class, 'storeAdmin'])->name('store-admin');
        Route::get('/admins/{admin}/edit', [SettingsController::class, 'editAdmin'])->name('edit-admin');
        Route::patch('/admins/{admin}', [SettingsController::class, 'updateAdmin'])->name('update-admin');
        Route::delete('/admins/{admin}', [SettingsController::class, 'destroyAdmin'])->name('destroy-admin');
        
        // 접근 제한
        Route::get('/access-control', [SettingsController::class, 'accessControl'])->name('access-control');
        Route::patch('/access-control', [SettingsController::class, 'updateAccessControl'])->name('update-access-control');
        
        // IP 차단
        Route::get('/ip-blocking', [SettingsController::class, 'ipBlocking'])->name('ip-blocking');
        Route::post('/ip-blocking', [SettingsController::class, 'addBlockedIp'])->name('add-blocked-ip');
        Route::delete('/ip-blocking/{ip}', [SettingsController::class, 'removeBlockedIp'])->name('remove-blocked-ip');
    });

    // 시스템 모니터링
    Route::prefix('monitoring')->name('monitoring.')->group(function () {
        Route::get('/', [MonitoringController::class, 'index'])->name('index');
        
        // 서버 상태
        Route::get('/server', [MonitoringController::class, 'server'])->name('server');
        Route::get('/server/metrics', [MonitoringController::class, 'serverMetrics'])->name('server-metrics');
        
        // 에러 로그
        Route::get('/logs', [MonitoringController::class, 'logs'])->name('logs');
        Route::get('/logs/download', [MonitoringController::class, 'downloadLogs'])->name('download-logs');
        Route::post('/logs/clear', [MonitoringController::class, 'clearLogs'])->name('clear-logs');
        
        // 성능 모니터링
        Route::get('/performance', [MonitoringController::class, 'performance'])->name('performance');
        Route::get('/performance/metrics', [MonitoringController::class, 'performanceMetrics'])->name('performance-metrics');
        
        // 데이터베이스 상태
        Route::get('/database', [MonitoringController::class, 'database'])->name('database');
        Route::get('/database/metrics', [MonitoringController::class, 'databaseMetrics'])->name('database-metrics');
        
        // 큐 모니터링
        Route::get('/queue', [MonitoringController::class, 'queue'])->name('queue');
        Route::get('/queue/metrics', [MonitoringController::class, 'queueMetrics'])->name('queue-metrics');
        Route::post('/queue/retry-failed', [MonitoringController::class, 'retryFailedJobs'])->name('retry-failed-jobs');
        Route::post('/queue/clear-failed', [MonitoringController::class, 'clearFailedJobs'])->name('clear-failed-jobs');
    });

    // 백업 및 복구
    Route::prefix('backup')->name('backup.')->group(function () {
        Route::get('/', [SettingsController::class, 'backup'])->name('index');
        Route::post('/create', [SettingsController::class, 'createBackup'])->name('create');
        Route::get('/download/{backup}', [SettingsController::class, 'downloadBackup'])->name('download');
        Route::delete('/{backup}', [SettingsController::class, 'deleteBackup'])->name('delete');
        Route::post('/restore/{backup}', [SettingsController::class, 'restoreBackup'])->name('restore');
        
        // 자동 백업 설정
        Route::get('/schedule', [SettingsController::class, 'backupSchedule'])->name('schedule');
        Route::patch('/schedule', [SettingsController::class, 'updateBackupSchedule'])->name('update-schedule');
    });
});