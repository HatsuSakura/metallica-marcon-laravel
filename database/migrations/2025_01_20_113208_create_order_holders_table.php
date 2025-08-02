<?php

use App\Models\Order;
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
        Schema::create('order_holders', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor(Order::class)->constrained('orders')->cascadeOnDelete();
            $table->foreignId('holder_id')->references('id')->on('holders');
            $table->integer('holder_piene')->default('0');
            $table->integer('holder_vuote')->default('0');
            $table->integer('holder_totale')->default('0');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_holders');
    }
};
