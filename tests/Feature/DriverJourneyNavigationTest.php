<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Journey;
use App\Models\User;
use App\Services\Driver\DriverJourneyReadModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DriverJourneyNavigationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('journeys', function (Blueprint $t) {
            $t->id(); $t->integer('driver_id'); $t->string('status'); $t->softDeletes();
        });
        DB::table('journeys')->insert(['id' => 159, 'driver_id' => 10, 'status' => 'attivo']);
        $this->withoutMiddleware([HandleInertiaRequests::class, \Inertia\Middleware::class]);
    }

    private function driver(int $id = 10): User
    {
        return User::factory()->make(['id' => $id, 'role' => UserRole::DRIVER, 'is_admin' => false, 'email_verified_at' => now()]);
    }

    public function test_old_index_redirects_to_dashboard_and_preserves_a_valid_selected_date(): void
    {
        $this->actingAs($this->driver())->get('/driver/journey')->assertRedirect(route('driver.home'));
        $this->get('/driver/journey?date=2026-10-25')->assertRedirect(route('driver.home', ['date' => '2026-10-25']));
        $this->get('/driver/journey?tab=storico')->assertRedirect(route('driver.home'));
    }

    public function test_history_and_management_share_the_workspace_with_distinct_readonly_modes(): void
    {
        $journey = Journey::findOrFail(159);
        $this->mock(DriverJourneyReadModel::class)->shouldReceive('load')->twice()->andReturn($journey);
        $this->actingAs($this->driver())->getJson('/driver/journey/159', ['X-Inertia' => 'true'])
            ->assertOk()->assertJsonPath('component', 'Driver/Journey/Workspace')->assertJsonPath('props.readOnly', true)->assertJsonPath('props.canViewExecutionHistory',false);
        $this->getJson('/driver/journey/159/edit', ['X-Inertia' => 'true'])
            ->assertOk()->assertJsonPath('component', 'Driver/Journey/Workspace')->assertJsonPath('props.readOnly', false)->assertJsonPath('props.canViewExecutionHistory',false);
    }

    public function test_other_drivers_cannot_open_the_journey(): void
    {
        $this->actingAs($this->driver(11))->get('/driver/journey/159')->assertForbidden();
        $this->get('/driver/journey/159/edit')->assertForbidden();
    }

    public function test_guest_cannot_reach_retired_index(): void
    {
        $this->get('/driver/journey')->assertRedirect(route('login'));
    }
}
