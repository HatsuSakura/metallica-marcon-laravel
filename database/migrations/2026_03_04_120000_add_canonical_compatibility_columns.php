<?php

use App\Enums\JourneyCargoStatus;
use App\Enums\JourneyStopStatus;
use App\Enums\JourneyStatus;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\OrdersTruckLocation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addCustomersColumns();
        $this->addCargosColumns();
        $this->addJourneysColumns();
        $this->addJourneyCargosColumns();
        $this->addJourneyCargoOrderItemColumns();
        $this->addJourneyEventsColumns();
        $this->addOrdersColumns();
        $this->addOrderHoldersColumns();
        $this->addOrderItemsColumns();
        $this->addSitesColumns();
        $this->addTimetablesColumns();
        $this->addUsersColumns();
        $this->addWarehousesColumns();
        $this->addWithdrawsColumns();

        $this->runBackfill();
    }

    public function down(): void
    {
        $this->dropColumnsIfExist('customers', [
            'is_occasional_customer',
            'company_name',
            'vat_number',
            'tax_code',
            'legal_address',
            'sdi_code',
            'sales_email',
            'administrative_email',
            'certified_email',
            'business_type',
        ]);

        $this->dropColumnsIfExist('cargos', ['crate_count', 'crate_slots', 'pallet_slots']);

        $this->dropColumnsIfExist('journeys', [
            'planned_start_at',
            'planned_end_at',
            'actual_start_at',
            'actual_end_at',
            'primary_warehouse_id',
            'primary_warehouse_download_at',
            'secondary_warehouse_id',
            'secondary_warehouse_download_at',
            'vehicle_cargo_id',
            'trailer_cargo_id',
            'logistics_user_id',
            'status',
        ]);

        $this->dropColumnsIfExist('journey_cargos', ['cargo_location', 'is_grounded', 'status']);
        $this->dropColumnsIfExist('journey_cargo_order_item', ['download_warehouse_id']);
        $this->dropColumnsIfExist('journey_events', ['status']);

        $this->dropColumnsIfExist('orders', [
            'logistics_user_id',
            'status',
            'expected_withdraw_at',
            'actual_withdraw_at',
            'has_crane',
            'crane_operator_user_id',
            'machinery_time_minutes',
        ]);

        $this->dropColumnsIfExist('order_holders', [
            'filled_holders_count',
            'empty_holders_count',
            'total_holders_count',
        ]);

        $this->dropColumnsIfExist('order_items', [
            'has_adr',
            'adr_un_code',
            'adr_lot_code',
            'warehouse_download_worker_id',
            'warehouse_download_at',
            'is_crane_eligible',
            'selection_duration_minutes',
            'machinery_time_share',
            'is_adr_total',
            'has_adr_total_exemption',
            'has_adr_partial_exemption',
            'status',
        ]);

        $this->dropColumnsIfExist('sites', [
            'name',
            'site_type',
            'address',
            'latitude',
            'longitude',
            'calculated_risk_factor',
            'days_until_next_withdraw',
            'has_electric_pallet_truck',
            'has_manual_pallet_truck',
        ]);

        $this->dropColumnsIfExist('timetables', ['hours_json']);
        $this->dropColumnsIfExist('users', ['is_crane_operator']);

        $this->dropColumnsIfExist('warehouses', ['name', 'address', 'latitude', 'longitude', 'notes']);

        $this->dropColumnsIfExist('withdraws', ['withdrawn_at', 'is_manual_entry', 'created_by_user_id']);
    }

    private function addCustomersColumns(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'is_occasional_customer')) {
                $table->boolean('is_occasional_customer')->nullable()->after('customer_occasionale');
            }
            if (!Schema::hasColumn('customers', 'company_name')) {
                $table->string('company_name')->nullable()->after('ragione_sociale');
            }
            if (!Schema::hasColumn('customers', 'vat_number')) {
                $table->string('vat_number')->nullable()->after('partita_iva');
            }
            if (!Schema::hasColumn('customers', 'tax_code')) {
                $table->string('tax_code')->nullable()->after('codice_fiscale');
            }
            if (!Schema::hasColumn('customers', 'legal_address')) {
                $table->string('legal_address')->nullable()->after('indirizzo_legale');
            }
            if (!Schema::hasColumn('customers', 'sdi_code')) {
                $table->string('sdi_code')->nullable()->after('codice_sdi');
            }
            if (!Schema::hasColumn('customers', 'sales_email')) {
                $table->string('sales_email')->nullable()->after('email_commerciale');
            }
            if (!Schema::hasColumn('customers', 'administrative_email')) {
                $table->string('administrative_email')->nullable()->after('email_amministrativa');
            }
            if (!Schema::hasColumn('customers', 'certified_email')) {
                $table->string('certified_email')->nullable()->after('pec');
            }
            if (!Schema::hasColumn('customers', 'business_type')) {
                $table->string('business_type')->nullable()->after('job_type');
            }
        });
    }

    private function addCargosColumns(): void
    {
        Schema::table('cargos', function (Blueprint $table) {
            if (!Schema::hasColumn('cargos', 'crate_count')) {
                $table->integer('crate_count')->nullable()->after('casse');
            }
            if (!Schema::hasColumn('cargos', 'crate_slots')) {
                $table->integer('crate_slots')->nullable()->after('spazi_casse');
            }
            if (!Schema::hasColumn('cargos', 'pallet_slots')) {
                $table->integer('pallet_slots')->nullable()->after('spazi_bancale');
            }
        });
    }

    private function addJourneysColumns(): void
    {
        Schema::table('journeys', function (Blueprint $table) {
            if (!Schema::hasColumn('journeys', 'planned_start_at')) {
                $table->timestamp('planned_start_at')->nullable()->after('dt_start');
            }
            if (!Schema::hasColumn('journeys', 'planned_end_at')) {
                $table->timestamp('planned_end_at')->nullable()->after('dt_end');
            }
            if (!Schema::hasColumn('journeys', 'actual_start_at')) {
                $table->timestamp('actual_start_at')->nullable()->after('real_dt_start');
            }
            if (!Schema::hasColumn('journeys', 'actual_end_at')) {
                $table->timestamp('actual_end_at')->nullable()->after('real_dt_end');
            }
            if (!Schema::hasColumn('journeys', 'primary_warehouse_id')) {
                $table->unsignedBigInteger('primary_warehouse_id')->nullable()->after('warehouse_id_1');
            }
            if (!Schema::hasColumn('journeys', 'primary_warehouse_download_at')) {
                $table->timestamp('primary_warehouse_download_at')->nullable()->after('warehouse_download_dt_1');
            }
            if (!Schema::hasColumn('journeys', 'secondary_warehouse_id')) {
                $table->unsignedBigInteger('secondary_warehouse_id')->nullable()->after('warehouse_id_2');
            }
            if (!Schema::hasColumn('journeys', 'secondary_warehouse_download_at')) {
                $table->timestamp('secondary_warehouse_download_at')->nullable()->after('warehouse_download_dt_2');
            }
            if (!Schema::hasColumn('journeys', 'vehicle_cargo_id')) {
                $table->unsignedBigInteger('vehicle_cargo_id')->nullable()->after('cargo_for_vehicle_id');
            }
            if (!Schema::hasColumn('journeys', 'trailer_cargo_id')) {
                $table->unsignedBigInteger('trailer_cargo_id')->nullable()->after('cargo_for_trailer_id');
            }
            if (!Schema::hasColumn('journeys', 'logistics_user_id')) {
                $table->unsignedBigInteger('logistics_user_id')->nullable()->after('logistic_id');
            }
            if (!Schema::hasColumn('journeys', 'status')) {
                $table->enum('status', array_column(JourneyStatus::cases(), 'value'))->nullable()->after('state');
            }
        });
    }

    private function addJourneyCargosColumns(): void
    {
        Schema::table('journey_cargos', function (Blueprint $table) {
            if (!Schema::hasColumn('journey_cargos', 'cargo_location')) {
                $table->enum('cargo_location', array_column(OrdersTruckLocation::cases(), 'value'))->nullable()->after('truck_location');
            }
            if (!Schema::hasColumn('journey_cargos', 'is_grounded')) {
                $table->boolean('is_grounded')->nullable()->after('is_grounding');
            }
            if (!Schema::hasColumn('journey_cargos', 'status')) {
                $table->enum('status', array_column(JourneyCargoStatus::cases(), 'value'))->nullable()->after('state');
            }
        });
    }

    private function addJourneyCargoOrderItemColumns(): void
    {
        Schema::table('journey_cargo_order_item', function (Blueprint $table) {
            if (!Schema::hasColumn('journey_cargo_order_item', 'download_warehouse_id')) {
                $table->unsignedBigInteger('download_warehouse_id')->nullable()->after('warehouse_download_id');
            }
        });
    }

    private function addJourneyEventsColumns(): void
    {
        Schema::table('journey_events', function (Blueprint $table) {
            if (!Schema::hasColumn('journey_events', 'status')) {
                $table->enum('status', array_column(JourneyStopStatus::cases(), 'value'))->nullable()->after('state');
            }
        });
    }

    private function addOrdersColumns(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'logistics_user_id')) {
                $table->unsignedBigInteger('logistics_user_id')->nullable()->after('logistic_id');
            }
            if (!Schema::hasColumn('orders', 'status')) {
                $table->enum('status', array_column(OrderStatus::cases(), 'value'))->nullable()->after('state');
            }
            if (!Schema::hasColumn('orders', 'expected_withdraw_at')) {
                $table->timestamp('expected_withdraw_at')->nullable()->after('expected_withdraw_dt');
            }
            if (!Schema::hasColumn('orders', 'actual_withdraw_at')) {
                $table->timestamp('actual_withdraw_at')->nullable()->after('real_withdraw_dt');
            }
            if (!Schema::hasColumn('orders', 'has_crane')) {
                $table->boolean('has_crane')->nullable()->after('has_ragno');
            }
            if (!Schema::hasColumn('orders', 'crane_operator_user_id')) {
                $table->unsignedBigInteger('crane_operator_user_id')->nullable()->after('ragnista_id');
            }
            if (!Schema::hasColumn('orders', 'machinery_time_minutes')) {
                $table->integer('machinery_time_minutes')->nullable()->after('machinery_time');
            }
        });
    }

    private function addOrderHoldersColumns(): void
    {
        Schema::table('order_holders', function (Blueprint $table) {
            if (!Schema::hasColumn('order_holders', 'filled_holders_count')) {
                $table->integer('filled_holders_count')->nullable()->after('holder_piene');
            }
            if (!Schema::hasColumn('order_holders', 'empty_holders_count')) {
                $table->integer('empty_holders_count')->nullable()->after('holder_vuote');
            }
            if (!Schema::hasColumn('order_holders', 'total_holders_count')) {
                $table->integer('total_holders_count')->nullable()->after('holder_totale');
            }
        });
    }

    private function addOrderItemsColumns(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'has_adr')) {
                $table->boolean('has_adr')->nullable()->after('adr');
            }
            if (!Schema::hasColumn('order_items', 'adr_un_code')) {
                $table->string('adr_un_code')->nullable()->after('adr_onu_code');
            }
            if (!Schema::hasColumn('order_items', 'adr_lot_code')) {
                $table->string('adr_lot_code')->nullable()->after('adr_lotto');
            }
            if (!Schema::hasColumn('order_items', 'warehouse_download_worker_id')) {
                $table->unsignedBigInteger('warehouse_download_worker_id')->nullable()->after('warehouse_downaload_worker_id');
            }
            if (!Schema::hasColumn('order_items', 'warehouse_download_at')) {
                $table->timestamp('warehouse_download_at')->nullable()->after('warehouse_downaload_dt');
            }
            if (!Schema::hasColumn('order_items', 'is_crane_eligible')) {
                $table->boolean('is_crane_eligible')->nullable()->after('is_ragnabile');
            }
            if (!Schema::hasColumn('order_items', 'selection_duration_minutes')) {
                $table->double('selection_duration_minutes')->nullable()->after('selection_time');
            }
            if (!Schema::hasColumn('order_items', 'machinery_time_share')) {
                $table->integer('machinery_time_share')->nullable()->after('machinery_time_fraction');
            }
            if (!Schema::hasColumn('order_items', 'is_adr_total')) {
                $table->boolean('is_adr_total')->nullable()->after('adr_totale');
            }
            if (!Schema::hasColumn('order_items', 'has_adr_total_exemption')) {
                $table->boolean('has_adr_total_exemption')->nullable()->after('adr_esenzione_totale');
            }
            if (!Schema::hasColumn('order_items', 'has_adr_partial_exemption')) {
                $table->boolean('has_adr_partial_exemption')->nullable()->after('adr_esenzione_parziale');
            }
            if (!Schema::hasColumn('order_items', 'status')) {
                $table->enum('status', array_column(OrderItemStatus::cases(), 'value'))->nullable()->after('state');
            }
        });
    }

    private function addSitesColumns(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            if (!Schema::hasColumn('sites', 'name')) {
                $table->string('name')->nullable()->after('denominazione');
            }
            if (!Schema::hasColumn('sites', 'site_type')) {
                $table->string('site_type')->nullable()->after('tipologia');
            }
            if (!Schema::hasColumn('sites', 'address')) {
                $table->string('address')->nullable()->after('indirizzo');
            }
            if (!Schema::hasColumn('sites', 'latitude')) {
                $table->double('latitude')->nullable()->after('lat');
            }
            if (!Schema::hasColumn('sites', 'longitude')) {
                $table->double('longitude')->nullable()->after('lng');
            }
            if (!Schema::hasColumn('sites', 'calculated_risk_factor')) {
                $table->double('calculated_risk_factor')->nullable()->after('fattore_rischio_calcolato');
            }
            if (!Schema::hasColumn('sites', 'days_until_next_withdraw')) {
                $table->bigInteger('days_until_next_withdraw')->nullable()->after('giorni_prossimo_ritiro');
            }
            if (!Schema::hasColumn('sites', 'has_electric_pallet_truck')) {
                $table->boolean('has_electric_pallet_truck')->nullable()->after('has_transpallet_el');
            }
            if (!Schema::hasColumn('sites', 'has_manual_pallet_truck')) {
                $table->boolean('has_manual_pallet_truck')->nullable()->after('has_transpallet_ma');
            }
        });
    }

    private function addTimetablesColumns(): void
    {
        Schema::table('timetables', function (Blueprint $table) {
            if (!Schema::hasColumn('timetables', 'hours_json')) {
                $table->longText('hours_json')->nullable()->after('hours_array');
            }
        });
    }

    private function addUsersColumns(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_crane_operator')) {
                $table->boolean('is_crane_operator')->nullable()->after('is_ragnista');
            }
        });
    }

    private function addWarehousesColumns(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            if (!Schema::hasColumn('warehouses', 'name')) {
                $table->string('name')->nullable()->after('denominazione');
            }
            if (!Schema::hasColumn('warehouses', 'address')) {
                $table->string('address')->nullable()->after('indirizzo');
            }
            if (!Schema::hasColumn('warehouses', 'latitude')) {
                $table->double('latitude')->nullable()->after('lat');
            }
            if (!Schema::hasColumn('warehouses', 'longitude')) {
                $table->double('longitude')->nullable()->after('lng');
            }
            if (!Schema::hasColumn('warehouses', 'notes')) {
                $table->text('notes')->nullable()->after('note');
            }
        });
    }

    private function addWithdrawsColumns(): void
    {
        Schema::table('withdraws', function (Blueprint $table) {
            if (!Schema::hasColumn('withdraws', 'withdrawn_at')) {
                $table->dateTime('withdrawn_at')->nullable()->after('withdraw_date');
            }
            if (!Schema::hasColumn('withdraws', 'is_manual_entry')) {
                $table->boolean('is_manual_entry')->nullable()->after('manual_insert');
            }
            if (!Schema::hasColumn('withdraws', 'created_by_user_id')) {
                $table->unsignedBigInteger('created_by_user_id')->nullable()->after('user_id');
            }
        });
    }

    private function runBackfill(): void
    {
        $this->copyColumn('customers', 'customer_occasionale', 'is_occasional_customer');
        $this->copyColumn('customers', 'ragione_sociale', 'company_name');
        $this->copyColumn('customers', 'partita_iva', 'vat_number');
        $this->copyColumn('customers', 'codice_fiscale', 'tax_code');
        $this->copyColumn('customers', 'indirizzo_legale', 'legal_address');
        $this->copyColumn('customers', 'codice_sdi', 'sdi_code');
        $this->copyColumn('customers', 'email_commerciale', 'sales_email');
        $this->copyColumn('customers', 'email_amministrativa', 'administrative_email');
        $this->copyColumn('customers', 'pec', 'certified_email');
        $this->copyColumn('customers', 'job_type', 'business_type');

        $this->copyColumn('cargos', 'casse', 'crate_count');
        $this->copyColumn('cargos', 'spazi_casse', 'crate_slots');
        $this->copyColumn('cargos', 'spazi_bancale', 'pallet_slots');

        $this->copyColumn('journeys', 'dt_start', 'planned_start_at');
        $this->copyColumn('journeys', 'dt_end', 'planned_end_at');
        $this->copyColumn('journeys', 'real_dt_start', 'actual_start_at');
        $this->copyColumn('journeys', 'real_dt_end', 'actual_end_at');
        $this->copyColumn('journeys', 'warehouse_id_1', 'primary_warehouse_id');
        $this->copyColumn('journeys', 'warehouse_download_dt_1', 'primary_warehouse_download_at');
        $this->copyColumn('journeys', 'warehouse_id_2', 'secondary_warehouse_id');
        $this->copyColumn('journeys', 'warehouse_download_dt_2', 'secondary_warehouse_download_at');
        $this->copyColumn('journeys', 'cargo_for_vehicle_id', 'vehicle_cargo_id');
        $this->copyColumn('journeys', 'cargo_for_trailer_id', 'trailer_cargo_id');
        $this->copyColumn('journeys', 'logistic_id', 'logistics_user_id');
        $this->copyColumn('journeys', 'state', 'status');

        $this->copyColumn('journey_cargos', 'truck_location', 'cargo_location');
        $this->copyColumn('journey_cargos', 'is_grounding', 'is_grounded');
        $this->copyColumn('journey_cargos', 'state', 'status');

        $this->copyColumn('journey_cargo_order_item', 'warehouse_download_id', 'download_warehouse_id');
        $this->copyColumn('journey_events', 'state', 'status');

        $this->copyColumn('orders', 'logistic_id', 'logistics_user_id');
        $this->copyColumn('orders', 'state', 'status');
        $this->copyColumn('orders', 'expected_withdraw_dt', 'expected_withdraw_at');
        $this->copyColumn('orders', 'real_withdraw_dt', 'actual_withdraw_at');
        $this->copyColumn('orders', 'has_ragno', 'has_crane');
        $this->copyColumn('orders', 'ragnista_id', 'crane_operator_user_id');
        $this->copyColumn('orders', 'machinery_time', 'machinery_time_minutes');

        $this->copyColumn('order_holders', 'holder_piene', 'filled_holders_count');
        $this->copyColumn('order_holders', 'holder_vuote', 'empty_holders_count');
        $this->copyColumn('order_holders', 'holder_totale', 'total_holders_count');

        $this->copyColumn('order_items', 'adr', 'has_adr');
        $this->copyColumn('order_items', 'adr_onu_code', 'adr_un_code');
        $this->copyColumn('order_items', 'adr_lotto', 'adr_lot_code');
        $this->copyColumn('order_items', 'warehouse_downaload_worker_id', 'warehouse_download_worker_id');
        $this->copyColumn('order_items', 'warehouse_downaload_dt', 'warehouse_download_at');
        $this->copyColumn('order_items', 'is_ragnabile', 'is_crane_eligible');
        $this->copyColumn('order_items', 'selection_time', 'selection_duration_minutes');
        $this->copyColumn('order_items', 'machinery_time_fraction', 'machinery_time_share');
        $this->copyColumn('order_items', 'adr_totale', 'is_adr_total');
        $this->copyColumn('order_items', 'adr_esenzione_totale', 'has_adr_total_exemption');
        $this->copyColumn('order_items', 'adr_esenzione_parziale', 'has_adr_partial_exemption');
        $this->copyColumn('order_items', 'state', 'status');

        $this->copyColumn('sites', 'denominazione', 'name');
        $this->copyColumn('sites', 'tipologia', 'site_type');
        $this->copyColumn('sites', 'indirizzo', 'address');
        $this->copyColumn('sites', 'lat', 'latitude');
        $this->copyColumn('sites', 'lng', 'longitude');
        $this->copyColumn('sites', 'fattore_rischio_calcolato', 'calculated_risk_factor');
        $this->copyColumn('sites', 'giorni_prossimo_ritiro', 'days_until_next_withdraw');
        $this->copyColumn('sites', 'has_transpallet_el', 'has_electric_pallet_truck');
        $this->copyColumn('sites', 'has_transpallet_ma', 'has_manual_pallet_truck');

        $this->copyColumn('timetables', 'hours_array', 'hours_json');
        $this->copyColumn('users', 'is_ragnista', 'is_crane_operator');

        $this->copyColumn('warehouses', 'denominazione', 'name');
        $this->copyColumn('warehouses', 'indirizzo', 'address');
        $this->copyColumn('warehouses', 'lat', 'latitude');
        $this->copyColumn('warehouses', 'lng', 'longitude');
        $this->copyColumn('warehouses', 'note', 'notes');

        $this->copyColumn('withdraws', 'withdraw_date', 'withdrawn_at');
        $this->copyColumn('withdraws', 'manual_insert', 'is_manual_entry');
        $this->copyColumn('withdraws', 'user_id', 'created_by_user_id');
    }

    private function copyColumn(string $table, string $source, string $target): void
    {
        if (!Schema::hasColumn($table, $source) || !Schema::hasColumn($table, $target)) {
            return;
        }

        DB::statement(sprintf(
            'UPDATE `%s` SET `%s` = `%s` WHERE `%s` IS NULL',
            $table,
            $target,
            $source,
            $target
        ));
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
};

