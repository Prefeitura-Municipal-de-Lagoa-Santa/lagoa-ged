<?php

use App\Http\Middleware\ForcePaginationHttps;
use App\Http\Middleware\ForceHttpsUrls;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\TrustProxies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // Add TrustProxies middleware to properly handle HTTPS detection
        $middleware->trustProxies(at: TrustProxies::class);

        $middleware->web(append: [
            ForcePaginationHttps::class,
            ForceHttpsUrls::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\ConvertFlashToSininho::class, // Aplicar globalmente a todas as rotas web
        ]);

        // Registrar o middleware para uso específico
        $middleware->alias([
            'flash.to.notification' => \App\Http\Middleware\ConvertFlashToSininho::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
