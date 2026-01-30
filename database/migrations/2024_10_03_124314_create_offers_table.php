<?php

use App\Models\User;
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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('listing_id')->constrained('listings');

            $table->foreignIdFor(
                User::class,
                'bidder_id'
            )->constrained('users');

            $table->unsignedBigInteger('amount');

            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
