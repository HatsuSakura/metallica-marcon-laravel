<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Listing;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Matteo',
            'email' => 'matteo@test.com',
            'is_admin' => true,

        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_admin' => true,

        ]);
        User::factory()->create([
            'name' => 'Second User',
            'email' => 'test2@example.com',
            'role' => 'driver',
        ]);

        User::factory()->create([
            'id' => 46,
            'name' => 'Mario',
            'email' => 'mario@metallicamarcon.it',
            'password' => '12345!',
            'role' => 'manager',
            'is_admin' => true,
        ]);

        User::factory()->create([
            'id' => 48,
            'name' => 'Alberto',
            'email' => 'alberto@metallicamarcon.it',
            'password' => '12345!',
            'role' => 'manager',
        ]);

        User::factory()->create([
            'id' => 47,
            'name' => 'Paolo',
            'email' => 'paoloo@metallicamarcon.it',
            'password' => '12345!',
            'role' => 'manager',
        ]);

        User::factory()->create([
            'id' => 52,
            'name' => 'Logistica',
            'email' => 'logistica@metallicamarcon.it',
            'password' => '12345!',
            'is_admin' => true,
        ]);

        Listing::factory(10)->create([
            'by_user_id' => 1 // to manage the first migrate:refresh --seed
        ]);
        Listing::factory(10)->create([
            'by_user_id' => 2 // to manage the first migrate:refresh --seed
        ]);

    }
}
