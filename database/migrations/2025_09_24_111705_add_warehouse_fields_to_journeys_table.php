<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journeys', function (Blueprint $table) {
            // 1° scarico
            $table->foreignId('warehouse_id_1')
                ->nullable()
                ->after('is_temporary_storage')
                ->constrained('warehouses')
                ->nullOnDelete();

            $table->timestamp('warehouse_download_dt_1')
                ->nullable()
                ->after('warehouse_id_1');

            // 2° scarico (opzionale se is_double_load = true)
            $table->foreignId('warehouse_id_2')
                ->nullable()
                ->after('warehouse_download_dt_1')
                ->constrained('warehouses')
                ->nullOnDelete();

            $table->timestamp('warehouse_download_dt_2')
                ->nullable()
                ->after('warehouse_id_2');
        });
    }

    public function down(): void
    {
        Schema::table('journeys', function (Blueprint $table) {
            // per i rollback va prima tolta la FK poi la colonna
            if (Schema::hasColumn('journeys', 'warehouse_id_1')) {
                $table->dropForeign(['warehouse_id_1']);
                $table->dropColumn('warehouse_id_1');
            }
            if (Schema::hasColumn('journeys', 'warehouse_download_dt_1')) {
                $table->dropColumn('warehouse_download_dt_1');
            }

            if (Schema::hasColumn('journeys', 'warehouse_id_2')) {
                $table->dropForeign(['warehouse_id_2']);
                $table->dropColumn('warehouse_id_2');
            }
            if (Schema::hasColumn('journeys', 'warehouse_download_dt_2')) {
                $table->dropColumn('warehouse_download_dt_2');
            }
        });
    }
};
