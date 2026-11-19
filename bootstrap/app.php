<?php

use App\Http\Middleware\EnsureRegistrationOpen;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['sidebar_state']);

        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->is('pembina', 'pembina/*') ? route('pembina.login') : '/admin/login',
        );

        $middleware->redirectUsersTo(
            fn (Request $request) => $request->user()?->isAdmin() ? route('admin.dashboard') : route('pembina.dashboard'),
        );

        $middleware->alias([
            'role' => EnsureRole::class,
            'registration.open' => EnsureRegistrationOpen::class,
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
