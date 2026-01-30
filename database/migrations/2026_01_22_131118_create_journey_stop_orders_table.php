<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('journey_stop_orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('journey_id')->constrained('journeys')->cascadeOnDelete();
            $table->foreignId('journey_stop_id')->constrained('journey_stops')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

            $table->timestamps();


            // Un ordine non può stare in due stop diversi dello stesso journey
            $table->unique(['journey_id', 'order_id'], 'uniq_journey_order_once');

            // Evita duplicazione dentro lo stesso stop
            $table->unique(['journey_stop_id', 'order_id'], 'uniq_stop_order_once');

            $table->index(['journey_stop_id']);
            $table->index(['order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journey_stop_orders');
    }
};
