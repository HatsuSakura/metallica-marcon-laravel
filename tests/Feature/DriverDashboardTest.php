<?php

namespace Tests\Feature;

use App\Http\Controllers\DriverDashboardController;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DriverDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-31 23:30:00', 'UTC'));
        foreach (['vehicles', 'trailers'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id(); $t->string('name'); $t->string('plate');
            });
        }
        Schema::create('journeys', function (Blueprint $t) {
            $t->id(); $t->integer('driver_id'); $t->string('status')->nullable();
            $t->integer('vehicle_id')->nullable(); $t->integer('trailer_id')->nullable();
            $t->dateTime('planned_start_at')->nullable(); $t->dateTime('actual_start_at')->nullable();
            $t->softDeletes();
        });
        Schema::create('journey_stops', function (Blueprint $t) {
            $t->id(); $t->integer('journey_id'); $t->string('status');
        });
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function page(array $query = []): array
    {
        $request = Request::create('/dashboard-driver', 'GET', $query);
        $request->headers->set('X-Inertia', 'true');
        $user = new User();
        $user->id = 2;
        $request->setUserResolver(fn () => $user);
        return (new DriverDashboardController())($request)->toResponse($request)->getData(true)['props'];
    }

    public function test_italian_day_scope_order_progress_and_active_outside_day(): void
    {
        DB::table('vehicles')->insert(['id' => 1, 'name' => 'Motrice test', 'plate' => 'TEST']);
        DB::table('trailers')->insert(['id' => 1, 'name' => 'Rimorchio test', 'plate' => 'TEST2']);
        $base = ['driver_id' => 2, 'status' => 'creato', 'vehicle_id' => 1, 'trailer_id' => 1,
            'planned_start_at' => '2026-10-31 23:00:00', 'actual_start_at' => null, 'deleted_at' => null];
        DB::table('journeys')->insert([
            ['id' => 1] + $base,
            ['id' => 2, 'planned_start_at' => '2026-11-01 22:59:59', 'status' => 'chiuso'] + $base,
            ['id' => 3, 'planned_start_at' => '2026-10-31 22:59:59', 'status' => 'attivo'] + $base,
            ['id' => 4, 'planned_start_at' => '2026-11-01 23:00:00'] + $base,
            ['id' => 5, 'driver_id' => 3, 'status' => 'attivo'] + $base,
            ['id' => 6, 'deleted_at' => '2026-10-31 12:00:00'] + $base,
            ['id' => 7, 'planned_start_at' => null, 'status' => 'attivo'] + $base,
        ]);
        foreach (['done', 'skipped', 'cancelled', 'planned'] as $status) {
            DB::table('journey_stops')->insert(['journey_id' => 1, 'status' => $status]);
        }
        $props = $this->page();
        $this->assertSame('2026-11-01', $props['today']);
        $this->assertSame('2026-11-01', $props['selectedDate']);
        $this->assertSame([1, 2], array_column($props['journeys'], 'id'));
        $this->assertSame([7, 3], array_column($props['otherActiveJourneys'], 'id'));
        $first = $props['journeys'][0];
        $this->assertSame(4, $first['stops_count']);
        $this->assertSame(1, $first['completed_stops_count']);
        $this->assertSame(1, $first['skipped_stops_count']);
        $this->assertSame(1, $first['cancelled_stops_count']);
        $this->assertSame('TEST', $first['vehicle']['plate']);
        $this->assertSame('TEST2', $first['trailer']['plate']);
        $past = $this->page(['date' => '2026-10-31']);
        $this->assertSame([3], array_column($past['journeys'], 'id'));
        $this->assertSame('2026-10-30', $past['previousDate']);
        $this->assertSame('2026-11-01', $past['nextDate']);
        $this->assertSame([], $this->page(['date' => '2026-12-15'])['journeys']);
    }

    public function test_daylight_saving_day_includes_25_hours(): void
    {
        foreach (['2026-10-24 22:00:00', '2026-10-25 22:59:59', '2026-10-25 23:00:00'] as $date) {
            DB::table('journeys')->insert(['driver_id' => 2, 'status' => 'eseguito', 'planned_start_at' => $date]);
        }
        $this->assertCount(2, $this->page(['date' => '2026-10-25'])['journeys']);
    }

    public function test_invalid_date_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->page(['date' => '2026-02-30']);
    }
}
