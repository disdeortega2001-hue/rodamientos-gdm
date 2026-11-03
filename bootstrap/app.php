<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\NormalizeDecimalComma;
use App\Http\Middleware\PreventDoubleSubmit;
use App\Http\Middleware\RefreshUsdRate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::group([], base_path('routes/system.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/panel/acceso');
        $middleware->web(append: [RefreshUsdRate::class]);
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'decimal' => NormalizeDecimalComma::class,
            'once' => PreventDoubleSubmit::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
