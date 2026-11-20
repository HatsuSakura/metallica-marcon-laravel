<?php

namespace App\Http\Middleware;

use App\Models\Agent;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class AuthenticateAgent
{
    public function handle(Request $request, Closure $next, string $ability)
    {
        $agent = $request->user();
        abort_unless($agent instanceof Agent && $request->bearerToken() && $agent->currentAccessToken() instanceof PersonalAccessToken, 401, 'Bearer token agente richiesto.');
        // Refresh mutable grants on every request; token grants can never exceed the agent's current grants.
        abort_unless($agent->is_active && in_array($ability, $agent->abilities ?? [], true) && $agent->tokenCan($ability), 403, 'Agente disabilitato o operazione non autorizzata.');
        $request->attributes->set('agent_ability', $ability);

        return $next($request)->header('Cache-Control', 'no-store, private');
    }
}
