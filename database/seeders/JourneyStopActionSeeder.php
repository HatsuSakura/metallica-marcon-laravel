<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JourneyStopAction;

class JourneyStopActionSeeder extends Seeder
{
    public function run(): void
    {
        $actions = [
            ['code' => 'parking_detach', 'label' => 'Parcheggio rimorchio e sgancio', 'requires_location' => true],
            ['code' => 'meal_lunch',     'label' => 'Pranzo', 'requires_location' => false],
            ['code' => 'meal_dinner',    'label' => 'Cena', 'requires_location' => false],
            ['code' => 'overnight',      'label' => 'Pernotto', 'requires_location' => true],
        ];

        foreach ($actions as $a) {
            JourneyStopAction::updateOrCreate(
                ['code' => $a['code']],
                ['label' => $a['label'], 'requires_location' => $a['requires_location'], 'is_active' => true]
            );
        }
    }
}
