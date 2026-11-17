<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Middleware;
use Tests\Support\JourneyExecutionTestCase;

class OrderIndexTest extends JourneyExecutionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([HandleInertiaRequests::class, Middleware::class]);
        $this->actingAs(User::factory()->make(['id' => 1, 'role' => UserRole::LOGISTIC, 'is_admin' => false, 'email_verified_at' => now()]));
        DB::table('customers')->where('id', 1)->update(['company_name' => 'S.A.I.L. Srl']);
        DB::table('customers')->insert(['id' => 2, 'company_name' => 'B.B.R. Electronics']);
        for ($id = 3; $id <= 32; $id++) {
            DB::table('orders')->insert(['id' => $id, 'customer_id' => 1, 'site_id' => 1, 'legacy_code' => 'O'.$id, 'status' => 'creato', 'created_at' => '2026-11-16 10:00:00']);
        }
        DB::table('orders')->insert(['id' => 33, 'customer_id' => 1, 'site_id' => 1, 'legacy_code' => 'NUOVO', 'status' => 'creato', 'created_at' => '2026-11-17 10:00:00']);
        DB::table('orders')->insert(['id' => 34, 'customer_id' => 2, 'site_id' => 1, 'legacy_code' => 'BBR', 'status' => 'creato', 'created_at' => '2026-11-15 10:00:00']);
    }

    public function test_new_orders_are_first_and_every_page_is_accessible(): void
    {
        $this->getJson('/order', ['X-Inertia' => 'true'])->assertOk()
            ->assertJsonPath('props.orders.total', 34)->assertJsonPath('props.orders.data.0.id', 33)->assertJsonPath('props.orders.data.1.id', 32)
            ->assertJsonCount(25, 'props.orders.data')->assertJsonPath('props.orders.last_page', 2);
        $this->getJson('/order?page=2', ['X-Inertia' => 'true'])->assertOk()->assertJsonCount(9, 'props.orders.data');
    }

    public function test_customer_filter_ignores_punctuation_and_searches_beyond_the_current_page(): void
    {
        $this->getJson('/order?customer=bre', ['X-Inertia' => 'true'])->assertOk()
            ->assertJsonPath('props.orders.total', 1)->assertJsonPath('props.orders.data.0.id', 34);
        $this->getJson('/order?customer=sail', ['X-Inertia' => 'true'])->assertOk()->assertJsonPath('props.orders.total', 33);
        $page = $this->getJson('/order?customer=sail&page=2', ['X-Inertia' => 'true'])->assertOk()->assertJsonCount(8, 'props.orders.data');
        $this->assertStringContainsString('customer=sail', $page->json('props.orders.prev_page_url'));
        $this->getJson('/order?customer=inesistente', ['X-Inertia' => 'true'])->assertOk()->assertJsonPath('props.orders.total', 0);
        $this->getJson('/order?customer=', ['X-Inertia' => 'true'])->assertOk()->assertJsonPath('props.orders.total',34);
    }
}
