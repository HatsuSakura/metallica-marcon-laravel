<?php

use App\Enums\OrdersTruckLocation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'cargo_location')) {
                $table
                    ->enum('cargo_location', array_column(OrdersTruckLocation::cases(), 'value'))
                    ->nullable()
                    ->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'cargo_location')) {
                $table->dropColumn('cargo_location');
            }
        });
    }
};

