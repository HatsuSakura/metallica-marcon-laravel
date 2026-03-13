<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'notes')) {
                $table->text('notes')->nullable()->after('certified_email');
            }
        });

        Schema::table('sites', function (Blueprint $table) {
            if (!Schema::hasColumn('sites', 'notes')) {
                $table->text('notes')->nullable()->after('has_adr_consultant');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'notes')) {
                $table->text('notes')->nullable()->after('machinery_time_minutes');
            }
        });

        Schema::table('journeys', function (Blueprint $table) {
            if (!Schema::hasColumn('journeys', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'notes')) {
                $table->dropColumn('notes');
            }
        });

        Schema::table('sites', function (Blueprint $table) {
            if (Schema::hasColumn('sites', 'notes')) {
                $table->dropColumn('notes');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'notes')) {
                $table->dropColumn('notes');
            }
        });

        Schema::table('journeys', function (Blueprint $table) {
            if (Schema::hasColumn('journeys', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
