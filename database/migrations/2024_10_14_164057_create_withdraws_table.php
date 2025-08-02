<?php

use App\Models\Customer;
use App\Models\Site;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWithdrawsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('withdraws', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->dateTime('withdraw_date');
            $table->float('residue_percentage', 10, 0)->nullable();
            $table->foreignIdFor(Customer::class)->constrained('customers');
            $table->foreignIdFor(Site::class)->constrained('sites');
            $table->foreignIdFor(Vehicle::class)->constrained('vehicles');
            $table->foreignIdFor(
                User::class,
                'driver_id'
            )->constrained('users');
            $table->foreignIdFor(User::class)->constrained('users');
            $table->boolean('manual_insert')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('withdraws');
    }
}
