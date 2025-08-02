<?php

use App\Models\Cargo;
use App\Models\Journey;
use App\Enums\OrdersTruckLocation;
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
        Schema::create('journey_cargos', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignIdFor(Cargo::class)->constrained('cargos');
            $table->foreignIdFor(Journey::class)->constrained('journeys');
            $table->enum('truck_location', array_column(OrdersTruckLocation::cases(), 'value'))
            ->nullable()
            ->default('vehicle'); // default in motrice, che va bene anche per furgoni e sponda.
            $table->boolean('is_grounding')->default(false); // definisce se i cassone è in messa a terra

            
            

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journey_cargos');
    }
};
