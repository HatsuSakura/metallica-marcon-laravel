<?php

use App\Http\Middleware\RequestTrace;
use Illuminate\Foundation\Application;
use App\Http\Middleware\DebugRedirects;
use App\Http\Middleware\RedirectToRoleHome;
use \Inertia\Middleware as InertiaMiddleware;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ConvertCaseFrontEndBackEnd;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        /*
        // Middleware di DEBUG
        $middleware->web(prepend: [
            RequestTrace::class,
        ]);
        */
        $middleware->web(append: [
            HandleInertiaRequests::class,       // For INERTIA setup & configuration
            InertiaMiddleware::class,           // For INERTIA setup & configuration
            ConvertCaseFrontEndBackEnd::class,  // To manage conversion into camel->snake TO BE and snake->camel to FE
            // Middleware di DEBUG
            //DebugRedirects::class,
        ]);
        $middleware->alias([
            'role.home.redirect' => RedirectToRoleHome::class,   // To redirect user to the right dashboard based on role
        ]);
        
        $middleware->validateCsrfTokens(except: [
            '/logout', // to prevent ERROR 419
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();

