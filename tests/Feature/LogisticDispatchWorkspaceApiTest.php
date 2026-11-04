<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Support\JourneyExecutionTestCase;

/** The old mutable dispatch contract is replaced by dedicated execution commands. */
class LogisticDispatchWorkspaceApiTest extends JourneyExecutionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\App\Http\Middleware\HandleInertiaRequests::class, \Inertia\Middleware::class]);
    }

    private function actor(UserRole $role): User
    {
        return User::factory()->make(['id' => 1, 'role' => $role, 'is_admin' => false, 'email_verified_at' => now()]);
    }

    public function test_started_legacy_dispatch_mutations_are_read_only(): void
    {
        DB::table('journeys')->where('id', 1)->update(['status' => 'attivo']);
        $this->actingAs($this->actor(UserRole::LOGISTIC));
        foreach (['plan', 'cargos', 'workspace'] as $path) {
            $this->putJson('/api/logistic/dispatch/1/'.$path, [])->assertStatus(409);
        }
        foreach (['hold', 'resume', 'confirm', 'events', 'close'] as $path) {
            $this->postJson('/api/logistic/dispatch/1/'.$path, [])->assertStatus(409);
        }
    }

    public function test_logistics_changes_execution_instead_of_old_dispatch_tables(): void
    {
        $this->start();
        $this->actingAs($this->actor(UserRole::LOGISTIC));
        $this->putJson('/api/logistic/dispatch/1/workspace', [])->assertStatus(409);
        $this->postJson('/api/journeys/1/execution/commands', [
            'revision' => 1, 'operation' => 'update_journey', 'input' => ['notes' => 'Percorso attualizzato'],
        ])->assertOk()->assertJsonPath('journey.execution_revision', 2)->assertJsonPath('journey.notes', 'Percorso attualizzato');
        $this->assertDatabaseHas('journeys', ['id' => 1, 'notes' => null]);
    }

    public function test_warehouse_role_cannot_modify_original_planning(): void
    {
        $this->actingAs($this->actor(UserRole::WAREHOUSE_MANAGER));
        $this->putJson('/api/warehouse-order-items/1', ['holder_quantity' => 99])->assertForbidden();
        $this->assertSame(6, Order::find(1)->items->first()->holder_quantity);
    }

    public function test_warehouse_receives_execution_without_changing_original_materials(): void
    {
        $this->start();
        $this->command('complete', ['stop_id' => 1, 'confirm_current_quantities' => true]);
        DB::table('warehouses')->insert(['id' => 1, 'name' => 'Deposito']);
        $this->actingAs($this->actor(UserRole::WAREHOUSE_MANAGER));
        $this->getJson('/api/journeys/1/execution')->assertOk()->assertJsonPath('journey.has_baseline', true);
        $this->postJson('/api/journeys/1/execution/commands', [
            'revision' => 2, 'operation' => 'warehouse_item', 'input' => ['item_id' => 1, 'warehouse_id' => 1, 'weight_gross' => 120, 'weight_tare' => 10, 'warehouse_status' => 'classified'],
        ])->assertOk()->assertJsonPath('journey.execution_revision', 3);
        $this->postJson('/api/journeys/1/execution/commands', [
            'revision' => 3, 'operation' => 'update_item', 'input' => ['item_id' => 1, 'holder_quantity' => 99],
        ])->assertForbidden();
        $this->assertSame(6, Order::find(1)->items->first()->holder_quantity);
        $this->assertSame('100', Order::find(1)->items->first()->weight_declared);
    }
}
