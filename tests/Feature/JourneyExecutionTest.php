<?php

namespace Tests\Feature;

use App\Models\Journey;
use App\Models\JourneyBaseline;
use App\Models\JourneyExecutionItem;
use App\Models\JourneyOrderExecution;
use App\Models\Order;
use App\Models\User;
use App\Services\JourneyExecution\ExecutionProjection;
use App\Services\JourneyExecution\JourneyExecutionService;
use App\Services\JourneyExecution\PlanSnapshot;
use App\Services\OrderDocumentGenerationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\JourneyExecutionTestCase;

class JourneyExecutionTest extends JourneyExecutionTestCase
{
    public function test_departure_is_atomic_idempotent_and_freezes_aggregate(): void
    {
        $this->start();
        $baseline = JourneyBaseline::firstOrFail();
        $before = $baseline->data;
        $this->assertCount(2, $before['orders']);
        $this->assertCount(2, $before['stops']);
        $this->assertDatabaseCount('journey_order_executions', 2);
        $this->assertDatabaseCount('journey_execution_items', 2);
        $this->start();
        $this->assertDatabaseCount('journey_baselines', 1);
        $this->assertDatabaseCount('journey_events', 1);
        $item = JourneyExecutionItem::firstOrFail();
        $this->command('update_item', ['item_id' => $item->id, 'holder_quantity' => 4, 'description' => 'Diverso']);
        $this->assertSame(6, Order::first()->items->first()->holder_quantity);
        $this->assertSame($before, $baseline->fresh()->data);
        $this->assertSame(4, $item->fresh()->data['holder_quantity']);
        $this->expectException(\LogicException::class);
        $baseline->data = [];
        $baseline->save();
    }

    public function test_skip_releases_order_immediately_and_old_execution_survives_replanning(): void
    {
        $this->start();
        $original = JourneyBaseline::first()->checksum;
        $this->command('skip', ['stop_id' => 1, 'reason_code' => 'customer_closed']);
        $this->assertNull(Order::find(1)->journey_id);
        $this->assertDatabaseMissing('order_commitments', ['order_id' => 1]);
        DB::transaction(function () {
            $source = Order::lockForUpdate()->find(1);
            app(JourneyExecutionService::class)->claim($source, 2);
            DB::table('orders')->where('id', 1)->update(['journey_id' => 2, 'notes' => 'Ripianificato']);
            DB::table('order_items')->where('id', 1)->update(['holder_quantity' => 99]);
        });
        $execution = JourneyOrderExecution::where('source_order_id', 1)->first();
        $effective = app(ExecutionProjection::class)->order($execution);
        $this->assertSame('released', $execution->status);
        $this->assertSame(6, $effective->items->first()->holder_quantity);
        $this->assertNotSame('Ripianificato', $effective->notes);
        $this->assertSame($original, JourneyBaseline::first()->checksum);
        $this->assertSame('attivo', Journey::find(1)->status->value);
        $this->conflict(fn () => $this->command('reopen', ['stop_id' => 1]));
    }

    public function test_revision_conflict_rolls_back_and_double_commitment_is_rejected(): void
    {
        $j = $this->start();
        $item = JourneyExecutionItem::first();
        $this->command('update_item', ['item_id' => $item->id, 'holder_quantity' => 4]);
        $this->conflict(fn () => app(JourneyExecutionService::class)->command($j, 1, 1, 'update_item', ['item_id' => $item->id, 'holder_quantity' => 99]));
        $this->assertSame(4, $item->fresh()->data['holder_quantity']);
        $this->conflict(fn () => app(JourneyExecutionService::class)->claim(Order::find(1), 2));
    }

    public function test_previous_and_original_restore_are_new_events_without_history_loss(): void
    {
        $this->start();
        $id = JourneyExecutionItem::first()->id;
        $this->command('update_item', ['item_id' => $id, 'holder_quantity' => 4]);
        $this->command('update_item', ['item_id' => $id, 'holder_quantity' => 8]);
        $this->command('restore_previous', ['item_id' => $id, 'field' => 'holder_quantity']);
        $this->assertSame(4, JourneyExecutionItem::find($id)->data['holder_quantity']);
        $this->command('restore_previous', ['item_id' => $id, 'field' => 'holder_quantity']);
        $this->assertSame(6, JourneyExecutionItem::find($id)->data['holder_quantity']);
        $this->command('update_item', ['item_id' => $id, 'holder_quantity' => 2]);
        $this->command('restore_original', ['item_id' => $id, 'field' => 'holder_quantity']);
        $this->assertSame(6, JourneyExecutionItem::find($id)->data['holder_quantity']);
        $this->assertDatabaseCount('journey_events', 7);
    }

    public function test_pickup_and_delivery_are_independent_and_partial_stop_cannot_release(): void
    {
        $this->start();
        $id = JourneyExecutionItem::first()->id;
        $holder = DB::table('journey_execution_holders')->first();
        $this->command('update_item', ['item_id' => $id, 'actual_containers' => 4]);
        $this->command('update_holder', ['holder_id' => $holder->id, 'actual_delivered' => 8]);
        $this->conflict(fn () => $this->command('skip', ['stop_id' => 1, 'reason_code' => 'other', 'driver_notes' => 'Parziale']), 422);
        $this->command('complete', ['stop_id' => 1, 'confirm_current_quantities' => true]);
        $this->assertSame(4, JourneyExecutionItem::find($id)->actual_containers);
        $this->assertSame(8, (int) DB::table('journey_execution_holders')->value('actual_delivered'));
        $this->assertDatabaseHas('order_commitments', ['order_id' => 1, 'phase' => 'fulfilled']);
    }

    public function test_document_hash_has_both_triggers_and_reprint_is_read_only(): void
    {
        $documents = app(OrderDocumentGenerationService::class);
        $this->assertTrue($documents->isCurrent(Order::find(1)));
        $this->assertFalse($documents->enqueueGeneration(Order::find(1)));
        $this->assertSame('pianificato', Order::find(1)->status->value);
        DB::table('journeys')->where('id', 1)->update(['notes' => 'Nuovo percorso']);
        $this->assertFalse($documents->isCurrent(Order::find(1)));
        DB::table('journeys')->where('id', 1)->update(['notes' => null]);
        $this->start();
        $e = JourneyOrderExecution::first();
        $effective = app(ExecutionProjection::class)->order($e);
        $this->assertTrue($documents->isCurrent($effective));
        $this->assertFalse($documents->enqueueGeneration($effective));
        $this->command('update_item', ['item_id' => JourneyExecutionItem::first()->id, 'description' => 'Cambio materiale']);
        $this->assertFalse($documents->isCurrent(app(ExecutionProjection::class)->order($e->fresh())));
        $documents->enqueueGeneration(app(ExecutionProjection::class)->order($e->fresh()));
        $requested = $e->fresh()->documents_requested_hash;
        $this->command('update_journey', ['notes' => 'Cambio percorso']);
        $this->assertSame('', $documents->generate(app(ExecutionProjection::class)->order($e->fresh()), $requested));
        $this->assertSame('pianificato', Order::find(1)->status->value);
        $this->assertCount(1, Storage::disk('local')->files('orders/documents/order-1'));
    }

    public function test_missing_documents_prevent_partial_departure_and_legacy_is_not_rebased(): void
    {
        Storage::disk('local')->deleteDirectory('orders/documents/order-2');
        $this->conflict(fn () => $this->start(), 422);
        $this->assertDatabaseCount('journey_baselines', 0);
        $this->assertSame('creato', Journey::find(1)->status->value);
        DB::table('journeys')->where('id', 1)->update(['status' => 'attivo']);
        $this->conflict(fn () => $this->start());
        $this->assertDatabaseCount('journey_baselines', 0);
    }

    public function test_real_document_generation_preserves_state_and_uses_execution_quantities(): void
    {
        $this->start();
        $item = JourneyExecutionItem::first();
        $this->command('update_item', ['item_id' => $item->id, 'actual_containers' => 4, 'actual_weight_kg' => 77]);
        $e = JourneyOrderExecution::first();
        $documents = app(OrderDocumentGenerationService::class);
        $order = app(ExecutionProjection::class)->order($e);
        $documents->generate($order);
        $e->refresh();
        $effective = app(ExecutionProjection::class)->order($e);
        $this->assertTrue($documents->isCurrent($effective));
        $this->assertSame(2, $e->documents_version);
        $manifest = json_decode(Storage::disk('local')->get($e->documents_base_path.'/manifest-v2.json'), true);
        $this->assertSame(4, $manifest['items'][0]['holder_quantity']);
        $this->assertSame('77.000', $manifest['items'][0]['weight_declared']);
        $this->assertSame('attivo', Journey::find(1)->status->value);
        $this->assertSame('pianificato', Order::find(1)->status->value);
        $this->assertSame(6, Order::find(1)->items->first()->holder_quantity);
        $this->assertFalse($documents->enqueueGeneration($effective));
    }

    public function test_source_model_writes_are_blocked_after_departure_even_outside_http(): void
    {
        $this->start();
        $this->conflict(fn () => Order::find(1)->update(['notes' => 'Non consentito']));
        $this->conflict(fn () => \App\Models\OrderItem::find(1)->update(['holder_quantity' => 99]));
        $this->conflict(fn () => Journey::find(1)->update(['notes' => 'Non consentito']));
        $this->assertSame(6, Order::find(1)->items->first()->holder_quantity);
    }

    public function test_http_authorization_revision_history_and_planning_boundary(): void
    {
        $this->withoutMiddleware([\App\Http\Middleware\HandleInertiaRequests::class, \Inertia\Middleware::class]);
        $driver = User::factory()->make(['id' => 1, 'role' => \App\Enums\UserRole::DRIVER, 'is_admin' => false, 'email_verified_at' => now()]);
        $this->actingAs($driver)->postJson('/api/journeys/1/execution/commands', [
            'revision' => 1, 'operation' => 'add_item', 'input' => ['execution_order_id' => 1, 'cer_code_id' => 1],
        ])->assertStatus(409);
        $this->postJson('/api/driver/journeys/1/start')->assertOk()->assertJsonPath('journey.has_baseline', true);
        $item = JourneyExecutionItem::first();
        $this->postJson('/api/journeys/1/execution/commands', [
            'revision' => 1, 'operation' => 'update_item', 'input' => ['item_id' => $item->id, 'holder_quantity' => 4],
        ])->assertOk()->assertJsonPath('journey.execution_revision', 2);
        $this->postJson('/api/journeys/1/execution/commands', [
            'revision' => 1, 'operation' => 'update_item', 'input' => ['item_id' => $item->id, 'holder_quantity' => 99],
        ])->assertStatus(409);
        $this->getJson('/api/journeys/1/execution/history')->assertForbidden();
        $this->actingAs(User::factory()->make(['id' => 1, 'role' => \App\Enums\UserRole::LOGISTIC, 'is_admin' => false, 'email_verified_at' => now()]));
        $this->getJson('/api/journeys/1/execution/history')->assertOk()->assertJsonPath('events.0.payload.operation', 'update_item');
        $other = User::factory()->make(['id' => 9, 'role' => \App\Enums\UserRole::DRIVER, 'is_admin' => false, 'email_verified_at' => now()]);
        $this->actingAs($other)->getJson('/api/journeys/1/execution/history')->assertForbidden();
        $this->postJson('/api/journeys/1/execution/commands', [
            'revision' => 2, 'operation' => 'finish', 'input' => [],
        ])->assertForbidden();
        $admin = User::factory()->make(['id' => 9, 'role' => \App\Enums\UserRole::DEVELOPER, 'is_admin' => true, 'email_verified_at' => now()]);
        $this->actingAs($admin)->putJson('/order/1', ['notes' => 'Sovrascrittura'])->assertStatus(409);
        $this->postJson('/api/warehouse-order-items/save-items', ['items' => [['id' => 1, 'holder_quantity' => 99]]])->assertStatus(409);
    }

    public function test_finish_requires_all_stops_and_reopen_preserves_baseline(): void
    {
        $this->start();
        $checksum = JourneyBaseline::first()->checksum;
        $this->conflict(fn () => $this->command('finish', []), 422);
        $this->command('complete', ['stop_id' => 1, 'confirm_current_quantities' => true]);
        $this->command('skip', ['stop_id' => 2, 'reason_code' => 'customer_closed']);
        $this->command('finish', []);
        $this->assertSame('eseguito', Journey::find(1)->status->value);
        $this->command('reopen', ['stop_id' => 2]);
        $this->assertSame('attivo', Journey::find(1)->status->value);
        $this->assertDatabaseHas('order_commitments', ['order_id' => 2, 'journey_id' => 1, 'phase' => 'active']);
        $this->assertSame($checksum, JourneyBaseline::first()->checksum);
    }

    public function test_warehouse_records_reference_execution_and_cannot_change_planned_items(): void
    {
        $this->start();
        $item = JourneyExecutionItem::first();
        DB::table('warehouses')->insert(['id' => 1, 'name' => 'Deposito']);
        $this->command('complete', ['stop_id' => 1, 'confirm_current_quantities' => true]);
        $this->command('skip', ['stop_id' => 2, 'reason_code' => 'customer_closed']);
        $this->command('finish', []);
        $this->conflict(fn () => $this->command('close', []), 422);
        $this->command('warehouse_item', ['item_id' => $item->id, 'warehouse_id' => 1, 'weight_gross' => 120, 'weight_tare' => 10, 'warehouse_status' => 'classified']);
        $this->assertSame(110, $item->fresh()->data['weight_net']);
        $this->conflict(fn () => $this->command('reopen', ['stop_id' => 1]));
        $this->command('close', []);
        $this->assertSame('chiuso', Journey::find(1)->status->value);
        $this->assertSame('100', Order::find(1)->items->first()->weight_declared);
        $this->assertSame(6, Order::find(1)->items->first()->holder_quantity);
    }

    public function test_new_material_stop_reorder_and_noop_share_the_same_protocol(): void
    {
        $this->start();
        $this->command('reorder', ['stop_ids' => [2, 1]]);
        $this->assertDatabaseHas('journey_stops', ['id' => 2, 'status' => 'in_progress', 'sequence' => 1]);
        $e = JourneyOrderExecution::first();
        $this->command('add_item', ['execution_order_id' => $e->id, 'cer_code_id' => 1, 'holder_id' => 1, 'holder_quantity' => 2, 'description' => 'Aggiunto']);
        $item = JourneyExecutionItem::orderByDesc('id')->first();
        $this->assertNull($item->source_order_item_id);
        $this->command('remove_item', ['item_id' => $item->id]);
        $this->command('restore_previous', ['item_id' => $item->id, 'field' => 'removed']);
        $this->assertFalse($item->fresh()->removed);
        $revision = Journey::find(1)->execution_revision;
        $this->command('update_item', ['item_id' => $item->id, 'holder_quantity' => 2]);
        $this->assertSame($revision, Journey::find(1)->execution_revision);
        $this->command('add_stop', ['kind' => 'customer', 'customer_id' => 1, 'site_id' => 1, 'description' => 'Ritiro imprevisto']);
        $this->assertDatabaseHas('journey_order_executions', ['journey_id' => 1, 'source_order_id' => null]);
        $this->assertDatabaseCount('orders', 2);
        $data = app(\App\Services\Driver\DriverJourneyReadModel::class)->load(Journey::find(1))->toArray();
        $this->assertCount(3, $data['stops']);
        $this->assertCount(2, JourneyBaseline::first()->data['stops']);
    }

    public function test_archived_customer_keeps_its_company_name_in_stop_details(): void
    {
        DB::table('customers')->where('id', 1)->update(['company_name' => 'Ragione Sociale Srl', 'deleted_at' => now()]);
        $data = app(\App\Services\Driver\DriverJourneyReadModel::class)->load(Journey::find(1))->toArray();
        $this->assertSame('Ragione Sociale Srl', $data['stops'][0]['customer']['company_name']);
        $this->assertSame('Ragione Sociale Srl', $data['stops'][0]['stop_orders'][0]['order']['customer']['company_name']);
    }

    public function test_unchanged_values_do_not_create_revisions_or_invalidate_documents(): void
    {
        $this->start();
        $item = JourneyExecutionItem::first();
        $this->command('update_item', ['item_id' => $item->id, 'cer_code_id' => 1, 'holder_id' => 1, 'weight_declared' => 100, 'holder_quantity' => 6, 'description' => 'Ferro', 'is_bulk' => false]);
        $this->command('update_journey', ['notes' => '']);
        $this->assertSame(1, Journey::find(1)->execution_revision);
        $this->assertDatabaseCount('journey_events', 1);
        $this->assertTrue(app(OrderDocumentGenerationService::class)->isCurrent(app(ExecutionProjection::class)->order(JourneyOrderExecution::first())));
    }

    public function test_driver_cannot_record_warehouse_events(): void
    {
        $this->withoutMiddleware([\App\Http\Middleware\HandleInertiaRequests::class, \Inertia\Middleware::class]);
        $this->start();
        $driver = User::factory()->make(['id' => 1, 'role' => \App\Enums\UserRole::DRIVER, 'is_admin' => false, 'email_verified_at' => now()]);
        $this->actingAs($driver)->postJson('/api/journeys/1/execution/commands', [
            'revision' => 1, 'operation' => 'warehouse_item', 'input' => ['item_id' => 1, 'warehouse_id' => 1, 'warehouse_status' => 'received'],
        ])->assertForbidden();
        $this->putJson('/driver/order/1', ['notes' => 'Modifica pianificato'])->assertStatus(409);
    }

    public function test_restoring_cer_and_holder_recovers_their_original_metadata(): void
    {
        $this->start();
        $item = JourneyExecutionItem::first();
        DB::table('cer_codes')->insert(['id' => 2, 'code' => '020104', 'description' => 'Altro CER']);
        DB::table('holders')->insert(['id' => 2, 'name' => 'Big bag']);
        $this->command('update_item', ['item_id' => $item->id, 'cer_code_id' => 2, 'holder_id' => 2]);
        $this->command('restore_previous', ['item_id' => $item->id, 'field' => 'holder_id']);
        $this->assertSame(1, $item->fresh()->data['holder_id']);
        $this->assertSame('Cassa', $item->fresh()->data['holder']['name']);
        DB::table('cer_codes')->where('id', 1)->update(['description' => 'Anagrafica cambiata dopo la partenza']);
        $this->command('restore_original', ['item_id' => $item->id, 'field' => 'cer_code_id']);
        $this->assertSame(1, $item->fresh()->data['cer_code_id']);
        $this->assertSame('Ferro', $item->fresh()->data['cer_code']['description']);
        $baseline = JourneyBaseline::first();
        $this->assertSame($baseline->checksum, app(PlanSnapshot::class)->checksum($baseline->data));
    }
}
