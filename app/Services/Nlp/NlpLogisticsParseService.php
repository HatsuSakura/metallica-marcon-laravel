<?php

namespace App\Services\Nlp;

use App\Services\Nlp\Validation\LogisticsQueryValidator;
use Illuminate\Validation\ValidationException;

class NlpLogisticsParseService
{
    public function __construct(
        protected NlpProviderFactory $providerFactory,
        protected LogisticsQueryValidator $validator
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @return array{parsed: array<string, mixed>, warnings: array<int, string>, provider_used: string, model_used: string|null}
     *
     * @throws ValidationException
     */
    public function parse(string $query, array $context = []): array
    {
        $providerUsed = 'heuristic';
        $modelUsed    = null;
        $extraWarnings = [];

        $parsed = $this->providerFactory->make('heuristic')->parseLogistics($query, $context);

        if ($this->shouldEscalateToLlm($parsed, $context)) {
            try {
                $llmResult    = $this->providerFactory->make('llm')->parseLogistics($query, $context);
                $parsed       = $llmResult;
                $providerUsed = 'llm';
                $modelUsed    = $parsed['_model_used'] ?? null;
            } catch (\Throwable) {
                // Silent fallback: keep heuristic result, surface warning to caller
                $extraWarnings[] = 'NLP_LLM_FALLBACK';
            }
        }

        $confidence         = $parsed['_confidence'] ?? null;
        $ambiguousReference = $parsed['_ambiguous_reference'] ?? null;
        unset($parsed['_confidence'], $parsed['_ambiguous_reference'], $parsed['_model_used']);

        $validated = $this->validator->validate($parsed);

        $validated['confidence']          = $confidence;
        $validated['ambiguous_reference'] = $ambiguousReference;
        $validated['provider_used']       = $providerUsed;
        $validated['model_used']          = $modelUsed;

        if ($extraWarnings) {
            $validated['warnings'] = array_merge($validated['warnings'], $extraWarnings);
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $parsed  Raw output from heuristic provider (includes _confidence)
     * @param  array<string, mixed>  $context Request context
     */
    private function shouldEscalateToLlm(array $parsed, array $context): bool
    {
        // Explicit user toggle always wins
        if ($context['ai'] ?? false) {
            return true;
        }

        // Automatic escalation gated behind config (default: off)
        $autoEscalate = (bool) config('services.nlp.auto_escalate_on_low_confidence', false);

        return $autoEscalate && ($parsed['_confidence']['level'] ?? 'alta') === 'bassa';
    }
}

