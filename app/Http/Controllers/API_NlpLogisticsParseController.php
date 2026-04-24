<?php

namespace App\Http\Controllers;

use App\Models\NlpQueryLog;
use App\Services\Nlp\NlpLogisticsParseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class API_NlpLogisticsParseController extends Controller
{
    public function __construct(
        protected NlpLogisticsParseService $parseService
    ) {
    }

    public function parse(Request $request): JsonResponse
    {
        Gate::authorize('useLogisticsNlp');

        $payload = $request->validate([
            'query' => 'required|string|min:2|max:2000',
            'context' => 'nullable|array',
        ]);

        $startedAt = hrtime(true);

        try {
            $result = $this->parseService->parse(
                $payload['query'],
                $payload['context'] ?? []
            );

            $latencyMs = (int) ((hrtime(true) - $startedAt) / 1_000_000);

            NlpQueryLog::create([
                'user_id'     => $request->user()?->id,
                'intent'      => 'logistics',
                'operation'   => 'parse',
                'raw_text'    => $payload['query'],
                'parsed_json' => $result['parsed'],
                'provider'    => config('services.nlp.provider', 'heuristic'),
                'success'     => true,
                'latency_ms'  => $latencyMs,
            ]);

            return response()->json([
                'ok'       => true,
                'parsed'   => $result['parsed'],
                'warnings' => $result['warnings'],
            ], 200);

        } catch (ValidationException $e) {
            NlpQueryLog::create([
                'user_id'    => $request->user()?->id,
                'intent'     => 'logistics',
                'operation'  => 'parse',
                'raw_text'   => $payload['query'],
                'provider'   => config('services.nlp.provider', 'heuristic'),
                'success'    => false,
                'error_code' => 'NLP_SCHEMA_VALIDATION_FAILED',
                'latency_ms' => (int) ((hrtime(true) - $startedAt) / 1_000_000),
            ]);

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

            NlpQueryLog::create([
                'user_id'    => $request->user()?->id,
                'intent'     => 'logistics',
                'operation'  => 'parse',
                'raw_text'   => $payload['query'],
                'provider'   => config('services.nlp.provider', 'heuristic'),
                'success'    => false,
                'error_code' => 'NLP_PROVIDER_ERROR',
                'latency_ms' => (int) ((hrtime(true) - $startedAt) / 1_000_000),
            ]);

            return response()->json([
                'ok'    => false,
                'error' => [
                    'code'    => 'NLP_PROVIDER_ERROR',
                    'message' => 'Unable to parse query at the moment.',
                ],
            ], 500);
        }
    }
}



