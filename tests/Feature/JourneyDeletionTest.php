<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Middleware;
use Tests\Support\JourneyExecutionTestCase;

class JourneyDeletionTest extends JourneyExecutionTestCase
{
    public function test_active_journey_cannot_be_deleted_by_logistics_or_admin(): void
    {
        $this->withoutMiddleware([HandleInertiaRequests::class, Middleware::class]);
        DB::table('journeys')->where('id', 1)->update(['status' => 'attivo']);
        $orders = DB::table('orders')->orderBy('id')->get()->toJson();

        foreach ([false, true] as $admin) {
            $this->actingAs(User::factory()->make([
                'id' => 1, 'role' => UserRole::LOGISTIC, 'is_admin' => $admin,
                'email_verified_at' => now(),
            ]));
            $this->deleteJson(route('journey.destroy', ['journey' => 1]))->assertStatus(409);
            $this->assertNull(DB::table('journeys')->where('id', 1)->value('deleted_at'));
            $this->assertSame($orders, DB::table('orders')->orderBy('id')->get()->toJson());
        }
    }
}
