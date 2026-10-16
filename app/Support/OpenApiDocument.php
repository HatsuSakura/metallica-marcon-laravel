<?php

namespace App\Support;

use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class OpenApiDocument
{
    public function build(): array
    {
        $paths = [];

        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/')) {
                continue;
            }

            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $paths['/'.$route->uri()][strtolower($method)] = $this->operation($route, $method);
            }
        }

        ksort($paths);

        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Metallica Marcon API',
                'version' => '1.0.0',
                'description' => 'Catalogo DEV delle API applicative. Le API usano la sessione Laravel, richiedono un utente verificato e applicano policy e gate specifici. Le operazioni mutative richiedono anche un token CSRF valido.',
            ],
            'servers' => [
                ['url' => url('/'), 'description' => app()->environment('production') ? 'Produzione' : ucfirst(app()->environment())],
            ],
            'paths' => $paths,
            'components' => [
                'securitySchemes' => [
                    'sessionCookie' => [
                        'type' => 'apiKey',
                        'in' => 'cookie',
                        'name' => config('session.cookie'),
                        'description' => 'Sessione Laravel autenticata. Le richieste POST, PUT, PATCH e DELETE richiedono inoltre il token CSRF same-origin.',
                    ],
                ],
                'schemas' => [
                    'ValidationErrors' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string'],
                            'errors' => ['type' => 'object', 'additionalProperties' => ['type' => 'array', 'items' => ['type' => 'string']]],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function operation(LaravelRoute $route, string $method): array
    {
        [$controller, $action] = $this->actionParts($route);
        $operation = [
            'tags' => [$this->tag($route->uri())],
            'summary' => trim(Str::headline($action).' · '.$controller),
            'operationId' => $this->operationId($method, $route->uri()),
            'description' => sprintf('Handler: `%s`.', $route->getActionName()),
            'security' => [['sessionCookie' => []]],
            'parameters' => $this->pathParameters($route->uri()),
            'responses' => [
                '200' => ['description' => 'Richiesta completata'],
                '302' => ['description' => 'Redirect alla pagina di login quando la sessione non è valida'],
                '403' => ['description' => 'Utente autenticato ma non autorizzato'],
                '404' => ['description' => 'Risorsa non trovata'],
                '422' => [
                    'description' => 'Payload non valido',
                    'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/ValidationErrors']]],
                ],
            ],
        ];

        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $operation['requestBody'] = [
                'required' => false,
                'description' => 'Il payload varia in base all’operazione; consultare le regole di validazione del controller indicato.',
                'content' => ['application/json' => ['schema' => ['type' => 'object', 'additionalProperties' => true]]],
            ];
        }

        return $operation;
    }

    private function actionParts(LaravelRoute $route): array
    {
        $action = $route->getActionName();
        if (! str_contains($action, '@')) {
            return ['Closure', strtolower($route->methods()[0])];
        }

        [$class, $method] = explode('@', $action, 2);

        return [class_basename($class), $method];
    }

    private function tag(string $uri): string
    {
        return Str::headline(explode('/', $uri)[1] ?? 'API');
    }

    private function operationId(string $method, string $uri): string
    {
        return strtolower($method).Str::studly(preg_replace('/[{}]/', '', $uri));
    }

    private function pathParameters(string $uri): array
    {
        preg_match_all('/\{([^}]+)\}/', $uri, $matches);

        return array_map(fn (string $name): array => [
            'name' => rtrim($name, '?'),
            'in' => 'path',
            'required' => ! str_ends_with($name, '?'),
            'schema' => ['type' => 'string'],
        ], $matches[1]);
    }
}
