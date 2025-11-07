<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('custom_l_cm', 8, 2)->nullable()->after('is_bulk');
            $table->decimal('custom_w_cm',   8, 2)->nullable()->after('custom_l_cm');
            $table->decimal('custom_h_cm',   8, 2)->nullable()->after('custom_w_cm');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['custom_l_cm','custom_w_cm','custom_h_cm']);
        });
    }
};
