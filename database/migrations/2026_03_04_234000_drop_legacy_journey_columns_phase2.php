<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropForeignIfExists('journeys', 'warehouse_id_1');
        $this->dropForeignIfExists('journeys', 'warehouse_id_2');

        Schema::table('journeys', function (Blueprint $table) {
            $legacyColumns = [
                'dt_start',
                'dt_end',
                'real_dt_start',
                'real_dt_end',
                'warehouse_id_1',
                'warehouse_download_dt_1',
                'warehouse_id_2',
                'warehouse_download_dt_2',
            ];

            $toDrop = array_values(array_filter($legacyColumns, fn (string $column) => Schema::hasColumn('journeys', $column)));
            if (!empty($toDrop)) {
                $table->dropColumn($toDrop);
            }
        });
    }

    public function down(): void
    {
        Schema::table('journeys', function (Blueprint $table) {
            if (!Schema::hasColumn('journeys', 'dt_start')) {
                $table->timestamp('dt_start')->nullable()->after('status');
            }
            if (!Schema::hasColumn('journeys', 'dt_end')) {
                $table->timestamp('dt_end')->nullable()->after('dt_start');
            }
            if (!Schema::hasColumn('journeys', 'real_dt_start')) {
                $table->timestamp('real_dt_start')->nullable()->after('actual_start_at');
            }
            if (!Schema::hasColumn('journeys', 'real_dt_end')) {
                $table->timestamp('real_dt_end')->nullable()->after('actual_end_at');
            }
            if (!Schema::hasColumn('journeys', 'warehouse_id_1')) {
                $table->unsignedBigInteger('warehouse_id_1')->nullable()->after('primary_warehouse_id');
            }
            if (!Schema::hasColumn('journeys', 'warehouse_download_dt_1')) {
                $table->timestamp('warehouse_download_dt_1')->nullable()->after('warehouse_id_1');
            }
            if (!Schema::hasColumn('journeys', 'warehouse_id_2')) {
                $table->unsignedBigInteger('warehouse_id_2')->nullable()->after('secondary_warehouse_id');
            }
            if (!Schema::hasColumn('journeys', 'warehouse_download_dt_2')) {
                $table->timestamp('warehouse_download_dt_2')->nullable()->after('warehouse_id_2');
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
