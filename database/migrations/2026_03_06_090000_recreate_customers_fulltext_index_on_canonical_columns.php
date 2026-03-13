<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $indexExists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'customers')
            ->where('index_name', 'customers_fulltext_index')
            ->exists();

        if ($indexExists) {
            DB::statement('ALTER TABLE `customers` DROP INDEX `customers_fulltext_index`');
        }

        DB::statement(
            'ALTER TABLE `customers` ADD FULLTEXT `customers_fulltext_index` (`company_name`, `vat_number`, `tax_code`, `legal_address`, `sales_email`, `administrative_email`, `certified_email`)'
        );
    }

    public function down(): void
    {
        $indexExists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'customers')
            ->where('index_name', 'customers_fulltext_index')
            ->exists();

        if ($indexExists) {
            DB::statement('ALTER TABLE `customers` DROP INDEX `customers_fulltext_index`');
        }
    }
};
