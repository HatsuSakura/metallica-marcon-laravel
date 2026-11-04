<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journeys', function (Blueprint $t) {
            $t->unsignedInteger('execution_revision')->default(0);
            $t->json('execution_data')->nullable();
        });
        Schema::create('journey_baselines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('journey_id')->unique()->constrained()->restrictOnDelete();
            $t->unsignedInteger('schema_version')->default(1);
            $t->json('data');
            $t->string('checksum', 64);
            $t->unsignedBigInteger('captured_by_user_id')->nullable();
            $t->timestamp('captured_at');
        });
        Schema::create('journey_order_executions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('journey_id')->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('source_order_id')->nullable()->index();
            $t->unsignedBigInteger('journey_stop_id')->index();
            $t->string('status')->default('pending');
            $t->json('data');
            $t->timestamps();
            $t->unique(['journey_id', 'source_order_id'], 'joe_source_unique');
        });
        Schema::create('journey_execution_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_execution_id')->constrained('journey_order_executions')->restrictOnDelete();
            $t->unsignedBigInteger('source_order_item_id')->nullable()->index();
            $t->json('data');
            $t->unsignedInteger('actual_containers')->nullable();
            $t->decimal('actual_weight_kg', 12, 3)->nullable();
            $t->boolean('removed')->default(false);
            $t->timestamps();
        });
        Schema::create('journey_execution_holders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_execution_id')->constrained('journey_order_executions')->restrictOnDelete();
            $t->unsignedBigInteger('source_order_holder_id')->nullable();
            $t->json('data');
            $t->unsignedInteger('actual_delivered')->nullable();
            $t->timestamps();
        });
        Schema::create('order_commitments', function (Blueprint $t) {
            $t->unsignedBigInteger('order_id')->primary();
            $t->foreignId('journey_id')->constrained()->restrictOnDelete();
            $t->string('phase')->default('planned');
        });
        // Compatibility with current planning: never invent a baseline for active legacy journeys.
        DB::table('orders as o')->join('journeys as j', 'j.id', '=', 'o.journey_id')
            ->whereNull('o.deleted_at')->whereNull('j.deleted_at')
            ->whereIn('j.status', ['creato', 'attivo'])->select('o.id as order_id', 'j.id as journey_id')
            ->orderBy('o.id')->chunk(250, fn ($rows) => DB::table('order_commitments')->insertOrIgnore(
                $rows->map(fn ($r) => ['order_id' => $r->order_id, 'journey_id' => $r->journey_id, 'phase' => 'planned'])->all()
            ));
        foreach (['orders', 'journey_order_executions'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                if ($table !== 'orders') {
                    $t->string('documents_status')->default('not_generated');
                    $t->unsignedInteger('documents_version')->default(0);
                    $t->timestamp('documents_generated_at')->nullable();
                    $t->text('documents_error')->nullable();
                }
                $t->string('documents_base_path')->nullable();
                $t->string('documents_input_hash', 64)->nullable();
                $t->string('documents_requested_hash', 64)->nullable();
                $t->timestamp('documents_generation_started_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn(['documents_base_path', 'documents_input_hash', 'documents_requested_hash', 'documents_generation_started_at']));
        Schema::dropIfExists('order_commitments');
        Schema::dropIfExists('journey_execution_holders');
        Schema::dropIfExists('journey_execution_items');
        Schema::dropIfExists('journey_order_executions');
        Schema::dropIfExists('journey_baselines');
        Schema::table('journeys', fn (Blueprint $t) => $t->dropColumn(['execution_revision', 'execution_data']));
    }
};
