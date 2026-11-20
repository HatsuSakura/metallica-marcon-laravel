<?php
namespace App\Support\Audit;

use App\Models\Agent;
use OwenIt\Auditing\Contracts\UserResolver;

class RequestActorResolver implements UserResolver
{
    public static function resolve()
    {
        if (request()->user() instanceof Agent) return request()->user();
        return \OwenIt\Auditing\Resolvers\UserResolver::resolve();
    }
}
