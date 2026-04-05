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
        $middleware->prepend([
            \App\Http\Middleware\AllowSpecificCors::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Sanctum SPA: клієнт викликає GET /sanctum/csrf-cookie, далі надсилає X-XSRF-TOKEN на stateful-запити.
        // Широкі винятки (api/*, login, register) прибираємо; додавайте сюди лише точкові URI для інтеграцій без CSRF (напр. вебхуки).
        $middleware->validateCsrfTokens(except: []);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
        //

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
