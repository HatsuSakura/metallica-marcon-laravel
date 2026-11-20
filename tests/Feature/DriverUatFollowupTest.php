<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Journey;
use App\Models\JourneyExecutionHolder;
use App\Models\JourneyExecutionItem;
use App\Models\Order;
use App\Models\User;
use App\Services\Driver\DriverJourneyReadModel;
use App\Services\JourneyExecution\DocumentRevision;
use App\Services\JourneyExecution\ExecutionProjection;
use App\Services\JourneyPlanning\PlanningInput;
use App\Services\OrderDocumentGenerationService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;
use Tests\Support\JourneyExecutionTestCase;

class DriverUatFollowupTest extends JourneyExecutionTestCase
{
    private function login(UserRole $role = UserRole::DRIVER, int $id = 1): void
    {
        $this->withoutMiddleware([HandleInertiaRequests::class, Middleware::class]);
        $this->actingAs(User::factory()->make(['id' => $id, 'role' => $role, 'is_admin' => false, 'email_verified_at' => now()]));
    }

    public function test_removed_material_can_be_restored_with_allocations_but_hidden_material_is_only_removed_from_ui(): void
    {
        DB::table('journeys')->where('id', 1)->update(['trailer_id' => 1]);
        foreach ([1, 2] as $id) {
            DB::table('orders')->where('id', $id)->update(['documents_input_hash' => app(DocumentRevision::class)->hash(Order::find($id))]);
        }
        $this->start();
        $this->command('set_item_allocations', ['item_id' => 1, 'vehicle_containers' => 4, 'trailer_containers' => 2]);
        $this->command('remove_item', ['item_id' => 1]);
        $read = app(DriverJourneyReadModel::class)->load(Journey::find(1));
        $this->assertCount(1, $read->orders->first()->removed_items);
        $this->command('restore_item', ['item_id' => 1]);
        $this->assertFalse(JourneyExecutionItem::find(1)->removed);
        $this->assertSame(6, JourneyExecutionItem::find(1)->allocations->sum('containers'));
        $this->command('remove_item', ['item_id' => 1]);
        $before = app(DocumentRevision::class)->hash(app(ExecutionProjection::class)->order(Journey::find(1)->orderExecutions()->first()));
        $this->command('hide_removed_item', ['item_id' => 1]);
        $this->assertTrue(JourneyExecutionItem::find(1)->removed);
        $this->assertTrue(JourneyExecutionItem::find(1)->hidden_from_driver);
        $this->assertSame($before, app(DocumentRevision::class)->hash(app(ExecutionProjection::class)->order(Journey::find(1)->orderExecutions()->first())));
        $this->assertCount(0, app(DriverJourneyReadModel::class)->load(Journey::find(1))->orders->first()->removed_items);
        $this->assertDatabaseCount('journey_execution_items', 2);
        $this->assertDatabaseHas('journey_events', ['journey_id' => 1]);
        $this->conflict(fn () => $this->command('restore_item', ['item_id' => 1]), 422);
    }

    public function test_restore_enforces_revision_pending_order_and_driver_scope(): void
    {
        $this->start();
        $this->command('remove_item', ['item_id' => 1]);
        $this->login();
        $this->postJson('/api/journeys/1/execution/commands', ['revision' => 1, 'operation' => 'restore_item', 'input' => ['item_id' => 1]])->assertConflict();
        $this->login(UserRole::DRIVER, 99);
        $this->postJson('/api/journeys/1/execution/commands', ['revision' => 2, 'operation' => 'restore_item', 'input' => ['item_id' => 1]])->assertForbidden();
        $this->login();
        $this->command('complete', ['stop_id' => 1, 'confirm_current_quantities' => true]);
        $this->postJson('/api/journeys/1/execution/commands', ['revision' => 3, 'operation' => 'restore_item', 'input' => ['item_id' => 1]])->assertNotFound();
    }

    public function test_driver_edits_only_effective_deliveries_and_zero_survives_completion(): void
    {
        $this->start();
        $this->login();
        $this->postJson('/api/journeys/1/execution/commands', ['revision' => 1, 'operation' => 'update_holder', 'input' => ['holder_id' => 1, 'total_holders_count' => 8]])->assertUnprocessable();
        $this->assertNull(JourneyExecutionHolder::find(1)->actual_delivered);
        $this->postJson('/api/journeys/1/execution/commands', ['revision' => 1, 'operation' => 'update_holder', 'input' => ['holder_id' => 1, 'actual_delivered' => 0]])->assertOk();
        $this->command('complete', ['stop_id' => 1, 'confirm_current_quantities' => true]);
        $holder = JourneyExecutionHolder::find(1);
        $this->assertSame(0, $holder->actual_delivered);
        $this->assertSame(6, $holder->data['total_holders_count']);
        $this->assertSame(6, Journey::find(1)->baseline->data['orders'][0]['holders'][0]['total_holders_count']);
    }

    public function test_header_preference_is_per_journey_authorized_and_does_not_change_documents_or_execution(): void
    {
        $before = app(DocumentRevision::class)->hash(Order::find(1));
        $this->login();
        $this->patchJson('/api/journeys/1/driver-preferences', ['header_open_on_entry' => false])->assertOk()->assertJsonPath('preferences.header_open_on_entry', false);
        $this->assertFalse(Journey::find(1)->header_open_on_entry);
        $this->assertTrue(Journey::find(2)->header_open_on_entry);
        $this->assertSame(0, Journey::find(1)->execution_revision);
        $this->assertSame($before, app(DocumentRevision::class)->hash(Order::find(1)));
        $this->login(UserRole::DRIVER, 99);
        $this->patchJson('/api/journeys/1/driver-preferences', ['header_open_on_entry' => true])->assertForbidden();
    }

    public function test_email_change_invalidates_verification_and_sends_only_to_new_address_after_commit(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->timestamp('email_verified_at')->nullable());
        DB::table('users')->where('id', 1)->update(['email' => 'old@example.test', 'email_verified_at' => now()]);
        Notification::fake();
        DB::transaction(function () {
            User::find(1)->update(['email' => 'new@example.test']);
            Notification::assertNothingSent();
        });
        $user = User::find(1);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class, fn ($n, $channels, $recipient) => $recipient->email === 'new@example.test');
        Notification::assertCount(1);
        $user->update(['email' => 'new@example.test', 'name' => 'Altro nome']);
        Notification::assertCount(1);
        try {
            DB::transaction(function () {
                User::find(1)->update(['email' => 'rollback@example.test']);
                throw new \RuntimeException('rollback');
            });
        } catch (\RuntimeException $e) {
        }
        Notification::assertCount(1);
        $this->assertSame('new@example.test', $user->fresh()->email);
    }

    public function test_changed_address_accepts_manual_resend_and_rejects_other_driver(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->timestamp('email_verified_at')->nullable());
        DB::table('users')->where('id', 1)->update(['email' => 'old@example.test', 'email_verified_at' => now()]);
        Notification::fake();
        User::find(1)->update(['email' => 'new@example.test']);
        $this->login(UserRole::LOGISTIC, 99);
        $this->postJson('/api/user/resend-verification/1')->assertOk();
        Notification::assertCount(2);
        Notification::assertSentTo(User::find(1), VerifyEmail::class, fn ($n, $channels, $recipient) => $recipient->email === 'new@example.test');
        $this->login(UserRole::DRIVER, 99);
        $this->postJson('/api/user/resend-verification/1')->assertForbidden();
        Notification::assertCount(2);
    }

    private function preparePlanning(): array
    {
        Schema::table('vehicles', function (Blueprint $t) {
            $t->string('type')->default('motrice');
            $t->boolean('has_trailer')->default(false);
        });
        Schema::table('cargos', function (Blueprint $t) {
            $t->boolean('is_long')->default(false);
        });
        DB::table('vehicles')->insert(['id' => 1, 'name' => 'Motrice']);
        DB::table('cargos')->insert(['id' => 1, 'name' => 'Corto']);
        Schema::table('journeys', function (Blueprint $t) {
            foreach (['vehicle_cargo_id', 'trailer_cargo_id', 'logistics_user_id'] as $key) {
                $t->integer($key)->nullable();
            }
        });
        Schema::table('journey_stops', function (Blueprint $t) {
            foreach (['customer_visit_index', 'location_lat', 'location_lng'] as $key) {
                $t->text($key)->nullable();
            }
        });
        DB::table('journeys')->where('id', 1)->update(['vehicle_id' => 1, 'vehicle_cargo_id' => 1, 'planned_start_at' => '2026-11-13 08:00:00', 'planned_end_at' => '2026-11-13 09:00:00']);
        DB::table('orders')->where('journey_id', 1)->update(['cargo_location' => 'vehicle']);
        foreach ([1, 2] as $id) {
            DB::table('orders')->where('id', $id)->update(['documents_input_hash' => app(DocumentRevision::class)->hash(Order::find($id))]);
        }

        return app(PlanningInput::class)->current(Journey::find(1));
    }

    public function test_unchanged_planning_save_preserves_stop_ids_revision_and_documents_even_with_string_ids(): void
    {
        $payload = $this->preparePlanning();
        $this->login(UserRole::LOGISTIC);
        $payload['vehicle_id'] = '1';
        $payload['notes'] = null;
        $payload['orders_truck'] = ['2', '1'];
        $before = DB::table('journey_stops')->get()->toJson();
        $this->put('/journey/1', $payload)->assertRedirect();
        $this->assertSame($before, DB::table('journey_stops')->get()->toJson());
        $this->assertSame(1, Journey::find(1)->plan_version);
        $this->assertSame('generated', Order::find(1)->documents_status->value);
        $this->assertTrue(app(OrderDocumentGenerationService::class)->isCurrent(Order::find(1)));
    }

    public function test_real_planning_change_invalidates_documents_without_resetting_business_state_or_removing_files(): void
    {
        $payload = $this->preparePlanning();
        $this->login(UserRole::LOGISTIC);
        $payload['notes'] = 'Nuova nota';
        $this->put('/journey/1', $payload)->assertRedirect();
        $this->assertSame('Nuova nota', Journey::find(1)->notes);
        $this->assertSame(2, Journey::find(1)->plan_version);
        $this->assertSame('pianificato', Order::find(1)->status->value);
        $this->assertSame('not_generated', Order::find(1)->documents_status->value);
        Storage::disk('local')->assertExists('orders/documents/order-1/order-O1-v1.xlsx');
    }

    public function test_stale_generation_does_not_permanently_block_saving_or_a_new_attempt(): void
    {
        $payload = $this->preparePlanning();
        $this->login(UserRole::LOGISTIC);
        DB::table('orders')->where('id', 1)->update(['documents_status' => 'generating', 'documents_generation_started_at' => now()->subMinutes(11), 'documents_requested_hash' => 'old']);
        $this->getJson('/api/journeys/1/documents-status')->assertOk()->assertJsonPath('orders.0.generation_active', false)->assertJsonPath('orders.0.can_generate', true);
        $payload['notes'] = 'Nuova revisione dopo timeout';
        $this->put('/journey/1', $payload)->assertRedirect();
        $this->assertSame('not_generated', Order::find(1)->documents_status->value);
        $this->assertNull(Order::find(1)->documents_requested_hash);
    }

    public function test_planning_edit_during_generation_is_rejected_and_stale_document_job_does_not_publish(): void
    {
        $payload = $this->preparePlanning();
        $this->login(UserRole::LOGISTIC);
        DB::table('orders')->where('id', 1)->update(['documents_status' => 'generating', 'documents_generation_started_at' => now(), 'documents_requested_hash' => 'old']);
        $payload['notes'] = 'Cambio durante generazione';
        $this->putJson('/journey/1', $payload)->assertConflict();
        $this->assertNull(Journey::find(1)->notes);
        $this->assertSame('', app(OrderDocumentGenerationService::class)->generate(Order::find(1), 'old'));
        $this->assertSame(1, Order::find(1)->documents_version);
    }
}
