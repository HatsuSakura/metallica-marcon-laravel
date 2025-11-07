<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // aggiungi il booleano dopo holder_id (adatta la posizione se vuoi)
            $table->boolean('is_bulk')->default(false)->after('holder_quantity');
            // opzionale: indice se prevedi filtri/where frequenti su is_bulk
            $table->index('is_bulk');
        });


        // 1) se c'è una FK su holder_id, va tolta prima di cambiare la colonna
        Schema::table('order_items', function (Blueprint $table) {
            // se conosci il nome esatto usa $table->dropForeign('order_items_holder_id_foreign');
            if (Schema::hasColumn('order_items', 'holder_id')) {
                try { $table->dropForeign(['holder_id']); } catch (\Throwable $e) {}
            }
        });

        // 2) rendi nullable le colonne
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('holder_id')->nullable()->change();
            $table->integer('holder_quantity')->nullable()->change();
        });

        // 3) ri-aggiungi la FK (opzionale: nullOnDelete se ti fa comodo)
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreign('holder_id')
                  ->references('id')->on('holders')
                  ->nullOnDelete(); // o ->cascadeOnDelete() se era così prima
        });


    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['is_bulk']);
            $table->dropColumn('is_bulk');
        });

        // ATTENZIONE: se esistono NULL, il change a NOT NULL fallisce.
        // prima ripulisci eventuali null con un valore “di fallback” o blocca il down.

        Schema::table('order_items', function (Blueprint $table) {
            try { $table->dropForeign(['holder_id']); } catch (\Throwable $e) {}
        });

        // qui ipotizzo che prima fossero NOT NULL
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('holder_id')->nullable(false)->change();
            $table->integer('holder_quantity')->nullable(false)->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreign('holder_id')
                  ->references('id')->on('holders')
                  ->cascadeOnDelete(); // ripristina il comportamento precedente se era diverso
        });
        
    }
};
