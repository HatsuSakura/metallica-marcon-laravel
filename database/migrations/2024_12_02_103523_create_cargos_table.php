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
        Schema::create('cargos', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name')->nullable()->default(null);
            $table->string('description')->nullable()->default(null);
            $table->boolean('is_cargo')->default(1); /* A meno della SPONDA (che identifica la motrice con sponda) e dei furgnoni sono tutti cassoni, quindi CARGO*/
            $table->boolean('is_long')->default(0);
            $table->integer('total_count')->default(0);
            $table->float('length')->nullable()->default(null);
            $table->integer('casse')->nullable()->default(null);
            $table->integer('spazi_casse')->nullable()->default(null);
            $table->integer('spazi_bancale')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cargos');
    }
};
