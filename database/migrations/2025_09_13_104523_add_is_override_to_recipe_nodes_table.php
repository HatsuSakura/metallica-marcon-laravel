<?php
// database/migrations/2025_09_13_000001_add_is_override_to_recipe_nodes_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('recipe_nodes', function (Blueprint $table) {
            $table->boolean('is_override')
                  ->default(false)
                  ->after('catalog_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('recipe_nodes', function (Blueprint $table) {
            $table->dropColumn('is_override');
        });
    }
};
