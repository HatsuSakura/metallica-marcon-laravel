<?php

use App\Models\Site;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Customer;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignIdFor(Customer::class)->constrained('customers');
            $table->foreignIdFor(Site::class)->constrained('sites');
            $table->foreignIdFor(
                User::class,
                'logistic_id'
            )->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
