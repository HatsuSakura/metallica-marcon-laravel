<?php

/**
 * in riferimento a config/dashboard.php
 */

namespace App\Http\Middleware;

use UnitEnum;
use BackedEnum;

use Closure;
use Illuminate\Http\Request;

class RedirectToRoleHome
{
    public function handle(Request $request, Closure $next)
    {

        // 🔒 Esegui SOLO su /dashboard
        if (!$request->routeIs('dashboard')) {
            return $next($request);
        }

        // 👤 Se guest → porta al login (niente fallback qui)
        if (!$request->user()) {
            return redirect()->route('login');
        }

        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $map = config('dashboard.homes', []);
        $roleKey = $user->role instanceof UnitEnum
            ? ($user->role instanceof BackedEnum ? $user->role->value : $user->role->name)
            : (string) $user->role;
        $route = $map[$roleKey] ?? config('dashboard.fallback', 'generic.home');

        // Evita loop: se sei già sulla route di casa, prosegui
        if ($request->routeIs($route)) {
            return $next($request);
        }

        // fallback come URI
        if (is_string($route) && str_starts_with($route, '/')) {
            return redirect()->to($route);     
        }
        // fallback come route name -> Comportamento di default
        return redirect()->route($route);
    }
}
