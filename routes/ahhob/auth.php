<?php

use App\Http\Controllers\Ahhob\Auth\AuthController;
use App\Http\Controllers\Ahhob\Admin\AdminAuthController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| 사용자 및 관리자 인증을 위한 라우트를 정의합니다.
| 각 기능별로 그룹화하여 관리합니다.
|
*/

// region --- 사용자 인증 라우트 (User Authentication Routes) ---

Route::middleware('guest:web')->group(function () {
    // 로그인 폼 표시 및 처리
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    // 회원가입 폼 표시 및 처리
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    // 비밀번호 재설정
    Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
});

Route::middleware('auth:web')->group(function () {
    // 로그아웃
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // 이메일 인증 관련
    Route::get('/email/verify', [AuthController::class, 'showEmailVerifyNotice'])->name('verification.notice');
    Route::post('/email/verification-notification', [AuthController::class, 'resendEmailVerification'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
    
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect('/')->with('success', '이메일 인증이 완료되었습니다.');
    })->middleware(['signed'])->name('verification.verify');
});

// endregion

// region --- 관리자 인증 라우트 (Admin Authentication Routes) ---

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        // 관리자 로그인 폼 표시 및 처리
        Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login']);
    });
    
    Route::middleware('auth:admin')->group(function () {
        // 관리자 로그아웃
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
    });
});

// endregion

