<?php
// database/migrations/2025_01_01_000000_create_catalog_items_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('catalog_items', function (Blueprint $t) {
      $t->id();
      $t->string('name')->unique();
      $t->enum('type', ['material','component']);
      $t->string('code')->nullable();
      $t->foreignId('parent_catalog_item_id')->nullable()->constrained('catalog_items')->nullOnDelete(); // opzionale
      $t->boolean('is_active')->default(true);
      $t->timestamps();
      $t->softDeletes();
    });
  }
  public function down(): void {
    Schema::dropIfExists('catalog_items');
  }
};
