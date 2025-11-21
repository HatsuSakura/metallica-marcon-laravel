<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('holders', function (Blueprint $table) {
            // Holder di destinazione dell’equivalenza (es. 2 = Bancale, 4 = Cassa)
            $table->unsignedBigInteger('equivalent_holder_id')->nullable()->after('volume');
            $table->unsignedInteger('equivalent_units')->nullable()->after('equivalent_holder_id');

            $table->foreign('equivalent_holder_id')
                  ->references('id')->on('holders')
                  ->onDelete('set null');

            $table->index('equivalent_holder_id');
        });
    }

    public function down(): void
    {
        Schema::table('holders', function (Blueprint $table) {
            // Drop FK e indice
            $table->dropForeign(['equivalent_holder_id']);
            $table->dropIndex(['equivalent_holder_id']);

            // Drop colonne
            $table->dropColumn('equivalent_units');
            $table->dropColumn('equivalent_holder_id');
        });
    }
};
