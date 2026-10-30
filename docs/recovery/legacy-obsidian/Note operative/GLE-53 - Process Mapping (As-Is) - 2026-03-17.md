---
title: GLE-53 - Process Mapping (As-Is)
date: 2026-03-17
project: GLE
issue: GLE-53
status: historical
source: codebase scan (models, migrations, controllers, frontend)
recovered_on: 2026-10-30
recovery_status: historical
---

> **Nota recuperata — storica, non procedura corrente.** Origine: vecchio vault Obsidian; recupero 2026-10-30. Per lo stato attuale consultare [sviluppi e riconciliazione](../../../development-status.md). Non eseguire comandi di deploy, migrazione o modifica dati sulla sola base di questa nota.


# Objective
Map the current real process (DB + code + UI) for journey end, warehouse decision, and unload management.

# Current data model (effective)
## Journey lifecycle and unload fields
- `journeys.status`: `creato -> attivo -> eseguito -> chiuso`.
- `journeys` contains unload planning fields:
  - `is_double_load`
  - `is_temporary_storage`
  - `primary_warehouse_id`, `primary_warehouse_download_at`
  - `secondary_warehouse_id`, `secondary_warehouse_download_at`
- Canonical field migration/backfill confirmed in `2026_03_04_120000_add_canonical_compatibility_columns.php`.
- Model confirms fields/casts/fillable.

References:
- `app/Models/Journey.php`
- `database/migrations/2026_03_04_120000_add_canonical_compatibility_columns.php`

## Driver stop execution model
- `journey_stops` with sequence + status (`planned|in_progress|done|skipped|cancelled`).
- `journey_stop_orders` binds each order to one stop in same journey.
- `journey_events` logs actions (`status`, `payload`, `created_by_user_id`).

References:
- `database/migrations/2026_01_22_131038_create_journey_stops_table.php`
- `database/migrations/2026_01_22_131118_create_journey_stop_orders_table.php`
- `database/migrations/2026_01_22_131148_create_journey_events_table.php`

## Legacy/operational cargo-unload model still active
- `journey_cargos` defines truck/trailer cargo destination warehouse and unload sequence.
- `journey_cargo_order_item` pivot defines per-item:
  - `is_double_load`
  - `download_warehouse_id` (canonical alias of legacy `warehouse_download_id`)
- This is the actual place where detailed unload decisions are managed today.

References:
- `app/Models/JourneyCargo.php`
- `database/migrations/2025_04_03_134007_create_journey_cargo_order_item_table.php`
- `database/migrations/2026_03_04_120000_add_canonical_compatibility_columns.php`

## Order planning gate (new docs-aware flow)
- `orders.status` includes `pronto`.
- Planning transition uses `creato -> pronto -> pianificato`.
- `orders.documents_state` (`not_generated|generating|generated|failed`) gates planning in Journey creation/update.

References:
- `app/Enums/OrderStatus.php`
- `app/Enums/OrderDocumentsState.php`
- `database/migrations/2026_03_14_120000_add_order_documents_columns.php`
- `database/migrations/2026_03_14_130000_add_ready_to_orders_status_enum.php`
- `app/Http/Controllers/JourneyController.php`

# Current process (as implemented)
## 1) Logistics plans journey
- Logistics can select only orders in `pronto` and with generated documents.
- Journey creation/update writes order assignment and `cargo_location` (`vehicle|trailer|fulfill`).
- Stops are created/updated (`customer` and optional `technical`) with ordered sequence.

References:
- `app/Http/Controllers/JourneyController.php`

## 2) Driver executes customer stops
- Driver starts journey via `/api/driver/journeys/{journey}/start`.
- Driver handles stop actions via:
  - `PUT /api/driver/journeys/{journey}/stops/{stop}/complete`
  - `PUT /api/driver/journeys/{journey}/stops/{stop}/skip`
  - `POST /api/driver/journeys/{journey}/stops/technical`
- Stop completion moves next planned stop to `in_progress`.

References:
- `routes/web.php`
- `app/Http/Controllers/API_DriverJourneyStopsController.php`

## 3) End-of-customer-stops behavior today
- Driver UI now explicitly tells driver to call logistics for unload instructions.
- There is no dedicated operational workflow in driver page for choosing warehouses.

Reference:
- `resources/js/Pages/Driver/Journey/Edit.vue`

## 4) Actual unload decision currently done in old flow
- Dashboard links to JourneyCargo create/edit/manage pages.
- Logistics (or warehouse flow) sets truck/trailer warehouse, sequence, grounding and per-item double-load destination.
- This remains the concrete operational implementation of unload orchestration.

References:
- `resources/js/Pages/Dashboard/Components/DashViaggioSimpleList.vue`
- `resources/js/Pages/JourneyCargo/Create.vue`
- `resources/js/Pages/JourneyCargo/Edit.vue`
- `app/Http/Controllers/JourneyCargoController.php`
- `app/Services/JourneyCargoService.php`

# Gaps versus required real-world process
1. Missing explicit state for "driver finished stops, waiting office logistics decision".
2. Missing explicit handling for "truck stopped, continue next day".
3. `DriverJourneyController@update` still allows setting warehouse unload fields and directly forces journey `status = eseguito`.
4. Office decision context (actual cargo placement changes reported by driver) is not first-class in journey lifecycle; it is indirectly handled only through JourneyCargo screens.
5. No single canonical "logistics dispatch board" specialized for this post-stop decision loop.

Reference:
- `app/Http/Controllers/DriverJourneyController.php`

# Practical conclusion
The codebase already has most data structures needed to support the desired office-driven unload dispatch process, but they are split between:
- new driver-stop lifecycle (`journey_stops`)
- older cargo-unload operational pages (`JourneyCargo/*`)

GLE-53 should unify these under one logistics dispatch workflow and state machine, keeping driver UI simple (execute stops + call office + receive instruction).
