<?php

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->tinyInteger('customer_occasionale')->nullable()->default(0);
            $table->foreignIdFor(
                User::class,
                'seller_id'
            )->constrained('users');
            $table->string('ragione_sociale');
            $table->string('partita_iva')->unique();
            $table->string('codice_fiscale')->unique();
            $table->string('indirizzo_legale');
            $table->string('codice_sdi');
            $table->string('job_type')->nullable()->default(null);
            $table->string('email_commerciale');
            $table->string('email_amministrativa');
            $table->string('pec');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customers');
    }
}
