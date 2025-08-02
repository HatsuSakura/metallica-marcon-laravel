<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('holders', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name')->nullable()->default(null);
            $table->string('description')->nullable()->default(null);
            $table->float('volume')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holders');
    }
};
