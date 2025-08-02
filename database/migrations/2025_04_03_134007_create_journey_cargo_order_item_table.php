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
        Schema::create('journey_cargo_order_item', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journey_cargo_id');
            $table->unsignedBigInteger('order_item_id');
            $table->boolean('is_double_load')->default(false);
            $table->unsignedBigInteger('warehouse_download_id')->nullable();
            $table->timestamps();

            $table->foreign('journey_cargo_id')
                  ->references('id')
                  ->on('journey_cargos')
                  ->onDelete('cascade');

            $table->foreign('order_item_id')
                  ->references('id')
                  ->on('order_items')
                  ->onDelete('cascade');

            $table->foreign('warehouse_download_id')
                  ->references('id')
                  ->on('warehouses')
                  ->onDelete('set null');

            // Ensure each OrderItem is linked only once per JourneyCargo.
            $table->unique(['journey_cargo_id', 'order_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journey_cargo_order_item');
    }
};
