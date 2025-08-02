<?php

use App\Models\User;
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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor(Order::class)->constrained('orders')->cascadeOnDelete();
            $table->foreignId('cer_code_id')->references('id')->on('cer_codes');
            $table->foreignId('holder_id')->references('id')->on('holders');
            $table->integer('holder_quantity');
            $table->text('description');
            $table->float('weight_declared');
            $table->float('weight_gross')->nullable()->default(null);
            $table->float('weight_tare')->nullable()->default(null);
            $table->float('weight_net')->nullable()->default(null);
            $table->boolean('adr')->default(0);
            $table->string('adr_onu_code')->nullable()->default(null);
            $table->string('adr_hp')->nullable()->default(null);
            $table->string('adr_lotto')->nullable()->default(null);
            $table->float('adr_volume')->nullable()->default(null);
            $table->text('warehouse_notes')->nullable()->default(null);
            $table->foreignIdFor(
                User::class,
                'worker_id'
            )->constrained('users')->nullable()->default(null);
            $table->integer('selection_time')->nullable()->default(null);
            $table->integer('machinery_time')->nullable()->default(null);
            $table->float('recognized_price')->nullable()->default(null);
            $table->float('recognized_weight')->nullable()->default(null);


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
