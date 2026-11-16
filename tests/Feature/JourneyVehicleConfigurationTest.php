<?php

namespace Tests\Feature;

use App\Services\JourneyPlanning\VehicleConfiguration;
use App\Http\Controllers\JourneyController;
use App\Models\Journey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class JourneyVehicleConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);
        DB::purge('sqlite');
        Schema::create('vehicles', function (Blueprint $t) { $t->id(); $t->string('type'); $t->boolean('has_trailer'); $t->softDeletes(); });
        Schema::create('trailers', function (Blueprint $t) { $t->id(); $t->softDeletes(); });
        Schema::create('cargos', function (Blueprint $t) { $t->id(); $t->string('name'); $t->boolean('is_long'); });
        DB::table('vehicles')->insert([
            ['id'=>1,'type'=>'motrice','has_trailer'=>true],
            ['id'=>2,'type'=>'sponda','has_trailer'=>true],
            ['id'=>3,'type'=>'furgone','has_trailer'=>true],
            ['id'=>4,'type'=>'motrice','has_trailer'=>false],
        ]);
        DB::table('trailers')->insert(['id'=>1]);
        DB::table('cargos')->insert([
            ['id'=>1,'name'=>'Lungo','is_long'=>true],['id'=>2,'name'=>'Corto','is_long'=>false],
            ['id'=>3,'name'=>'Sponda','is_long'=>false],['id'=>4,'name'=>'Furgone','is_long'=>false],
        ]);
    }

    public function test_valid_solo_and_long_short_combinations(): void
    {
        foreach ([[1,1,null,null],[4,2,null,null],[2,3,null,null],[3,4,null,null],[1,1,1,2],[1,2,1,1]] as $row) {
            app(VehicleConfiguration::class)->validate(array_combine(['vehicle_id','vehicle_cargo_id','trailer_id','trailer_cargo_id'], $row));
            $this->addToAssertionCount(1);
        }
    }

    public function test_invalid_combinations_and_orphaned_orders_are_rejected(): void
    {
        $base = ['vehicle_id'=>1,'vehicle_cargo_id'=>1];
        $cases = [
            ['vehicle_id'=>99], ['vehicle_cargo_id'=>99],
            ['trailer_id'=>1], ['trailer_id'=>99,'trailer_cargo_id'=>2],
            ['trailer_id'=>1,'trailer_cargo_id'=>1],
            ['vehicle_cargo_id'=>2,'trailer_id'=>1,'trailer_cargo_id'=>2],
            ['vehicle_id'=>2,'vehicle_cargo_id'=>3,'trailer_id'=>1,'trailer_cargo_id'=>1],
            ['vehicle_id'=>3,'vehicle_cargo_id'=>4,'trailer_id'=>1,'trailer_cargo_id'=>1],
            ['vehicle_id'=>4,'trailer_id'=>1,'trailer_cargo_id'=>2],
            ['vehicle_cargo_id'=>3], ['trailer_id'=>1,'trailer_cargo_id'=>3],
            ['trailer_cargo_id'=>2], ['orders_trailer'=>[1]], ['orders_fulfill'=>[1]],
        ];
        foreach ($cases as $case) {
            try {
                app(VehicleConfiguration::class)->validate(array_replace($base,$case));
                $this->fail('Configurazione non valida accettata: '.json_encode($case));
            } catch (ValidationException $e) { $this->assertNotEmpty($e->errors()); }
        }
    }

    public function test_store_and_update_apply_configuration_guard_before_writing(): void
    {
        Gate::shouldReceive('authorize')->twice()->andReturn(true);
        $input = ['planned_start_at'=>'2026-11-17 08:00:00','planned_end_at'=>'2026-11-17 12:00:00',
            'driver_id'=>1,'vehicle_id'=>2,'vehicle_cargo_id'=>3,'trailer_id'=>1,'trailer_cargo_id'=>1];
        foreach (['store','update'] as $method) {
            $request = Request::create('/journey', 'POST', $input);
            try {
                $controller = app(JourneyController::class);
                $method === 'store' ? $controller->store($request) : $controller->update($request, new Journey());
                $this->fail('Guardia configurazione non applicata da '.$method);
            } catch (ValidationException $e) { $this->assertArrayHasKey('trailer_id', $e->errors()); }
        }
    }
}
