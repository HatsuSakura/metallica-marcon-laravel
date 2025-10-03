<?php
// database/migrations/2025_01_01_000100_create_recipes_tables.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('recipes', function (Blueprint $t) {
      $t->id();
      $t->string('name')->unique();
      $t->unsignedInteger('version')->default(1);
      $t->boolean('is_active')->default(true);
      $t->timestamps();
      $t->softDeletes();
    });

    Schema::create('recipe_nodes', function (Blueprint $t) {
      $t->id();
      $t->foreignId('recipe_id')->constrained()->cascadeOnDelete();
      $t->foreignId('parent_node_id')->nullable()->constrained('recipe_nodes')->nullOnDelete();
      $t->foreignId('catalog_item_id')->constrained('catalog_items')->cascadeOnDelete();
      $t->unsignedInteger('sort')->default(0);
      // opzionale: un rapporto suggerito (es. % o frazione della massa)
      $t->decimal('suggested_ratio', 8,3)->nullable();
      $t->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists('recipe_nodes');
    Schema::dropIfExists('recipes');
  }
};
