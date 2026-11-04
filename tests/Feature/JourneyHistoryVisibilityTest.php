<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Tests\Support\JourneyExecutionTestCase;

class JourneyHistoryVisibilityTest extends JourneyExecutionTestCase
{
    public function test_history_is_reserved_for_logistics_developer_and_admin_roles(): void
    {
        $this->start();
        $this->withoutMiddleware([\App\Http\Middleware\HandleInertiaRequests::class, \Inertia\Middleware::class]);
        foreach ([
            [UserRole::DRIVER, false, false],
            [UserRole::WAREHOUSE_MANAGER, false, false],
            [UserRole::LOGISTIC, false, true],
            [UserRole::MANAGER, false, true],
            [UserRole::DEVELOPER, false, true],
            [UserRole::DRIVER, true, true],
        ] as [$role,$admin,$allowed]) {
            $this->actingAs(User::factory()->make(['id' => 1, 'role' => $role, 'is_admin' => $admin, 'email_verified_at' => now()]));
            $this->getJson('/api/journeys/1/execution/history')->assertStatus($allowed ? 200 : 403);
            $this->getJson('/journeys/1/execution', ['X-Inertia' => 'true'])->assertOk()->assertJsonPath('props.canViewExecutionHistory', $allowed);
        }
    }
}
