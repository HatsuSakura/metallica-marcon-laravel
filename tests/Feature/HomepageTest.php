<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Enums\UserRole;

use function Laravel\Prompts\password;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HomepageTest extends TestCase
{
    // This refreshes the database after each test
    // and rolls back any changes made during the test.
    // use it only on a dedicated TEST database 
    
    // use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_homepage_is_loading(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_homepage_contains_text(): void
    {
        $response = $this->get('/');

        $response->assertSeeText('Marcon');
    }

    public function test_user_is_saved_on_database(): void
    {
        $user = User::create([
            'name' => 'Test',
            'surname' => 'Automatico',
            'email' => 'test.automatico@test.com',
            'password' => bcrypt('password!'),
            'role' => UserRole::DEVELOPER->value,
        ]);
 
        $this->assertNotNull($user);
        $this->assertEquals('Automatico', $user->surname);
    }
}
