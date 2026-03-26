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
        if (!Schema::hasTable('journey_cargos') || !Schema::hasColumn('journey_cargos', 'warehouse_chief_id')) {
            return;
        }

        Schema::table('journey_cargos', function (Blueprint $table) {
            $table->dropForeign(['warehouse_chief_id']);
            $table->dropColumn('warehouse_chief_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('journey_cargos') || Schema::hasColumn('journey_cargos', 'warehouse_chief_id')) {
            return;
        }

        Schema::table('journey_cargos', function (Blueprint $table) {
            $table->foreignIdFor(
                User::class,
                'warehouse_chief_id'
            )->nullable()->default(null)->constrained('users');
        });
    }
};
