<?php

use App\Models\User;
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
        Schema::table('journey_cargos', function (Blueprint $table) {
            $table->dropForeign(['worker_id']);
            $table->dropColumn('warehouse_chief_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journey_cargos', function (Blueprint $table) {
            $table->foreignIdFor(
                User::class,
                'warehouse_chief_id'
            )->nullable()->default(null)->constrained('users');
        });
    }
};
