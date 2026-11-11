<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Journey;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Middleware;
use Tests\Support\JourneyExecutionTestCase;

class JourneyStartReorderTest extends JourneyExecutionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([HandleInertiaRequests::class, Middleware::class]);
        $this->actingAs(User::factory()->make(['id' => 1, 'role' => UserRole::DRIVER, 'is_admin' => false, 'email_verified_at' => now()]));
    }

    public function test_driver_cannot_reorder_the_plan_and_start_returns_everything_needed_to_reorder_execution(): void
    {
        $this->postJson('/api/journeys/1/execution/commands', ['revision' => 1, 'operation' => 'reorder', 'input' => ['stop_ids' => [2, 1]]])->assertConflict();
        $this->assertSame('creato', DB::table('journeys')->where('id', 1)->value('status'));
        $this->assertSame(0, DB::table('journey_baselines')->count());
        $this->assertSame([1, 2], DB::table('journey_stops')->orderBy('sequence')->pluck('id')->all());
        $this->postJson('/api/driver/journeys/1/start')->assertOk()->assertJsonPath('journey.status', 'attivo')
            ->assertJsonPath('journey.has_baseline', true)->assertJsonPath('executionLookups.cers.0.id', 1)
            ->assertJsonPath('executionLookups.sites.0.id', 1);
        $startedAt = Journey::find(1)->actual_start_at;
        $baseline = Journey::find(1)->baseline->data;
        $this->assertNotNull($startedAt);
        $this->assertSame([1, 2], DB::table('journey_stops')->orderBy('sequence')->pluck('id')->all());
        $this->postJson('/api/journeys/1/execution/commands', ['revision' => 1, 'operation' => 'reorder', 'input' => ['stop_ids' => [2, 1]]])->assertOk()
            ->assertJsonPath('journey.stops.0.id', 2)->assertJsonPath('journey.stops.0.status', 'in_progress');
        $this->assertSame($baseline, Journey::find(1)->baseline->data);
        $this->postJson('/api/driver/journeys/1/start')->assertOk();
        $this->assertSame(1, DB::table('journey_baselines')->count());
        $this->assertSame($startedAt->toDateTimeString(), Journey::find(1)->actual_start_at->toDateTimeString());
    }

    public function test_blocked_start_keeps_the_original_plan_and_does_not_enable_reorder(): void
    {
        DB::table('orders')->where('id', 1)->update(['documents_status' => 'not_generated']);
        $this->postJson('/api/driver/journeys/1/start')->assertUnprocessable();
        $this->assertSame(0, DB::table('journey_baselines')->count());
        $this->assertSame([1, 2], DB::table('journey_stops')->orderBy('sequence')->pluck('id')->all());
        $this->assertNull(Journey::find(1)->actual_start_at);
        $this->assertSame(0, Journey::find(1)->execution_revision);
    }

    public function test_another_driver_cannot_start_or_reorder_this_journey(): void
    {
        $this->actingAs(User::factory()->make(['id' => 99, 'role' => UserRole::DRIVER, 'is_admin' => false, 'email_verified_at' => now()]));
        $this->postJson('/api/driver/journeys/1/start')->assertForbidden();
        $this->postJson('/api/journeys/1/execution/commands', ['revision' => 1, 'operation' => 'reorder', 'input' => ['stop_ids' => [2, 1]]])->assertForbidden();
        $this->assertSame(0, DB::table('journey_baselines')->count());
    }
}
