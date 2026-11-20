<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Journey;
use App\Models\JourneyExecutionItem;
use App\Models\JourneyExecutionUpdate;
use App\Models\User;
use App\Notifications\JourneyExecutionUpdated;
use App\Services\JourneyExecution\ExecutionNotificationView;
use App\Services\JourneyExecution\JourneyExecutionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Middleware;
use Tests\Support\JourneyExecutionTestCase;

class JourneyNotificationsTest extends JourneyExecutionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::table('users')->insert([
            ['id' => 2, 'name' => 'Logistica A', 'role' => 'logistic'], ['id' => 3, 'name' => 'Logistica B', 'role' => 'logistic'],
            ['id' => 4, 'name' => 'Altro autista', 'role' => 'driver'], ['id' => 5, 'name' => 'Manager', 'role' => 'manager'],
        ]);
        DB::table('journey_stop_actions')->insert(['id' => 1, 'label' => 'Sosta tecnica', 'is_active' => 1]);
        DB::table('warehouses')->insert(['id' => 1, 'name' => 'Magazzino']);
        $this->withoutMiddleware([HandleInertiaRequests::class, Middleware::class]);
    }

    private function login(int $id = 2): void
    {
        $role = $id === 1 || $id === 4 ? UserRole::DRIVER : UserRole::LOGISTIC;
        $this->actingAs(User::factory()->make(['id' => $id, 'name' => 'Operatore', 'role' => $role, 'is_admin' => false, 'email_verified_at' => now()]));
    }

    private function change(array $commands, bool $preview = false, ?string $id = null, ?int $revision = null)
    {
        return $this->postJson('/api/journeys/1/execution/interventions', [
            'intervention_id' => $id ?? (string) Str::uuid(), 'revision' => $revision ?? Journey::find(1)->execution_revision,
            'preview' => $preview, 'commands' => array_map(fn ($row) => ['id' => $row['id'] ?? (string) Str::uuid()] + $row, $commands),
        ]);
    }

    public function test_driver_completion_notifies_all_logistics_but_no_drivers_and_preserves_plan_comparison(): void
    {
        $this->start();
        $this->command('complete', ['stop_id' => 1, 'confirm_current_quantities' => true]);
        $this->assertSame([2, 3], DB::table('notifications')->orderBy('notifiable_id')->pluck('notifiable_id')->all());
        $notification = DB::table('notifications')->first();
        $this->assertSame(JourneyExecutionUpdated::TYPE, $notification->type);
        $data = json_decode($notification->data, true);
        $this->assertSame('complete', $data['event']);
        $this->assertSame([], $data['baseline_changes']);
        $this->assertCount(1, $data['orders']);
    }

    public function test_reopening_and_second_completion_are_distinct_with_stable_previous_outcome(): void
    {
        $this->start();
        $this->command('complete', ['stop_id' => 1, 'confirm_current_quantities' => true]);
        $this->command('reopen', ['stop_id' => 1]);
        $this->command('complete', ['stop_id' => 1, 'confirm_current_quantities' => true]);
        $this->assertDatabaseCount('notifications', 6);
        $update = JourneyExecutionUpdate::orderByDesc('revision')->first();
        $this->assertSame([], $update->payload['previous_outcome_changes']);
        $this->assertSame([], $update->payload['baseline_changes']);
    }

    public function test_logistic_update_notifies_only_assigned_driver_and_all_logistics(): void
    {
        $this->start();
        app(JourneyExecutionService::class)->command(Journey::find(1), 2, 1, 'update_journey', ['notes' => 'Nuove istruzioni']);
        $this->assertSame([1, 2, 3], DB::table('notifications')->orderBy('notifiable_id')->pluck('notifiable_id')->all());
        $this->command('complete', ['stop_id' => 1, 'confirm_current_quantities' => true]);
        DB::table('notifications')->delete();
        DB::table('journeys')->where('id', 1)->update(['status' => 'eseguito']);
        app(JourneyExecutionService::class)->command(Journey::find(1), 2, 3, 'warehouse_item', ['item_id' => 1, 'warehouse_id' => 1, 'warehouse_status' => 'received']);
        $this->assertSame([2, 3], DB::table('notifications')->orderBy('notifiable_id')->pluck('notifiable_id')->all());
    }

    public function test_mixed_batch_is_atomic_and_emits_one_copy_per_recipient(): void
    {
        $this->start();
        $this->login();
        $item = JourneyExecutionItem::orderBy('id')->first();
        $commands = [
            ['operation' => 'add_stop', 'input' => ['kind' => 'technical', 'technical_action_id' => 1, 'description' => 'Rifornimento']],
            ['operation' => 'cancel_stop', 'input' => ['stop_id' => 2]],
            ['operation' => 'update_item', 'input' => ['item_id' => $item->id, 'holder_quantity' => 5]],
        ];
        $this->change($commands, true)->assertOk();
        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseCount('journey_stops', 2);
        $this->assertSame(1, Journey::find(1)->execution_revision);
        $this->change($commands)->assertOk();
        $this->assertDatabaseCount('notifications', 3);
        $this->assertDatabaseHas('journey_stops', ['id' => 2, 'status' => 'cancelled']);
        $this->assertDatabaseCount('journey_stops', 3);
        $this->assertSame(5, JourneyExecutionItem::find($item->id)->data['holder_quantity']);
        $update = JourneyExecutionUpdate::first();
        $this->assertSame(['add_stop', 'cancel_stop', 'update_item'], $update->operations);
        $this->assertNotEmpty($update->payload['changes']);
    }

    public function test_preview_references_survive_different_generated_ids_at_confirmation(): void
    {
        $this->start();
        $this->login();
        $add = (string) Str::uuid();
        $commands = [
            ['id' => $add, 'operation' => 'add_stop', 'input' => ['kind' => 'customer', 'customer_id' => 1, 'site_id' => 1]],
            ['operation' => 'add_item', 'input' => ['execution_order_id' => '@'.$add.':order:0', 'cer_code_id' => 1, 'holder_id' => 1, 'holder_quantity' => 1, 'warehouse_id' => 1]],
        ];
        $this->change($commands, true)->assertOk()->assertJsonPath('references.order.3', '@'.$add.':order:0');
        $this->assertDatabaseCount('journey_order_executions', 2);
        // Another journey consumes IDs after preview (MySQL also consumes IDs on rollback).
        DB::table('journey_stops')->insert(['id' => 100, 'journey_id' => 2, 'kind' => 'customer', 'sequence' => 1, 'status' => 'planned']);
        DB::table('journey_order_executions')->insert(['id' => 100, 'journey_id' => 2, 'journey_stop_id' => 100, 'source_order_id' => null, 'status' => 'pending', 'data' => '{}']);
        $this->change($commands)->assertOk();
        $this->assertDatabaseCount('journey_order_executions', 4);
        $this->assertDatabaseCount('journey_execution_items', 3);
        $this->assertDatabaseHas('journey_execution_items', ['order_execution_id' => 101]);
    }

    public function test_idempotent_replay_conflicting_key_and_noop(): void
    {
        $this->start();
        $this->login();
        $id = (string) Str::uuid();
        $commandId = (string) Str::uuid();
        $commands = [['id' => $commandId, 'operation' => 'update_journey', 'input' => ['notes' => 'Confermata']]];
        $this->change($commands, false, $id, 1)->assertOk();
        $this->change($commands, false, $id, 1)->assertOk()->assertJsonPath('replayed', true);
        $this->assertDatabaseCount('notifications', 3);
        $this->assertSame(2, Journey::find(1)->execution_revision);
        $commands[0]['input']['notes'] = 'Differente';
        $this->change($commands, false, $id, 1)->assertStatus(409);
        $this->change([['operation' => 'update_journey', 'input' => ['notes' => 'Confermata']]])->assertOk();
        $this->assertDatabaseCount('notifications', 3);
    }

    public function test_rollback_conflict_and_driver_batch_authorization(): void
    {
        $this->start();
        $this->login();
        $this->change([['operation' => 'update_journey', 'input' => ['notes' => 'Non salvare']], ['operation' => 'update_item', 'input' => ['item_id' => 999, 'description' => 'Invalido']]])->assertNotFound();
        $this->assertDatabaseCount('notifications', 0);
        $this->assertSame(1, Journey::find(1)->execution_revision);
        $this->change([['operation' => 'update_journey', 'input' => ['notes' => 'Conflitto']]], false, null, 99)->assertStatus(409);
        $this->login(1);
        $this->change([['operation' => 'update_journey', 'input' => ['notes' => 'Non autorizzato']]])->assertForbidden();
    }

    public function test_personal_paging_dates_read_state_and_unread_counter(): void
    {
        $this->login();
        $ids = [];
        for ($index = 0; $index < 23; $index++) {
            $ids[] = $id = (string) Str::uuid();
            DB::table('notifications')->insert(['id' => $id, 'type' => 'legacy', 'notifiable_type' => User::class, 'notifiable_id' => 2,
                'data' => json_encode(['message' => 'Avviso '.$index]), 'created_at' => '2026-11-17 10:00:00', 'updated_at' => '2026-11-17 10:00:00']);
        }
        $foreign = (string) Str::uuid();
        DB::table('notifications')->insert(['id' => $foreign, 'type' => 'legacy', 'notifiable_type' => User::class, 'notifiable_id' => 3,
            'data' => '{}', 'created_at' => '2026-11-17 10:00:00', 'updated_at' => '2026-11-17 10:00:00']);
        $this->getJson('/notification?from=2026-11-17&to=2026-11-17', ['X-Inertia' => 'true'])
            ->assertOk()->assertJsonPath('props.notifications.total', 23)->assertJsonCount(20, 'props.notifications.data');
        $this->getJson('/notification?from=2026-11-17&to=2026-11-17&page=2', ['X-Inertia' => 'true'])
            ->assertOk()->assertJsonCount(3, 'props.notifications.data');
        $this->getJson('/notification?to=2026-11-16', ['X-Inertia' => 'true'])->assertOk()->assertJsonPath('props.notifications.total', 0);
        $this->getJson('/notification?from=2026-11-18&to=2026-11-17', ['X-Inertia' => 'true'])->assertUnprocessable();
        $this->getJson('/api/notifications/count')->assertOk()->assertJsonPath('unreadCount', 23);
        $this->put('/notification/'.$foreign.'/seen')->assertForbidden();
        $this->put('/notification/'.$ids[0].'/seen')->assertRedirect();
        $this->getJson('/api/notifications/count')->assertJsonPath('unreadCount', 22);
        $this->getJson('/notification?read=read', ['X-Inertia' => 'true'])->assertJsonPath('props.notifications.total', 1);
        $this->assertDatabaseCount('notifications', 24);
    }

    public function test_technical_stop_outcomes_and_partial_movements_guard(): void
    {
        $this->start();
        $this->login();
        $this->change([['operation' => 'add_stop', 'input' => ['kind' => 'technical', 'technical_action_id' => 1, 'description' => 'Pausa']]])->assertOk();
        DB::table('notifications')->delete();
        $this->command('complete', ['stop_id' => 1, 'confirm_current_quantities' => true]);
        $this->command('complete', ['stop_id' => 2, 'confirm_current_quantities' => true]);
        $this->command('skip', ['stop_id' => 3, 'reason_code' => 'traffic']);
        $data = JourneyExecutionUpdate::orderByDesc('revision')->first()->payload;
        $this->assertSame('skip', $data['event']);
        $this->assertSame([], $data['orders']);
        $this->command('reopen', ['stop_id' => 3]);
        $this->command('complete', ['stop_id' => 3]);
        $this->assertDatabaseCount('notifications', 10);
    }

    public function test_changed_quantity_survives_reopen_and_notification_snapshots_are_immutable(): void
    {
        $this->start();
        $this->command('update_item', ['item_id' => 1, 'actual_containers' => 4]);
        $this->command('complete', ['stop_id' => 1, 'confirm_current_quantities' => true]);
        $first = JourneyExecutionUpdate::orderByDesc('revision')->first();
        $quantity = collect($first->payload['baseline_changes'])->firstWhere('field', 'actual_containers');
        $this->assertSame(6, $quantity['before']);
        $this->assertSame(4, $quantity['after']);
        $this->command('reopen', ['stop_id' => 1]);
        $this->command('update_item', ['item_id' => 1, 'actual_containers' => 5]);
        $this->command('complete', ['stop_id' => 1, 'confirm_current_quantities' => true]);
        $last = JourneyExecutionUpdate::orderByDesc('revision')->first();
        $quantity = collect($last->payload['previous_outcome_changes'])->firstWhere('field', 'actual_containers');
        $this->assertSame(4, $quantity['before']);
        $this->assertSame(5, $quantity['after']);
        $this->assertSame($first->payload, $first->fresh()->payload);
    }

    public function test_partial_movement_cannot_be_cancelled_and_manager_does_not_notify_driver(): void
    {
        $this->start();
        $this->login();
        $this->command('update_item', ['item_id' => 1, 'actual_containers' => 1]);
        DB::table('notifications')->delete();
        $this->change([['operation' => 'cancel_stop', 'input' => ['stop_id' => 1]]])->assertUnprocessable();
        $this->assertDatabaseCount('notifications', 0);
        app(JourneyExecutionService::class)->command(Journey::find(1), 5, 2, 'update_journey', ['notes' => 'Dal responsabile']);
        $this->assertSame([2, 3], DB::table('notifications')->orderBy('notifiable_id')->pluck('notifiable_id')->all());
    }

    public function test_failed_database_delivery_rolls_back_the_intervention_and_domain_events(): void
    {
        $this->start();
        Notification::partialMock()->shouldReceive('sendNow')->once()->andThrow(new \RuntimeException('Delivery unavailable'));
        try {
            app(JourneyExecutionService::class)->command(Journey::find(1), 2, 1, 'update_journey', ['notes' => 'Non confermata']);
            $this->fail('Expected failed delivery');
        } catch (\RuntimeException $error) {
            $this->assertSame('Delivery unavailable', $error->getMessage());
        }
        $this->assertSame(1, Journey::find(1)->execution_revision);
        $this->assertDatabaseCount('journey_execution_updates', 0);
        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseCount('journey_events', 1);
    }

    public function test_order_links_point_to_execution_context_and_respect_journey_authorization(): void
    {
        $this->start();
        $this->login();
        $this->command('skip', ['stop_id' => 1, 'reason_code' => 'customer_closed']);
        $this->getJson('/notification', ['X-Inertia' => 'true'])->assertOk()
            ->assertJsonPath('props.notifications.data.0.orders.0.label','O1')
            ->assertJsonPath('props.notifications.data.0.orders.0.url',route('journey.execution',['journey' => 1, 'stop' => 1, 'execution_order' => 1]));
        $notification = User::find(2)->notifications()->first();
        $view = app(ExecutionNotificationView::class)->present($notification,User::find(4));
        $this->assertNull($view['journey_url']);
        $this->assertSame([],$view['orders']);
    }

    public function test_single_command_retry_uses_the_same_intervention_without_duplicate_outcomes(): void
    {
        $this->start();
        $this->login(1);
        $input = ['revision'=>1,'intervention_id'=>(string) Str::uuid(),'operation'=>'complete','input'=>['stop_id'=>1,'confirm_current_quantities'=>true]];
        $this->postJson('/api/journeys/1/execution/commands',$input)->assertOk();
        $this->postJson('/api/journeys/1/execution/commands',$input)->assertOk();
        $this->assertDatabaseCount('notifications',2);
        $this->assertSame(2,Journey::find(1)->execution_revision);
        $this->postJson('/api/journeys/1/execution/commands',['revision'=>2,'operation'=>'cancel_stop','input'=>['stop_id'=>2]])->assertForbidden();
    }

    public function test_intervention_returning_to_initial_values_does_not_notify(): void
    {
        $this->start();
        $this->login();
        $this->change([
            ['operation'=>'update_journey','input'=>['notes'=>'Bozza']],
            ['operation'=>'update_journey','input'=>['notes'=>'']],
        ])->assertOk();
        $this->assertDatabaseCount('notifications',0);
        $this->assertSame(true,JourneyExecutionUpdate::first()->payload['noop']);
    }

    public function test_driver_operational_changes_are_silent_until_outcome_with_complete_plan_delta(): void
    {
        DB::table('trailers')->insert(['id'=>1,'name'=>'Rimorchio']);
        DB::table('journeys')->where('id',1)->update(['trailer_id'=>1]);
        foreach ([1,2] as $id) {
            DB::table('orders')->where('id',$id)->update(['documents_input_hash'=>app(\App\Services\JourneyExecution\DocumentRevision::class)->hash(\App\Models\Order::find($id))]);
        }
        $this->start();
        $this->command('update_item',['item_id'=>1,'actual_containers'=>4]);
        $this->command('set_item_allocations',['item_id'=>1,'vehicle_containers'=>2,'trailer_containers'=>2]);
        foreach ([true,false,true] as $loaded) {
            $this->command('set_item_loaded',['item_id'=>1,'is_loaded'=>$loaded]);
        }
        $this->assertDatabaseCount('notifications',0);
        $this->assertDatabaseCount('journey_execution_updates',5);
        $this->assertDatabaseCount('journey_events',6);
        $this->command('complete',['stop_id'=>1,'confirm_current_quantities'=>true]);
        $this->assertDatabaseCount('notifications',2);
        $data = User::find(2)->notifications()->first()->data;
        $this->assertSame('plan',$data['summary_basis']);
        $this->assertSame($data['baseline_changes'],$data['changes']);
        $rows = collect($data['changes']);
        $this->assertSame(4,$rows->firstWhere('field','actual_containers')['after']);
        $this->assertSame(6,$rows->firstWhere('field','vehicle_containers')['before']);
        $this->assertSame(2,$rows->firstWhere('field','vehicle_containers')['after']);
        $this->assertSame(0,$rows->firstWhere('field','trailer_containers')['before']);
        $this->assertSame(2,$rows->firstWhere('field','trailer_containers')['after']);
        $this->assertFalse($rows->contains('field','is_loaded'));
        $this->assertFalse($rows->contains('field','status'));
    }

    public function test_silent_driver_command_keeps_retry_protection_and_successful_response(): void
    {
        $this->start();
        $this->login(1);
        $input=['revision'=>1,'intervention_id'=>(string) Str::uuid(),'operation'=>'set_item_loaded','input'=>['item_id'=>1,'is_loaded'=>true]];
        $this->postJson('/api/journeys/1/execution/commands',$input)->assertOk();
        $this->postJson('/api/journeys/1/execution/commands',$input)->assertOk();
        $this->assertDatabaseCount('notifications',0);
        $this->assertDatabaseCount('journey_execution_updates',1);
        $this->assertSame(2,Journey::find(1)->execution_revision);
    }

    public function test_previous_version_unsplit_snapshot_does_not_create_a_false_distribution_delta(): void
    {
        $this->start();
        $this->command('complete',['stop_id'=>1,'confirm_current_quantities'=>true]);
        $previous=JourneyExecutionUpdate::first();
        $snapshot=$previous->snapshot;
        foreach ($snapshot as $key=>&$record) {
            if (str_starts_with($key,'item:')) {
                $record['values']['vehicle_containers']=0;
                $record['values']['trailer_containers']=0;
            }
        }
        unset($record);
        $previous->update(['snapshot'=>$snapshot,'payload'=>array_replace($previous->payload,['schema_version'=>1])]);
        $this->command('reopen',['stop_id'=>1]);
        $this->command('complete',['stop_id'=>1,'confirm_current_quantities'=>true]);
        $this->assertSame([],JourneyExecutionUpdate::orderByDesc('revision')->first()->payload['previous_outcome_changes']);
        $this->assertSame($snapshot,$previous->fresh()->snapshot);
    }
    public function test_driver_finish_notifies_logistics_once_with_journey_plan_comparison_and_order_links(): void
    {
        $this->start();
        $this->login(1);
        $this->postJson('/api/journeys/1/execution/commands', [
            'revision'=>1,'operation'=>'finish','input'=>[],
        ])->assertUnprocessable();
        $this->assertDatabaseCount('notifications',0);
        $this->command('update_item',['item_id'=>1,'actual_containers'=>4]);
        $this->command('complete',['stop_id'=>1,'confirm_current_quantities'=>true]);
        $this->command('complete',['stop_id'=>2,'confirm_current_quantities'=>true]);
        $before=DB::table('notifications')->count();
        $input=['revision'=>Journey::find(1)->execution_revision,'intervention_id'=>(string)Str::uuid(),'operation'=>'finish','input'=>[]];
        $this->postJson('/api/journeys/1/execution/commands',$input)->assertOk();
        $this->postJson('/api/journeys/1/execution/commands',$input)->assertOk();
        $this->assertSame($before+2,DB::table('notifications')->count());
        $payload=JourneyExecutionUpdate::orderByDesc('revision')->first()->payload;
        $this->assertSame('finish',$payload['event']);
        $this->assertSame('Viaggio terminato · Viaggio #1',$payload['message']);
        $this->assertSame('plan',$payload['summary_basis']);
        $this->assertNull($payload['stop']);
        $this->assertCount(2,$payload['orders']);
        $this->assertSame(4,collect($payload['changes'])->firstWhere('field','actual_containers')['after']);
        $this->assertSame('eseguito',Journey::find(1)->status->value);
        $recipients=DB::table('notifications')->get()->filter(fn($n)=>json_decode($n->data,true)['event']==='finish')->pluck('notifiable_id')->sort()->values()->all();
        $this->assertSame([2,3],$recipients);
    }

}
