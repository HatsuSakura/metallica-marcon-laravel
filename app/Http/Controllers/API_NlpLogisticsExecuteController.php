<?php

namespace App\Http\Controllers;

use App\Models\NlpQueryLog;
use App\Services\Nlp\LogisticsCandidateQueryBuilder;
use App\Services\Nlp\NlpLogisticsParseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class API_NlpLogisticsExecuteController extends Controller
{
    public function __construct(
        protected NlpLogisticsParseService $parseService,
        protected LogisticsCandidateQueryBuilder $builder,
    ) {}

    public function execute(Request $request): JsonResponse
    {
        Gate::authorize('useLogisticsNlp');

        $payload = $request->validate([
            'query'   => 'required|string|min:2|max:2000',
            'context' => 'nullable|array',
        ]);

        $startedAt = hrtime(true);

        try {
            $parsed = $this->parseService->parse(
                $payload['query'],
                $payload['context'] ?? []
            );

            $result = $this->builder->build($parsed['parsed']);

            $latencyMs = (int) ((hrtime(true) - $startedAt) / 1_000_000);

            NlpQueryLog::create([
                'user_id'     => $request->user()?->id,
                'intent'      => 'logistics',
                'operation'   => 'execute',
                'raw_text'    => $payload['query'],
                'parsed_json' => $parsed['parsed'],
                'provider'    => config('services.nlp.provider', 'heuristic'),
                'model'       => null,
                'success'     => true,
                'latency_ms'  => $latencyMs,
            ]);

            // For order_requests the builder returns orders (not sites).
            // Derive unique sites from orders so the map panel always reads result.sites.
            $sites = $result['sites']->isNotEmpty()
                ? $result['sites']
                : $result['orders']->map(fn ($o) => $o->site)->filter()->unique('id')->values();

            return response()->json([
                'ok'                  => true,
                'parsed'              => $parsed['parsed'],
                'warnings'            => $parsed['warnings'],
                'confidence'          => $parsed['confidence'],
                'ambiguous_reference' => $parsed['ambiguous_reference'] ?? null,
                'result'   => [
                    'sites'  => $sites,
                    'orders' => $result['orders'],
                    'meta'   => $result['meta'],
                ],
            ]);

        } catch (ValidationException $e) {
            $this->logFailure($request, $payload['query'], 'NLP_SCHEMA_VALIDATION_FAILED', hrtime(true) - $startedAt);

            return response()->json([
                'ok'    => false,
                'error' => [
                    'code'    => 'NLP_SCHEMA_VALIDATION_FAILED',
                    'message' => 'Parsed query does not match schema.',
                    'details' => $e->errors(),
                ],
            ], 422);

        } catch (\Throwable $e) {
            report($e);
            $this->logFailure($request, $payload['query'], 'NLP_PROVIDER_ERROR', hrtime(true) - $startedAt);

            return response()->json([
                'ok'    => false,
                'error' => [
                    'code'    => 'NLP_PROVIDER_ERROR',
                    'message' => 'Unable to process query at the moment.',
                ],
            ], 500);
        }
    }

    private function logFailure(Request $request, string $rawText, string $errorCode, int $elapsedNs): void
    {
        NlpQueryLog::create([
            'user_id'    => $request->user()?->id,
            'intent'     => 'logistics',
            'operation'  => 'execute',
            'raw_text'   => $rawText,
            'provider'   => config('services.nlp.provider', 'heuristic'),
            'success'    => false,
            'error_code' => $errorCode,
            'latency_ms' => (int) ($elapsedNs / 1_000_000),
        ]);
    }
}
