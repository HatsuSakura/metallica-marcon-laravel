<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropForeignIfExists('journeys', 'cargo_for_vehicle_id');
        $this->dropForeignIfExists('journeys', 'cargo_for_trailer_id');
        $this->dropForeignIfExists('journey_cargo_order_item', 'warehouse_download_id');
        $this->dropForeignIfExists('withdraws', 'user_id');

        $this->dropColumnsIfExist('customers', [
            'customer_occasionale',
            'ragione_sociale',
            'partita_iva',
            'codice_fiscale',
            'indirizzo_legale',
            'codice_sdi',
            'email_commerciale',
            'email_amministrativa',
            'pec',
            'job_type',
        ]);

        $this->dropColumnsIfExist('journeys', [
            'state',
            'cargo_for_vehicle_id',
            'cargo_for_trailer_id',
        ]);

        $this->dropColumnsIfExist('journey_cargos', [
            'truck_location',
            'is_grounding',
            'state',
        ]);

        $this->dropColumnsIfExist('journey_cargo_order_item', [
            'warehouse_download_id',
        ]);

        $this->dropColumnsIfExist('journey_events', [
            'state',
        ]);

        $this->dropColumnsIfExist('orders', [
            'state',
            'truck_location',
        ]);

        $this->dropColumnsIfExist('order_items', [
            'state',
        ]);

        $this->dropColumnsIfExist('sites', [
            'denominazione',
            'tipologia',
            'indirizzo',
            'lat',
            'lng',
            'fattore_rischio_calcolato',
            'giorni_prossimo_ritiro',
            'has_transpallet_el',
            'has_transpallet_ma',
        ]);

        $this->dropColumnsIfExist('timetables', [
            'hours_array',
        ]);

        $this->dropColumnsIfExist('users', [
            'is_ragnista',
        ]);

        $this->dropColumnsIfExist('warehouses', [
            'denominazione',
            'indirizzo',
            'lat',
            'lng',
            'note',
        ]);

        $this->dropColumnsIfExist('withdraws', [
            'withdraw_date',
            'manual_insert',
            'user_id',
        ]);
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'customer_occasionale')) $table->boolean('customer_occasionale')->nullable();
            if (!Schema::hasColumn('customers', 'ragione_sociale')) $table->string('ragione_sociale')->nullable();
            if (!Schema::hasColumn('customers', 'partita_iva')) $table->string('partita_iva')->nullable();
            if (!Schema::hasColumn('customers', 'codice_fiscale')) $table->string('codice_fiscale')->nullable();
            if (!Schema::hasColumn('customers', 'indirizzo_legale')) $table->string('indirizzo_legale')->nullable();
            if (!Schema::hasColumn('customers', 'codice_sdi')) $table->string('codice_sdi')->nullable();
            if (!Schema::hasColumn('customers', 'email_commerciale')) $table->string('email_commerciale')->nullable();
            if (!Schema::hasColumn('customers', 'email_amministrativa')) $table->string('email_amministrativa')->nullable();
            if (!Schema::hasColumn('customers', 'pec')) $table->string('pec')->nullable();
            if (!Schema::hasColumn('customers', 'job_type')) $table->string('job_type')->nullable();
        });

        Schema::table('journeys', function (Blueprint $table) {
            if (!Schema::hasColumn('journeys', 'state')) $table->string('state')->nullable();
            if (!Schema::hasColumn('journeys', 'cargo_for_vehicle_id')) $table->unsignedBigInteger('cargo_for_vehicle_id')->nullable();
            if (!Schema::hasColumn('journeys', 'cargo_for_trailer_id')) $table->unsignedBigInteger('cargo_for_trailer_id')->nullable();
        });

        Schema::table('journey_cargos', function (Blueprint $table) {
            if (!Schema::hasColumn('journey_cargos', 'truck_location')) $table->string('truck_location')->nullable();
            if (!Schema::hasColumn('journey_cargos', 'is_grounding')) $table->boolean('is_grounding')->nullable();
            if (!Schema::hasColumn('journey_cargos', 'state')) $table->string('state')->nullable();
        });

        Schema::table('journey_cargo_order_item', function (Blueprint $table) {
            if (!Schema::hasColumn('journey_cargo_order_item', 'warehouse_download_id')) $table->unsignedBigInteger('warehouse_download_id')->nullable();
        });

        Schema::table('journey_events', function (Blueprint $table) {
            if (!Schema::hasColumn('journey_events', 'state')) $table->string('state')->nullable();
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'state')) $table->string('state')->nullable();
            if (!Schema::hasColumn('orders', 'truck_location')) $table->string('truck_location')->nullable();
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'state')) $table->string('state')->nullable();
        });

        Schema::table('sites', function (Blueprint $table) {
            if (!Schema::hasColumn('sites', 'denominazione')) $table->string('denominazione')->nullable();
            if (!Schema::hasColumn('sites', 'tipologia')) $table->string('tipologia')->nullable();
            if (!Schema::hasColumn('sites', 'indirizzo')) $table->string('indirizzo')->nullable();
            if (!Schema::hasColumn('sites', 'lat')) $table->string('lat')->nullable();
            if (!Schema::hasColumn('sites', 'lng')) $table->string('lng')->nullable();
            if (!Schema::hasColumn('sites', 'fattore_rischio_calcolato')) $table->double('fattore_rischio_calcolato')->nullable();
            if (!Schema::hasColumn('sites', 'giorni_prossimo_ritiro')) $table->integer('giorni_prossimo_ritiro')->nullable();
            if (!Schema::hasColumn('sites', 'has_transpallet_el')) $table->boolean('has_transpallet_el')->nullable();
            if (!Schema::hasColumn('sites', 'has_transpallet_ma')) $table->boolean('has_transpallet_ma')->nullable();
        });

        Schema::table('timetables', function (Blueprint $table) {
            if (!Schema::hasColumn('timetables', 'hours_array')) $table->longText('hours_array')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_ragnista')) $table->boolean('is_ragnista')->nullable();
        });

        Schema::table('warehouses', function (Blueprint $table) {
            if (!Schema::hasColumn('warehouses', 'denominazione')) $table->string('denominazione')->nullable();
            if (!Schema::hasColumn('warehouses', 'indirizzo')) $table->string('indirizzo')->nullable();
            if (!Schema::hasColumn('warehouses', 'lat')) $table->string('lat')->nullable();
            if (!Schema::hasColumn('warehouses', 'lng')) $table->string('lng')->nullable();
            if (!Schema::hasColumn('warehouses', 'note')) $table->text('note')->nullable();
        });

        Schema::table('withdraws', function (Blueprint $table) {
            if (!Schema::hasColumn('withdraws', 'withdraw_date')) $table->dateTime('withdraw_date')->nullable();
            if (!Schema::hasColumn('withdraws', 'manual_insert')) $table->boolean('manual_insert')->nullable();
            if (!Schema::hasColumn('withdraws', 'user_id')) $table->unsignedBigInteger('user_id')->nullable();
        });
    }

    private function dropColumnsIfExist(string $table, array $columns): void
    {
        $toDrop = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn($table, $column)));

        if (empty($toDrop)) {
            return;
        }

        Schema::table($table, function (Blueprint $tableBlueprint) use ($toDrop) {
            $tableBlueprint->dropColumn($toDrop);
        });
    }

    private function dropForeignIfExists(string $table, string $column): void
    {
        if (!Schema::hasColumn($table, $column)) {
            return;
        }

        $database = DB::getDatabaseName();

        $constraint = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');

        if ($constraint) {
            DB::statement(sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $table, $constraint));
        }
    }
};
