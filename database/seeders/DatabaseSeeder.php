<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        if (app()->environment('production')) {
            $this->runProduction();
        } else {
            $this->runDevelopment();
        }
    }

    private function runProduction(): void
    {
        // id=1
        User::create([
            'name' => 'Matteo',
            'surname' => 'Argenton',
            'email' => 'm.argenton@creactiveagency.com',
            'password' => env('SEED_ADMIN_PASSWORD'),
            'is_admin' => true,
            'role' => 'developer',
            'email_verified_at' => now(),
        ]);

        // id=2 — legacy id_seller=46, remapped by ETL
        User::create([
            'name' => 'Mario',
            'surname' => 'Marcon',
            'email' => 'mario@metallicamarcon.it',
            'password' => '12345678!',
            'is_admin' => true,
            'role' => 'manager',
            'email_verified_at' => now(),
        ]);

        // id=3 — legacy id_seller=47, remapped by ETL
        User::create([
            'name' => 'Paolo',
            'surname' => 'Marcon',
            'email' => 'paolo@metallicamarcon.it',
            'password' => '12345678!',
            'is_admin' => false,
            'role' => 'manager',
            'email_verified_at' => now(),
        ]);

        // id=4 — legacy id_seller=48, remapped by ETL
        User::create([
            'name' => 'Alberto',
            'surname' => 'Marcon',
            'email' => 'alberto@metallicamarcon.it',
            'password' => '12345678!',
            'is_admin' => false,
            'role' => 'manager',
            'email_verified_at' => now(),
        ]);
    }

    private function runDevelopment(): void
    {
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
    }
}
