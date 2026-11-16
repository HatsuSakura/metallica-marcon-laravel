<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;
use Tests\Support\JourneyExecutionTestCase;

class JourneySaveActionsTest extends JourneyExecutionTestCase
{
    public function test_save_stays_and_save_exit_returns_to_index_for_changes_and_noop(): void
    {
        $this->withoutMiddleware([HandleInertiaRequests::class, Middleware::class]);
        $this->actingAs(User::factory()->make(['id'=>1,'role'=>UserRole::LOGISTIC,'is_admin'=>false,'email_verified_at'=>now()]));
        Schema::table('journeys', function (Blueprint $t) { $t->integer('vehicle_cargo_id')->nullable(); $t->integer('trailer_cargo_id')->nullable(); $t->integer('logistics_user_id')->nullable(); });
        Schema::table('vehicles', function (Blueprint $t) { $t->string('type')->nullable(); $t->boolean('has_trailer')->default(false); });
        Schema::table('cargos', function (Blueprint $t) { $t->boolean('is_long')->default(false); });
        DB::table('vehicles')->insert(['id'=>1,'name'=>'Motrice','type'=>'motrice']);
        DB::table('cargos')->insert(['id'=>1,'name'=>'Corto','is_long'=>false]);
        DB::table('orders')->update(['journey_id'=>null]);
        DB::table('journey_stop_orders')->delete();
        DB::table('journey_stops')->delete();
        $input = ['vehicle_id'=>1,'vehicle_cargo_id'=>1,'driver_id'=>1,
            'planned_start_at'=>'2026-11-17 08:00:00','planned_end_at'=>'2026-11-17 12:00:00',
            'orders_truck'=>[],'orders_trailer'=>[],'orders_fulfill'=>[],'stops'=>[]];
        foreach (['save_stay'=>'journey.edit','save_exit'=>'journey.index'] as $action=>$routeName) {
            $payload = $input + ['notes'=>$action,'post_action'=>$action];
            $url = route($routeName,$action === 'save_stay' ? ['journey'=>1] : []);
            $this->put(route('journey.update',['journey'=>1]),$payload)->assertRedirect($url);
            $this->assertSame($action,DB::table('journeys')->where('id',1)->value('notes'));
            $version = DB::table('journeys')->where('id',1)->value('plan_version');
            $this->put(route('journey.update',['journey'=>1]),$payload)->assertRedirect($url);
            $this->assertSame($version,DB::table('journeys')->where('id',1)->value('plan_version'));
        }
    }
}
