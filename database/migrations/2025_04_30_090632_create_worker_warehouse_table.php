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
        Schema::create('worker_warehouse', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('worker_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->timestamps();

            // Foreign keys and cascading deletes.
            $table->foreign('worker_id')
                  ->references('id')
                  ->on('workers')
                  ->onDelete('cascade');

            $table->foreign('warehouse_id')
                  ->references('id')
                  ->on('warehouses')
                  ->onDelete('cascade');

            // Ensure that the combination of user and warehouse is unique.
            $table->unique(['worker_id', 'warehouse_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_warehouse');
    }
};
