<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders()
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',

        then: function () {
            Route::middleware('web')->group(__DIR__ . '/../routes/app.php');
            // Route::middleware('api')->group(__DIR__ . '/../routes/app-api.php');
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Production runs behind a reverse proxy (DirectAdmin) that terminates
        // HTTPS and forwards plain HTTP to PHP. Without this, Laravel builds
        // absolute URLs (e.g. Livewire upload endpoints, Storage::url()) as
        // http://..., which the host redirects to https:// as a 301/302 —
        // turning Livewire's POST into a GET → MethodNotAllowedHttpException,
        // and breaking generated storage URLs.
        $middleware->trustProxies(at: '*');

        // Force the HTTPS scheme so asset()/Storage::url()/Livewire endpoints
        // are generated as https:// in production.
        if (env('APP_ENV') !== 'local') {
            URL::forceScheme('https');
        }
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
