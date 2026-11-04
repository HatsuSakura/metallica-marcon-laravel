<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MoveDriverJourneysToTodayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance('env', 'local');
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Storage::fake('local');
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-31 23:30:00', 'UTC'));
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('email');
        });
        Schema::create('journeys', function (Blueprint $t) {
            $t->id();
            $t->integer('driver_id')->nullable();
            $t->string('status')->nullable();
            $t->dateTime('planned_start_at')->nullable();
            $t->dateTime('planned_end_at')->nullable();
            $t->dateTime('actual_start_at')->nullable();
            $t->softDeletes();
        });
        DB::table('users')->insert(['id' => 1, 'email' => 'driver@example.test']);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_preview_apply_timezone_duration_scope_and_idempotence(): void
    {
        $base = ['driver_id' => 1, 'status' => 'creato', 'deleted_at' => null,
            'planned_start_at' => '2026-03-31 11:37:00',
            'planned_end_at' => '2026-04-01 12:37:00',
            'actual_start_at' => '2026-03-31 11:40:00'];
        DB::table('journeys')->insert([
            ['id' => 1] + $base,
            ['id' => 2, 'status' => 'eseguito'] + $base,
            ['id' => 3, 'driver_id' => 2] + $base,
            ['id' => 4, 'planned_start_at' => '2026-11-02 10:00:00'] + $base,
            ['id' => 5, 'status' => null] + $base,
            ['id' => 6, 'status' => 'attivo', 'planned_end_at' => null] + $base,
            ['id' => 7, 'deleted_at' => '2026-04-01 00:00:00'] + $base,
        ]);
        $before = DB::table('journeys')->orderBy('id')->get()->keyBy('id');
        $options = ['--driver' => 'driver@example.test'];
        $this->artisan('dev:journeys-today', $options)->assertSuccessful();
        $this->assertEquals($before, DB::table('journeys')->orderBy('id')->get()->keyBy('id'));

        $this->artisan('dev:journeys-today', $options + ['--apply' => true])->assertSuccessful();
        $after = DB::table('journeys')->orderBy('id')->get()->keyBy('id');
        // Rome is already on November 1, preserving 13:37 local across DST.
        $this->assertSame('2026-11-01 12:37:00', $after[1]->planned_start_at);
        $this->assertSame('2026-11-02 13:37:00', $after[1]->planned_end_at);
        $this->assertSame($before[1]->actual_start_at, $after[1]->actual_start_at);
        $this->assertNull($after[6]->planned_end_at);
        foreach ([2, 3, 4, 5, 7] as $id) {
            $this->assertEquals($before[$id], $after[$id]);
        }
        $files = Storage::disk('local')->files('dev-journey-date-shifts');
        $this->assertCount(1, $files);
        $report = json_decode(Storage::disk('local')->get($files[0]), true);
        $this->assertSame('2026-03-31 11:37:00', $report['changes'][0]['before']['planned_start_at']);
        $this->artisan('dev:journeys-today', $options + ['--apply' => true])->assertSuccessful();
        $this->assertEquals($after, DB::table('journeys')->orderBy('id')->get()->keyBy('id'));
        $this->assertCount(1, Storage::disk('local')->files('dev-journey-date-shifts'));
    }

    public function test_environment_and_explicit_scope_are_required(): void
    {
        $this->artisan('dev:journeys-today')->assertFailed();
        $this->artisan('dev:journeys-today', ['--driver' => 'missing@example.test'])->assertFailed();
        $this->artisan('dev:journeys-today', ['--driver' => 'driver@example.test', '--all-drivers' => true])->assertFailed();
        $this->app->instance('env', 'production');
        $this->artisan('dev:journeys-today', ['--all-drivers' => true, '--apply' => true])->assertFailed();
    }
}
