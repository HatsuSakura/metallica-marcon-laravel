<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Journey;
use App\Models\JourneyExecutionItem;
use App\Models\Order;
use App\Models\User;
use App\Services\Driver\DriverJourneyReadModel;
use App\Services\JourneyExecution\DocumentRevision;
use App\Services\JourneyExecution\ItemCargoLocation;
use Illuminate\Support\Facades\DB;
use Tests\Support\JourneyExecutionTestCase;

class JourneyItemCargoLocationTest extends JourneyExecutionTestCase
{
    private function prepareDeparture(): void
    {
        DB::table('trailers')->insert(['id' => 1, 'name' => 'Rimorchio']);
        DB::table('journeys')->where('id', 1)->update(['trailer_id' => 1]);
        foreach ([1, 2] as $id) {
            DB::table('orders')->where('id', $id)->update(['documents_input_hash' => app(DocumentRevision::class)->hash(Order::findOrFail($id))]);
        }
        $this->start();
    }

    private function item(int $sourceId = 1): \App\Models\OrderItem
    {
        return app(DriverJourneyReadModel::class)->load(Journey::find(1))->orders->flatMap->items->firstWhere('source_order_item_id', $sourceId);
    }

    public function test_planned_item_allocation_precedes_order_default_and_capacity_has_no_default(): void
    {
        $resolver = app(ItemCargoLocation::class);
        $snapshot = ['cargos' => [['id' => 1, 'cargo_location' => 'vehicle'], ['id' => 2, 'cargo_location' => 'trailer']],
            'allocations' => [['order_item_id' => 7, 'journey_cargo_id' => 2, 'source' => 'planned', 'allocated_containers' => 8]]];
        $this->assertSame('trailer', $resolver->planned($snapshot, ['cargo_location' => 'vehicle'], ['id' => 7]));
        $this->assertNull($resolver->planned($snapshot, ['cargo_location' => 'fulfill'], ['id' => 7]));
        $this->assertSame('vehicle', $resolver->planned([], [], []));
        $snapshot['allocations'][] = ['order_item_id' => 7, 'journey_cargo_id' => 1, 'allocated_containers' => 2];
        $this->assertNull($resolver->planned($snapshot, ['cargo_location' => 'vehicle'], ['id' => 7]));
    }

    public function test_location_is_saved_per_item_and_restored_without_changing_the_order_or_baseline(): void
    {
        DB::table('orders')->where('id', 1)->update(['cargo_location' => 'vehicle']);
        $this->prepareDeparture();
        $baseline = Journey::find(1)->baseline->data;
        $item = $this->item();
        $this->assertSame('vehicle', $item->cargo_location);
        $this->command('update_item', ['item_id' => $item->id, 'cargo_location' => 'vehicle']);
        $this->assertSame(1, Journey::find(1)->execution_revision);
        $this->command('update_item', ['item_id' => $item->id, 'cargo_location' => 'trailer']);
        $this->assertSame('trailer', $this->item()->cargo_location);
        $this->assertSame('vehicle', $this->item(2)->cargo_location);
        $event = Journey::find(1)->events()->latest('id')->first();
        $change = collect($event->payload['changes'])->firstWhere('path', 'items.'.$item->id.'.data.cargo_location');
        $this->assertSame('vehicle', $change['before']);
        $this->assertSame('trailer', $change['after']);
        $this->command('restore_original', ['item_id' => $item->id, 'field' => 'cargo_location']);
        $this->assertSame('vehicle', $this->item()->cargo_location);
        $this->assertSame($baseline, Journey::find(1)->baseline->data);
        $this->assertSame('vehicle', Order::find(1)->getRawOriginal('cargo_location'));
    }

    public function test_capacity_items_remain_unselected_until_the_driver_chooses(): void
    {
        DB::table('orders')->where('id', 1)->update(['cargo_location' => 'fulfill']);
        $this->prepareDeparture();
        $item = $this->item();
        $this->assertNull($item->cargo_location);
        $this->command('update_item', ['item_id' => $item->id, 'cargo_location' => 'trailer']);
        $this->assertSame('trailer', $this->item()->cargo_location);
        $this->command('restore_original', ['item_id' => $item->id, 'field' => 'cargo_location']);
        $this->assertNull($this->item()->cargo_location);
    }

    public function test_driver_selection_is_returned_immediately_and_persisted_on_reload(): void
    {
        $this->prepareDeparture();
        $this->withoutMiddleware([\App\Http\Middleware\HandleInertiaRequests::class, \Inertia\Middleware::class]);
        $this->actingAs(User::factory()->make(['id' => 1, 'role' => UserRole::DRIVER, 'is_admin' => false, 'email_verified_at' => now()]));
        $item = $this->item();
        $this->postJson('/api/journeys/1/execution/commands', ['revision' => 1, 'operation' => 'update_item', 'input' => ['item_id' => $item->id, 'cargo_location' => 'trailer']])
            ->assertOk()->assertJsonPath('journey.orders.0.items.0.cargo_location', 'trailer');
        $this->getJson('/api/journeys/1/execution')->assertOk()->assertJsonPath('journey.orders.0.items.0.cargo_location', 'trailer');
    }

    public function test_api_prevents_nonexistent_trailer_and_records_driver_selection(): void
    {
        $this->withoutMiddleware([\App\Http\Middleware\HandleInertiaRequests::class, \Inertia\Middleware::class]);
        $this->actingAs(User::factory()->make(['id' => 1, 'role' => UserRole::DRIVER, 'is_admin' => false, 'email_verified_at' => now()]));
        $this->start();
        $item = JourneyExecutionItem::first();
        $this->postJson('/api/journeys/1/execution/commands', ['revision' => 1, 'operation' => 'update_item', 'input' => ['item_id' => $item->id, 'cargo_location' => 'trailer']])->assertUnprocessable();
        $this->assertSame(1, Journey::find(1)->execution_revision);
        $this->postJson('/api/journeys/1/execution/commands', ['revision' => 1, 'operation' => 'update_item', 'input' => ['item_id' => $item->id, 'cargo_location' => 'fulfill']])->assertUnprocessable();
        $this->command('complete', ['stop_id' => 1, 'confirm_current_quantities' => true]);
        $this->postJson('/api/journeys/1/execution/commands', ['revision' => 2, 'operation' => 'update_item', 'input' => ['item_id' => $item->id, 'cargo_location' => 'vehicle']])->assertNotFound();
    }
}
