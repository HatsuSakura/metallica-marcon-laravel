<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Tests\TestCase;

class DevApiDocsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(HandleInertiaRequests::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dev/api-docs')->assertRedirect('/login');
        $this->get('/dev/openapi.json')->assertRedirect('/login');
    }

    public function test_ordinary_authenticated_user_cannot_view_docs(): void
    {
        $user = User::factory()->make([
            'role' => UserRole::DRIVER,
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->get('/dev/api-docs')->assertForbidden();
        $this->actingAs($user)->get('/dev/openapi.json')->assertForbidden();
    }

    public function test_developer_can_view_scalar_and_openapi_document(): void
    {
        $user = User::factory()->make([
            'role' => UserRole::DEVELOPER,
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/dev/api-docs')
            ->assertOk()
            ->assertSee('api-reference')
            ->assertSee('hideTestRequestButton');

        $response = $this->actingAs($user)->get('/dev/openapi.json');
        $response->assertOk()->assertJsonPath('openapi', '3.1.0');
        $this->assertNotEmpty($response->json('paths'));
        $this->assertArrayHasKey('/api/logistic/dispatch/{journey}/workspace', $response->json('paths'));
    }

    public function test_admin_can_view_docs(): void
    {
        $user = User::factory()->make([
            'role' => UserRole::MANAGER,
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->get('/dev/api-docs')->assertOk();
    }
}
