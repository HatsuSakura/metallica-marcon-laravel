<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\CerCode;
use App\Models\Order;
use App\Models\User;
use App\Rules\AvailableCerCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Middleware;
use Tests\Support\JourneyExecutionTestCase;

class CerCodeRegistryTest extends JourneyExecutionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([HandleInertiaRequests::class, Middleware::class]);
    }

    private function login(UserRole $role = UserRole::LOGISTIC, bool $admin = false): void
    {
        $this->actingAs(User::factory()->make(['id' => 1, 'role' => $role, 'is_admin' => $admin, 'email_verified_at' => now()]));
    }

    private function payload(array $changes = []): array
    {
        return array_replace(['code' => '15 01 10*', 'description' => 'Imballaggi contaminati', 'is_dangerous' => true, 'is_active' => true], $changes);
    }

    public function test_every_route_is_restricted_to_the_four_authorized_profiles(): void
    {
        foreach (UserRole::cases() as $role) {
            $this->login($role);
            $allowed = in_array($role, [UserRole::LOGISTIC, UserRole::MANAGER, UserRole::DEVELOPER], true);
            foreach (['/cer-code', '/cer-code/create', '/cer-code/1/edit'] as $path) {
                $this->get($path)->assertStatus($allowed ? 200 : 403);
            }
            if (!$allowed) {
                $this->post('/cer-code', $this->payload())->assertForbidden();
                $this->put('/cer-code/1', $this->payload())->assertForbidden();
            }
        }
        $this->login(UserRole::CUSTOMER, true);
        $this->get('/cer-code')->assertOk();
        $this->post('/cer-code', $this->payload())->assertRedirect('/cer-code');
        $this->put('/cer-code/1', $this->payload(['code' => '170405', 'description' => 'Ferro aggiornato']))->assertRedirect('/cer-code');
    }

    public function test_create_normalizes_code_and_rejects_duplicate_import_formats_and_invalid_input(): void
    {
        $this->login();
        $this->post('/cer-code', $this->payload())->assertRedirect('/cer-code');
        $this->assertDatabaseHas('cer_codes', ['code' => '150110', 'is_dangerous' => true, 'is_active' => true]);
        $this->post('/cer-code', $this->payload(['code' => '150110']))->assertSessionHasErrors('code');
        $this->post('/cer-code', $this->payload(['code' => '12x', 'description' => ' ']))->assertSessionHasErrors(['code', 'description']);
        DB::table('cer_codes')->insert(['code' => '16 01 07*', 'description' => 'Importato', 'is_dangerous' => true, 'is_active' => false]);
        $this->post('/cer-code', $this->payload(['code' => '160107']))->assertSessionHasErrors('code');
    }

    public function test_search_pagination_and_inactive_filter(): void
    {
        $this->login();
        for ($i = 0; $i < 28; $i++) {
            DB::table('cer_codes')->insert(['code' => (string)(200000+$i), 'description' => 'Materiale prova', 'is_dangerous' => false]);
        }
        $this->getJson('/cer-code', ['X-Inertia' => 'true'])->assertOk()->assertJsonPath('props.codes.total', 29)->assertJsonCount(25, 'props.codes.data');
        $this->getJson('/cer-code?page=2', ['X-Inertia' => 'true'])->assertOk()->assertJsonCount(4, 'props.codes.data');
        $this->getJson('/cer-code?search=17%2004%2005', ['X-Inertia' => 'true'])->assertOk()->assertJsonPath('props.codes.total', 1);
        $this->getJson('/cer-code?search=Ferro', ['X-Inertia' => 'true'])->assertOk()->assertJsonPath('props.codes.total', 1);
        CerCode::findOrFail(1)->update(['is_active' => false]);
        $this->getJson('/cer-code?status=inactive', ['X-Inertia' => 'true'])->assertOk()->assertJsonPath('props.codes.total', 1);
    }

    public function test_deactivation_preserves_history_and_blocks_new_assignments_but_allows_existing_row(): void
    {
        $this->login();
        $this->put('/cer-code/1', $this->payload(['code' => '170405', 'is_active' => false]))->assertRedirect('/cer-code');
        $this->assertNotNull(Order::findOrFail(1)->items()->first()->cerCode);
        $new = Validator::make(['items' => [['cer_code_id' => 1]]], ['items.*.cer_code_id' => [new AvailableCerCode]]);
        $this->assertTrue($new->fails());
        $existing = Validator::make(['items' => [['id' => 1, 'cer_code_id' => 1]]], ['items.*.cer_code_id' => [new AvailableCerCode(Order::findOrFail(1))]]);
        $this->assertFalse($existing->fails());
        $foreign = Validator::make(['items' => [['id' => 2, 'cer_code_id' => 1]]], ['items.*.cer_code_id' => [new AvailableCerCode(Order::findOrFail(1))]]);
        $this->assertTrue($foreign->fails());
        $this->put('/cer-code/1', $this->payload(['code' => '170405', 'is_active' => true]))->assertRedirect('/cer-code');
        $this->assertFalse(Validator::make(['cer_code_id' => 1], ['cer_code_id' => [new AvailableCerCode]])->fails());
    }
    public function test_inactive_cer_is_retained_but_not_addable_in_journey_execution(): void
    {
        $this->login();
        $this->start();
        CerCode::findOrFail(1)->update(['is_active' => false]);
        $this->postJson('/api/journeys/1/execution/commands', [
            'revision' => 1, 'operation' => 'add_item',
            'input' => ['execution_order_id' => 1, 'cer_code_id' => 1],
        ])->assertUnprocessable()->assertJsonValidationErrors('cer_code_id');
        $this->postJson('/api/journeys/1/execution/commands', [
            'revision' => 1, 'operation' => 'update_item',
            'input' => ['item_id' => 1, 'cer_code_id' => '1', 'description' => 'Descrizione aggiornata'],
        ])->assertOk();
        $this->assertDatabaseCount('journey_execution_items', 2);
    }

}
