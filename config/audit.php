<?php

use OwenIt\Auditing\Models\Audit;
use OwenIt\Auditing\Resolvers\IpAddressResolver;
use OwenIt\Auditing\Resolvers\UrlResolver;
use OwenIt\Auditing\Resolvers\UserAgentResolver;
use OwenIt\Auditing\Resolvers\UserResolver;

return [
    'enabled' => env('AUDITING_ENABLED', true),

    'implementation' => Audit::class,

    'user' => [
        'morph_prefix' => 'user',
        'guards' => [
            'web',
            'sanctum',
        ],
        'resolver' => UserResolver::class,
    ],

    'resolvers' => [
        'ip_address' => IpAddressResolver::class,
        'user_agent' => UserAgentResolver::class,
        'url' => UrlResolver::class,
    ],

    'events' => [
        'created',
        'updated',
        'deleted',
        'restored',
    ],

    'strict' => false,
    'exclude' => [],
    'empty_values' => false,
    'allowed_empty_values' => [
        'retrieved',
    ],
    'allowed_array_values' => false,
    'timestamps' => false,
    'threshold' => 0,
    'driver' => 'database',

    'drivers' => [
        'database' => [
            'table' => 'audits',
            'connection' => null,
        ],
    ],

    'queue' => [
        'enable' => false,
        'connection' => 'sync',
        'queue' => 'default',
        'delay' => 0,
    ],

    'console' => false,
];
