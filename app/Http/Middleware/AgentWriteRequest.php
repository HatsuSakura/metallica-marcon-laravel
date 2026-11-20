<?php

namespace App\Http\Middleware;

use App\Models\Agent;
use Closure;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentWriteRequest
{
    public function handle(Request $request, Closure $next)
    {
        $key = $request->header('Idempotency-Key');
        abort_unless(is_string($key) && strlen($key) >= 8 && strlen($key) <= 200, 422, 'Idempotency-Key obbligatoria (8–200 caratteri).');
        $hash = hash('sha256', json_encode([$request->method(), $request->path(), $request->header('If-Match'), $request->all()], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($request, $next, $key, $hash) {
            // Serializes retries for this integration, including requests using different tokens.
            $agent = Agent::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless($agent->is_active && in_array($request->attributes->get('agent_ability'), $agent->abilities, true), 403);
            $prior = DB::table('agent_api_requests')->where('agent_id', $agent->id)->where('key_hash', hash('sha256', $key))->first();
            if ($prior) {
                abort_unless(hash_equals($prior->request_hash, $hash), 409, 'Idempotency-Key già usata per una richiesta diversa.');

                return response()->json($body = json_decode($prior->response, true), $prior->status)->header('Idempotency-Replayed', 'true')->header('ETag', '"'.($body['data']['version'] ?? '').'"');
            }
            $response = $next($request);
            if ($response->getStatusCode() >= 400) {
                throw new HttpResponseException($response);
            }
            DB::table('agent_api_requests')->insert([
                'agent_id' => $agent->id, 'token_id' => $request->user()->currentAccessToken()->id,
                'key_hash' => hash('sha256', $key), 'request_hash' => $hash,
                'method' => $request->method(), 'path' => $request->path(), 'status' => $response->getStatusCode(),
                'response' => $response->getContent(), 'context' => json_encode(['execution_id' => mb_substr($request->header('X-Agent-Run-Id', ''), 0, 200), 'source_reference' => mb_substr($request->header('X-Source-Reference', ''), 0, 200)]),
                'created_at' => now(),
            ]);

            return $response;
        });
    }
}
