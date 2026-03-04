<?php

namespace App\Services\Nlp;

use App\Services\Nlp\Providers\HeuristicNlpProvider;
use App\Services\Nlp\Providers\NlpProvider;

class NlpProviderFactory
{
    public function make(): NlpProvider
    {
        $provider = strtolower((string) config('services.nlp.provider', 'heuristic'));

        return match ($provider) {
            'heuristic' => new HeuristicNlpProvider(),
            default => new HeuristicNlpProvider(),
        };
    }
}

