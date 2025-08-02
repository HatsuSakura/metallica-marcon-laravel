<?php

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
        Schema::table('customers', function (Blueprint $table) {
            $table->fullText(
                [
                    'ragione_sociale', 
                    'partita_iva', 
                    'codice_fiscale',
                    'indirizzo_legale', 
                    'email_commerciale', 
                    'email_amministrativa',
                    'pec'
                ],
                'customers_fulltext_index' // Specify a shorter index name
            ); // Add full-text index on 'name' and 'email' columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            //
        });
    }
};
