<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Journey;
use App\Models\JourneyExecutionHolder;
use App\Models\Order;
use App\Models\User;
use App\Services\Driver\DriverJourneyReadModel;
use App\Services\JourneyExecution\DocumentRevision;
use Illuminate\Support\Facades\DB;
use Tests\Support\JourneyExecutionTestCase;

class JourneyDeliveryCapacityTest extends JourneyExecutionTestCase
{
    private function threeCustomers(): void
    {
        DB::table('orders')->insert(['id' => 3, 'journey_id' => 1, 'customer_id' => 1, 'site_id' => 1, 'legacy_code' => 'O3', 'status' => 'pianificato', 'documents_status' => 'generated', 'documents_version' => 1]);
        DB::table('order_holders')->insert(['id' => 3, 'order_id' => 3, 'holder_id' => 1, 'total_holders_count' => 6]);
        DB::table('journey_stops')->insert(['id' => 3, 'journey_id' => 1, 'kind' => 'customer', 'customer_id' => 1, 'sequence' => 3, 'planned_sequence' => 3, 'status' => 'planned']);
        DB::table('journey_stop_orders')->insert(['journey_id' => 1, 'journey_stop_id' => 3, 'order_id' => 3]);
        foreach ([1,2,3] as $id) {
            DB::table('orders')->where('id', $id)->update(['documents_input_hash' => app(DocumentRevision::class)->hash(Order::find($id))]);
            \Illuminate\Support\Facades\Storage::disk('local')->put("orders/documents/order-$id/order-O$id-v1.xlsx", 'fixture');
        }
        $this->start();
    }

    private function remaining(): int
    {
        return app(DriverJourneyReadModel::class)->load(Journey::find(1))->container_balance['groups'][0]['quantity'];
    }

    public function test_three_customers_can_redistribute_stock_but_cannot_confirm_more_than_available(): void
    {
        $this->threeCustomers();
        $this->command('update_holder', ['holder_id' => 1, 'actual_delivered' => 7]);
        $this->command('complete', ['stop_id' => 1, 'confirm_current_quantities' => true]);
        $this->assertSame(11, $this->remaining());
        $this->command('complete', ['stop_id' => 2, 'confirm_current_quantities' => true]);
        $this->assertSame(5, $this->remaining());
        $revision = Journey::find(1)->execution_revision;
        $events = DB::table('journey_events')->count();
        $this->conflict(fn () => $this->command('complete', ['stop_id' => 3, 'confirm_current_quantities' => true]), 422);
        $this->assertNull(JourneyExecutionHolder::find(3)->actual_delivered);
        $this->assertDatabaseHas('journey_stops', ['id' => 3, 'status' => 'in_progress']);
        $this->assertDatabaseHas('orders', ['id' => 3, 'status' => 'pianificato']);
        $this->assertSame($revision, Journey::find(1)->execution_revision);
        $this->assertSame($events, DB::table('journey_events')->count());
        $this->command('update_holder', ['holder_id' => 3, 'actual_delivered' => 5]);
        $this->command('complete', ['stop_id' => 3, 'confirm_current_quantities' => true]);
        $this->assertSame(0, $this->remaining());
        $this->assertSame(18, JourneyExecutionHolder::sum('actual_delivered'));
        $this->assertSame(6, Journey::find(1)->baseline->data['orders'][0]['holders'][0]['total_holders_count']);
    }

    public function test_undelivered_stock_can_return_home_and_saved_delivery_can_be_corrected(): void
    {
        $this->threeCustomers();
        $this->command('update_holder', ['holder_id' => 1, 'actual_delivered' => 7]);
        $this->command('update_holder', ['holder_id' => 1, 'actual_delivered' => 8]);
        $this->command('complete', ['stop_id' => 1, 'confirm_current_quantities' => true]);
        $this->command('update_holder', ['holder_id' => 2, 'actual_delivered' => 4]);
        $this->command('complete', ['stop_id' => 2, 'confirm_current_quantities' => true]);
        $this->command('update_holder', ['holder_id' => 3, 'actual_delivered' => 0]);
        $this->command('complete', ['stop_id' => 3, 'confirm_current_quantities' => true]);
        $this->command('finish', []);
        $this->assertSame(6, $this->remaining());
    }

    public function test_direct_api_and_new_delivery_cannot_exceed_stock_or_borrow_other_container_types(): void
    {
        $this->threeCustomers();
        $this->withoutMiddleware([\App\Http\Middleware\HandleInertiaRequests::class, \Inertia\Middleware::class]);
        $this->actingAs(User::factory()->make(['id'=>1,'role'=>UserRole::DRIVER,'is_admin'=>false,'email_verified_at'=>now()]));
        $this->postJson('/api/journeys/1/execution/commands', ['revision'=>1,'operation'=>'update_holder','input'=>['holder_id'=>1,'actual_delivered'=>19]])->assertUnprocessable()->assertSee('vuoti disponibili');
        $this->assertNull(JourneyExecutionHolder::find(1)->actual_delivered);
        DB::table('holders')->insert(['id'=>2,'name'=>'Fusto']);
        $this->postJson('/api/journeys/1/execution/commands', ['revision'=>1,'operation'=>'add_holder','input'=>['execution_order_id'=>1,'holder_id'=>2,'total_holders_count'=>1,'actual_delivered'=>1]])->assertUnprocessable();
        $this->assertDatabaseCount('journey_execution_holders',3);
        $this->command('update_holder', ['holder_id'=>1,'actual_delivered'=>12]);
        $revision=Journey::find(1)->execution_revision;
        $this->postJson('/api/journeys/1/execution/commands', ['revision'=>1,'operation'=>'update_holder','input'=>['holder_id'=>2,'actual_delivered'=>7]])->assertConflict();
        $this->postJson('/api/journeys/1/execution/commands', ['revision'=>$revision,'operation'=>'update_holder','input'=>['holder_id'=>2,'actual_delivered'=>7]])->assertUnprocessable();
        $this->assertSame(6,$this->remaining());
    }
}
