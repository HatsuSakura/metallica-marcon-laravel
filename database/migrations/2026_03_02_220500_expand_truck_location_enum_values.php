<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            ALTER TABLE `orders`
            MODIFY `truck_location` ENUM('vehicle','trailer','fulfill')
            NULL DEFAULT NULL
        ");

        DB::statement("
            ALTER TABLE `journey_cargos`
            MODIFY `truck_location` ENUM('vehicle','trailer','fulfill')
            NULL DEFAULT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            UPDATE `orders`
            SET `truck_location` = NULL
            WHERE `truck_location` = 'fulfill'
        ");

        DB::statement("
            UPDATE `journey_cargos`
            SET `truck_location` = NULL
            WHERE `truck_location` = 'fulfill'
        ");

        DB::statement("
            ALTER TABLE `orders`
            MODIFY `truck_location` ENUM('vehicle','trailer')
            NULL DEFAULT NULL
        ");

        DB::statement("
            ALTER TABLE `journey_cargos`
            MODIFY `truck_location` ENUM('vehicle','trailer')
            NULL DEFAULT NULL
        ");
    }
};
