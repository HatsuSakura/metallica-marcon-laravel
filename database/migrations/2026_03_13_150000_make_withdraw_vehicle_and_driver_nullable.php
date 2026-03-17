<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE `withdraws` MODIFY `vehicle_id` BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE `withdraws` MODIFY `driver_id` BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        $fallbackVehicleId = DB::table('vehicles')->min('id');
        $fallbackDriverId = DB::table('users')->min('id');

        if (!$fallbackVehicleId || !$fallbackDriverId) {
            throw new \RuntimeException('Cannot revert withdraw nullable columns: missing fallback vehicle/user.');
        }

        DB::table('withdraws')
            ->whereNull('vehicle_id')
            ->update(['vehicle_id' => $fallbackVehicleId]);

        DB::table('withdraws')
            ->whereNull('driver_id')
            ->update(['driver_id' => $fallbackDriverId]);

        DB::statement('ALTER TABLE `withdraws` MODIFY `vehicle_id` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `withdraws` MODIFY `driver_id` BIGINT UNSIGNED NOT NULL');
    }
};
