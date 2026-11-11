<?php

namespace Tests\Support;

use App\Models\Journey;
use App\Models\Order;
use App\Services\JourneyExecution\DocumentRevision;
use App\Services\JourneyExecution\JourneyExecutionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

abstract class JourneyExecutionTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'auditing.enabled' => false]);
        DB::purge('sqlite');
        // Only the domain fixture and the new migration: never touch the development database.
        $definitions = [
            'users' => ['name', 'surname', 'user_code', 'email', 'role'],
            'warehouses' => ['name'], 'vehicles' => ['name', 'plate'], 'trailers' => ['name', 'plate'],
            'customers' => ['company_name', 'legal_address', 'seller_id:i'],
            'sites' => ['customer_id:i', 'name', 'address', 'notes'], 'timetables' => ['site_id:i', 'hours_json'],
            'internal_contacts' => ['site_id:i', 'name', 'surname', 'mobile', 'phone', 'email', 'role'],
            'cer_codes' => ['code', 'description', 'is_dangerous:i'], 'holders' => ['name', 'is_custom:i'],
            'order_item_groups' => ['order_id:i', 'cer_code_id:i', 'label'],
            'journeys' => ['driver_id:i', 'vehicle_id:i', 'trailer_id:i', 'status', 'notes', 'planned_start_at', 'planned_end_at', 'actual_start_at', 'actual_end_at', 'plan_version:i'],
            'orders' => ['journey_id:i', 'customer_id:i', 'site_id:i', 'logistics_user_id:i', 'legacy_code', 'status', 'notes', 'cargo_location', 'documents_status', 'documents_version:i', 'documents_generated_at', 'documents_error'],
            'order_items' => ['order_id:i', 'cer_code_id:i', 'order_item_group_id:i', 'holder_id:i', 'holder_quantity:i', 'is_bulk:i', 'description', 'weight_declared', 'warehouse_id:i'],
            'order_holders' => ['order_id:i', 'holder_id:i', 'filled_holders_count:i', 'empty_holders_count:i', 'total_holders_count:i'],
            'journey_stops' => ['journey_id:i', 'kind', 'customer_id:i', 'technical_action_id:i', 'sequence:i', 'planned_sequence:i', 'status', 'description', 'address_text', 'notes', 'started_at', 'completed_at', 'reason_code', 'driver_notes'],
            'journey_stop_orders' => ['journey_id:i', 'journey_stop_id:i', 'order_id:i'],
            'cargos' => ['name'], 'journey_cargos' => ['journey_id:i', 'cargo_id:i', 'cargo_location'],
            'journey_cargo_allocations' => ['journey_id:i', 'order_item_id:i', 'journey_cargo_id:i', 'phase'],
            'journey_cargo_order_item' => ['journey_cargo_id:i', 'order_item_id:i', 'is_double_load:i', 'download_warehouse_id:i'],
            'journey_stop_actions' => ['label', 'is_active:i'],
            'journey_events' => ['journey_id:i', 'journey_stop_id:i', 'created_by_user_id:i', 'payload'],
        ];
        foreach ($definitions as $table => $columns) {
            Schema::create($table, function (Blueprint $t) use ($columns) {
                $t->id();
                foreach ($columns as $column) {
                    [$name,$type] = array_pad(explode(':', $column), 2, 's');
                    $type === 'i' ? $t->integer($name)->nullable() : $t->text($name)->nullable();
                }
                $t->timestamps();
                $t->softDeletes();
            });
        }
        Schema::create('user_warehouse', function (Blueprint $t) {
            $t->integer('user_id');
            $t->integer('warehouse_id');
            $t->timestamps();
        });
        Schema::create('versions', function (Blueprint $t) {
            $t->increments('version_id');
            $t->integer('versionable_id');
            $t->string('versionable_type');
            $t->integer('user_id')->nullable();
            $t->text('model_data');
            $t->string('reason')->nullable();
            $t->timestamps();
        });
        DB::table('users')->insert(['id' => 1, 'name' => 'Autista', 'role' => 'driver']);
        DB::table('customers')->insert(['id' => 1, 'company_name' => 'Cliente']);
        DB::table('sites')->insert(['id' => 1, 'customer_id' => 1, 'name' => 'Sede']);
        DB::table('holders')->insert(['id' => 1, 'name' => 'Cassa']);
        DB::table('cer_codes')->insert(['id' => 1, 'code' => '170405', 'description' => 'Ferro', 'is_dangerous' => 0]);
        foreach ([1, 2] as $id) {
            DB::table('journeys')->insert(['id' => $id, 'driver_id' => 1, 'status' => 'creato', 'plan_version' => 1]);
        }
        foreach ([1, 2] as $id) {
            DB::table('orders')->insert(['id' => $id, 'journey_id' => 1, 'customer_id' => 1, 'site_id' => 1, 'legacy_code' => 'O'.$id, 'status' => 'pianificato', 'documents_status' => 'generated', 'documents_version' => 1]);
            DB::table('order_items')->insert(['id' => $id, 'order_id' => $id, 'cer_code_id' => 1, 'holder_id' => 1, 'holder_quantity' => 6, 'weight_declared' => 100, 'description' => 'Ferro', 'is_bulk' => 0]);
            DB::table('order_holders')->insert(['id' => $id, 'order_id' => $id, 'holder_id' => 1, 'total_holders_count' => 6]);
            DB::table('journey_stops')->insert(['id' => $id, 'journey_id' => 1, 'kind' => 'customer', 'customer_id' => 1, 'sequence' => $id, 'planned_sequence' => $id, 'status' => 'planned']);
            DB::table('journey_stop_orders')->insert(['journey_id' => 1, 'journey_stop_id' => $id, 'order_id' => $id]);
        }
        (require database_path('migrations/2026_10_31_200000_create_journey_execution_context.php'))->up();
        (require database_path('migrations/2026_11_02_180000_add_execution_load_allocations.php'))->up();
        Storage::fake('local');
        Bus::fake();
        foreach ([1, 2] as $id) {
            $hash = app(DocumentRevision::class)->hash(Order::findOrFail($id));
            DB::table('orders')->where('id', $id)->update(['documents_input_hash' => $hash]);
            Storage::disk('local')->put("orders/documents/order-$id/order-O$id-v1.xlsx", 'fixture');
        }
    }

    protected function start(): Journey
    {
        return app(JourneyExecutionService::class)->start(Journey::findOrFail(1), 1);
    }

    protected function command(string $operation, array $input): Journey
    {
        $j = Journey::findOrFail(1);

        return app(JourneyExecutionService::class)->command($j, 1, $j->execution_revision, $operation, $input);
    }

    protected function conflict(callable $fn, int $status = 409): void
    {
        try {
            $fn();
            $this->fail('Expected conflict');
        } catch (HttpException $e) {
            $this->assertSame($status, $e->getStatusCode());
        }
    }
}
