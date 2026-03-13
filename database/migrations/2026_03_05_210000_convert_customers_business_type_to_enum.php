<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Normalize existing values to canonical enum domain before ALTER.
        DB::statement("UPDATE `customers` SET `business_type` = LOWER(TRIM(`business_type`)) WHERE `business_type` IS NOT NULL");

        DB::statement("
            UPDATE `customers`
            SET `business_type` = CASE
                WHEN `business_type` IN ('1', 'generico', 'generic') THEN 'generico'
                WHEN `business_type` IN ('2', 'industriale', 'industrial') THEN 'industriale'
                WHEN `business_type` IN ('3', 'commerciale', 'commercial') THEN 'commerciale'
                WHEN `business_type` IN ('4', 'agricola', 'agricultural') THEN 'agricola'
                WHEN `business_type` IN ('0', '', 'null', 'n/a', 'na', '-') THEN NULL
                ELSE NULL
            END
        ");

        DB::statement("
            ALTER TABLE `customers`
            MODIFY COLUMN `business_type` ENUM('generico','industriale','commerciale','agricola') NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE `customers`
            MODIFY COLUMN `business_type` VARCHAR(191) NULL
        ");
    }
};

