<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('journey_stops', function (Blueprint $table) {
            $table->text('driver_notes')->nullable()->after('reason_text');
        });
    }

    public function down(): void
    {
        Schema::table('journey_stops', function (Blueprint $table) {
            $table->dropColumn('driver_notes');
        });
    }
};
