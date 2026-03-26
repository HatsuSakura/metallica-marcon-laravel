<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')
            ->whereNotNull('expected_withdraw_at')
            ->where('expected_withdraw_at', '<=', '1970-01-01 23:59:59')
            ->update(['expected_withdraw_at' => null]);

        DB::table('orders')
            ->whereNotNull('fixed_withdraw_at')
            ->where('fixed_withdraw_at', '<=', '1970-01-01 23:59:59')
            ->update(['fixed_withdraw_at' => null]);

        DB::table('orders')
            ->whereNotNull('requested_at')
            ->where('requested_at', '<=', '1970-01-01 23:59:59')
            ->update(['requested_at' => null]);
    }

    public function down(): void
    {
        // Cleanup migration: irreversible by design.
    }
};
