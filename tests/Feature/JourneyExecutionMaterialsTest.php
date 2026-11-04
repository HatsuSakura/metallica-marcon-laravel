<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Journey;
use App\Models\JourneyBaseline;
use App\Models\JourneyExecutionItem;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Support\JourneyExecutionTestCase;

class JourneyExecutionMaterialsTest extends JourneyExecutionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\App\Http\Middleware\HandleInertiaRequests::class, \Inertia\Middleware::class]);
        $this->actingAs(User::factory()->make(['id' => 1, 'role' => UserRole::DRIVER, 'is_admin' => false, 'email_verified_at' => now()]));
        DB::table('cer_codes')->insert(['id' => 2, 'code' => '090106', 'description' => 'Materiale aggiunto', 'is_dangerous' => 0]);
        DB::table('holders')->insert(['id' => 2, 'name' => 'Fusto']);
    }

    public function test_driver_adds_a_new_cer_and_updates_existing_item_only_in_execution(): void
    {
        $this->start();
        $checksum = JourneyBaseline::first()->checksum;
        $this->postJson('/api/journeys/1/execution/commands', [
            'revision' => 1, 'operation' => 'add_item', 'input' => ['execution_order_id' => 1, 'cer_code_id' => 2, 'holder_id' => 2, 'holder_quantity' => 3, 'weight_declared' => 900, 'description' => 'Materiale non previsto', 'is_bulk' => false],
        ])->assertOk()->assertJsonPath('journey.execution_revision', 2);
        $added = JourneyExecutionItem::orderByDesc('id')->first();
        $this->assertNull($added->source_order_item_id);
        $this->assertSame(2, $added->data['cer_code_id']);
        $this->assertSame(3, $added->data['holder_quantity']);
        $this->assertSame('Materiale non previsto', $added->data['description']);
        $this->postJson('/api/journeys/1/execution/commands', [
            'revision' => 2, 'operation' => 'update_item', 'input' => ['item_id' => 1, 'cer_code_id' => 2, 'holder_id' => 2, 'holder_quantity' => 4, 'weight_declared' => 840, 'description' => 'Rettifica in fase attuativa', 'actual_containers' => 4, 'actual_weight_kg' => 840],
        ])->assertOk()->assertJsonPath('journey.execution_revision', 3);
        $view = $this->getJson('/api/journeys/1/execution')->assertOk();
        $items = $view->json('journey.stops.0.stop_orders.0.order.items');
        $this->assertCount(2, $items);
        $updated = collect($items)->firstWhere('id', 1);
        $this->assertSame('090106', $updated['cer_code']['code']);
        $this->assertSame(4, $updated['holder_quantity']);
        $this->assertSame(840, $updated['weight_declared']);
        $this->assertSame('Rettifica in fase attuativa', $updated['description']);
        $this->assertSame(4, $updated['actual_containers']);
        $this->assertSame('840.000', $updated['actual_weight_kg']);
        $this->assertSame(1, OrderItem::find(1)->cer_code_id);
        $this->assertSame(6, OrderItem::find(1)->holder_quantity);
        $this->assertSame('Ferro', OrderItem::find(1)->description);
        $this->assertDatabaseCount('order_items', 2);
        $this->assertSame($checksum, JourneyBaseline::first()->checksum);
        $this->getJson('/api/journeys/1/execution/history')->assertForbidden();
        $this->actingAs(User::factory()->make(['id' => 1, 'role' => \App\Enums\UserRole::LOGISTIC, 'is_admin' => false, 'email_verified_at' => now()]));
        $this->getJson('/api/journeys/1/execution/history')->assertOk()->assertJsonPath('events.0.payload.operation', 'update_item')->assertJsonPath('events.1.payload.operation', 'add_item');
    }

    public function test_new_material_keeps_explicit_actual_zero_and_completed_order_requires_reopening(): void
    {
        $this->start();
        $this->postJson('/api/journeys/1/execution/commands', [
            'revision' => 1, 'operation' => 'add_item', 'input' => ['execution_order_id' => 1, 'cer_code_id' => 2, 'holder_id' => 2, 'holder_quantity' => 3, 'weight_declared' => 900, 'actual_containers' => 0, 'actual_weight_kg' => 0],
        ])->assertOk();
        $added = JourneyExecutionItem::orderByDesc('id')->first();
        $this->assertSame(0, $added->actual_containers);
        $this->assertSame('0.000', $added->actual_weight_kg);
        $this->command('complete', ['stop_id' => 1, 'confirm_current_quantities' => true]);
        $revision = Journey::find(1)->execution_revision;
        $this->postJson('/api/journeys/1/execution/commands', [
            'revision' => $revision, 'operation' => 'update_item', 'input' => ['item_id' => 1, 'description' => 'Non consentito su tappa conclusa'],
        ])->assertNotFound();
        $this->postJson('/api/journeys/1/execution/commands', [
            'revision' => $revision, 'operation' => 'add_item', 'input' => ['execution_order_id' => 1, 'cer_code_id' => 2],
        ])->assertNotFound();
        $this->assertSame($revision, Journey::find(1)->execution_revision);
        $this->assertDatabaseCount('journey_execution_items', 3);
    }
}
