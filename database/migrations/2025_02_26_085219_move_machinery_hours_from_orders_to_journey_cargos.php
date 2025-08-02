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
        // Remove the column from the child table
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('machinery_time');
        });

        // Add the column to the parent table
        Schema::table('journey_cargos', function (Blueprint $table) {
            $table->integer('machinery_time')->nullable(); // Adjust type and options as needed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the column from the parent table
        Schema::table('journey_cargos', function (Blueprint $table) {
            $table->dropColumn('machinery_time');
        });

        // Re-add the column to the child table
        Schema::table('orders', function (Blueprint $table) {
            $table->integer('machinery_time')->nullable(); // Use same type and options as before
        });
    }
};
