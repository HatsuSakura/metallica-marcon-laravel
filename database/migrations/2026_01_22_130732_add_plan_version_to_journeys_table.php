<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('journeys', function (Blueprint $table) {
            if (!Schema::hasColumn('journeys', 'plan_version')) {
                $table->unsignedInteger('plan_version')->default(1)->after('state');
            }
        });
    }

    public function down(): void
    {
        Schema::table('journeys', function (Blueprint $table) {
            if (Schema::hasColumn('journeys', 'plan_version')) {
                $table->dropColumn('plan_version');
            }
        });
    }
};
