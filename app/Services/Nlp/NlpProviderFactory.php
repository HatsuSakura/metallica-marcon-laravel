<?php

namespace App\Services\Nlp;

use App\Services\Nlp\Providers\HeuristicNlpProvider;
use App\Services\Nlp\Providers\LlmNlpProvider;
use App\Services\Nlp\Providers\NlpProvider;

class NlpProviderFactory
{
    public function make(string $override = null): NlpProvider
    {
        $provider = $override ?? strtolower((string) config('services.nlp.provider', 'heuristic'));

        return match ($provider) {
            'llm', 'openai' => new LlmNlpProvider(
                apiKey: (string) config('services.openai.api_key', ''),
                model: (string) config('services.nlp.llm_model', 'gpt-4o-mini'),
            ),
            default => new HeuristicNlpProvider(),
        };
    }
}

