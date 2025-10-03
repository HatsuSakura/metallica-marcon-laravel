<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RequestTrace
{
    public function handle(Request $request, Closure $next)
    {
        \Log::info('[REQ]', [
            'method' => $request->method(),
            'url'    => $request->fullUrl(),
            'route'  => optional($request->route())->getName(),
            'user'   => optional($request->user())->id,
        ]);

        $response = $next($request);

        if ($response instanceof RedirectResponse) {
            \Log::info('[REDIRECT]', [
                'from' => $request->fullUrl(),
                'to'   => $response->getTargetUrl(),
                'via'  => optional($request->route())->getName(),
            ]);
        } else {
            \Log::info('[RES]', [
                'url'    => $request->fullUrl(),
                'status' => $response->getStatusCode(),
            ]);
        }

        return $response;
    }
}
