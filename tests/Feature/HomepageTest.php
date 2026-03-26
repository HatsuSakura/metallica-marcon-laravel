<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Enums\UserRole;

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

        $response->assertRedirect('/login');
    }

    public function test_homepage_contains_text(): void
    {
        $user = User::create([
            'name' => 'Homepage',
            'surname' => 'Tester',
            'email' => 'homepage.tester+'.uniqid().'@test.com',
            'password' => bcrypt('password!'),
            'role' => UserRole::DEVELOPER->value,
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect('/login');
    }

    public function test_user_is_saved_on_database(): void
    {
        $user = User::create([
            'name' => 'Test',
            'surname' => 'Automatico',
            'email' => 'test.automatico+'.uniqid().'@test.com',
            'password' => bcrypt('password!'),
            'role' => UserRole::DEVELOPER->value,
        ]);
 
        $this->assertNotNull($user);
        $this->assertEquals('Automatico', $user->surname);
    }
}
