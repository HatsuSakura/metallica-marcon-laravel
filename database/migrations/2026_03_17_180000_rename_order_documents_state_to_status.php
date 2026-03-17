<?php

use App\Enums\OrderDocumentsStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'documents_status')) {
                $table->enum('documents_status', array_column(OrderDocumentsStatus::cases(), 'value'))
                    ->default(OrderDocumentsStatus::NOT_GENERATED->value);
            }
        });

        if (Schema::hasColumn('orders', 'documents_state') && Schema::hasColumn('orders', 'documents_status')) {
            DB::statement("UPDATE orders SET documents_status = documents_state WHERE documents_state IS NOT NULL");
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'documents_state')) {
                $table->dropColumn('documents_state');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'documents_state')) {
                $table->enum('documents_state', array_column(OrderDocumentsStatus::cases(), 'value'))
                    ->default(OrderDocumentsStatus::NOT_GENERATED->value);
            }
        });

        if (Schema::hasColumn('orders', 'documents_state') && Schema::hasColumn('orders', 'documents_status')) {
            DB::statement("UPDATE orders SET documents_state = documents_status WHERE documents_status IS NOT NULL");
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'documents_status')) {
                $table->dropColumn('documents_status');
            }
        });
    }
};
