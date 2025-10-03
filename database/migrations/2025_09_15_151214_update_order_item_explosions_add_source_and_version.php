<?php
// database/migrations/2025_09_14_000001_update_order_item_explosions_add_source_and_version.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('order_item_explosions', function (Blueprint $t) {
            // solo se non esistono già
            if (!Schema::hasColumn('order_item_explosions', 'explosion_source')) {
                $t->enum('explosion_source', ['ad_hoc','recipe'])->nullable()->after('catalog_item_id');
            }
            if (!Schema::hasColumn('order_item_explosions', 'recipe_version')) {
                $t->unsignedInteger('recipe_version')->nullable()->after('recipe_id');
            }
        });
    }

    public function down(): void {
        Schema::table('order_item_explosions', function (Blueprint $t) {
            if (Schema::hasColumn('order_item_explosions', 'explosion_source')) {
                $t->dropColumn('explosion_source');
            }
            if (Schema::hasColumn('order_item_explosions', 'recipe_version')) {
                $t->dropColumn('recipe_version');
            }
        });
    }
};
