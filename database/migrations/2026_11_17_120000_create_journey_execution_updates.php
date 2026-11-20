<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journey_execution_updates', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->unsignedBigInteger('journey_id')->index();
            $t->unsignedBigInteger('actor_id');
            $t->unsignedBigInteger('stop_id')->nullable()->index();
            $t->unsignedInteger('revision');
            $t->string('request_hash', 64)->nullable();
            $t->json('operations');
            $t->json('snapshot');
            $t->json('payload');
            $t->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journey_execution_updates');
    }
};
