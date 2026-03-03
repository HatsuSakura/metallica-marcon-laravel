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
