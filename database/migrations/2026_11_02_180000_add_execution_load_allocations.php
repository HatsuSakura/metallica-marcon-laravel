<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journey_execution_items', fn (Blueprint $table) => $table->boolean('is_loaded')->default(false));
        Schema::create('journey_execution_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('execution_item_id')->constrained('journey_execution_items')->restrictOnDelete();
            $table->string('cargo_location', 16);
            $table->unsignedInteger('containers');
            $table->timestamps();
            $table->unique(['execution_item_id', 'cargo_location'], 'jea_item_location_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journey_execution_allocations');
        Schema::table('journey_execution_items', fn (Blueprint $table) => $table->dropColumn('is_loaded'));
    }
};
