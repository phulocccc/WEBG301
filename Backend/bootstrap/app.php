<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'login',
            'ideas',
            'ideas/*', // Dòng này sẽ bỏ chặn cho cả /ideas/1/comments
            'ideas/*/react',
            'api/*',    // Bỏ qua cho mọi API (như /api/ideas)
            'login',    // Bỏ qua cho lúc đăng nhập
            'register'  // Bỏ qua cho lúc đăng ký
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
