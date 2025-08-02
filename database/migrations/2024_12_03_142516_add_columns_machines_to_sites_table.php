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
        Schema::table('sites', function (Blueprint $table) {
            $table->boolean('has_muletto')->default(false);
            $table->boolean('has_transpallet_el')->default(false);
            $table->boolean('has_transpallet_ma')->default(false);
            $table->text('other_machines')->default(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn('has_muletto');
            $table->dropColumn('has_transpallet_el');
            $table->dropColumn('has_transpallet_ma');
            $table->dropColumn('other_machines');
        });
    }
};
