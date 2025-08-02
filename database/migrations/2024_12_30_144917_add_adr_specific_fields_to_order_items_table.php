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
        Schema::table('order_items', function (Blueprint $table) {
            $table->boolean('adr_totale')->nullable()->default(null);
            $table->boolean('adr_esenzione_totale')->nullable()->default(null);
            $table->boolean('adr_esenzione_parziale')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('adr_totale');
            $table->dropColumn('adr_esenzione_totale');
            $table->dropColumn('adr_esenzione_parziale');
        });
    }
};
