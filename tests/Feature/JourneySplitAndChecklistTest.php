<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Journey;
use App\Models\JourneyExecutionItem;
use App\Models\Order;
use App\Models\User;
use App\Services\Driver\DriverJourneyReadModel;
use App\Services\JourneyExecution\DocumentRevision;
use Illuminate\Support\Facades\DB;
use Tests\Support\JourneyExecutionTestCase;

class JourneySplitAndChecklistTest extends JourneyExecutionTestCase
{
    private function depart(bool $trailer = true): void
    {
        if ($trailer) {
            DB::table('trailers')->insert(['id' => 1, 'name' => 'Rimorchio']);
            DB::table('journeys')->where('id', 1)->update(['trailer_id' => 1]);
        }
        foreach ([1, 2] as $id) {
            DB::table('orders')->where('id', $id)->update(['documents_input_hash' => app(DocumentRevision::class)->hash(Order::find($id))]);
        }
        $this->start();
    }

    private function split(int $vehicle = 4, int $trailer = 2): Journey
    {
        return $this->command('set_item_allocations', ['item_id' => 1, 'vehicle_containers' => $vehicle, 'trailer_containers' => $trailer]);
    }

    private function projectedItem(): \App\Models\OrderItem
    {
        return app(DriverJourneyReadModel::class)->load(Journey::find(1))->orders->flatMap->items->firstWhere('id', 1);
    }

    public function test_split_is_two_allocations_of_one_item_and_preserves_original_plan(): void
    {
        $this->depart();
        $baseline = Journey::find(1)->baseline->data;
        $this->split();
        $item = $this->projectedItem();
        $this->assertSame(2, JourneyExecutionItem::count()); // One per source item, never cloned for the split.
        $this->assertSame(['trailer' => 2, 'vehicle' => 4], collect($item->load_allocations)->pluck('containers', 'cargo_location')->all());
        $this->assertTrue($item->load_allocation_valid);
        $this->assertFalse($item->is_loaded);
        $this->assertSame(6, $item->holder_quantity);
        $this->assertSame($baseline, Journey::find(1)->baseline->data);
        $this->assertSame(6, DB::table('order_items')->where('id', 1)->value('holder_quantity'));
        $this->assertSame(0, DB::table('journey_cargo_allocations')->count());
        $this->split();
        $this->assertSame(2, Journey::find(1)->execution_revision);
        $this->assertSame(2, DB::table('journey_execution_allocations')->count());
    }

    public function test_invalid_totals_and_locations_do_not_partially_write(): void
    {
        $this->depart();
        foreach ([[5, 2], [2, 2], [-1, 7], [1.5, 4.5]] as [$vehicle,$trailer]) {
            $this->conflict(fn () => $this->split($vehicle, $trailer), 422);
            $this->assertSame(0, DB::table('journey_execution_allocations')->count());
        }
        $this->assertSame(1, Journey::find(1)->execution_revision);
        $this->split(6, 0);
        $this->assertSame(2, DB::table('journey_execution_allocations')->count());
        $this->command('update_item', ['item_id' => 1, 'cargo_location' => 'vehicle']);
        $this->assertSame(0, DB::table('journey_execution_allocations')->count());
        $this->assertSame('vehicle', $this->projectedItem()->cargo_location);
    }

    public function test_checklist_is_persistent_and_independent_of_stop_completion_and_quantities(): void
    {
        $this->depart();
        $this->split();
        $this->command('set_item_loaded', ['item_id' => 1, 'is_loaded' => true]);
        $this->assertTrue($this->projectedItem()->is_loaded);
        $this->assertNull(JourneyExecutionItem::find(1)->actual_containers);
        $this->assertSame('in_progress', DB::table('journey_stops')->where('id', 1)->value('status'));
        $this->command('set_item_loaded', ['item_id' => 1, 'is_loaded' => true]);
        $this->assertSame(3, Journey::find(1)->execution_revision);
        $this->conflict(fn () => $this->command('skip', ['stop_id' => 1, 'reason_code' => 'customer_closed']), 422);
        $this->command('complete', ['stop_id' => 1, 'confirm_current_quantities' => true]);
        $this->command('reopen', ['stop_id' => 1]);
        $this->assertTrue($this->projectedItem()->is_loaded);
        $this->assertSame(2, DB::table('journey_execution_allocations')->count());
        $this->command('set_item_loaded', ['item_id' => 1, 'is_loaded' => false]);
        $this->assertFalse($this->projectedItem()->is_loaded);
    }

    public function test_quantity_changes_require_reconciliation_instead_of_silently_rebalancing(): void
    {
        $this->depart();
        $this->split();
        $this->command('set_item_loaded', ['item_id' => 1, 'is_loaded' => true]);
        $this->command('update_item', ['item_id' => 1, 'holder_quantity' => 7]);
        $item = $this->projectedItem();
        $this->assertFalse($item->is_loaded);
        $this->assertFalse($item->load_allocation_valid);
        $this->assertSame(6, collect($item->load_allocations)->sum('containers'));
        $this->conflict(fn () => $this->command('set_item_loaded', ['item_id' => 1, 'is_loaded' => true]), 422);
        $this->conflict(fn () => $this->command('complete', ['stop_id' => 1, 'confirm_current_quantities' => true]), 422);
        $this->assertNull(JourneyExecutionItem::find(1)->actual_containers); // Completion rolled back.
        $this->split(4, 3);
        $this->command('set_item_loaded', ['item_id' => 1, 'is_loaded' => true]);
        $this->assertTrue($this->projectedItem()->is_loaded);
        $this->command('update_item', ['item_id' => 1, 'description' => 'Descrizione più precisa']);
        $this->assertTrue($this->projectedItem()->is_loaded);
        $this->split(3, 4);
        $this->assertFalse($this->projectedItem()->is_loaded);
    }

    public function test_cargo_restore_and_undo_keep_the_split_consistent(): void
    {
        $this->depart();
        $this->split();
        $this->command('set_item_loaded', ['item_id' => 1, 'is_loaded' => true]);
        $this->command('restore_original', ['item_id' => 1, 'field' => 'cargo_location']);
        $this->assertSame(0, DB::table('journey_execution_allocations')->count());
        $this->assertSame('vehicle', $this->projectedItem()->cargo_location);
        $event = Journey::find(1)->events()->latest('id')->first();
        $this->command('undo_restore', ['event_id' => $event->id]);
        $this->assertSame(['trailer' => 2, 'vehicle' => 4], collect($this->projectedItem()->load_allocations)->pluck('containers', 'cargo_location')->all());
        $this->assertFalse($this->projectedItem()->is_loaded);
    }

    public function test_split_uses_actual_total_and_rejects_bulk_single_container_or_missing_trailer(): void
    {
        $this->depart(false);
        $this->conflict(fn () => $this->split(), 422);
        DB::table('journeys')->where('id', 1)->update(['trailer_id' => 1]);
        $this->command('update_item', ['item_id' => 1, 'actual_containers' => 5]);
        $this->split(3, 2);
        $this->assertSame(5, $this->projectedItem()->load_total);
        $this->command('update_item', ['item_id' => 1, 'cargo_location' => 'vehicle', 'actual_containers' => 1]);
        $this->conflict(fn () => $this->split(1, 0), 422);
        $this->command('update_item', ['item_id' => 1, 'is_bulk' => true, 'actual_containers' => null]);
        $this->conflict(fn () => $this->split(), 422);
    }

    public function test_driver_api_commands_enforce_scope_revision_and_roundtrip_the_checklist(): void
    {
        $this->depart();
        $this->withoutMiddleware([\App\Http\Middleware\HandleInertiaRequests::class, \Inertia\Middleware::class]);
        $this->actingAs(User::factory()->make(['id' => 1, 'role' => UserRole::DRIVER, 'is_admin' => false, 'email_verified_at' => now()]));
        $this->postJson('/api/journeys/1/execution/commands', ['revision' => 1, 'operation' => 'set_item_allocations', 'input' => ['item_id' => 1, 'vehicle_containers' => 4, 'trailer_containers' => 2]])->assertOk();
        $this->postJson('/api/journeys/1/execution/commands', ['revision' => 1, 'operation' => 'set_item_loaded', 'input' => ['item_id' => 1, 'is_loaded' => true]])->assertConflict();
        $this->postJson('/api/journeys/1/execution/commands', ['revision' => 2, 'operation' => 'set_item_loaded', 'input' => ['item_id' => 1, 'is_loaded' => true]])->assertOk()->assertJsonPath('journey.orders.0.items.0.is_loaded', true);
        $this->getJson('/api/journeys/1/execution')->assertOk()->assertJsonCount(2, 'journey.orders.0.items.0.load_allocations')->assertJsonPath('journey.orders.0.items.0.is_loaded', true);
        $other = User::factory()->make(['id' => 99, 'role' => UserRole::DRIVER, 'is_admin' => false, 'email_verified_at' => now()]);
        $this->actingAs($other)->postJson('/api/journeys/1/execution/commands', ['revision' => 3, 'operation' => 'set_item_loaded', 'input' => ['item_id' => 1, 'is_loaded' => false]])->assertForbidden();
    }
}
