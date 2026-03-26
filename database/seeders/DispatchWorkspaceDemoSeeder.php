<?php

namespace Database\Seeders;

use App\Enums\DispatchStatus;
use App\Enums\JourneyStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DispatchWorkspaceDemoSeeder extends Seeder
{
    private array $columnsCache = [];

    public function run(): void
    {
        $now = now();

        if (DB::table('orders')->where('legacy_code', 'DISPATCH_DEMO_001')->exists()) {
            return;
        }

        $logisticUserId = $this->firstOrInsertBy('users', ['email' => 'dispatch.demo.logistic@local.test'], [
            'name' => 'Dispatch',
            'surname' => 'Logistic',
            'email' => 'dispatch.demo.logistic@local.test',
            'password' => bcrypt('password'),
            'role' => UserRole::LOGISTIC->value,
            'email_verified_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $driverUserId = $this->firstOrInsertBy('users', ['email' => 'dispatch.demo.driver@local.test'], [
            'name' => 'Dispatch',
            'surname' => 'Driver',
            'email' => 'dispatch.demo.driver@local.test',
            'password' => bcrypt('password'),
            'role' => UserRole::DRIVER->value,
            'email_verified_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $customerId = $this->firstOrInsertBy('customers', ['vat_number' => 'ITDISPATCH00001'], [
            'seller_id' => $logisticUserId,
            'is_occasional_customer' => 0,
            'company_name' => 'Cliente Demo Dispatch',
            'vat_number' => 'ITDISPATCH00001',
            'tax_code' => 'DISPATCH00001',
            'legal_address' => 'Via Demo 1',
            'sdi_code' => 'DEMO123',
            'business_type' => 'generico',
            'sales_email' => 'dispatch.demo.customer@test.local',
            'administrative_email' => 'dispatch.demo.customer@test.local',
            'certified_email' => 'dispatch.demo.customer@test.local',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $siteId = $this->firstOrInsertBy('sites', [
            'customer_id' => $customerId,
            'name' => 'Sede Demo Dispatch',
        ], [
            'customer_id' => $customerId,
            'name' => 'Sede Demo Dispatch',
            'site_type' => 'operativa',
            'is_main' => 1,
            'address' => 'Via Demo Site 10',
            'latitude' => 45.75,
            'longitude' => 11.95,
            'calculated_risk_factor' => 0,
            'days_until_next_withdraw' => 0,
            'has_muletto' => 1,
            'has_electric_pallet_truck' => 1,
            'has_manual_pallet_truck' => 1,
            'other_machines' => '',
            'has_adr_consultant' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $warehouseAId = $this->firstOrInsertBy('warehouses', ['name' => 'Warehouse Demo A'], [
            'name' => 'Warehouse Demo A',
            'address' => 'Via Magazzino A',
            'latitude' => 45.8,
            'longitude' => 11.9,
            'notes' => 'Demo A',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $warehouseBId = $this->firstOrInsertBy('warehouses', ['name' => 'Warehouse Demo B'], [
            'name' => 'Warehouse Demo B',
            'address' => 'Via Magazzino B',
            'latitude' => 45.7,
            'longitude' => 11.85,
            'notes' => 'Demo B',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $trailerId = $this->insertGetIdCompat('trailers', [
            'name' => 'Trailer Demo Dispatch',
            'plate' => 'DMTR001',
            'is_front_cargo' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $vehicleId = $this->insertGetIdCompat('vehicles', [
            'name' => 'Truck Demo Dispatch',
            'plate' => 'DMVH001',
            'driver_id' => $driverUserId,
            'trailer_id' => $trailerId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $vehicleCargoId = $this->insertGetIdCompat('cargos', [
            'name' => 'Cassone Demo Motrice',
            'description' => 'Motrice',
            'is_cargo' => 1,
            'is_long' => 0,
            'total_count' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $trailerCargoId = $this->insertGetIdCompat('cargos', [
            'name' => 'Cassone Demo Rimorchio',
            'description' => 'Rimorchio',
            'is_cargo' => 1,
            'is_long' => 0,
            'total_count' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $journeyId = $this->insertGetIdCompat('journeys', [
            'vehicle_id' => $vehicleId,
            'vehicle_cargo_id' => $vehicleCargoId,
            'trailer_id' => $trailerId,
            'trailer_cargo_id' => $trailerCargoId,
            'driver_id' => $driverUserId,
            'logistics_user_id' => $logisticUserId,
            'status' => JourneyStatus::STATUS_ACTIVE->value,
            'dispatch_status' => DispatchStatus::PENDING->value,
            'is_double_load' => 1,
            'is_temporary_storage' => 0,
            'primary_warehouse_id' => $warehouseAId,
            'secondary_warehouse_id' => $warehouseBId,
            'planned_start_at' => $now,
            'actual_start_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,

            // legacy compatibility columns
            'cargo_for_vehicle_id' => $vehicleCargoId,
            'cargo_for_trailer_id' => $trailerCargoId,
            'logistic_id' => $logisticUserId,
            'dt_start' => $now,
        ]);

        $orderId = $this->insertGetIdCompat('orders', [
            'legacy_code' => 'DISPATCH_DEMO_001',
            'customer_id' => $customerId,
            'site_id' => $siteId,
            'logistics_user_id' => $logisticUserId,
            'journey_id' => $journeyId,
            'status' => OrderStatus::STATUS_PLANNED->value,
            'requested_at' => $now,
            'cargo_location' => 'fulfill',
            'created_at' => $now,
            'updated_at' => $now,
            'logistic_id' => $logisticUserId,
        ]);

        $cerCodeId = $this->firstOrInsertBy('cer_codes', ['code' => '15 01 10*'], [
            'code' => '15 01 10*',
            'description' => 'imballaggi contenenti residui di sostanze pericolose',
            'is_dangerous' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $holderId = $this->firstOrInsertBy('holders', ['name' => 'Cassone 30mc Demo'], [
            'name' => 'Cassone 30mc Demo',
            'description' => 'Demo holder',
            'volume' => 30,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $itemAId = $this->insertGetIdCompat('order_items', [
            'order_id' => $orderId,
            'cer_code_id' => $cerCodeId,
            'holder_id' => $holderId,
            'holder_quantity' => 4,
            'description' => 'Casse filtro vernici',
            'weight_declared' => 600,
            'warehouse_id' => $warehouseAId,
            'status' => 'creato',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $itemBId = $this->insertGetIdCompat('order_items', [
            'order_id' => $orderId,
            'cer_code_id' => $cerCodeId,
            'holder_id' => $holderId,
            'holder_quantity' => 2,
            'description' => 'Contenitori solvente',
            'weight_declared' => 300,
            'warehouse_id' => $warehouseAId,
            'status' => 'creato',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $journeyCargoVehicleId = $this->insertGetIdCompat('journey_cargos', [
            'journey_id' => $journeyId,
            'cargo_id' => $vehicleCargoId,
            'cargo_location' => 'vehicle',
            'warehouse_id' => $warehouseAId,
            'download_sequence' => 1,
            'status' => 'creato',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $journeyCargoTrailerId = $this->insertGetIdCompat('journey_cargos', [
            'journey_id' => $journeyId,
            'cargo_id' => $trailerCargoId,
            'cargo_location' => 'trailer',
            'warehouse_id' => $warehouseBId,
            'download_sequence' => 2,
            'status' => 'creato',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->insertGetIdCompat('journey_load_census_items', [
            'journey_id' => $journeyId,
            'order_item_id' => $itemAId,
            'actual_containers' => 5,
            'total_weight_kg' => 750,
            'source' => 'phone',
            'reported_by_user_id' => $logisticUserId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->insertGetIdCompat('journey_load_census_items', [
            'journey_id' => $journeyId,
            'order_item_id' => $itemBId,
            'actual_containers' => 2,
            'total_weight_kg' => 300,
            'source' => 'phone',
            'reported_by_user_id' => $logisticUserId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $allocationA1Id = $this->insertGetIdCompat('journey_cargo_allocations', [
            'journey_id' => $journeyId,
            'journey_cargo_id' => $journeyCargoVehicleId,
            'order_item_id' => $itemAId,
            'allocated_containers' => 3,
            'estimated_weight_kg' => 450,
            'source' => 'actual',
            'created_by_user_id' => $logisticUserId,
            'updated_by_user_id' => $logisticUserId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $allocationA2Id = $this->insertGetIdCompat('journey_cargo_allocations', [
            'journey_id' => $journeyId,
            'journey_cargo_id' => $journeyCargoTrailerId,
            'order_item_id' => $itemAId,
            'allocated_containers' => 2,
            'estimated_weight_kg' => 300,
            'source' => 'actual',
            'created_by_user_id' => $logisticUserId,
            'updated_by_user_id' => $logisticUserId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $allocationB1Id = $this->insertGetIdCompat('journey_cargo_allocations', [
            'journey_id' => $journeyId,
            'journey_cargo_id' => $journeyCargoVehicleId,
            'order_item_id' => $itemBId,
            'allocated_containers' => 2,
            'estimated_weight_kg' => 300,
            'source' => 'actual',
            'created_by_user_id' => $logisticUserId,
            'updated_by_user_id' => $logisticUserId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->insertGetIdCompat('journey_cargo_unload_instructions', [
            'journey_cargo_allocation_id' => $allocationA1Id,
            'target_warehouse_id' => $warehouseAId,
            'unload_sequence' => 1,
            'instruction_type' => 'simple',
            'created_by_user_id' => $logisticUserId,
            'updated_by_user_id' => $logisticUserId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->insertGetIdCompat('journey_cargo_unload_instructions', [
            'journey_cargo_allocation_id' => $allocationA2Id,
            'target_warehouse_id' => $warehouseBId,
            'unload_sequence' => 2,
            'instruction_type' => 'simple',
            'created_by_user_id' => $logisticUserId,
            'updated_by_user_id' => $logisticUserId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->insertGetIdCompat('journey_cargo_unload_instructions', [
            'journey_cargo_allocation_id' => $allocationB1Id,
            'target_warehouse_id' => $warehouseAId,
            'unload_sequence' => 1,
            'instruction_type' => 'double',
            'proposed_for_transshipment' => 1,
            'transshipment_reason' => 'Congestione magazzino A in fascia oraria critica',
            'created_by_user_id' => $logisticUserId,
            'updated_by_user_id' => $logisticUserId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->insertGetIdCompat('transshipment_needs', [
            'journey_id' => $journeyId,
            'order_item_id' => $itemBId,
            'from_warehouse_id' => $warehouseBId,
            'to_warehouse_id' => $warehouseAId,
            'quantity_containers' => 1,
            'estimated_weight_kg' => 150,
            'status' => 'proposed',
            'notes' => 'Proposta demo: item scaricato nel magazzino non pianificato',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function firstOrInsertBy(string $table, array $where, array $values): int
    {
        $existing = DB::table($table)->where($where)->value('id');
        if ($existing) {
            return (int) $existing;
        }
        return $this->insertGetIdCompat($table, $values);
    }

    private function insertGetIdCompat(string $table, array $values): int
    {
        return (int) DB::table($table)->insertGetId($this->filterKnownColumns($table, $values));
    }

    private function filterKnownColumns(string $table, array $values): array
    {
        $columns = $this->tableColumns($table);
        return array_filter(
            $values,
            fn ($_, $key) => in_array($key, $columns, true),
            ARRAY_FILTER_USE_BOTH
        );
    }

    private function tableColumns(string $table): array
    {
        if (isset($this->columnsCache[$table])) {
            return $this->columnsCache[$table];
        }

        $columns = collect(DB::select("SHOW COLUMNS FROM `{$table}`"))
            ->map(fn ($row) => (string) $row->Field)
            ->all();

        $this->columnsCache[$table] = $columns;
        return $columns;
    }
}
