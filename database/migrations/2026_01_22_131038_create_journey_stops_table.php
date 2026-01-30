<?php

use App\Enums\JourneyStopKind;
use App\Enums\JourneyStopState;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('journey_stops', function (Blueprint $table) {
            $table->id();

            $table->foreignId('journey_id')
                ->constrained('journeys')
                ->cascadeOnDelete();

            // kind: customer | technical

            $table->enum('kind', array_column(JourneyStopKind::cases(), 'value'))
            ->nullable()
            ->default(JourneyStopKind::Customer);


            // customer stop
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();

            // multi-visita sullo stesso customer nello stesso journey
            $table->unsignedInteger('customer_visit_index')
                ->nullable()
                ->default(1);

            // technical stop
            $table->foreignId('technical_action_id')
                ->nullable()
                ->constrained('journey_stop_actions')
                ->nullOnDelete();

            // sequenze
            $table->unsignedInteger('planned_sequence')->default(0);
            $table->unsignedInteger('sequence')->default(0);

            // status: planned|in_progress|done|skipped|cancelled
            $table->enum('status', array_column(JourneyStopState::cases(), 'value'))
            ->nullable()
            ->default(JourneyStopState::Planned);

            // location (sia customer che technical)
            $table->decimal('location_lat', 10, 7)->nullable();
            $table->decimal('location_lng', 10, 7)->nullable();
            $table->string('address_text')->nullable();

            // tempi (tieni pochi campi chiari)
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // note / reason
            $table->text('notes')->nullable();
            $table->string('reason_code', 64)->nullable();
            $table->text('reason_text')->nullable();

            $table->timestamps();

            // Indici utili per dashboard e reorder
            $table->index(['journey_id', 'sequence']);
            $table->index(['journey_id', 'status']);
            $table->index(['journey_id', 'kind']);

            // Unicità "visita" (in MySQL più NULL non collide, quindi gli stop technical con customer_id NULL non creano problemi)
            $table->unique(['journey_id', 'customer_id', 'customer_visit_index'], 'journey_customer_visit_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journey_stops');
    }
};
