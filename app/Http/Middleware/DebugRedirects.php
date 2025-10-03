<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DebugRedirects
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($response instanceof RedirectResponse) {
            \Log::info('[REDIRECT]', [
                'from'   => $request->fullUrl(),
                'to'     => $response->getTargetUrl(),
                'route'  => optional($request->route())->getName(),
                'userId' => optional($request->user())->id,
            ]);
        }

        return $response;
    }
}
