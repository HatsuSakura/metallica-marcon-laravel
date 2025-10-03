<?php
// database/migrations/xxxx_add_catalog_item_fk_to_recipes.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('recipes', function (Blueprint $t) {
            $t->foreignId('catalog_item_id')->nullable()->unique()->constrained('catalog_items')->nullOnDelete();
        });
    }
    public function down(): void {
        Schema::table('recipes', function (Blueprint $t) {
            $t->dropConstrainedForeignId('catalog_item_id');
        });
    }
};
