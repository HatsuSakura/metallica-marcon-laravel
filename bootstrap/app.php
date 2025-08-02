<?php

use App\Http\Middleware\ConvertCaseFrontEndBackEnd;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\HandleInertiaRequests;
use \Inertia\Middleware as InertiaMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,       // For INERTIA setup & configuration
            InertiaMiddleware::class,           // For INERTIA setup & configuration
            ConvertCaseFrontEndBackEnd::class,  // To manage conversion into camel->snake TO BE and snake->camel to FE
        ]); 
        
        $middleware->validateCsrfTokens(except: [
            '/logout', // to prevent ERROR 419
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();

