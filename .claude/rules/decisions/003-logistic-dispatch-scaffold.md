# Decision Note 2026-03-17 - Logistic Dispatch PR-01 Scaffold

## Context
- Branch: `feature/logistic-dispatch-board` (created from `main`).
- Goal: start implementation without coupling to `feature/gle-55-56-63`.

## Method Decisions
- Start with vertical scaffold (route -> controller -> service -> page) to validate wiring early.
- Keep `Dashboard/Logistic.vue` as launcher and add a direct link to the new Dispatch board.
- Keep transition operations command-style under `/api/logistic/dispatch/*`.
- Persist dispatch planning on `journeys` existing fields (`primary_warehouse_id`, `secondary_warehouse_id`, `is_double_load`, `is_temporary_storage`, `plan_version`) and log every command in `journey_events`.

## Non-goals in this slice
- No legacy compatibility bridge.
- No final state-machine hard locks yet.
- No DB migration in this first scaffold slice.
