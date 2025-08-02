<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Drop foreign key constraints first
            $table->dropForeign(['journey_cargo_id']);
            $table->dropForeign(['warehouse_download_id']);
            $table->dropColumn('journey_cargo_id');
            $table->dropColumn('is_double_load');
            $table->dropColumn('warehouse_download_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            //
        });
    }
};
