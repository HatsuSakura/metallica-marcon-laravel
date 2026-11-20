<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Http\Controllers\Api\V1\ReferenceController;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Agent;
use App\Models\Order;
use App\Models\User;
use App\Support\AgentApi\Abilities;
use App\Support\OpenApiDocument;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;
use Laravel\Sanctum\Sanctum;
use Tests\Support\JourneyExecutionTestCase;

class AgentApiTest extends JourneyExecutionTestCase
{
    private Agent $agent;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([HandleInertiaRequests::class, Middleware::class]);
        (require database_path('migrations/2026_11_20_180000_create_agent_api_tables.php'))->up();
        Schema::table('orders', function (Blueprint $t) {
            $t->boolean('is_urgent')->default(false);
            $t->dateTime('requested_at')->nullable();
            $t->dateTime('expected_withdraw_at')->nullable();
            $t->dateTime('fixed_withdraw_at')->nullable();
        });
        Schema::table('order_items', function (Blueprint $t) {
            $t->integer('updated_by_user_id')->nullable();
        });
        Schema::table('journeys', function (Blueprint $t) {
            $t->integer('logistics_user_id')->nullable();
            $t->integer('vehicle_cargo_id')->nullable();
            $t->integer('trailer_cargo_id')->nullable();
        });
        Schema::table('vehicles', function (Blueprint $t) {
            $t->string('type')->default('motrice');
            $t->boolean('has_trailer')->default(true);
        });
        Schema::table('cargos', function (Blueprint $t) {
            $t->boolean('is_long')->default(false);
        });
        Schema::table('journey_stops', function (Blueprint $t) {
            $t->integer('customer_visit_index')->nullable();
            $t->decimal('location_lat', 10, 7)->nullable();
            $t->decimal('location_lng', 10, 7)->nullable();
        });
        Schema::create('order_counters', function (Blueprint $t) {
            $t->integer('year')->primary();
            $t->integer('counter');
            $t->timestamps();
        });
        DB::table('customers')->where('id', 1)->update(['seller_id' => 1]);
        DB::table('users')->where('id', 1)->update(['user_code' => 'TEST']);
        DB::table('vehicles')->insert(['id' => 1, 'name' => 'Motrice']);
        DB::table('cargos')->insert(['id' => 1, 'name' => 'Corto']);
        DB::table('warehouses')->insert(['id' => 1, 'name' => 'Magazzino']);
        DB::table('holders')->insert(['id' => 2, 'name' => 'Cassa standard']);
        $this->agent = Agent::create(['name' => 'Test agent', 'slug' => 'test-agent', 'is_active' => true, 'abilities' => array_keys(Abilities::ALL)]);
        $this->token = $this->agent->createToken('test', $this->agent->abilities, now()->addDay())->plainTextToken;
    }

    private function api(string $method, string $path, array $data = [], array $headers = [])
    {
        // Sanctum's request guard must not retain a previously resolved token between test requests.
        app('auth')->forgetGuards();

        return $this->json($method, '/api/v1/'.$path, $data, ['Authorization' => 'Bearer '.$this->token] + $headers);
    }

    private function payload(): array
    {
        return ['customer_id' => 1, 'site_id' => 1, 'is_urgent' => false, 'requested_at' => '2026-11-20 10:00:00', 'expected_withdraw_at' => '2026-11-21 10:00:00',
            'items' => [['cer_code_id' => 1, 'is_bulk' => false, 'holder_id' => 2, 'holder_quantity' => 3, 'description' => 'Ferro', 'weight_declared' => 100, 'warehouse_id' => 1]], 'holders' => []];
    }

    public function test_bearer_is_required_even_without_accept_header_and_tokens_are_scoped_and_revocable(): void
    {
        $this->get('/api/v1/orders')->assertUnauthorized();
        $this->api('GET', 'orders')->assertOk();
        $readToken = $this->agent->createToken('read', ['orders:read'], now()->addDay());
        $this->token = $readToken->plainTextToken;
        $this->api('POST', 'orders', $this->payload(), ['Idempotency-Key' => 'request-0001'])->assertForbidden();
        $readToken->accessToken->delete();
        $this->api('GET', 'orders')->assertUnauthorized();
        $this->token = $this->agent->createToken('expired', ['orders:read'], now()->subMinute())->plainTextToken;
        $this->api('GET', 'orders')->assertUnauthorized();
        $this->token = $this->agent->createToken('valid', ['orders:read'], now()->addDay())->plainTextToken;
        $this->agent->update(['is_active' => false]);
        $this->api('GET', 'orders')->assertForbidden();
    }

    public function test_create_order_is_atomic_idempotent_and_has_explicit_json_contract(): void
    {
        $before = Order::count();
        $first = $this->api('POST', 'orders', $this->payload(), ['Idempotency-Key' => 'request-0001'])->assertCreated();
        $this->assertCount(1, $first->json('data.items'));
        $this->assertSame($before + 1, Order::count());
        $this->api('POST', 'orders', $this->payload(), ['Idempotency-Key' => 'request-0001'])->assertCreated()->assertHeader('Idempotency-Replayed', 'true')->assertJsonPath('data.id', $first->json('data.id'));
        $this->api('POST', 'orders', array_replace($this->payload(), ['notes' => 'Different']), ['Idempotency-Key' => 'request-0001'])->assertConflict();
        $bad = $this->payload();
        $bad['items'][0]['cer_code_id'] = 999;
        $this->api('POST', 'orders', $bad, ['Idempotency-Key' => 'request-0002'])->assertUnprocessable();
        $this->assertSame($before + 1, Order::count());
    }

    public function test_patch_requires_fresh_version_and_items_are_nested_under_their_order(): void
    {
        $created = $this->api('POST', 'orders', $this->payload(), ['Idempotency-Key' => 'request-0001'])->assertCreated();
        $id = $created->json('data.id');
        $version = $created->json('data.version');
        $this->api('PATCH', "orders/$id", ['notes' => 'Changed'], ['Idempotency-Key' => 'request-0002'])->assertStatus(428);
        $saved = $this->api('PATCH', "orders/$id", ['notes' => 'Changed'], ['Idempotency-Key' => 'request-0002', 'If-Match' => $version])->assertOk();
        $this->api('PATCH', "orders/$id", ['notes' => 'Stale'], ['Idempotency-Key' => 'request-0003', 'If-Match' => $version])->assertConflict();
        $v = $saved->json('data.version');
        $this->api('PATCH', "orders/$id/items/1", ['description' => 'Wrong order'], ['Idempotency-Key' => 'request-0004', 'If-Match' => $v])->assertNotFound();
        $this->api('POST', "orders/$id/items", $this->payload()['items'][0], ['Idempotency-Key' => 'request-0005', 'If-Match' => $v])->assertCreated()->assertJsonCount(2, 'data.items');
    }

    public function test_only_developer_can_manage_agents_even_if_other_role_is_admin(): void
    {
        foreach ([UserRole::MANAGER, UserRole::LOGISTIC, UserRole::DRIVER] as $role) {
            $this->actingAs(User::factory()->make(['id' => 1, 'role' => $role, 'is_admin' => true, 'email_verified_at' => now()]), 'web');
            $this->get('/dev/agents')->assertForbidden();
            $this->post('/dev/agents/'.$this->agent->id.'/tokens', ['name' => 'no', 'days' => 90, 'abilities' => ['orders:read']])->assertForbidden();
        }
        $this->actingAs(User::factory()->make(['id' => 1, 'role' => UserRole::DEVELOPER, 'is_admin' => false, 'email_verified_at' => now()]), 'web');
        $this->get('/dev/agents')->assertOk();
        $issued = $this->postJson('/dev/agents/'.$this->agent->id.'/tokens', ['name' => 'yes', 'days' => 90, 'abilities' => ['orders:read']])->assertCreated()->assertHeader('Cache-Control', 'no-store, private');
        $plain = $issued->json('token');
        $stored = $this->agent->tokens()->orderByDesc('id')->first();
        $this->assertNotSame($plain, $stored->token);
        $this->assertNotNull($stored->expires_at);
        $this->delete('/dev/agents/'.$this->agent->id.'/tokens/'.$stored->id)->assertRedirect();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $stored->id]);
    }

    public function test_create_journey_and_guard_against_stealing_orders_and_modifying_started_journeys(): void
    {
        $order = $this->api('POST', 'orders', $this->payload(), ['Idempotency-Key' => 'request-0001'])->assertCreated()->json('data.id');
        $plan = ['planned_start_at' => '2026-11-21 08:00:00', 'planned_end_at' => '2026-11-21 18:00:00', 'driver_id' => 1, 'vehicle_id' => 1, 'vehicle_cargo_id' => 1, 'orders_truck' => [$order], 'stops' => [['kind' => 'customer', 'sequence' => 1, 'customer_id' => 1, 'orders' => [$order]]]];
        $created = $this->api('POST', 'journeys', $plan, ['Idempotency-Key' => 'request-0002'])->assertCreated();
        $id = $created->json('data.id');
        $this->assertDatabaseHas('order_commitments', ['order_id' => $order, 'journey_id' => $id]);
        $this->api('POST', 'journeys', $plan, ['Idempotency-Key' => 'request-0003'])->assertConflict();
        DB::table('journeys')->where('id', $id)->update(['status' => 'attivo']);
        $this->api('PATCH', "journeys/$id", ['notes' => 'No'], ['Idempotency-Key' => 'request-0004', 'If-Match' => $created->json('data.version')])->assertConflict();
        $v = $this->api('GET', "orders/$order")->json('data.version');
        $this->api('PATCH', "orders/$order", ['notes' => 'No'], ['Idempotency-Key' => 'request-0005', 'If-Match' => $v])->assertConflict();
    }

    public function test_reference_endpoints_and_journey_patch_and_dynamic_grant_reduction(): void
    {
        foreach (array_keys(ReferenceController::SOURCES) as $resource) {
            $this->api('GET', 'references/'.$resource)->assertOk();
        }
        $plan = ['planned_start_at' => '2026-11-21 08:00:00', 'planned_end_at' => '2026-11-21 18:00:00', 'driver_id' => 1, 'vehicle_id' => 1, 'vehicle_cargo_id' => 1, 'orders_truck' => [1, 2], 'stops' => [['kind' => 'customer', 'sequence' => 1, 'customer_id' => 1, 'orders' => [1, 2]]]];
        DB::table('orders')->update(['journey_id' => null]);
        DB::table('order_commitments')->delete();
        $created = $this->api('POST', 'journeys', $plan, ['Idempotency-Key' => 'request-0001'])->assertCreated();
        $id = $created->json('data.id');
        $saved = $this->api('PATCH', "journeys/$id", ['notes' => 'New plan note'], ['Idempotency-Key' => 'request-0002', 'If-Match' => $created->json('data.version')])->assertOk()->assertJsonPath('data.notes', 'New plan note');
        $this->api('PATCH', "journeys/$id", ['notes' => 'Stale'], ['Idempotency-Key' => 'request-0003', 'If-Match' => $created->json('data.version')])->assertConflict();
        $this->agent->update(['abilities' => ['orders:read']]);
        $this->api('GET', 'journeys')->assertForbidden();
        $this->api('GET', 'orders')->assertOk();
    }

    public function test_openapi_contains_bearer_and_precise_payloads_without_changing_legacy_session_security(): void
    {
        $doc = app(OpenApiDocument::class)->build();
        $this->assertSame([['agentBearer' => []]], $doc['paths']['/api/v1/orders']['post']['security']);
        $this->assertSame('#/components/schemas/AgentOrderCreate', $doc['paths']['/api/v1/orders']['post']['requestBody']['content']['application/json']['schema']['$ref']);
        $this->assertSame('orders:create',$doc['paths']['/api/v1/orders']['post']['x-required-ability']);
        $this->assertSame([['sessionCookie' => []]],$doc['paths']['/api/orders/{order}/generate-documents']['post']['security']);
        $this->assertArrayHasKey('/api/v1/orders/{order}/items/{item}', $doc['paths']);
    }
    public function test_browser_session_does_not_authenticate_agent_api_and_token_does_not_authenticate_web(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->token)->get('/dev/agents')->assertRedirect('/login');
        $this->flushHeaders();
        $this->actingAs(User::factory()->make(['id' => 1, 'role' => UserRole::DEVELOPER, 'is_admin' => false, 'email_verified_at' => now()]), 'web');
        $this->getJson('/api/v1/orders')->assertUnauthorized();
    }

    public function test_audit_records_agent_identity_without_impersonating_a_human_user(): void
    {
        (require database_path('migrations/2026_03_27_090000_create_audits_table.php'))->up();
        config(['auditing.enabled' => true, 'audit.console' => true]);
        $created = $this->api('POST', 'orders', $this->payload(), ['Idempotency-Key' => 'request-audit'])->assertCreated();
        $audit = DB::table('audits')->where('auditable_type', Order::class)->where('auditable_id', $created->json('data.id'))->first();
        $this->assertNotNull($audit);
        $this->assertSame(Agent::class, $audit->user_type);
        $this->assertSame($this->agent->id, $audit->user_id);
        $this->assertDatabaseHas('agent_api_requests', ['agent_id' => $this->agent->id, 'status' => 201]);
    }

}
