<?php

use App\Models\Site;
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
        Schema::create('internal_contacts', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('surname')->nullable()->default(null);
            $table->string('phone')->nullable()->default(null);
            $table->string('mobile')->nullable()->default(null);
            $table->string('email')->nullable()->default(null);
            $table->string('role')->nullable()->default(null);
            $table->foreignIdFor(
                Site::class,
                'site_id'
            )->constrained('sites');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_contacts');
    }
};
