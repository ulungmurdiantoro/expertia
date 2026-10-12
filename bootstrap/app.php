<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\TrackActivityLog;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            TrackActivityLog::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Form Inertia menampilkan error validasi di bawah field; tanpa ini, respons 429
        // mentah muncul sebagai modal error.
        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) {
            if (! $request->header('X-Inertia')) {
                return null;
            }

            $field = match (true) {
                $request->routeIs('register.store', 'login') => 'email',
                $request->routeIs('articles.comments.store') => 'content',
                default => 'throttle',
            };

            return back()->withErrors([$field => 'Terlalu banyak permintaan. Coba lagi beberapa saat lagi.']);
        });
    })->create();
