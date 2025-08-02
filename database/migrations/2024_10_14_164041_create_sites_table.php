<?php

use App\Models\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignIdFor(Customer::class)->constrained('customers')->onDelete('cascade');
            $table->string('denominazione');
            $table->string('tipologia')->nullable()->default(null);
            $table->boolean('is_main')->nullable()->default(1);
            $table->string('indirizzo');
            $table->float('lat');
            $table->float('lng');
            $table->float('fattore_rischio_calcolato')->default(0);
            $table->bigInteger('giorni_prossimo_ritiro')->default(0);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sites');
    }
}
