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
        Schema::table('journey_cargos', function (Blueprint $table) {
            $table->unsignedTinyInteger('download_sequence')->after('warehouse_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journey_cargos', function (Blueprint $table) {
            $table->dropColumn('download_sequence');
        });
    }
};
