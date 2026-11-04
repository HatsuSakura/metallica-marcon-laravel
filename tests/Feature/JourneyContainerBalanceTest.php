<?php

namespace Tests\Feature;

use App\Models\Journey;
use App\Models\JourneyOrderExecution;
use App\Services\Driver\DriverJourneyReadModel;
use Illuminate\Support\Facades\DB;
use Tests\Support\JourneyExecutionTestCase;

class JourneyContainerBalanceTest extends JourneyExecutionTestCase
{
    private function balance(): array
    {
        return app(DriverJourneyReadModel::class)->load(Journey::find(1))->container_balance;
    }

    public function test_residual_uses_departure_totals_even_if_current_delivery_plan_changes(): void
    {
        $this->start();
        $this->assertSame(12, $this->balance()['groups'][0]['quantity']);
        $holder = JourneyOrderExecution::where('source_order_id', 1)->first()->holders->first();
        $this->command('update_holder', ['holder_id' => $holder->id, 'total_holders_count' => 4]);
        $this->assertSame(12, $this->balance()['groups'][0]['quantity']);
        $this->command('complete', ['stop_id' => 1, 'confirm_current_quantities' => true]);
        $group = $this->balance()['groups'][0];
        $this->assertSame(12, $group['initial_quantity']);
        $this->assertSame(4, $group['delivered_quantity']);
        $this->assertSame(8, $group['quantity']); // Six assigned to the next customer, plus the two not delivered.
        $this->command('reopen', ['stop_id' => 1]);
        $this->assertSame(8, $this->balance()['groups'][0]['quantity']);
        $this->command('complete', ['stop_id' => 1]);
        $this->assertSame(8, $this->balance()['groups'][0]['quantity']);
    }

    public function test_actual_corrections_and_skipped_stops_leave_physical_stock_coherent(): void
    {
        $this->start();
        $holder = JourneyOrderExecution::where('source_order_id', 1)->first()->holders->first();
        $this->command('update_holder', ['holder_id' => $holder->id, 'actual_delivered' => 4]);
        $this->assertSame(8, $this->balance()['groups'][0]['quantity']);
        $this->command('update_holder', ['holder_id' => $holder->id, 'actual_delivered' => 2]);
        $this->assertSame(10, $this->balance()['groups'][0]['quantity']);
        $this->command('update_holder', ['holder_id' => $holder->id, 'actual_delivered' => 0]);
        $this->command('skip', ['stop_id' => 1, 'reason_code' => 'customer_closed']);
        $this->assertSame(12, $this->balance()['groups'][0]['quantity']);
        DB::table('order_holders')->where('order_id', 1)->update(['total_holders_count' => 99]);
        $this->assertSame(12, $this->balance()['groups'][0]['quantity']);
    }

    public function test_new_types_do_not_create_stock_and_negative_balances_are_not_clamped(): void
    {
        $this->start();
        DB::table('holders')->insert(['id' => 2, 'name' => 'Fusto']);
        $this->command('add_holder', ['execution_order_id' => 1, 'holder_id' => 2, 'total_holders_count' => 3]);
        $balance = $this->balance();
        $this->assertSame(0, collect($balance['groups'])->firstWhere('holder_id', 2)['quantity']);
        $new = JourneyOrderExecution::find(1)->holders()->whereNull('source_order_holder_id')->first();
        $this->command('update_holder', ['holder_id' => $new->id, 'actual_delivered' => 1]);
        $balance = $this->balance();
        $this->assertSame(-1, collect($balance['groups'])->firstWhere('holder_id', 2)['quantity']);
        $this->assertTrue($balance['has_shortage']);
    }

    public function test_zero_and_missing_completed_actuals_remain_distinct(): void
    {
        $this->start();
        $holder = JourneyOrderExecution::find(1)->holders->first();
        $this->command('update_holder', ['holder_id' => $holder->id, 'actual_delivered' => 0]);
        $this->assertSame(12, $this->balance()['groups'][0]['quantity']);
        $this->command('complete', ['stop_id' => 1, 'confirm_current_quantities' => true]);
        DB::table('journey_execution_holders')->where('id', $holder->id)->update(['actual_delivered' => null]);
        $group = $this->balance()['groups'][0];
        $this->assertTrue($group['missing']);
        $this->assertNull($group['quantity']);
    }

    public function test_delivery_command_response_and_reload_expose_updated_residuals(): void
    {
        $this->start();
        $this->withoutMiddleware([\App\Http\Middleware\HandleInertiaRequests::class, \Inertia\Middleware::class]);
        $this->actingAs(\App\Models\User::factory()->make(['id' => 1, 'role' => \App\Enums\UserRole::DRIVER, 'is_admin' => false, 'email_verified_at' => now()]));
        $holder = JourneyOrderExecution::find(1)->holders->first();
        $this->postJson('/api/journeys/1/execution/commands', [
            'revision' => 1, 'operation' => 'update_holder', 'input' => ['holder_id' => $holder->id, 'actual_delivered' => 4],
        ])->assertOk()->assertJsonPath('journey.container_balance.groups.0.quantity', 8)
            ->assertJsonPath('journey.container_balance.groups.0.initial_quantity', 12);
        $this->getJson('/api/journeys/1/execution')->assertOk()->assertJsonPath('journey.container_balance.groups.0.quantity', 8);
    }
}
