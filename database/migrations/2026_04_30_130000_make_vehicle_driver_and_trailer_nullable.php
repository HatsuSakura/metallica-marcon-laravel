<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE `vehicles` MODIFY `driver_id` BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE `vehicles` MODIFY `trailer_id` BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        $fallbackDriverId = DB::table('users')->where('role', 'driver')->orderBy('id')->value('id');
        $fallbackTrailerId = DB::table('trailers')->orderBy('id')->value('id');

        if ($fallbackDriverId === null || $fallbackTrailerId === null) {
            throw new RuntimeException('Cannot restore vehicles.driver_id/trailer_id to NOT NULL without fallback records.');
        }

        DB::table('vehicles')
            ->whereNull('driver_id')
            ->update(['driver_id' => $fallbackDriverId]);

        DB::table('vehicles')
            ->whereNull('trailer_id')
            ->update(['trailer_id' => $fallbackTrailerId]);

        DB::statement('ALTER TABLE `vehicles` MODIFY `driver_id` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `vehicles` MODIFY `trailer_id` BIGINT UNSIGNED NOT NULL');
    }
};
