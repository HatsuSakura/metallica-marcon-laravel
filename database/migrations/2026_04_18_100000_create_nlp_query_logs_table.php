<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nlp_query_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('intent', 32);   // logistics | analytics
            $table->string('operation', 32); // parse | execute
            $table->text('raw_text');
            $table->json('parsed_json')->nullable();
            $table->string('provider', 64)->nullable();
            $table->string('model', 64)->nullable();
            $table->boolean('success')->default(true);
            $table->string('error_code', 64)->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->json('token_usage')->nullable();
            $table->timestamps();

            $table->index(['intent', 'operation', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['success', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nlp_query_logs');
    }
};
