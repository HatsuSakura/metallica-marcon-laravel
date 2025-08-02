<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;

class ConvertCaseFrontEndBackEnd
{
    public function handle($request, Closure $next)
    {
        // Convert request keys from camelCase to snake_case
        $snakeCaseInput = collect($request->all())
            ->mapWithKeys(function ($value, $key) {
                return [Str::snake($key) => $value];
            })
            ->toArray();

        $request->replace($snakeCaseInput);

        // Process the request
        $response = $next($request);

        // Check if the response is a JSON response
        if ($response instanceof JsonResponse) {
            // Convert response data keys from snake_case to camelCase
            $camelCaseData = collect($response->getData(true))
                ->mapWithKeys(function ($value, $key) {
                    return [Str::camel($key) => $value];
                })
                ->toArray();

            // Set the transformed data back into the response
            $response->setData($camelCaseData);
        }

        return $response;
    }
}
