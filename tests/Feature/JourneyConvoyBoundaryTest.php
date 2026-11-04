<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Journey;
use App\Models\Order;
use App\Models\User;
use App\Services\JourneyExecution\DocumentRevision;
use Illuminate\Support\Facades\DB;
use Tests\Support\JourneyExecutionTestCase;

class JourneyConvoyBoundaryTest extends JourneyExecutionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach ([1, 2] as $id) {
            DB::table('vehicles')->insert(['id' => $id, 'name' => 'Motrice '.$id]);
            DB::table('trailers')->insert(['id' => $id, 'name' => 'Rimorchio '.$id]);
        }
        DB::table('journeys')->where('id', 1)->update(['vehicle_id' => 1, 'trailer_id' => 1]);
        foreach ([1, 2] as $id) {
            DB::table('orders')->where('id', $id)->update(['documents_input_hash' => app(DocumentRevision::class)->hash(Order::find($id))]);
        }
    }

    public function test_logistics_can_change_planned_convoy_before_departure(): void
    {
        Journey::find(1)->update(['vehicle_id' => 2, 'trailer_id' => 2]);
        $this->assertDatabaseHas('journeys', ['id' => 1, 'vehicle_id' => 2, 'trailer_id' => 2]);
    }

    public function test_started_convoy_cannot_be_changed_through_execution_api_even_with_null_trailer(): void
    {
        $this->start();
        $this->withoutMiddleware([\App\Http\Middleware\HandleInertiaRequests::class, \Inertia\Middleware::class]);
        foreach ([false, true] as $admin) {
            $this->actingAs(User::factory()->make(['id' => 1, 'role' => $admin ? UserRole::LOGISTIC : UserRole::DRIVER, 'is_admin' => $admin, 'email_verified_at' => now()]));
            foreach ([['vehicle_id' => 2], ['trailer_id' => 2], ['trailer_id' => null], ['vehicle' => ['id' => 2]], ['trailer' => null]] as $input) {
                $this->postJson('/api/journeys/1/execution/commands', ['revision' => 1, 'operation' => 'update_journey', 'input' => $input])
                    ->assertStatus(422)->assertSee('convoglio');
            }
        }
        $this->assertDatabaseHas('journeys', ['id' => 1, 'vehicle_id' => 1, 'trailer_id' => 1, 'execution_revision' => 1]);
        $this->assertNull(Journey::find(1)->execution_data);
        $this->assertDatabaseCount('journey_events', 1);
    }

    public function test_domain_command_also_protects_convoy_and_still_allows_notes(): void
    {
        $this->start();
        $this->conflict(fn () => $this->command('update_journey', ['vehicle_id' => 2]), 422);
        $this->conflict(fn () => $this->command('update_journey', ['trailer_id' => null]), 422);
        $this->command('update_journey', ['notes' => 'Deviazione per traffico']);
        $this->assertSame(['notes' => 'Deviazione per traffico'], Journey::find(1)->execution_data);
        $this->assertDatabaseHas('journeys', ['id' => 1, 'vehicle_id' => 1, 'trailer_id' => 1]);
    }
}
