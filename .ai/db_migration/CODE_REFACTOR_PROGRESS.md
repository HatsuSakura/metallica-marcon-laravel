# Code Refactor Progress

## Phase 6 - Module-by-module code reference refactor

### Batch 1 (historical)
- Added temporary compatibility sync trait (now removed in Batch 4):
  - `app/Models/Concerns/SyncsLegacyColumns.php`
- Enabled old/new column dual-sync in models during transition window:
  - `Customer`, `Site`, `Warehouse`, `Withdraw`, `User`
  - `Order`, `OrderItem`, `Journey`, `JourneyCargo`, `JourneyEvent`
- Introduced canonical columns in model fillables/casts where relevant:
  - `state -> status`
  - `logistic_id -> logistics_user_id`
  - customer/site/warehouse/withdraw/order/order_item canonical fields
- Updated key backend flows to start writing canonical names:
  - `CustomerController` (store/update, counts query)
  - `SiteController` (update mapping to canonical fields)
- Updated NLP customer name resolution to canonical fallback:
  - `HeuristicNlpProvider`
- Updated shared frontend references with fallback (`new || old`):
  - `CustomerAddress.vue`
  - `getIconForSite.js`
  - `getIconForOrder.js`
  - `MapInfoPanel.vue`

### Validation
- PHP syntax check passed on all changed PHP files (`php -l`).

### Notes
- This batch is transitional and backward compatible by design.
- Old columns are still used across many controllers/components and will be migrated in subsequent batches.

### Batch 2-3 (completed)
- Refactored Order/OrderItem flows to canonical fields in core controllers:
  - `OrderController`
  - `DriverOrderController`
  - `WorkerOrderController`
  - `API_DriverOrderUpdateController`
- Refactored Journey/JourneyCargo flows to canonical fields in core controllers/services:
  - `JourneyController`
  - `DriverJourneyController`
  - `JourneyCargoController`
  - `WorkerJourneyCargo`
  - `API_DriverJourneyUpdateController`
  - `JourneyCargoService`
- Key shifts applied:
  - `state -> status` in queries/transitions/updates
  - `logistic_id -> logistics_user_id` mapping in create/update payloads
  - order datetime mapping to canonical (`expected_withdraw_at`, `actual_withdraw_at`)
  - journey cargo canonical fields in service (`cargo_location`, `is_grounded`, `status`, `download_warehouse_id`)
  - customer/site/warehouse relation selects include canonical names while preserving legacy fallbacks

### Validation
- PHP syntax check passed on all changed Batch 2-3 files (`php -l`).

### Remaining work
- Frontend pages/forms still contain many legacy property names; they currently work due compatibility sync but need full canonical adoption.
- Enum class rename (`*State` naming alignment) still pending by design.

### Batch 4 (in progress)
- Removed compatibility sync trait usage from models:
  - `Customer`, `Site`, `Warehouse`, `Withdraw`, `User`
  - `Order`, `OrderItem`, `Journey`, `JourneyCargo`, `JourneyEvent`
- Removed `legacyColumnMap()` methods from the above models.
- Deleted temporary trait file:
  - `app/Models/Concerns/SyncsLegacyColumns.php`
- Canonicalized additional controller references:
  - `API_DriverJourneyStopsController` now writes `actual_start_at`
  - `DriverJourneyController` now orders by `planned_start_at`/`actual_end_at`
- Added first cleanup migration for de-referenced legacy columns:
  - `database/migrations/2026_03_04_230000_drop_legacy_columns_phase1.php`
  - drops (phase 1): `orders.logistic_id`, `orders.expected_withdraw_dt`, `orders.real_withdraw_dt`, `journeys.logistic_id`, `order_items.adr_onu_code`
- Added second cleanup migration for Journey legacy datetime/warehouse columns:
  - `database/migrations/2026_03_04_234000_drop_legacy_journey_columns_phase2.php`
  - drops (phase 2): `journeys.dt_start`, `journeys.dt_end`, `journeys.real_dt_start`, `journeys.real_dt_end`, `journeys.warehouse_id_1`, `journeys.warehouse_download_dt_1`, `journeys.warehouse_id_2`, `journeys.warehouse_download_dt_2`

### Current known pending items (after Batch 4)
- Warehouse flows still use legacy names (`has_ragno`, `ragnista_id`, `machinery_time`, `warehouse_downaload_*`, `selection_time`, `is_ragnabile`) and need dedicated canonical refactor before final legacy-column purge.
- Customer/Site/Warehouse/Withdraw modules still expose mixed legacy naming in payloads and filters; canonical-only cleanup is pending.
- Full “drop legacy columns” migration (global) must wait until the above refs are removed.

### Batch 5 (in progress) - Warehouse naming block
- Canonicalized active Warehouse Order API payloads:
  - `API_WarehouseOrdersController`: `has_crane`, `crane_operator_user_id`, `machinery_time_minutes`
  - `API_WarehouseOrderItemsController` (bulk order-level save): same canonical order fields
- Canonicalized active Warehouse Order frontend flow:
  - `resources/js/Pages/Warehouse/Order/Edit.vue`
  - `resources/js/Pages/Warehouse/Order/Components/OrderItemRow.vue`
- Item-level canonical names now used in active warehouse save payloads:
  - `is_crane_eligible` (was `is_ragnabile`)
  - `selection_duration_minutes` (was `selection_time`)
  - `warehouse_download_worker_id` / `warehouse_download_at` (was `warehouse_downaload_*`)

### Remaining after Batch 5
- Warehouse JourneyCargo screens/controllers still use legacy naming and need dedicated pass.
- Legacy columns for warehouse block cannot be dropped yet until JourneyCargo + residual modules are aligned.

### Batch 6 (in progress) - Site/Customer naming block
- Canonicalized Customer create/edit payload contract (frontend + backend):
  - Frontend forms now submit canonical keys:
    - `isOccasionalCustomer`, `companyName`, `vatNumber`, `taxCode`
    - `legalAddress`, `latitude`, `longitude`
    - `sellerId`, `sdiCode`, `businessType`
    - `salesEmail`, `administrativeEmail`, `certifiedEmail`
  - `CustomerController` store/update validations and writes aligned to canonical DB columns.
- Canonicalized Site boolean/update API contract:
  - `API_SiteBooleanUpdateController`: `name`, `has_electric_pallet_truck`, `has_manual_pallet_truck`.
  - `SiteController` update validation/mapping now canonical (`name`, `site_type`, `address`, `latitude`, `longitude`, risk/withdraw fields).
- Canonicalized active Customer site-tab booleans:
  - `TabSite.vue` now uses `has_electric_pallet_truck` / `has_manual_pallet_truck`.
- Updated cross-module site equipment readers with canonical-first fallback:
  - `Journey/Components/OrderInfo.vue`
  - `JourneyCargo/Components/OrderInfo.vue`
  - `Order/Components/SiteMezziDiSollevamento.vue`

### Batch 7 (completed) - Canonical-only cleanup + legacy drop phase 4
- Removed residual runtime references to legacy columns in active backend/frontend flows:
  - `orders.truck_location -> orders.cargo_location`
  - `journey_cargo_order_item.warehouse_download_id -> download_warehouse_id`
  - `timetables.hours_array -> hours_json` (frontend readers)
  - `users.is_ragnista -> is_crane_operator` (warehouse worker selectors)
  - `journey_cargos.is_grounding -> is_grounded` (UI read path + service payload contract)
- Canonicalized additional runtime logic:
  - `JourneyController` and `DriverJourneyController` order detach/update paths now use `cargo_location`.
  - `JourneyCargoController` destroy path now restores order status with `OrdersState` and clears `cargo_location`.
  - `WithdrawController` + `Withdraw/Create.vue` now use canonical payload:
    - `withdrawn_at`, `residue_percentage`, `created_by_user_id`, `is_manual_entry`.
- Added final drop migration for the completed legacy subset:
  - `database/migrations/2026_03_05_021500_drop_remaining_legacy_columns_phase4.php`
  - drops legacy columns across:
    - `customers`, `journeys`, `journey_cargos`, `journey_cargo_order_item`, `journey_events`
    - `orders`, `order_items`
    - `sites`, `timetables`, `users`, `warehouses`, `withdraws`

### Batch 8 (completed) - Order holders naming block
- Canonicalized `order_holders` runtime contract across backend and frontend:
  - `holder_piene -> filled_holders_count`
  - `holder_vuote -> empty_holders_count`
  - `holder_totale -> total_holders_count`
- Updated model and validations:
  - `app/Models/OrderHolder.php` fillable aligned to canonical fields.
  - `OrderController`, `DriverOrderController`, `WorkerOrderController`, `WorkerJourneyCargo` holder validations aligned.
- Updated active UI flows/components:
  - `Order/Create.vue`, `Order/Edit.vue`
  - `Driver/Order/Edit.vue`
  - Holder and summary components under `Pages/Order/*`, `Pages/Driver/Order/*`, `Pages/Warehouse/*`, `Pages/Driver/Journey/*`.
- Added legacy drop migration for this block:
  - `database/migrations/2026_03_05_040000_drop_legacy_order_holders_columns_phase5.php`
  - drops `order_holders.holder_piene`, `order_holders.holder_vuote`, `order_holders.holder_totale`.
- Git reference:
  - commit `3fed08f` (`refactor: consolidate db standardization, relator purge, and holders canonicalization`)

### Remaining work after Batch 8
- No known runtime references to legacy standardization columns remain in active Order/Journey flows.
- Optional next step: compact/squash migration history for production bootstrap once staging validation is completed.

### Structural cleanup completed
- Removed `Pages/Relator/*` namespace in favor of canonical `Pages/*` modules.
- Removed legacy `Relator*Controller` classes in favor of canonical controllers:
  - `CargoController`, `CustomerController`, `DashboardController`, `OrderController`, `SiteController`, `TrailerController`, `UserController`, `VehicleController`, `WithdrawController`
- Removed `relator.*` route-name usage for migrated modules; route names are now canonical (`order.*`, `customer.*`, `journey.*`, `journeyCargo.*`, etc.).
