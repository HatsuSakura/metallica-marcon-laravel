<?php

use App\Models\Cargo;
use App\Models\Trailer;
use App\Models\User;
use App\Models\Vehicle;
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
        Schema::create('journeys', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->timestamp('dt_start')->nullable();
            $table->timestamp('dt_end')->nullable();
            $table->boolean('is_double_load')->default(0);
            $table->boolean('is_temporary_storage')->default(0);
            $table->foreignIdFor(Vehicle::class)->constrained('vehicles');
            $table->foreignIdFor(
                Cargo::class,
                'cargo_for_vehicle_id'
            )->constrained('cargos');
            $table->foreignIdFor(Trailer::class)->constrained('trailers');
            $table->foreignIdFor(
                Cargo::class,
                'cargo_for_trailer_id'
            )->constrained('cargos');
            $table->foreignIdFor(
                User::class,
                'driver_id'
            )->constrained('users');
            $table->foreignIdFor(
                User::class,
                'logistic_id'
            )->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journeys');
    }
};
