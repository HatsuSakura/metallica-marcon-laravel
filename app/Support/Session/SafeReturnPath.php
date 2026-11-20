<?php

namespace App\Support\Session;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class SafeReturnPath
{
    public function resolve(mixed $value): string
    {
        if (!is_string($value) || strlen($value) > 2000) return '/dashboard';
        $decoded = rawurldecode($value);
        if (!str_starts_with($decoded, '/') || str_starts_with($decoded, '//') || preg_match('/[\\\\\x00-\x1f\x7f]/', $decoded)) return '/dashboard';
        $parts = parse_url($value);
        if ($parts === false || isset($parts['host']) || isset($parts['scheme'])) return '/dashboard';
        try {
            $route = Route::getRoutes()->match(Request::create($value, 'GET'));
            $name = $route->getName() ?? '';
            if (preg_match('/^(?:login|logout|password\.|verification\.|session\.)/', $name) || str_starts_with($route->uri(), 'api/')) return '/dashboard';
            if (!in_array('auth', $route->gatherMiddleware(), true)) return '/dashboard';
        } catch (\Throwable) { return '/dashboard'; }
        return $value;
    }
}
