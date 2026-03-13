<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sites') || !Schema::hasTable('customers')) {
            return;
        }

        // Align existing data to the domain rule:
        // active site cannot belong to a soft-deleted customer.
        DB::statement("\n            UPDATE `sites` s\n            INNER JOIN `customers` c ON c.`id` = s.`customer_id`\n            SET\n                s.`deleted_at` = COALESCE(s.`deleted_at`, c.`deleted_at`, NOW()),\n                s.`updated_at` = NOW()\n            WHERE s.`deleted_at` IS NULL\n              AND c.`deleted_at` IS NOT NULL\n        ");
    }

    public function down(): void
    {
        // Irreversible data alignment: no-op.
    }
};
