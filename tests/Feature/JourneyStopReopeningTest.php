<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Journey;
use App\Models\JourneyBaseline;
use App\Models\JourneyExecutionItem;
use App\Models\JourneyOrderExecution;
use App\Models\Order;
use App\Models\User;
use App\Services\Driver\DriverJourneyReadModel;
use App\Services\JourneyExecution\DocumentRevision;
use Illuminate\Support\Facades\DB;
use Tests\Support\JourneyExecutionTestCase;

class JourneyStopReopeningTest extends JourneyExecutionTestCase
{
    public function test_reopening_returns_current_focus_preserving_actuals_and_first_start(): void
    {
        $this->start();
        $started = DB::table('journey_stops')->where('id', 1)->value('started_at');
        $this->command('update_item', ['item_id' => 1, 'actual_containers' => 4, 'actual_weight_kg' => 80]);
        $holder = DB::table('journey_execution_holders')->where('order_execution_id', 1)->value('id');
        $this->command('update_holder', ['holder_id' => $holder, 'actual_delivered' => 8]);
        $this->command('complete', ['stop_id' => 1]);
        $nextStarted = DB::table('journey_stops')->where('id', 2)->value('started_at');
        $this->command('update_item', ['item_id' => 2, 'actual_containers' => 2]);
        $checksum = JourneyBaseline::first()->checksum;
        $this->command('reopen', ['stop_id' => 1]);
        $this->assertDatabaseHas('journey_stops', ['id' => 1, 'status' => 'in_progress', 'completed_at' => null, 'started_at' => $started]);
        $this->assertDatabaseHas('journey_stops', ['id' => 2, 'status' => 'planned', 'started_at' => $nextStarted]);
        $this->assertSame(1, DB::table('journey_stops')->where('status', 'in_progress')->count());
        $this->assertSame(4, JourneyExecutionItem::find(1)->actual_containers);
        $this->assertSame('80.000', JourneyExecutionItem::find(1)->actual_weight_kg);
        $this->assertSame(2, JourneyExecutionItem::find(2)->actual_containers);
        $this->assertSame(8, (int) DB::table('journey_execution_holders')->where('id', $holder)->value('actual_delivered'));
        $this->assertSame($checksum, JourneyBaseline::first()->checksum);
        $view = app(DriverJourneyReadModel::class)->load(Journey::find(1))->toArray();
        $this->assertSame('in_progress', $view['stops'][0]['status']);
        $this->assertSame('pending', $view['stops'][0]['stop_orders'][0]['order']['execution_status']);
        $this->assertDatabaseHas('order_commitments', ['order_id' => 1, 'phase' => 'active']);
        $event = Journey::find(1)->events()->orderByDesc('id')->first();
        $this->assertSame('done', $event->payload['reopened_from']);
        $this->assertSame([2], $event->payload['deferred_stop_ids']);
        $this->assertSame(1, $event->created_by_user_id);
        $this->assertNotEmpty($event->created_at);
        $this->assertNotEmpty($event->payload['changes']);
    }

    public function test_recompletion_does_not_duplicate_orders_items_deliveries_or_reservations(): void
    {
        $this->start();
        $this->command('complete', ['stop_id' => 1, 'confirm_current_quantities' => true]);
        $this->command('reopen', ['stop_id' => 1]);
        $this->command('complete', ['stop_id' => 1]);
        $this->assertDatabaseCount('journey_order_executions', 2);
        $this->assertDatabaseCount('journey_execution_items', 2);
        $this->assertDatabaseCount('journey_execution_holders', 2);
        $this->assertDatabaseCount('order_commitments', 2);
        $this->assertDatabaseHas('journey_stops', ['id' => 2, 'status' => 'in_progress']);
        $this->assertDatabaseHas('order_commitments', ['order_id' => 1, 'phase' => 'fulfilled']);
    }

    public function test_receipt_and_reassignment_have_visible_block_reasons_and_no_side_effects(): void
    {
        $this->start();
        $this->command('complete', ['stop_id' => 1, 'confirm_current_quantities' => true]);
        DB::table('warehouses')->insert(['id' => 1, 'name' => 'Deposito']);
        $this->command('warehouse_item', ['item_id' => 1, 'warehouse_id' => 1, 'warehouse_status' => 'received']);
        $revision = Journey::find(1)->execution_revision;
        $view = app(DriverJourneyReadModel::class)->load(Journey::find(1))->toArray();
        $this->assertFalse($view['stops'][0]['reopen_available']);
        $this->assertStringContainsString('magazzino', $view['stops'][0]['reopen_unavailable_reason']);
        $this->conflict(fn () => $this->command('reopen', ['stop_id' => 1]));
        $this->assertSame($revision, Journey::find(1)->execution_revision);
        $this->assertDatabaseHas('journey_stops', ['id' => 1, 'status' => 'done']);
        $this->command('skip', ['stop_id' => 2, 'reason_code' => 'customer_closed']);
        DB::table('orders')->where('id', 2)->update(['journey_id' => 2]);
        DB::table('order_commitments')->insert(['order_id' => 2, 'journey_id' => 2, 'phase' => 'planned']);
        $view = app(DriverJourneyReadModel::class)->load(Journey::find(1))->toArray();
        $this->assertFalse($view['stops'][1]['reopen_available']);
        $this->assertStringContainsString('ripianificato', $view['stops'][1]['reopen_unavailable_reason']);
        $this->conflict(fn () => $this->command('reopen', ['stop_id' => 2]));
    }

    public function test_multi_order_reopening_is_atomic_when_one_order_is_blocked(): void
    {
        DB::table('journey_stop_orders')->where('order_id', 2)->update(['journey_stop_id' => 1]);
        foreach ([1, 2] as $id) {
            DB::table('orders')->where('id', $id)->update(['documents_input_hash' => app(DocumentRevision::class)->hash(Order::find($id))]);
        }
        $this->start();
        $this->command('skip', ['stop_id' => 1, 'reason_code' => 'customer_closed']);
        DB::table('orders')->where('id', 2)->update(['journey_id' => 2]);
        DB::table('order_commitments')->insert(['order_id' => 2, 'journey_id' => 2, 'phase' => 'planned']);
        $this->conflict(fn () => $this->command('reopen', ['stop_id' => 1]));
        $this->assertNull(Order::find(1)->journey_id);
        $this->assertDatabaseMissing('order_commitments', ['order_id' => 1]);
        $this->assertSame('released', JourneyOrderExecution::where('source_order_id', 1)->value('status'));
        $this->assertDatabaseHas('journey_stops', ['id' => 1, 'status' => 'skipped']);
    }

    public function test_ended_journey_reopens_without_recapturing_baseline_but_closed_journey_cannot(): void
    {
        $this->start();
        $start = Journey::find(1)->actual_start_at;
        $checksum = JourneyBaseline::first()->checksum;
        $this->command('complete', ['stop_id' => 1, 'confirm_current_quantities' => true]);
        $this->command('skip', ['stop_id' => 2, 'reason_code' => 'customer_closed']);
        $this->command('finish', []);
        $this->command('reopen', ['stop_id' => 1]);
        $this->assertSame('attivo', Journey::find(1)->status->value);
        $this->assertNull(Journey::find(1)->actual_end_at);
        $this->assertEquals($start, Journey::find(1)->actual_start_at);
        $this->assertSame($checksum, JourneyBaseline::first()->checksum);
        DB::table('journeys')->where('id', 1)->update(['status' => 'chiuso']);
        $this->conflict(fn () => $this->command('reopen', ['stop_id' => 2]), 422);
    }

    public function test_http_reopening_is_persistent_and_rejects_other_drivers_and_stale_revisions(): void
    {
        $this->start();
        $this->command('complete', ['stop_id' => 1, 'confirm_current_quantities' => true]);
        $this->withoutMiddleware([\App\Http\Middleware\HandleInertiaRequests::class, \Inertia\Middleware::class]);
        $this->actingAs(User::factory()->make(['id' => 1, 'role' => UserRole::DRIVER, 'is_admin' => false, 'email_verified_at' => now()]));
        $this->postJson('/api/journeys/1/execution/commands', ['revision' => 2, 'operation' => 'reopen', 'input' => ['stop_id' => 1]])
            ->assertOk()->assertJsonPath('journey.stops.0.status', 'in_progress')->assertJsonPath('journey.stops.1.status', 'planned');
        $this->getJson('/api/journeys/1/execution')->assertOk()->assertJsonPath('journey.stops.0.status', 'in_progress');
        $this->postJson('/api/journeys/1/execution/commands', ['revision' => 2, 'operation' => 'reopen', 'input' => ['stop_id' => 1]])->assertStatus(409);
        $this->actingAs(User::factory()->make(['id' => 9, 'role' => UserRole::DRIVER, 'is_admin' => false, 'email_verified_at' => now()]));
        $this->postJson('/api/journeys/1/execution/commands', ['revision' => 3, 'operation' => 'reopen', 'input' => ['stop_id' => 1]])->assertForbidden();
    }

    public function test_reopening_is_disabled_if_driver_has_started_another_journey(): void
    {
        $this->start();
        $this->command('complete', ['stop_id' => 1, 'confirm_current_quantities' => true]);
        $this->command('skip', ['stop_id' => 2, 'reason_code' => 'customer_closed']);
        $this->command('finish', []);
        DB::table('journeys')->where('id', 2)->update(['status' => 'attivo', 'execution_revision' => 1]);
        $view = app(DriverJourneyReadModel::class)->load(Journey::find(1))->toArray();
        $this->assertFalse($view['stops'][0]['reopen_available']);
        $this->assertStringContainsString('altro viaggio', $view['stops'][0]['reopen_unavailable_reason']);
        $this->conflict(fn () => $this->command('reopen', ['stop_id' => 1]));
        $this->assertSame('eseguito', Journey::find(1)->status->value);
    }

    public function test_archived_source_does_not_allow_reopening_but_keeps_execution_readable(): void
    {
        $this->start();
        $this->command('complete', ['stop_id' => 1, 'confirm_current_quantities' => true]);
        DB::table('orders')->where('id', 1)->update(['deleted_at' => now()]);
        $view = app(DriverJourneyReadModel::class)->load(Journey::find(1))->toArray();
        $this->assertFalse($view['stops'][0]['reopen_available']);
        $this->assertStringContainsString('non più disponibile', $view['stops'][0]['reopen_unavailable_reason']);
        $this->assertSame(6, $view['stops'][0]['stop_orders'][0]['order']['items'][0]['actual_containers']);
        $this->conflict(fn () => $this->command('reopen', ['stop_id' => 1]));
    }
}
