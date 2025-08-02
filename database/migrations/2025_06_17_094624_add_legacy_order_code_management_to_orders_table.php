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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('legacy_code')->nullable()->default(null)->after('id');
        });

        Schema::create('order_counters', function (Blueprint $table) {
            $table->year('year')->primary();
            $table->unsignedBigInteger('counter')->default(0);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('legacy_code');
        });

        Schema::dropIfExists('order_counters');
    }
};
