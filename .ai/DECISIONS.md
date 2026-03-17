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
