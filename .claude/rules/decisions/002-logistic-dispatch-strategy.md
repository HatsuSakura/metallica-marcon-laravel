# ADR 2026-03-17 - Logistic Dispatch Implementation Strategy

## Status
Accepted

## Context
- Current logistics entry page is `resources/js/Pages/Dashboard/Logistic.vue`.
- New business process (GLE-53) introduces a real dispatch workflow handled by logistics operators after journey stops are completed.
- Legacy "warehouse/journey cargo old process" never went to production and must be fully removed.
- We need a clean structure that supports strict state transitions and auditable operations.

## Decision
1. Keep `Dashboard/Logistic.vue` as a navigation/entry page only.
2. Implement the new feature as a dedicated domain module:
   - Frontend pages under `resources/js/Pages/LogisticDispatch/*`
   - Reusable UI blocks under `resources/js/Pages/LogisticDispatch/Partials/*`
3. Implement backend in dedicated dispatch components:
   - `app/Http/Controllers/LogisticDispatchController.php` for Inertia views/read models
   - `app/Http/Controllers/API_LogisticDispatchController.php` for mutation commands
   - `app/Services/Dispatch/*` for business rules and state machine logic
4. Use command-style endpoints for stateful operations (not generic CRUD for transitions).
5. Remove legacy journey cargo/warehouse process paths and related dead code as part of the planned PR slices.

## Consequences
- Pros:
  - Clear bounded context for dispatch workflow.
  - Reduced coupling with generic dashboard concerns.
  - Better auditability and correctness via explicit transition commands.
  - Faster evolution because legacy compatibility is intentionally out of scope.
- Cons:
  - Requires migration of UI navigation and permissions to the new module.
  - Temporary duplication risk during transition window, mitigated by early legacy removal PR.

## Implementation Boundaries
- Dashboard remains a launcher.
- Dispatch board becomes the operational surface.
- Driver pages consume outcomes, not dispatch decision authoring.
- No backward compatibility layer for the non-production legacy process.

## Traceability
- YouTrack: GLE-53
- Obsidian docs: `GLE-53 - Target State Design (To-Be) - 2026-03-17`
