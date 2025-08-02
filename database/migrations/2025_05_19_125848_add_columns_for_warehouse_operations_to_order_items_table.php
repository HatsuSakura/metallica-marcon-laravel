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
            $table->renameColumn('worker_id', 'warehouse_downaload_worker_id');
            $table->timestamp('warehouse_downaload_dt')->nullable()->after('warehouse_downaload_worker_id');
            $table->foreignId('warehouse_weighing_worker_id')->after('warehouse_downaload_dt')->nullable()->constrained('users');
            $table->timestamp('warehouse_weighing_dt')->nullable()->after('warehouse_weighing_worker_id');
            $table->foreignId('warehouse_selection_worker_id')->after('warehouse_weighing_dt')->nullable()->constrained('users');
            $table->timestamp('warehouse_selection_dt')->nullable()->after('warehouse_selection_worker_id');

            $table->boolean('has_non_conformity')->default(false)->after('warehouse_notes');
            $table->text('warehouse_non_conformity')->nullable()->default(null)->after('has_non_conformity');
            $table->boolean('has_exploded_children')->default(false)->after('warehouse_non_conformity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['warehouse_weighing_worker_id']);
            $table->dropForeign(['warehouse_selection_worker_id']);
            $table->renameColumn('warehouse_downaload_worker_id', 'worker_id');
            $table->dropColumn('warehouse_downaload_dt');
            $table->dropColumn('warehouse_weighing_worker_id');
            $table->dropColumn('warehouse_weighing_dt');
            $table->dropColumn('warehouse_selection_worker_id');
            $table->dropColumn('warehouse_selection_dt');

            $table->dropColumn('has_non_conformity');
            $table->dropColumn('warehouse_non_conformity');
            $table->dropColumn('has_exploded_children');
        });
    }
};
