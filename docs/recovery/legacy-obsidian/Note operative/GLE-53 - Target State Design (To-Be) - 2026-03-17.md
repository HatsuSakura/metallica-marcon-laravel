---
title: GLE-53 - Target State Design (To-Be)
date: 2026-03-17
project: GLE
issue: GLE-53
status: historical
depends_on:
  - GLE-53 - Process Mapping (As-Is) - 2026-03-17
recovered_on: 2026-10-30
recovery_status: historical
---

> **Nota recuperata — storica, non procedura corrente.** Origine: vecchio vault Obsidian; recupero 2026-10-30. Per lo stato attuale consultare [sviluppi e riconciliazione](../../../development-status.md). Non eseguire comandi di deploy, migrazione o modifica dati sulla sola base di questa nota.


# Design decision
This design is in a dedicated note (not merged into As-Is), so:
- As-Is remains an immutable factual baseline.
- To-Be can evolve with stakeholder feedback without rewriting historical mapping.

# Objective
Implement an office-driven dispatch workflow after customer stops completion:
- Driver finishes stops.
- Driver calls office.
- Logistics operator decides unload strategy (single, double, temporary storage, overnight hold).
- Warehouse execution follows that decision.

# Target process (To-Be)
## Phase A - Delivery execution (Driver)
1. Driver executes stop sequence as now.
2. When last customer stop is completed, journey enters `awaiting_dispatch` (new state).
3. Driver sees "attendere istruzioni logistica" + optional button to confirm call done.

## Phase B - Dispatch decision (Logistics office)
1. Logistics opens a dedicated board "Dispatch post-consegna".
2. For each journey in `awaiting_dispatch`, operator sees:
- journey summary
- stop completion context
- journey cargo actual composition (truck/trailer/fulfill)
- anomalies reported by driver (if any)
3. Operator chooses strategy:
- `single_unload`
- `double_unload`
- `temporary_storage`
- `overnight_hold`
4. Operator assigns:
- primary warehouse (+ scheduled datetime)
- optional secondary warehouse (+ scheduled datetime)
- per-item download destination if double-load/mixed
5. Operator confirms dispatch plan.
6. Journey transitions to `dispatch_planned`.

## Phase C - Warehouse execution
1. Warehouse uses existing JourneyCargo operational flow (or successor UI) with prefilled dispatch plan.
2. Journey transitions:
- `dispatch_planned -> unloading_in_progress`
- `unloading_in_progress -> eseguito` (when unload completed)
- eventually `eseguito -> chiuso` with existing closure rules.

# State model proposal
## Journey states
Existing:
- `creato`, `attivo`, `eseguito`, `chiuso`

Add:
- `awaiting_dispatch` (post-stops, waiting office decision)
- `dispatch_planned` (office decision confirmed)
- `unloading_in_progress` (warehouse operations active)
- `overnight_hold` (paused till next day, resumable)

Suggested transitions:
- `creato -> attivo`
- `attivo -> awaiting_dispatch` (auto at last stop done)
- `awaiting_dispatch -> dispatch_planned`
- `awaiting_dispatch -> overnight_hold`
- `overnight_hold -> dispatch_planned`
- `dispatch_planned -> unloading_in_progress`
- `unloading_in_progress -> eseguito`
- `eseguito -> chiuso`

# DB additions (minimal, mapped to current schema)
## 1) journeys table
Add columns:
- `dispatch_strategy` enum: `single_unload|double_unload|temporary_storage|overnight_hold`
- `dispatch_notes` text nullable
- `dispatch_decided_by_user_id` FK users nullable
- `dispatch_decided_at` datetime nullable
- `dispatch_hold_until_at` datetime nullable

Keep and reuse existing columns:
- `is_double_load`, `is_temporary_storage`
- `primary_warehouse_id`, `primary_warehouse_download_at`
- `secondary_warehouse_id`, `secondary_warehouse_download_at`

## 2) journey_events payload conventions
No schema change required; standardize event payload keys:
- `event: dispatch_decided`
- `event: dispatch_hold_set`
- `event: dispatch_replanned`
- include strategy, operator id, old/new plan summary.

## 3) Optional (if needed after MVP)
`journey_dispatch_item_overrides` table only if pivot-based logic becomes too hard to audit.
For MVP, prefer reusing `journey_cargo_order_item` (`is_double_load`, `download_warehouse_id`).

# API / endpoints design
## Logistics dispatch API (new)
- `GET /api/logistics/journeys/awaiting-dispatch`
  - list journeys in `awaiting_dispatch|overnight_hold`
- `GET /api/logistics/journeys/{journey}/dispatch-context`
  - journey, stops, orders, items, cargo positions, current unload assignments
- `PUT /api/logistics/journeys/{journey}/dispatch-plan`
  - validates strategy + warehouses + dates + per-item overrides
  - sets `dispatch_*` columns, warehouse columns, flags
  - transitions state to `dispatch_planned` (or `overnight_hold`)
- `POST /api/logistics/journeys/{journey}/dispatch-hold`
  - set hold until datetime and reason
- `POST /api/logistics/journeys/{journey}/dispatch-resume`
  - move `overnight_hold -> awaiting_dispatch` or directly `dispatch_planned`

## Driver stop API changes (existing controller)
In `complete()` flow:
- when no next planned stop exists:
  - set journey state `awaiting_dispatch`
  - write event `all_customer_stops_completed`
  - do not set `eseguito`

## Warehouse progression APIs
- Reuse existing JourneyCargo and Warehouse APIs.
- Add state-transition endpoint or service method to move:
  - `dispatch_planned -> unloading_in_progress`
  - `unloading_in_progress -> eseguito`

# UI design mapped to current frontend
## 1) New page (GLE-53 core)
`resources/js/Pages/Logistic/JourneyDispatch/Index.vue`
- left: journeys queue (`awaiting_dispatch`, `overnight_hold`)
- center: selected journey detail + cargo matrix
- right: dispatch decision panel

## 2) Driver journey pages
- Keep current behavior in `Driver/Journey/Edit.vue` but show clear banner:
  - `awaiting_dispatch`: "Istruzioni logistica in preparazione"
  - `dispatch_planned`: show destination summary
  - `overnight_hold`: show hold until date/time

## 3) Reuse existing cargo components where possible
- Pull logic from `JourneyCargo/Create.vue` + `JourneyCargo/Edit.vue` into reusable composables/components
- avoid parallel duplicated business logic in two places

# Validation rules (core)
1. `single_unload`: requires primary warehouse only.
2. `double_unload`: requires primary + secondary warehouses and different ids.
3. `temporary_storage`: requires primary warehouse, may postpone secondary.
4. `overnight_hold`: requires hold datetime in future.
5. Per-item override consistency:
- item can map only to warehouses in the chosen dispatch plan.
- if `is_double_load = false`, `download_warehouse_id` must equal cargo’s target warehouse.

# Migration plan (safe incremental)
## Step 1 - Schema
- add `dispatch_*` columns and new enum values in `journeys.status`
- add indexes for board query (`status`, `driver_id`, `actual_end_at`)

## Step 2 - Backend behavior
- update driver stop completion logic for last-stop transition to `awaiting_dispatch`
- implement logistics dispatch endpoints + policy checks

## Step 3 - Frontend
- introduce dispatch board page
- adapt driver page state banners
- keep old JourneyCargo pages operational during transition

## Step 4 - Backfill and compatibility
- For currently active journeys with all stops completed and not unloaded, backfill to `awaiting_dispatch`
- preserve existing `journey_cargo_order_item` data

## Step 5 - Hardening
- add feature tests for transitions and plan validation
- add audit trail assertions on `journey_events`

# Risks and mitigations
1. Duplicate logic between JourneyCargo old pages and new dispatch board.
- Mitigation: extract shared server-side service for dispatch plan persistence.

2. Ambiguous ownership between logistics and warehouse roles.
- Mitigation: strict policy gates per transition.

3. State drift (journey says `dispatch_planned` but cargo data incomplete).
- Mitigation: transactional update for journey + cargo/pivot writes.

# Recommended first implementation slice (MVP)
1. Add `awaiting_dispatch` + `dispatch_planned` states.
2. Auto-transition to `awaiting_dispatch` at last stop completion.
3. Build minimal dispatch board with:
- strategy selection
- primary/secondary warehouse
- save plan
4. Keep per-item double-load editing in existing JourneyCargo Edit for MVP.
5. Integrate per-item overrides directly into dispatch board in phase 2.

# Open decisions for next reasoning session
1. Should `overnight_hold` be separate status or a flag on `awaiting_dispatch`?
2. Should dispatch decision be reversible after warehouse starts unload?
3. Do we want mandatory driver call acknowledgment event before dispatch planning?
4. Should `is_temporary_storage` remain a boolean or be absorbed by `dispatch_strategy` only?


# Implementation checklist (authoritative) - PR slices

## Strategic decision (confirmed)
- Legacy `JourneyCargo` planning flow is **not** to be kept aligned.
- It will be **decommissioned and removed**.
- No backward-compatibility layer is required for non-production flows.

This supersedes any previous note text suggesting temporary coexistence as a long-term strategy.

## PR-01 - Schema and enums (foundation)
### Goal
Introduce target dispatch lifecycle at DB level.

### Changes
- Extend `JourneyStatus` with:
  - `awaiting_dispatch`
  - `dispatch_planned`
  - `unloading_in_progress`
  - `overnight_hold`
- Add `journeys` columns:
  - `dispatch_strategy` enum (`single_unload|double_unload|temporary_storage|overnight_hold`)
  - `dispatch_notes` text nullable
  - `dispatch_decided_by_user_id` nullable FK users
  - `dispatch_decided_at` nullable datetime
  - `dispatch_hold_until_at` nullable datetime
- Add indexes:
  - `(status, driver_id)`
  - `(status, actual_end_at)`

### Deliverables
- migrations + enum updates
- zero behavior changes yet
- tests for migration integrity

### Done when
- fresh migrate works
- current app boots with new enum values

## PR-02 - Backend state machine and services
### Goal
Centralize journey dispatch transitions in backend services.

### Changes
- Create `JourneyDispatchService` (single source of truth) with methods:
  - `markAwaitingDispatch(journey)`
  - `planDispatch(journey, payload, user)`
  - `setOvernightHold(journey, payload, user)`
  - `resumeFromHold(journey, user)`
  - `startUnloading(journey, user)`
  - `completeUnloading(journey, user)`
- Update driver stop completion logic:
  - in `API_DriverJourneyStopsController@complete`, when last stop done -> `awaiting_dispatch`
  - log `journey_events` payload `all_customer_stops_completed`
- Add policy checks for logistics/warehouse transitions.

### Deliverables
- service class + controller integration
- transition tests (happy path + invalid transitions)

### Done when
- no direct ad-hoc status writes outside service for dispatch states

## PR-03 - Logistics Dispatch API
### Goal
Expose dedicated office APIs for post-stop dispatch decisions.

### Endpoints
- `GET /api/logistics/journeys/awaiting-dispatch`
- `GET /api/logistics/journeys/{journey}/dispatch-context`
- `PUT /api/logistics/journeys/{journey}/dispatch-plan`
- `POST /api/logistics/journeys/{journey}/dispatch-hold`
- `POST /api/logistics/journeys/{journey}/dispatch-resume`
- `POST /api/logistics/journeys/{journey}/start-unloading`
- `POST /api/logistics/journeys/{journey}/complete-unloading`

### Validation rules
- `single_unload`: primary required, secondary forbidden/null
- `double_unload`: primary+secondary required and distinct
- `temporary_storage`: primary required, secondary optional
- `overnight_hold`: hold datetime required and future
- per-item `download_warehouse_id` must belong to selected plan warehouses

### Deliverables
- request validators
- API feature tests for auth + validation + transitions

### Done when
- full dispatch lifecycle executable from API only

## PR-04 - New Logistics UI board (GLE-53 core)
### Goal
Build the real office dashboard for decision-making.

### New pages/components
- `resources/js/Pages/Logistic/JourneyDispatch/Index.vue`
- components:
  - queue panel (`awaiting_dispatch`, `overnight_hold`)
  - journey context panel (stops/orders/items/cargo placement)
  - dispatch decision form panel

### UX requirements
- fast triage list with badges/state/time
- editable dispatch strategy + warehouses + schedule
- hold/resume actions
- clear action log snippet from `journey_events`

### Deliverables
- full UI connected to PR-03 APIs
- basic E2E smoke test (or equivalent component+API integration test)

### Done when
- logistics can dispatch a finished journey without touching old screens

## PR-05 - Driver and Warehouse UI alignment
### Goal
Align existing UIs to new state machine.

### Changes
- Driver journey pages:
  - show clear banners for `awaiting_dispatch`, `dispatch_planned`, `overnight_hold`
  - prevent driver from directly finalizing unload strategy
- Warehouse operational pages:
  - consume `dispatch_planned` data and transition to `unloading_in_progress`/`eseguito`
- Remove/disable any driver-side controls that set warehouse plan fields directly.

### Deliverables
- updated Driver/Journey pages
- updated warehouse action entry points
- regression tests on role permissions

### Done when
- role boundaries are enforced in UI and backend

## PR-06 - Legacy flow decommission and cleanup
### Goal
Destroy deprecated JourneyCargo planning flow.

### Changes
- Remove old entry points from dashboard:
  - `DashViaggioSimpleList` links to `journeyCargo.create/edit` for planning
- Remove obsolete pages/controllers/routes used only by old planning flow
  - `JourneyCargo/Create.vue`, `JourneyCargo/Edit.vue` (if no longer used)
  - related planning-only controller actions/routes
- Keep only pieces still required for warehouse execution; move them behind new dispatch flow if needed.
- Delete dead code and stale comments.

### Deliverables
- route cleanup
- controller/page deletions
- docs update in Obsidian + changelog entry

### Done when
- no UI path or API path uses legacy planning flow
- search for `journeyCargo.create` / `journeyCargo.edit` planning usage returns none

## PR-07 - Data backfill and hardening
### Goal
Safely transition in-progress data and stabilize release.

### Changes
- Backfill script:
  - identify journeys with all customer stops completed and unresolved dispatch
  - set `status = awaiting_dispatch`
- Add auditing consistency checks (event presence for state moves).
- Add operational runbook:
  - rollback procedure
  - manual correction commands

### Deliverables
- one-off artisan command for backfill
- feature tests for backfill idempotency
- runbook note

### Done when
- staging data migrated and verified
- production rollout checklist signed

## Execution order
1. PR-01
2. PR-02
3. PR-03
4. PR-04
5. PR-05
6. PR-06
7. PR-07

## Non-goals for this epic
- preserving parity with never-released legacy planning UX
- maintaining dual dispatch systems in parallel beyond short technical transition windows

## Implementation strategy decision (2026-03-17)

Method decisions have been formalized in repository ADR:
- `.ai/decisions/2026-03-17-logistic-dispatch-implementation-strategy.md`

Documentation summary:
- Keep `Dashboard/Logistic.vue` as entry page only.
- Build the new workflow as a dedicated Dispatch module (`Pages/LogisticDispatch/*`).
- Use explicit command-style endpoints for transitions.
- Remove and decommission old non-production warehouse/journey-cargo process (no compatibility layer).

## Implementation progress note (2026-03-17)

Started implementation on dedicated branch `feature/logistic-dispatch-board` from `main` (parallel-safe with `feature/gle-55-56-63`).

Initial scaffold delivered:
- New module pages: `Pages/LogisticDispatch/Board.vue`, `Pages/LogisticDispatch/Show.vue`
- New controllers/services: `LogisticDispatchController`, `API_LogisticDispatchController`, `JourneyDispatchPlanService`
- New routes:
  - Web: `/logistic/dispatch`, `/logistic/dispatch/{journey}`
  - API commands: `/api/logistic/dispatch/{journey}/plan|hold|resume|complete`
- Dashboard logistic now links to Dispatch Board.
