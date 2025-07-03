<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Ahhob\Shop\CategoryController;
use App\Http\Controllers\Ahhob\Shop\ProductController;
use App\Http\Controllers\Ahhob\Shop\CartController;
use App\Http\Controllers\Ahhob\Shop\OrderController;
use App\Http\Controllers\Ahhob\Shop\WishlistController;
use App\Http\Controllers\Ahhob\Shop\PaymentController;

/*
|--------------------------------------------------------------------------
| 쇼핑몰 라우트 (Shop Routes)
|--------------------------------------------------------------------------
*/

// 쇼핑몰 메인
Route::get('/', [ProductController::class, 'index'])->name('shop.index');

// 카테고리
Route::prefix('categories')->name('categories.')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('index');
    Route::get('/{categorySlug}', [CategoryController::class, 'show'])->name('show');
});

// 상품
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('/{productSlug}', [ProductController::class, 'show'])->name('show');
});

// 장바구니
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add', [CartController::class, 'add'])->name('add');
    Route::patch('/{cartItem}', [CartController::class, 'update'])->name('update');
    Route::delete('/{cartItem}', [CartController::class, 'destroy'])->name('destroy');
    Route::delete('/', [CartController::class, 'clear'])->name('clear');
});

// 회원 전용 기능
Route::middleware('auth:web')->group(function () {
    // 위시리스트
    Route::prefix('wishlist')->name('wishlist.')->group(function () {
        Route::get('/', [WishlistController::class, 'index'])->name('index');
        Route::post('/add', [WishlistController::class, 'add'])->name('add');
        Route::delete('/{wishlistItem}', [WishlistController::class, 'destroy'])->name('destroy');
        Route::post('/{wishlistItem}/move-to-cart', [WishlistController::class, 'moveToCart'])->name('move-to-cart');
    });
    
    // 주문
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/create', [OrderController::class, 'create'])->name('create');
        Route::post('/', [OrderController::class, 'store'])->name('store');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::patch('/{order}/cancel', [OrderController::class, 'cancel'])->name('cancel');
        
        // 결제 관련
        Route::prefix('{order}/payment')->name('payment.')->group(function () {
            Route::get('/', [PaymentController::class, 'show'])->name('show');
            Route::post('/process', [PaymentController::class, 'process'])->name('process');
            Route::get('/success', [PaymentController::class, 'success'])->name('success');
            Route::get('/failure', [PaymentController::class, 'failure'])->name('failure');
            Route::get('/cancel', [PaymentController::class, 'cancel'])->name('cancel');
            Route::get('/return', [PaymentController::class, 'success'])->name('return');
        });
    });
});

// 결제 관련 API (인증 불필요)
Route::prefix('payment')->name('payment.')->group(function () {
    Route::post('/status', [PaymentController::class, 'status'])->name('status');
    Route::post('/cancel', [PaymentController::class, 'cancelPayment'])->name('cancel');
    Route::post('/refund', [PaymentController::class, 'refund'])->name('refund');
    Route::post('/retry', [PaymentController::class, 'retry'])->name('retry');
    Route::get('/methods', [PaymentController::class, 'availableMethods'])->name('methods');
    
    // 웹훅 (CSRF 제외)
    Route::post('/webhook/{gateway}', [PaymentController::class, 'webhook'])
        ->name('webhook')
        ->withoutMiddleware(['web']);
});