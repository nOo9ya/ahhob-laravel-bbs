<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Ahhob\Admin\Dashboard\Shop\DashboardController;
use App\Http\Controllers\Ahhob\Admin\Dashboard\Shop\ProductController;
use App\Http\Controllers\Ahhob\Admin\Dashboard\Shop\OrderController;
use App\Http\Controllers\Ahhob\Admin\Dashboard\Shop\PaymentController;

/*
|--------------------------------------------------------------------------
| 관리자 쇼핑몰 라우트 (Admin Shop Routes)
|--------------------------------------------------------------------------
*/

// 쇼핑몰 대시보드
Route::prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::get('/sales', [DashboardController::class, 'sales'])->name('sales');
    Route::get('/customers', [DashboardController::class, 'customers'])->name('customers');
    Route::get('/products-analysis', [DashboardController::class, 'products'])->name('products');
    
    // AJAX 엔드포인트
    Route::get('/real-time-stats', [DashboardController::class, 'realTimeStats'])->name('real-time-stats');
    Route::get('/chart-data', [DashboardController::class, 'chartData'])->name('chart-data');
});

// 상품 관리
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('/create', [ProductController::class, 'create'])->name('create');
    Route::post('/', [ProductController::class, 'store'])->name('store');
    Route::get('/{product}', [ProductController::class, 'show'])->name('show');
    Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
    Route::put('/{product}', [ProductController::class, 'update'])->name('update');
    Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
    
    // 대량 작업
    Route::post('/bulk-action', [ProductController::class, 'bulkAction'])->name('bulk-action');
    
    // 재고 관리
    Route::get('/stock/management', [ProductController::class, 'stock'])->name('stock');
    Route::patch('/{product}/stock', [ProductController::class, 'updateStock'])->name('update-stock');
});

// 주문 관리
Route::prefix('orders')->name('orders.')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('/{order}', [OrderController::class, 'show'])->name('show');
    
    // 주문 상태 관리
    Route::patch('/{order}/status', [OrderController::class, 'updateStatus'])->name('update-status');
    Route::patch('/{order}/payment-status', [OrderController::class, 'updatePaymentStatus'])->name('update-payment-status');
    Route::patch('/{order}/shipping', [OrderController::class, 'updateShipping'])->name('update-shipping');
    Route::patch('/{order}/cancel', [OrderController::class, 'cancel'])->name('cancel');
    Route::patch('/{order}/refund', [OrderController::class, 'refund'])->name('refund');
    
    // 주문 아이템 관리
    Route::patch('/items/{orderItem}/status', [OrderController::class, 'updateItemStatus'])->name('update-item-status');
    
    // 대량 작업
    Route::post('/bulk-action', [OrderController::class, 'bulkAction'])->name('bulk-action');
    
    // AJAX 엔드포인트
    Route::get('/statistics/data', [OrderController::class, 'statistics'])->name('statistics');
});

// 결제 관리
Route::prefix('payments')->name('payments.')->group(function () {
    Route::get('/', [PaymentController::class, 'index'])->name('index');
    Route::get('/{transaction}', [PaymentController::class, 'show'])->name('show');
    
    // 결제 액션
    Route::post('/{transaction}/cancel', [PaymentController::class, 'cancel'])->name('cancel');
    Route::post('/{transaction}/refund', [PaymentController::class, 'refund'])->name('refund');
    Route::post('/{transaction}/retry', [PaymentController::class, 'retry'])->name('retry');
    Route::post('/{transaction}/sync-status', [PaymentController::class, 'syncStatus'])->name('sync-status');
    
    // 대량 작업
    Route::post('/bulk-action', [PaymentController::class, 'bulkAction'])->name('bulk-action');
    
    // 분석 및 리포트
    Route::get('/dashboard/analytics', [PaymentController::class, 'dashboard'])->name('dashboard');
    Route::get('/analytics/report', [PaymentController::class, 'analytics'])->name('analytics');
});