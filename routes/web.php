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

Route::get('/', function () {
    return view('ahhob.home');
})->name('home');

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
