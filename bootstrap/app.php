<?php

use App\Http\Middleware\ConvertCaseFrontEndBackEnd;
use App\Http\Middleware\DebugRedirects;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PlanningWriteBoundary;
use App\Http\Middleware\RedirectToRoleHome;
use App\Http\Middleware\RequestTrace;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Inertia\Middleware as InertiaMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        /*
        // Middleware di DEBUG
        $middleware->web(prepend: [
            RequestTrace::class,
        ]);
        */
        $middleware->web(append: [
            HandleInertiaRequests::class,
            PlanningWriteBoundary::class,       // For INERTIA setup & configuration
            InertiaMiddleware::class,           // For INERTIA setup & configuration
            ConvertCaseFrontEndBackEnd::class,  // To manage conversion into camel->snake TO BE and snake->camel to FE
            // Middleware di DEBUG
            // DebugRedirects::class,
        ]);
        $middleware->alias([
            'role.home.redirect' => RedirectToRoleHome::class,   // To redirect user to the right dashboard based on role
        ]);

        $middleware->validateCsrfTokens(except: [
            '/logout', // to prevent ERROR 419
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Throwable $error, \Illuminate\Http\Request $request) {
            if (!$request->is('api/v1/*') || $error instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface || $error instanceof \Illuminate\Validation\ValidationException || $error instanceof \Illuminate\Auth\AuthenticationException || $error instanceof \Illuminate\Auth\Access\AuthorizationException || $error instanceof \Illuminate\Http\Exceptions\HttpResponseException) return null;
            return response()->json(['message' => 'Errore interno. Riprovare con la stessa Idempotency-Key o contattare il supporto.'], 500);
        });
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/v1/*') || $request->expectsJson());
    })
    ->create();
