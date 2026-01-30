<?php

use App\Enums\JourneyStopState;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('journey_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('journey_id')->constrained('journeys')->cascadeOnDelete();
            $table->foreignId('journey_stop_id')->nullable()->constrained('journey_stops')->nullOnDelete();

            $table->enum('state', array_column(JourneyStopState::cases(), 'value'))
            ->nullable()
            ->default(null);
            $table->json('payload')->nullable();

            // Chi ha fatto l'azione (driver/dispatcher/admin)
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['journey_id', 'created_at']);
            $table->index(['journey_stop_id', 'created_at']);
            $table->index(['state', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journey_events');
    }
};
