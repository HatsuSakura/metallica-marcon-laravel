<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropColumnsIfExist('order_holders', [
            'holder_piene',
            'holder_vuote',
            'holder_totale',
        ]);
    }

    public function down(): void
    {
        Schema::table('order_holders', function (Blueprint $table) {
            if (!Schema::hasColumn('order_holders', 'holder_piene')) {
                $table->integer('holder_piene')->nullable();
            }
            if (!Schema::hasColumn('order_holders', 'holder_vuote')) {
                $table->integer('holder_vuote')->nullable();
            }
            if (!Schema::hasColumn('order_holders', 'holder_totale')) {
                $table->integer('holder_totale')->nullable();
            }
        });
    }

    private function dropColumnsIfExist(string $table, array $columns): void
    {
        $toDrop = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn($table, $column)));

        if (empty($toDrop)) {
            return;
        }

        Schema::table($table, function (Blueprint $tableBlueprint) use ($toDrop) {
            $tableBlueprint->dropColumn($toDrop);
        });
    }
};

