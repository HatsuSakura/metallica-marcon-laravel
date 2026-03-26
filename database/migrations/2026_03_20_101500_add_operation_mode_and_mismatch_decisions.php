<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('journey_cargos') && !Schema::hasColumn('journey_cargos', 'operation_mode')) {
            Schema::table('journey_cargos', function (Blueprint $table) {
                $table->enum('operation_mode', ['unload', 'drop_only'])
                    ->default('unload')
                    ->after('is_grounded');
            });
        }

        if (!Schema::hasTable('journey_cargo_mismatch_decisions')) {
            Schema::create('journey_cargo_mismatch_decisions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('journey_id')->constrained('journeys')->cascadeOnDelete();
                $table->foreignId('journey_cargo_id')->constrained('journey_cargos')->cascadeOnDelete();
                $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
                $table->enum('decision', ['double_unload', 'grounding']);
                $table->foreignId('secondary_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['journey_cargo_id', 'order_item_id'], 'jcmd_cargo_item_unique');
                $table->index(['journey_id', 'decision'], 'jcmd_journey_decision_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('journey_cargo_mismatch_decisions');

        if (Schema::hasTable('journey_cargos') && Schema::hasColumn('journey_cargos', 'operation_mode')) {
            Schema::table('journey_cargos', function (Blueprint $table) {
                $table->dropColumn('operation_mode');
            });
        }
    }
};

