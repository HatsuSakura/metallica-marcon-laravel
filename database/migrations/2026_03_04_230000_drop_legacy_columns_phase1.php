<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropForeignIfExists('orders', 'logistic_id');
        $this->dropForeignIfExists('journeys', 'logistic_id');

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'logistic_id')) {
                $table->dropColumn('logistic_id');
            }
            if (Schema::hasColumn('orders', 'expected_withdraw_dt')) {
                $table->dropColumn('expected_withdraw_dt');
            }
            if (Schema::hasColumn('orders', 'real_withdraw_dt')) {
                $table->dropColumn('real_withdraw_dt');
            }
        });

        Schema::table('journeys', function (Blueprint $table) {
            if (Schema::hasColumn('journeys', 'logistic_id')) {
                $table->dropColumn('logistic_id');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'adr_onu_code')) {
                $table->dropColumn('adr_onu_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'logistic_id')) {
                $table->unsignedBigInteger('logistic_id')->nullable()->after('site_id');
            }
            if (!Schema::hasColumn('orders', 'expected_withdraw_dt')) {
                $table->timestamp('expected_withdraw_dt')->nullable()->after('requested_at');
            }
            if (!Schema::hasColumn('orders', 'real_withdraw_dt')) {
                $table->timestamp('real_withdraw_dt')->nullable()->after('expected_withdraw_at');
            }
        });

        Schema::table('journeys', function (Blueprint $table) {
            if (!Schema::hasColumn('journeys', 'logistic_id')) {
                $table->unsignedBigInteger('logistic_id')->nullable()->after('driver_id');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'adr_onu_code')) {
                $table->string('adr_onu_code')->nullable()->after('adr');
            }
        });
    }

    private function dropForeignIfExists(string $table, string $column): void
    {
        $database = DB::getDatabaseName();

        $constraint = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');

        if ($constraint) {
            DB::statement(sprintf(
                'ALTER TABLE `%s` DROP FOREIGN KEY `%s`',
                $table,
                $constraint
            ));
        }
    }
};
