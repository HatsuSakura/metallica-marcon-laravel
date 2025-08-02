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
            $table->boolean('is_holder_dirty')->default(false)->after('warehouse_notes');
            $table->integer('total_dirty_holders')->nullable()->after('is_holder_dirty');
            $table->boolean('is_holder_broken')->default(false)->after('total_dirty_holders');
            $table->integer('total_broken_holders')->nullable()->after('is_holder_broken');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('is_holder_dirty');
            $table->dropColumn('total_dirty_holders');
            $table->dropColumn('is_holder_broken');
            $table->dropColumn('total_broken_holders');
        });
    }
};
