<?php
// database/migrations/2025_01_01_000200_create_order_item_explosions_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('order_item_explosions', function (Blueprint $t) {
      $t->id();

      $t->foreignId('order_item_id')->constrained()->cascadeOnDelete();

      // multi-livello (es: Trasformatore -> Rame/Ferro)
      $t->foreignId('parent_explosion_id')->nullable()
        ->constrained('order_item_explosions')
        ->nullOnDelete();

      $t->foreignId('catalog_item_id')->constrained('catalog_items')->cascadeOnDelete();

      // origine
      $t->enum('explosion_source', ['ad_hoc','recipe'])->nullable();
      $t->foreignId('recipe_id')->nullable()->constrained('recipes')->nullOnDelete();
      $t->unsignedInteger('recipe_version')->nullable();

      // dati operativi minimi
      $t->decimal('weight_net', 10,3)->nullable(); // peso del “figlio”
      $t->text('notes')->nullable();

      $t->unsignedInteger('sort')->default(0);

      $t->timestamps();
    });
  }
  public function down(): void {
    Schema::dropIfExists('order_item_explosions');
  }
};
