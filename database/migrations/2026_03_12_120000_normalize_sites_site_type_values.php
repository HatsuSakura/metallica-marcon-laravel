<?php

use App\Enums\SiteTipologia;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sites') || !Schema::hasColumn('sites', 'site_type')) {
            return;
        }

        // Normalize to lower-case for deterministic matching.
        DB::statement("UPDATE `sites` SET `site_type` = LOWER(TRIM(`site_type`)) WHERE `site_type` IS NOT NULL");

        DB::statement("\n            UPDATE `sites`\n            SET `site_type` = CASE\n                WHEN `site_type` IN ('1', 'fully_operative', 'fully operative', 'operativa', 'operativo', 'principale') THEN '" . SiteTipologia::FULLY_OPERATIVE->value . "'\n                WHEN `site_type` IN ('2', 'only_legal', 'only legal', 'legal', 'solo_legal') THEN '" . SiteTipologia::ONLY_LEGAL->value . "'\n                WHEN `site_type` IN ('3', 'only_stock', 'only stock', 'stock', 'solo_stock', 'magazzino') THEN '" . SiteTipologia::ONLY_STOCK->value . "'\n                WHEN `site_type` IN ('', '0', 'null', 'n/a', 'na', '-') THEN NULL\n                ELSE NULL\n            END\n            WHERE `site_type` IS NOT NULL\n        ");
    }

    public function down(): void
    {
        // Irreversible data normalization: keep canonical values as-is.
    }
};
