<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Consistency check for an already authenticated browser, never an authentication credential. */
class EnsureSessionOwner
{
    public function handle(Request $request, Closure $next)
    {
        $expected = $request->header('X-Session-User');
        if ($expected !== null && (!$request->user() || !ctype_digit($expected) || (string) $request->user()->getAuthIdentifier() !== $expected)) {
            return response()->json(['message' => 'La sessione è cambiata. Accedi nuovamente per continuare.'], 401, ['X-Session-Expired' => '1', 'Cache-Control' => 'no-store, private']);
        }
        return $next($request);
    }
}
