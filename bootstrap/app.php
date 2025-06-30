<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 커스텀 인증 미들웨어 등록
        $middleware->alias([
            'ahhob.auth' => \App\Http\Middleware\Ahhob\RedirectIfNotAuthenticated::class,
            'ahhob.guest' => \App\Http\Middleware\Ahhob\RedirectIfAuthenticated::class,
            'admin.permission' => \App\Http\Middleware\Ahhob\AdminPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
