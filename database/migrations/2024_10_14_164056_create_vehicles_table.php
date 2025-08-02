<?php

use App\Models\Trailer;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name')->nullable()->default(null);
            $table->string('description')->nullable()->default(null);
            $table->string('plate')->nullable()->default(null);
            $table->string('type')->nullable()->default(null);
            $table->foreignIdFor(
                User::class,
                'driver_id'
            )->constrained('users');
            $table->foreignIdFor(
                Trailer::class,
                'trailer_id'
            )->constrained('trailers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
