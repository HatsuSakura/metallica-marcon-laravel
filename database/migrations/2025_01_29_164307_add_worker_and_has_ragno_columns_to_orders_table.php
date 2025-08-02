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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignIdFor(
                User::class,
                'worker_id'
            )->nullable()->default(null)->constrained('users');
            $table->boolean('has_ragno')->default(false);
            $table->foreignIdFor(
                User::class,
                'ragnista_id'
            )->nullable()->default(null)->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('worker_id');
            $table->dropColumn('has_ragno');
            $table->dropColumn('ragnista_id');
        });
    }
};
