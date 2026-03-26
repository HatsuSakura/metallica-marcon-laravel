<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LogisticDispatchWorkspaceApiTest extends TestCase
{
    use DatabaseTransactions;

    private array $columnsCache = [];

    public function test_logistic_can_save_workspace_and_confirm(): void
    {
        $ctx = $this->seedWorkspaceFixture();

        $savePayload = [
            'census' => [
                'items' => [[
                    'order_item_id' => $ctx['order_item_id'],
                    'actual_containers' => 5,
                    'total_weight_kg' => 600,
                    'notes' => '2 casse extra',
                    'source' => 'phone',
                ]],
            ],
            'allocations' => [
                [
                    'journey_cargo_id' => $ctx['journey_cargo_vehicle_id'],
                    'order_item_id' => $ctx['order_item_id'],
                    'allocated_containers' => 3,
                    'source' => 'actual',
                ],
                [
                    'journey_cargo_id' => $ctx['journey_cargo_trailer_id'],
                    'order_item_id' => $ctx['order_item_id'],
                    'allocated_containers' => 2,
                    'source' => 'actual',
                ],
            ],
            'unload_instructions' => [
                [
                    'journey_cargo_id' => $ctx['journey_cargo_vehicle_id'],
                    'order_item_id' => $ctx['order_item_id'],
                    'target_warehouse_id' => $ctx['warehouse_a_id'],
                    'instruction_type' => 'simple',
                    'unload_sequence' => 1,
                ],
                [
                    'journey_cargo_id' => $ctx['journey_cargo_trailer_id'],
                    'order_item_id' => $ctx['order_item_id'],
                    'target_warehouse_id' => $ctx['warehouse_b_id'],
                    'instruction_type' => 'simple',
                    'unload_sequence' => 2,
                ],
            ],
            'mismatch_decisions' => [
                [
                    'journey_cargo_id' => $ctx['journey_cargo_trailer_id'],
                    'order_item_id' => $ctx['order_item_id'],
                    'decision' => 'double_unload',
                    'secondary_warehouse_id' => $ctx['warehouse_a_id'],
                ],
            ],
        ];

        $saveResponse = $this->actingAs($ctx['logistic_user'])
            ->putJson("/api/logistic/dispatch/{$ctx['journey_id']}/workspace", $savePayload)
            ->assertOk();

        $this->assertDatabaseHas('journey_cargo_allocations', [
            'journey_id' => $ctx['journey_id'],
            'order_item_id' => $ctx['order_item_id'],
            'journey_cargo_id' => $ctx['journey_cargo_vehicle_id'],
            'source' => 'actual',
            'allocated_containers' => 3,
        ]);

        $this->actingAs($ctx['logistic_user'])
            ->postJson("/api/logistic/dispatch/{$ctx['journey_id']}/confirm", ['notes' => 'ok'])
            ->assertOk()
            ->assertJsonPath('journey.dispatch_status', 'in_progress');
    }

    public function test_save_workspace_rejects_split_over_census(): void
    {
        $ctx = $this->seedWorkspaceFixture();

        $payload = [
            'census' => [
                'items' => [[
                    'order_item_id' => $ctx['order_item_id'],
                    'actual_containers' => 1,
                    'total_weight_kg' => 100,
                ]],
            ],
            'allocations' => [[
                'journey_cargo_id' => $ctx['journey_cargo_vehicle_id'],
                'order_item_id' => $ctx['order_item_id'],
                'allocated_containers' => 2,
                'source' => 'actual',
            ]],
        ];

        $this->actingAs($ctx['logistic_user'])
            ->putJson("/api/logistic/dispatch/{$ctx['journey_id']}/workspace", $payload)
            ->assertStatus(422);
    }

    public function test_non_logistic_cannot_save_workspace(): void
    {
        $ctx = $this->seedWorkspaceFixture();

        $payload = [
            'census' => [
                'items' => [[
                    'order_item_id' => $ctx['order_item_id'],
                    'actual_containers' => 1,
                ]],
            ],
        ];

        $this->actingAs($ctx['warehouse_manager_user'])
            ->putJson("/api/logistic/dispatch/{$ctx['journey_id']}/workspace", $payload)
            ->assertForbidden();
    }

    public function test_non_logistic_cannot_confirm_or_close_or_approve_transshipment(): void
    {
        $ctx = $this->seedWorkspaceFixture();

        $transshipmentId = DB::table('transshipment_needs')->insertGetId([
            'journey_id' => $ctx['journey_id'],
            'order_item_id' => $ctx['order_item_id'],
            'from_warehouse_id' => $ctx['warehouse_a_id'],
            'to_warehouse_id' => $ctx['warehouse_b_id'],
            'quantity_containers' => 1,
            'status' => 'proposed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($ctx['warehouse_manager_user'])
            ->postJson("/api/logistic/dispatch/{$ctx['journey_id']}/confirm", [])
            ->assertForbidden();

        $this->actingAs($ctx['warehouse_manager_user'])
            ->postJson("/api/logistic/dispatch/{$ctx['journey_id']}/close", [])
            ->assertForbidden();

        $this->actingAs($ctx['warehouse_manager_user'])
            ->postJson("/api/logistic/transshipments/{$transshipmentId}/approve", [])
            ->assertForbidden();
    }

    public function test_worker_can_append_warehouse_exception_but_not_transshipment_proposal(): void
    {
        $ctx = $this->seedWorkspaceFixture();

        $this->actingAs($ctx['warehouse_worker_user'])
            ->postJson("/api/logistic/dispatch/{$ctx['journey_id']}/events", [
                'event' => 'warehouse_exception_logged',
                'payload' => [
                    'order_item_id' => $ctx['order_item_id'],
                    'reason' => 'test',
                ],
            ])
            ->assertStatus(201);

        $this->actingAs($ctx['warehouse_worker_user'])
            ->postJson("/api/logistic/dispatch/{$ctx['journey_id']}/events", [
                'event' => 'transshipment_proposed',
                'payload' => [
                    'order_item_id' => $ctx['order_item_id'],
                    'from_warehouse_id' => $ctx['warehouse_a_id'],
                    'to_warehouse_id' => $ctx['warehouse_b_id'],
                    'containers' => 1,
                ],
            ])
            ->assertForbidden();
    }

    public function test_transshipment_proposed_requires_order_item_in_journey(): void
    {
        $ctx = $this->seedWorkspaceFixture();
        $alien = $this->seedAlienOrderItem();

        $this->actingAs($ctx['logistic_user'])
            ->postJson("/api/logistic/dispatch/{$ctx['journey_id']}/events", [
                'event' => 'transshipment_proposed',
                'payload' => [
                    'order_item_id' => $alien['order_item_id'],
                    'from_warehouse_id' => $ctx['warehouse_a_id'],
                    'to_warehouse_id' => $ctx['warehouse_b_id'],
                    'containers' => 1,
                ],
            ])
            ->assertStatus(422);
    }

    public function test_confirm_rejects_incomplete_split(): void
    {
        $ctx = $this->seedWorkspaceFixture();

        $payload = [
            'census' => [
                'items' => [[
                    'order_item_id' => $ctx['order_item_id'],
                    'actual_containers' => 5,
                    'total_weight_kg' => 600,
                ]],
            ],
            'allocations' => [[
                'journey_cargo_id' => $ctx['journey_cargo_vehicle_id'],
                'order_item_id' => $ctx['order_item_id'],
                'allocated_containers' => 3,
                'source' => 'actual',
            ]],
            'unload_instructions' => [[
                'journey_cargo_id' => $ctx['journey_cargo_vehicle_id'],
                'order_item_id' => $ctx['order_item_id'],
                'target_warehouse_id' => $ctx['warehouse_a_id'],
                'instruction_type' => 'simple',
                'unload_sequence' => 1,
            ]],
        ];

        $this->actingAs($ctx['logistic_user'])
            ->putJson("/api/logistic/dispatch/{$ctx['journey_id']}/workspace", $payload)
            ->assertOk();

        $this->actingAs($ctx['logistic_user'])
            ->postJson("/api/logistic/dispatch/{$ctx['journey_id']}/confirm", [])
            ->assertStatus(422);
    }

    public function test_close_requires_dispatch_in_progress_or_on_hold(): void
    {
        $ctx = $this->seedWorkspaceFixture();

        $this->actingAs($ctx['logistic_user'])
            ->postJson("/api/logistic/dispatch/{$ctx['journey_id']}/close", [])
            ->assertStatus(422);
    }

    private function seedWorkspaceFixture(): array
    {
        $now = now();
        $suffix = uniqid('', true);

        $logisticUserId = $this->insertGetIdCompat('users', [
            'name' => 'Log',
            'email' => "logistic-{$suffix}@example.test",
            'password' => bcrypt('password'),
            'role' => UserRole::LOGISTIC->value,
            'email_verified_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $driverUserId = $this->insertGetIdCompat('users', [
            'name' => 'Driver',
            'email' => "driver-{$suffix}@example.test",
            'password' => bcrypt('password'),
            'role' => UserRole::DRIVER->value,
            'email_verified_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $warehouseManagerId = $this->insertGetIdCompat('users', [
            'name' => 'WM',
            'email' => "wm-{$suffix}@example.test",
            'password' => bcrypt('password'),
            'role' => UserRole::WAREHOUSE_MANAGER->value,
            'email_verified_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $warehouseWorkerId = $this->insertGetIdCompat('users', [
            'name' => 'WW',
            'email' => "ww-{$suffix}@example.test",
            'password' => bcrypt('password'),
            'role' => UserRole::WAREHOUSE_WORKER->value,
            'email_verified_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $customerId = $this->insertGetIdCompat('customers', [
            'seller_id' => $logisticUserId,
            'company_name' => 'Cliente Test',
            'ragione_sociale' => 'Cliente Test',
            'vat_number' => 'IT12345678901',
            'partita_iva' => 'IT12345678901',
            'tax_code' => 'RSSMRA80A01H501Z',
            'codice_fiscale' => 'RSSMRA80A01H501Z',
            'legal_address' => 'Via Roma 1',
            'indirizzo_legale' => 'Via Roma 1',
            'sdi_code' => 'SDI1234',
            'codice_sdi' => 'SDI1234',
            'sales_email' => 'sales@test.local',
            'email_commerciale' => 'sales@test.local',
            'administrative_email' => 'admin@test.local',
            'email_amministrativa' => 'admin@test.local',
            'certified_email' => 'pec@test.local',
            'pec' => 'pec@test.local',
            'customer_occasionale' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $siteId = $this->insertGetIdCompat('sites', [
            'customer_id' => $customerId,
            'name' => 'Sede Test',
            'denominazione' => 'Sede Test',
            'site_type' => 'operativa',
            'tipologia' => 'operativa',
            'is_main' => 1,
            'address' => 'Via Milano 10',
            'indirizzo' => 'Via Milano 10',
            'latitude' => 45.1,
            'lat' => 45.1,
            'longitude' => 11.1,
            'lng' => 11.1,
            'calculated_risk_factor' => 0,
            'fattore_rischio_calcolato' => 0,
            'days_until_next_withdraw' => 0,
            'giorni_prossimo_ritiro' => 0,
            'has_muletto' => 0,
            'has_electric_pallet_truck' => 0,
            'has_manual_pallet_truck' => 0,
            'other_machines' => '',
            'has_adr_consultant' => 0,
            'notes' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $warehouseAId = $this->insertGetIdCompat('warehouses', [
            'name' => 'Warehouse A',
            'denominazione' => 'Warehouse A',
            'address' => 'A Street',
            'indirizzo' => 'A Street',
            'latitude' => 45.2,
            'lat' => 45.2,
            'longitude' => 11.2,
            'lng' => 11.2,
            'notes' => 'A',
            'note' => 'A',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $warehouseBId = $this->insertGetIdCompat('warehouses', [
            'name' => 'Warehouse B',
            'denominazione' => 'Warehouse B',
            'address' => 'B Street',
            'indirizzo' => 'B Street',
            'latitude' => 45.3,
            'lat' => 45.3,
            'longitude' => 11.3,
            'lng' => 11.3,
            'notes' => 'B',
            'note' => 'B',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $trailerId = $this->insertGetIdCompat('trailers', [
            'name' => 'Trailer Test',
            'plate' => 'TR123',
            'is_front_cargo' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $vehicleId = $this->insertGetIdCompat('vehicles', [
            'name' => 'Truck Test',
            'plate' => 'VH123',
            'driver_id' => $driverUserId,
            'trailer_id' => $trailerId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $cargoVehicleId = $this->insertGetIdCompat('cargos', [
            'name' => 'Cargo Vehicle',
            'description' => 'vehicle',
            'is_cargo' => 1,
            'is_long' => 0,
            'total_count' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $cargoTrailerId = $this->insertGetIdCompat('cargos', [
            'name' => 'Cargo Trailer',
            'description' => 'trailer',
            'is_cargo' => 1,
            'is_long' => 0,
            'total_count' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $journeyId = $this->insertGetIdCompat('journeys', [
            'vehicle_id' => $vehicleId,
            'vehicle_cargo_id' => $cargoVehicleId,
            'cargo_for_vehicle_id' => $cargoVehicleId,
            'trailer_id' => $trailerId,
            'trailer_cargo_id' => $cargoTrailerId,
            'cargo_for_trailer_id' => $cargoTrailerId,
            'driver_id' => $driverUserId,
            'logistics_user_id' => $logisticUserId,
            'logistic_id' => $logisticUserId,
            'status' => 'attivo',
            'dispatch_status' => 'pending',
            'is_double_load' => 0,
            'is_temporary_storage' => 0,
            'dt_start' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $orderId = $this->insertGetIdCompat('orders', [
            'customer_id' => $customerId,
            'site_id' => $siteId,
            'logistics_user_id' => $logisticUserId,
            'logistic_id' => $logisticUserId,
            'journey_id' => $journeyId,
            'status' => 'pianificato',
            'requested_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $cerCodeId = $this->insertGetIdCompat('cer_codes', [
            'code' => '12 34 56',
            'description' => 'CER TEST',
            'is_dangerous' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $holderId = $this->insertGetIdCompat('holders', [
            'name' => 'Cassone',
            'description' => 'holder',
            'volume' => 1.0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $orderItemId = $this->insertGetIdCompat('order_items', [
            'order_id' => $orderId,
            'cer_code_id' => $cerCodeId,
            'holder_id' => $holderId,
            'holder_quantity' => 4,
            'description' => 'Item test',
            'weight_declared' => 480.0,
            'warehouse_id' => $warehouseAId,
            'status' => 'creato',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $journeyCargoVehicleId = $this->insertGetIdCompat('journey_cargos', [
            'journey_id' => $journeyId,
            'cargo_id' => $cargoVehicleId,
            'cargo_location' => 'vehicle',
            'warehouse_id' => $warehouseAId,
            'download_sequence' => 1,
            'status' => 'creato',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $journeyCargoTrailerId = $this->insertGetIdCompat('journey_cargos', [
            'journey_id' => $journeyId,
            'cargo_id' => $cargoTrailerId,
            'cargo_location' => 'trailer',
            'warehouse_id' => $warehouseBId,
            'download_sequence' => 2,
            'status' => 'creato',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'journey_id' => $journeyId,
            'order_item_id' => $orderItemId,
            'journey_cargo_vehicle_id' => $journeyCargoVehicleId,
            'journey_cargo_trailer_id' => $journeyCargoTrailerId,
            'warehouse_a_id' => $warehouseAId,
            'warehouse_b_id' => $warehouseBId,
            'logistic_user' => \App\Models\User::findOrFail($logisticUserId),
            'warehouse_manager_user' => \App\Models\User::findOrFail($warehouseManagerId),
            'warehouse_worker_user' => \App\Models\User::findOrFail($warehouseWorkerId),
        ];
    }

    private function seedAlienOrderItem(): array
    {
        $now = now();
        $orderId = $this->insertGetIdCompat('orders', [
            'customer_id' => DB::table('customers')->value('id'),
            'site_id' => DB::table('sites')->value('id'),
            'logistics_user_id' => DB::table('users')->where('role', UserRole::LOGISTIC->value)->value('id'),
            'logistic_id' => DB::table('users')->where('role', UserRole::LOGISTIC->value)->value('id'),
            'status' => 'creato',
            'requested_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $itemId = $this->insertGetIdCompat('order_items', [
            'order_id' => $orderId,
            'cer_code_id' => DB::table('cer_codes')->value('id'),
            'holder_id' => DB::table('holders')->value('id'),
            'holder_quantity' => 1,
            'description' => 'Alien item',
            'weight_declared' => 10,
            'warehouse_id' => DB::table('warehouses')->value('id'),
            'status' => 'creato',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return ['order_item_id' => $itemId];
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
