<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_item_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->onDelete('cascade');
            $table->string('path'); // path relativo nello storage
            $table->string('original_name')->nullable(); // nome originale del file
            $table->string('mime_type')->nullable();     // image/jpeg, image/png...
            $table->unsignedInteger('size')->nullable();  // dimensione in byte
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_images');
    }
};

