<?php

use App\Enums\UserRole;
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
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
            $table->boolean('can_login')->default(true); // o false per i worker
            $table->enum('role', array_column(UserRole::cases(), 'value'))->nullable()->after('can_login')->change();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manage_workers_as_user_without_email', function (Blueprint $table) {
            $table->dropColumn('can_login');
        });
    }
};
