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
        // Remove the column from the child table
        Schema::table('journey_cargos', function (Blueprint $table) {
            $table->dropForeign(['ragnista_id']);

            $table->dropColumn('has_ragno');
            $table->dropColumn('ragnista_id');
            $table->dropColumn('machinery_time');
        });

        // Add the columns back to the parent table
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('has_ragno')->default(false);
            $table->foreignIdFor(
                User::class,
                'ragnista_id'
            )->nullable()->default(null)->constrained('users');
            $table->integer('machinery_time')->nullable(); // Adjust type and options as needed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the column from the child table
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['ragnista_id']);
            
            $table->dropColumn('has_ragno');
            $table->dropColumn('ragnista_id');
            $table->dropColumn('machinery_time');
        });

        // Add the columns back to the parent table
        Schema::table('journey_cargos', function (Blueprint $table) {
            $table->boolean('has_ragno')->default(false);
            $table->foreignIdFor(
                User::class,
                'ragnista_id'
            )->nullable()->default(null)->constrained('users');
            $table->integer('machinery_time')->nullable(); // Adjust type and options as needed
        });
    }
};
