<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Journey;
use App\Models\JourneyExecutionItem;
use App\Models\User;
use App\Services\Driver\DriverJourneyReadModel;
use App\Services\JourneyExecution\ExecutionRestoration;
use Tests\Support\JourneyExecutionTestCase;

class JourneyRestorationTest extends JourneyExecutionTestCase
{
    private function previewOptions(int $id = 1, string $field = 'holder_quantity'): array
    {
        return app(ExecutionRestoration::class)->previews(Journey::find(1), JourneyExecutionItem::where('id', $id)->get())[$id][$field];
    }

    public function test_preview_exposes_saved_values_and_merges_coincident_targets(): void
    {
        $this->start();
        $initial = $this->previewOptions();
        $this->assertSame(6, $initial['current']['value']);
        $this->assertFalse($initial['previous']['available']);
        $this->assertFalse($initial['original']['available']);
        $this->command('update_item', ['item_id' => 1, 'holder_quantity' => 4]);
        $preview = $this->previewOptions();
        $this->assertSame(4, $preview['current']['value']);
        $this->assertSame(6, $preview['previous']['value']);
        $this->assertSame(6, $preview['original']['value']);
        $this->assertTrue($preview['same_targets']);
        $this->command('update_item', ['item_id' => 1, 'holder_quantity' => 8]);
        $preview = $this->previewOptions();
        $this->assertSame(4, $preview['previous']['value']);
        $this->assertSame(6, $preview['original']['value']);
        $this->assertFalse($preview['same_targets']);
        $this->assertSame(3, Journey::find(1)->execution_revision);
    }

    public function test_undo_previous_restoration_restores_value_and_reenables_undo_stack(): void
    {
        $this->start();
        $this->command('update_item', ['item_id' => 1, 'holder_quantity' => 4]);
        $this->command('update_item', ['item_id' => 1, 'holder_quantity' => 8]);
        $this->command('restore_previous', ['item_id' => 1, 'field' => 'holder_quantity']);
        $event = Journey::find(1)->events()->orderByDesc('id')->first();
        $this->assertSame(4, JourneyExecutionItem::find(1)->data['holder_quantity']);
        $this->command('undo_restore', ['event_id' => $event->id]);
        $this->assertSame(8, JourneyExecutionItem::find(1)->data['holder_quantity']);
        $this->assertSame(4, $this->previewOptions()['previous']['value']);
        $this->command('restore_previous', ['item_id' => 1, 'field' => 'holder_quantity']);
        $this->command('restore_previous', ['item_id' => 1, 'field' => 'holder_quantity']);
        $this->assertSame(6, JourneyExecutionItem::find(1)->data['holder_quantity']);
        $this->assertFalse($this->previewOptions()['previous']['available']);
        $this->assertDatabaseCount('journey_events', 7);
    }

    public function test_undo_original_restoration_does_not_erase_or_replay_cancelled_history(): void
    {
        $this->start();
        $this->command('update_item', ['item_id' => 1, 'holder_quantity' => 4]);
        $this->command('restore_original', ['item_id' => 1, 'field' => 'holder_quantity']);
        $event = Journey::find(1)->events()->orderByDesc('id')->first();
        $this->command('undo_restore', ['event_id' => $event->id]);
        $this->assertSame(4, JourneyExecutionItem::find(1)->data['holder_quantity']);
        $this->command('restore_previous', ['item_id' => 1, 'field' => 'holder_quantity']);
        $this->assertSame(6, JourneyExecutionItem::find(1)->data['holder_quantity']);
        $this->assertFalse($this->previewOptions()['previous']['available']);
    }

    public function test_stale_or_arbitrary_events_cannot_be_undone(): void
    {
        $this->start();
        $this->command('update_item', ['item_id' => 1, 'holder_quantity' => 4]);
        $edit = Journey::find(1)->events()->orderByDesc('id')->first();
        $this->conflict(fn () => $this->command('undo_restore', ['event_id' => $edit->id]));
        $this->command('restore_previous', ['item_id' => 1, 'field' => 'holder_quantity']);
        $restore = Journey::find(1)->events()->orderByDesc('id')->first();
        $this->command('update_order', ['execution_order_id' => 1, 'notes' => 'Altra modifica']);
        $this->conflict(fn () => $this->command('undo_restore', ['event_id' => $restore->id]));
        $this->assertSame(6, JourneyExecutionItem::find(1)->data['holder_quantity']);
    }

    public function test_added_and_removed_items_have_explicit_safe_previews(): void
    {
        $this->start();
        $this->command('add_item', ['execution_order_id' => 1, 'cer_code_id' => 1, 'holder_quantity' => 2]);
        $id = JourneyExecutionItem::orderByDesc('id')->value('id');
        $this->command('update_item', ['item_id' => $id, 'holder_quantity' => 3]);
        $options = $this->previewOptions($id);
        $this->assertFalse($options['original']['exists']);
        $this->assertFalse($options['original']['available']);
        $this->assertStringContainsString('aggiunto', $options['original']['reason']);
        $this->assertSame(2, $options['previous']['value']);
        $this->command('remove_item', ['item_id' => 1]);
        $view = app(DriverJourneyReadModel::class)->load(Journey::find(1))->toArray();
        $removed = $view['stops'][0]['stop_orders'][0]['order']['removed_items'][0];
        $this->assertTrue($removed['restore_options']['removed']['same_targets']);
        $this->assertSame('Materiale presente', $removed['restore_options']['removed']['previous']['label']);
    }

    public function test_zero_is_not_confused_with_unregistered_actuals(): void
    {
        $this->start();
        $this->command('update_item', ['item_id' => 1, 'actual_containers' => 0]);
        $preview = $this->previewOptions(1, 'actual_containers');
        $this->assertSame('0', $preview['current']['label']);
        $this->assertSame('Non ancora registrato', $preview['previous']['label']);
        $this->assertTrue($preview['same_targets']);
        $this->command('restore_previous', ['item_id' => 1, 'field' => 'actual_containers']);
        $event = Journey::find(1)->events()->orderByDesc('id')->first();
        $this->command('undo_restore', ['event_id' => $event->id]);
        $this->assertSame(0, JourneyExecutionItem::find(1)->actual_containers);
    }

    public function test_http_response_supplies_undo_and_keeps_every_operation_audited(): void
    {
        $this->start();
        $this->withoutMiddleware([\App\Http\Middleware\HandleInertiaRequests::class, \Inertia\Middleware::class]);
        $this->actingAs(User::factory()->make(['id' => 1, 'role' => UserRole::DRIVER, 'is_admin' => false, 'email_verified_at' => now()]));
        $this->command('update_item', ['item_id' => 1, 'holder_quantity' => 4]);
        $response = $this->postJson('/api/journeys/1/execution/commands', [
            'revision' => 2, 'operation' => 'restore_previous', 'input' => ['item_id' => 1, 'field' => 'holder_quantity'],
        ])->assertOk()->assertJsonPath('undo.revision', 3)
            ->assertJsonPath('journey.stops.0.stop_orders.0.order.items.0.restore_options.holder_quantity.current.value', 6);
        $event = $response->json('undo.event_id');
        $this->postJson('/api/journeys/1/execution/commands', [
            'revision' => 3, 'operation' => 'undo_restore', 'input' => ['event_id' => $event],
        ])->assertOk()->assertJsonPath('undo', null)->assertJsonPath('journey.execution_revision', 4);
        $this->assertSame(4, JourneyExecutionItem::find(1)->data['holder_quantity']);
        $this->getJson('/api/journeys/1/execution/history')->assertForbidden();
        $this->actingAs(User::factory()->make(['id' => 1, 'role' => \App\Enums\UserRole::LOGISTIC, 'is_admin' => false, 'email_verified_at' => now()]));
        $this->getJson('/api/journeys/1/execution/history')->assertOk()
            ->assertJsonPath('events.0.payload.operation', 'undo_restore')->assertJsonPath('events.0.payload.cancels_restore_event_id', $event);
    }
}
