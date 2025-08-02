<?php

use App\Enums\JourneysState;
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
        Schema::table('journeys', function (Blueprint $table) {
            $table->enum('state', array_column(JourneysState::cases(), 'value'))
                ->default(JourneysState::STATE_CREATED->value);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journeys', function (Blueprint $table) {
            $table->dropColumn('state');
        });
    }
};
