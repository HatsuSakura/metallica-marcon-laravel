<?php

use App\Enums\OrderDocumentsStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'documents_state')) {
                $table->enum('documents_state', array_column(OrderDocumentsStatus::cases(), 'value'))
                    ->default(OrderDocumentsStatus::NOT_GENERATED->value)
                    ->after('status');
            }

            if (!Schema::hasColumn('orders', 'documents_generated_at')) {
                $table->timestamp('documents_generated_at')->nullable()->after('documents_state');
            }

            if (!Schema::hasColumn('orders', 'documents_error')) {
                $table->text('documents_error')->nullable()->after('documents_generated_at');
            }

            if (!Schema::hasColumn('orders', 'documents_version')) {
                $table->unsignedInteger('documents_version')->default(0)->after('documents_error');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $toDrop = [];

            foreach (['documents_state', 'documents_generated_at', 'documents_error', 'documents_version'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $toDrop[] = $column;
                }
            }

            if (!empty($toDrop)) {
                $table->dropColumn($toDrop);
            }
        });
    }
};


