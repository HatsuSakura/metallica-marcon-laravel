<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Support\AgentApi\Abilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AgentController extends Controller
{
    public function index()
    {
        return inertia('Agent/Index', ['agents' => Agent::with(['tokens' => fn ($q) => $q->select('id', 'tokenable_id', 'tokenable_type', 'name', 'abilities', 'created_at', 'last_used_at', 'expires_at')->orderByDesc('id')])->orderBy('name')->get(), 'availableAbilities' => Abilities::ALL]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:120', 'slug' => 'required|alpha_dash:ascii|max:100|unique:agents,slug', 'abilities' => 'required|array|min:1', 'abilities.*' => ['required', 'distinct', Rule::in(array_keys(Abilities::ALL))]]);
        $agent = Agent::create($data + ['is_active' => true]);
        $this->audit($request, $agent, 'agent_created');

        return back()->with('success', "Agente {$agent->name} creato. Ora puoi generare un token.");
    }

    public function update(Request $request, Agent $agent)
    {
        $data = $request->validate(['name' => 'required|string|max:120', 'is_active' => 'required|boolean', 'abilities' => 'required|array|min:1', 'abilities.*' => ['required', 'distinct', Rule::in(array_keys(Abilities::ALL))]]);
        DB::transaction(function () use ($agent, $data, $request) {
            $agent = Agent::whereKey($agent->id)->lockForUpdate()->firstOrFail();
            $agent->update($data);
            $this->audit($request, $agent, 'agent_updated');
        });

        return back()->with('success', "Agente {$agent->name} aggiornato.");
    }

    public function issueToken(Request $request, Agent $agent)
    {
        $data = $request->validate(['name' => 'required|string|max:120', 'days' => 'required|integer|min:1|max:365', 'abilities' => 'required|array|min:1', 'abilities.*' => ['required', 'distinct', Rule::in(array_keys(Abilities::ALL))]]);

        return DB::transaction(function () use ($agent, $request, $data) {
            $agent = Agent::whereKey($agent->id)->lockForUpdate()->firstOrFail();
            abort_unless($agent->is_active, 409, 'Riattiva l’agente prima di generare un token.');
            abort_if(array_diff($data['abilities'], $agent->abilities), 422, 'Il token non può avere permessi superiori all’agente.');
            $token = $agent->createToken($data['name'], $data['abilities'], now()->addDays($data['days']));
            $this->audit($request, $agent, 'token_issued', $token->accessToken->id);

            return response()->json(['token' => $token->plainTextToken, 'expires_at' => $token->accessToken->expires_at], 201, ['Cache-Control' => 'no-store, private', 'Pragma' => 'no-cache']);
        });
    }

    public function revokeToken(Request $request, Agent $agent, int $token)
    {
        DB::transaction(function () use ($agent, $token, $request) {
            $agent = Agent::whereKey($agent->id)->lockForUpdate()->firstOrFail();
            $agent->tokens()->whereKey($token)->firstOrFail()->delete();
            $this->audit($request, $agent, 'token_revoked', $token);
        });

        return back()->with('success', 'Token revocato.');
    }

    private function audit(Request $request, Agent $agent, string $action, ?int $token = null): void
    {
        DB::table('agent_admin_events')->insert(['agent_id' => $agent->id, 'user_id' => $request->user()->id, 'action' => $action, 'details' => json_encode(['name' => $agent->name, 'is_active' => $agent->is_active, 'abilities' => $agent->abilities]), 'token_id' => $token, 'created_at' => now()]);
    }
}
