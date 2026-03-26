<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('journey_load_census_items')) {
            Schema::create('journey_load_census_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('journey_id')->constrained('journeys')->cascadeOnDelete();
                $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
                $table->unsignedInteger('actual_containers');
                $table->decimal('total_weight_kg', 10, 2)->nullable();
                $table->text('notes')->nullable();
                $table->enum('source', ['phone', 'driver_ui', 'warehouse_ui'])->default('phone');
                $table->foreignId('reported_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['journey_id', 'order_item_id'], 'jlci_journey_item_uniq');
                $table->index(['journey_id', 'source'], 'jlci_journey_source_idx');
            });
        }

        if (!Schema::hasTable('journey_cargo_allocations')) {
            Schema::create('journey_cargo_allocations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('journey_id')->constrained('journeys')->cascadeOnDelete();
                $table->foreignId('journey_cargo_id')->constrained('journey_cargos')->cascadeOnDelete();
                $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
                $table->unsignedInteger('allocated_containers');
                $table->decimal('estimated_weight_kg', 10, 2)->nullable();
                $table->enum('source', ['planned', 'actual'])->default('actual');
                $table->boolean('is_exception')->default(false);
                $table->string('exception_reason')->nullable();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['journey_id', 'journey_cargo_id'], 'jca_journey_cargo_idx');
                $table->index(['order_item_id', 'source'], 'jca_item_source_idx');
                $table->unique(['journey_cargo_id', 'order_item_id', 'source'], 'jca_cargo_item_source_uniq');
            });
        }

        if (!$this->hasIndex('journey_cargo_allocations', 'jca_cargo_item_source_uniq')) {
            DB::statement('CREATE UNIQUE INDEX jca_cargo_item_source_uniq ON journey_cargo_allocations (journey_cargo_id, order_item_id, source)');
        }

        if (!Schema::hasTable('journey_cargo_unload_instructions')) {
            Schema::create('journey_cargo_unload_instructions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('journey_cargo_allocation_id')
                    ->constrained('journey_cargo_allocations', 'id', 'jcui_alloc_fk')
                    ->cascadeOnDelete();
                $table->foreignId('target_warehouse_id')
                    ->constrained('warehouses', 'id', 'jcui_target_wh_fk');
                $table->unsignedTinyInteger('unload_sequence')->nullable();
                $table->enum('instruction_type', ['simple', 'double', 'drop_only'])->default('simple');
                $table->foreignId('planned_target_warehouse_id')
                    ->nullable()
                    ->constrained('warehouses', 'id', 'jcui_pl_target_wh_fk')
                    ->nullOnDelete();
                $table->boolean('proposed_for_transshipment')->default(false);
                $table->string('transshipment_reason')->nullable();
                $table->foreignId('created_by_user_id')
                    ->nullable()
                    ->constrained('users', 'id', 'jcui_created_by_fk')
                    ->nullOnDelete();
                $table->foreignId('updated_by_user_id')
                    ->nullable()
                    ->constrained('users', 'id', 'jcui_updated_by_fk')
                    ->nullOnDelete();
                $table->timestamps();

                $table->index(['target_warehouse_id', 'proposed_for_transshipment'], 'jcui_target_transshipment_idx');
            });
        }

        // If previous attempts created the table without FK constraints, backfill them.
        if (!$this->hasForeignKey('journey_cargo_unload_instructions', 'jcui_alloc_fk')) {
            DB::statement('ALTER TABLE journey_cargo_unload_instructions ADD CONSTRAINT jcui_alloc_fk FOREIGN KEY (journey_cargo_allocation_id) REFERENCES journey_cargo_allocations(id) ON DELETE CASCADE');
        }
        if (!$this->hasForeignKey('journey_cargo_unload_instructions', 'jcui_target_wh_fk')) {
            DB::statement('ALTER TABLE journey_cargo_unload_instructions ADD CONSTRAINT jcui_target_wh_fk FOREIGN KEY (target_warehouse_id) REFERENCES warehouses(id)');
        }
        if (!$this->hasForeignKey('journey_cargo_unload_instructions', 'jcui_pl_target_wh_fk')) {
            DB::statement('ALTER TABLE journey_cargo_unload_instructions ADD CONSTRAINT jcui_pl_target_wh_fk FOREIGN KEY (planned_target_warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL');
        }
        if (!$this->hasForeignKey('journey_cargo_unload_instructions', 'jcui_created_by_fk')) {
            DB::statement('ALTER TABLE journey_cargo_unload_instructions ADD CONSTRAINT jcui_created_by_fk FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL');
        }
        if (!$this->hasForeignKey('journey_cargo_unload_instructions', 'jcui_updated_by_fk')) {
            DB::statement('ALTER TABLE journey_cargo_unload_instructions ADD CONSTRAINT jcui_updated_by_fk FOREIGN KEY (updated_by_user_id) REFERENCES users(id) ON DELETE SET NULL');
        }

        if (!Schema::hasTable('transshipment_needs')) {
            Schema::create('transshipment_needs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('journey_id')->constrained('journeys')->cascadeOnDelete();
                $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
                $table->foreignId('from_warehouse_id')->constrained('warehouses');
                $table->foreignId('to_warehouse_id')->constrained('warehouses');
                $table->unsignedInteger('quantity_containers');
                $table->decimal('estimated_weight_kg', 10, 2)->nullable();
                $table->enum('status', ['proposed', 'approved', 'planned', 'in_progress', 'completed', 'cancelled'])
                    ->default('proposed');
                $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('planned_journey_id')->nullable()->constrained('journeys')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['status', 'journey_id'], 'tsn_status_journey_idx');
                $table->index(['from_warehouse_id', 'to_warehouse_id'], 'tsn_from_to_wh_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transshipment_needs');
        Schema::dropIfExists('journey_cargo_unload_instructions');
        Schema::dropIfExists('journey_cargo_allocations');
        Schema::dropIfExists('journey_load_census_items');
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }

    private function hasForeignKey(string $table, string $constraintName): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.table_constraints')
            ->where('constraint_schema', $database)
            ->where('table_name', $table)
            ->where('constraint_name', $constraintName)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
};
