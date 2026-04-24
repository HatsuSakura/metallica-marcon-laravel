<?php

namespace App\Services\Nlp;

use App\Services\Nlp\Validation\LogisticsQueryValidator;
use Illuminate\Validation\ValidationException;

class NlpLogisticsParseService
{
    public function __construct(
        protected NlpProviderFactory $providerFactory,
        protected LogisticsQueryValidator $validator
    ) {
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{parsed: array<string, mixed>, warnings: array<int, string>}
     *
     * @throws ValidationException
     */
    public function parse(string $query, array $context = []): array
    {
        $provider = $this->providerFactory->make();
        $parsed = $provider->parseLogistics($query, $context);

        $confidence          = $parsed['_confidence'] ?? null;
        $ambiguousReference  = $parsed['_ambiguous_reference'] ?? null;
        unset($parsed['_confidence'], $parsed['_ambiguous_reference']);

        $validated = $this->validator->validate($parsed);
        $validated['confidence']           = $confidence;
        $validated['ambiguous_reference']  = $ambiguousReference;

        return $validated;
    }
}

