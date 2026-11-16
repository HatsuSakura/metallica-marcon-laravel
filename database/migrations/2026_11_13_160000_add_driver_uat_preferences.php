<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('journeys', fn (Blueprint $t) => $t->boolean('header_open_on_entry')->default(true));
        Schema::table('journey_execution_items', fn (Blueprint $t) => $t->boolean('hidden_from_driver')->default(false));
    }

    public function down(): void
    {
        Schema::table('journeys', fn (Blueprint $t) => $t->dropColumn('header_open_on_entry'));
        Schema::table('journey_execution_items', fn (Blueprint $t) => $t->dropColumn('hidden_from_driver'));
    }
};
