<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropForeignIfExists('orders', 'ragnista_id');
        $this->dropForeignIfExists('order_items', 'warehouse_downaload_worker_id');

        Schema::table('orders', function (Blueprint $table) {
            $legacyColumns = [
                'has_ragno',
                'ragnista_id',
                'machinery_time',
            ];

            $toDrop = array_values(array_filter($legacyColumns, fn (string $column) => Schema::hasColumn('orders', $column)));
            if (!empty($toDrop)) {
                $table->dropColumn($toDrop);
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            $legacyColumns = [
                'is_ragnabile',
                'selection_time',
                'warehouse_downaload_worker_id',
                'warehouse_downaload_dt',
            ];

            $toDrop = array_values(array_filter($legacyColumns, fn (string $column) => Schema::hasColumn('order_items', $column)));
            if (!empty($toDrop)) {
                $table->dropColumn($toDrop);
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'has_ragno')) {
                $table->boolean('has_ragno')->nullable()->after('worker_id');
            }
            if (!Schema::hasColumn('orders', 'ragnista_id')) {
                $table->unsignedBigInteger('ragnista_id')->nullable()->after('has_ragno');
            }
            if (!Schema::hasColumn('orders', 'machinery_time')) {
                $table->integer('machinery_time')->nullable()->after('ragnista_id');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'is_ragnabile')) {
                $table->boolean('is_ragnabile')->nullable()->after('has_selection');
            }
            if (!Schema::hasColumn('order_items', 'selection_time')) {
                $table->double('selection_time')->nullable()->after('is_ragnabile');
            }
            if (!Schema::hasColumn('order_items', 'warehouse_downaload_worker_id')) {
                $table->unsignedBigInteger('warehouse_downaload_worker_id')->nullable()->after('warehouse_non_conformity');
            }
            if (!Schema::hasColumn('order_items', 'warehouse_downaload_dt')) {
                $table->timestamp('warehouse_downaload_dt')->nullable()->after('warehouse_downaload_worker_id');
            }
        });
    }

    private function dropForeignIfExists(string $table, string $column): void
    {
        if (!Schema::hasColumn($table, $column)) {
            return;
        }

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

