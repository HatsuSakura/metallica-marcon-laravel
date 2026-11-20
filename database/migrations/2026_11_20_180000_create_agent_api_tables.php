<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->boolean('is_active')->default(true);
            $t->json('abilities');
            $t->timestamps();
        });
        Schema::create('agent_admin_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('agent_id')->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('user_id');
            $t->string('action');
            $t->json('details')->nullable();
            $t->unsignedBigInteger('token_id')->nullable();
            $t->timestamp('created_at');
        });
        Schema::create('personal_access_tokens', function (Blueprint $t) {
            $t->id();
            $t->morphs('tokenable');
            $t->string('name');
            $t->string('token', 64)->unique();
            $t->text('abilities')->nullable();
            $t->timestamp('last_used_at')->nullable();
            $t->timestamp('expires_at')->nullable()->index();
            $t->timestamps();
        });
        Schema::create('agent_api_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('agent_id')->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('token_id');
            $t->string('key_hash', 64);
            $t->string('request_hash', 64);
            $t->string('method', 10);
            $t->string('path');
            $t->unsignedSmallInteger('status');
            $t->json('response');
            $t->json('context')->nullable();
            $t->timestamp('created_at');
            $t->unique(['agent_id', 'key_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_api_requests');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('agent_admin_events');
        Schema::dropIfExists('agents');
    }
};
