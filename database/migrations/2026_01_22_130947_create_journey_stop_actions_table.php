<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('journey_stop_actions', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();  // es: parking_detach, meal_lunch, meal_dinner, overnight
            $table->string('label');           // es: "Parcheggio rimorchio e sgancio"
            $table->boolean('requires_location')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journey_stop_actions');
    }
};
