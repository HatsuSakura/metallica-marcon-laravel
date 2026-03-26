# Architectural Decisions

## D1: AI outputs JSON only
Reason: safety and deterministic execution.

## D2: Separate NLP endpoints
- /api/nlp/logistics
- /api/nlp/analytics
Reason: different risk and query profiles.

## D3: Log all NLP queries
Reason: audit, debugging, future training.

## D4: MySQL retained for now
Reason: sufficient for current scale.
Migration path to PostGIS preserved via abstraction.

## D5: Frontend renders charts
Backend returns normalized datasets + suggested visualization.
Reason: avoid coupling backend to specific chart libraries.

## D6: Single-shot first
Dialog refinement planned but not in MVP.

## D7: No runtime-compiled Vue components
Use only standard SFC `.vue` components imported by parent pages.
Avoid runtime templates in JS objects (`template: '...'`) to prevent production-only rendering issues.

## D8: Dispatch as dedicated domain module (GLE-53)
- `Dashboard/Logistic.vue` remains entry-only.
- New workflow implemented under `Pages/LogisticDispatch/*`.
- State transitions exposed as explicit command endpoints.
- Legacy non-production journey cargo/warehouse process removed, not preserved.
Reference: `.ai/decisions/2026-03-17-logistic-dispatch-implementation-strategy.md`

## D9: Logistic Dispatch board isolated from GLE-55-56-63
- New branch `feature/logistic-dispatch-board` from `main`.
- Dispatch flow developed as dedicated module (`Pages/LogisticDispatch/*`) with command endpoints under `/api/logistic/dispatch/*`.
- Initial scaffold tracks decisions in `.ai/decisions/2026-03-17-logistic-dispatch-pr01-scaffold.md`.

## D10: Status naming convention enforced for order document lifecycle
- Renamed enum usage from `OrderDocumentsState` to `OrderDocumentsStatus`.
- Renamed order field usage from `documents_state` to `documents_status`.
- Added compatibility migration `2026_03_17_180000_rename_order_documents_state_to_status`.

## D11: Source file encoding standard
- All source files (`.vue`, `.js`, `.ts`, `.php`, `.md`, config text files) must be saved in UTF-8.
- Avoid tools/commands that write in ANSI/Windows-1252 by default.
- Reason: prevent mojibake and corrupted UI strings (for example `Quantità` -> `Quantit�`).

## D12: Enum architecture standard for all status domains
- Every new status enum must include:
  - `fromMixed(...)` (and `tryFromMixed(...)` when nullable parsing is needed),
  - `canTransitionTo(...)`,
  - `allowedTransitions()`.
- Every model status field must be cast to its enum in `$casts`.
- Controllers/services must not compare raw status strings directly from model attributes:
  - resolve state through enum (`fromMixed`) and use enum transitions.
- Frontend must consume centralized status constants/helpers (`resources/js/Constants/*Status*.js`) for labels, badge classes, and guards.
- Raw status literals are allowed only at integration boundaries (DB schema enum definitions, request query flags, or external payload contracts), then immediately normalized through enum/constants.

## D13: Order controller refactor paused, resume from Driver flow first
- The refactor of order controllers impacts both Driver and Warehouse processes and is currently paused by customer request.
- `OrderController` remains the canonical order controller.
- `DriverOrderController` is still active and is the first branch to analyze/absorb when the refactor resumes.
- `WorkerOrderController` currently appears closer to legacy/dead code than to an active user-facing flow and must be verified before any rewrite.
- `WarehouseManagerOrderController` is not a direct clone of canonical order CRUD and must be treated as a separate operational flow.
- Do not mix this refactor with unrelated feature work; resume it only as a dedicated effort.
- Functional context completed before pause:
  - `fixed_withdraw_at` implemented on orders,
  - logistic dashboard widget for fixed-date orders implemented,
  - YouTrack issues `GLE-54` and `GLE-94` completed.
