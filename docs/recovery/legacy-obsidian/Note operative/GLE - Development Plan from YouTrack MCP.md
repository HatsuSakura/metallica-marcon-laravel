---
title: GLE - Development Plan from YouTrack MCP
date: 2026-03-12
project: GLE
status: historical
source: YouTrack MCP analysis
recovered_on: 2026-10-30
recovery_status: historical
---

> **Nota recuperata — storica, non procedura corrente.** Origine: vecchio vault Obsidian; recupero 2026-10-30. Per lo stato attuale consultare [sviluppi e riconciliazione](../../../development-status.md). Non eseguire comandi di deploy, migrazione o modifica dati sulla sola base di questa nota.


Yes, and this MCP connection is strong enough for real planning work.

## What this unlocks
1. Read live backlog status/types/assignees.
2. Build dependency-aware plans from real issue IDs.
3. Batch-create/link/update issues as implementation progresses.
4. Keep Obsidian and YouTrack aligned.

## Open backlog clusters (from `GLE #Unresolved`)
1. Notes epic: `GLE-51`, `GLE-59`, `GLE-60`, `GLE-61`, `GLE-62`, and consumer `GLE-52`.
2. PDF/order docs: `GLE-56` and child `GLE-63`; related ADR doc line `GLE-18`, `GLE-19`.
3. Journey/dashboard evolution: `GLE-53`, `GLE-25`, `GLE-1`, `GLE-38`, `GLE-24`, `GLE-22`, `GLE-23`.
4. Order process/domain: `GLE-54`, `GLE-55`, `GLE-36`, `GLE-32`, `GLE-6`, `GLE-12`, `GLE-17`, `GLE-5`.
5. UX/performance quick win: `GLE-58`, `GLE-64`.
6. Legacy commercial/dashboard threads still open: `GLE-7`, `GLE-8`, `GLE-10`, `GLE-11`, `GLE-13`, `GLE-14`, `GLE-20`, `GLE-21`, `GLE-26`.

## Dependency-first execution logic
1. Implement data primitives first (new fields/flags/services).
2. Then surface data in UI (driver/logistics dashboards, forms).
3. Then add process orchestration (manual withdraw, templates, special flows).

## Optimized plan (recommended order)

1. **Foundation Wave (high leverage, low coupling)**
- `GLE-51` + `GLE-59/60/61/62` (notes fields and persistence).
- `GLE-54` (fixed date/time on order with toggle).
- `GLE-58`, `GLE-64` (map/customer filters “Vai!” explicit apply).
Reason: all are bounded changes and unblock consumer screens.

2. **Consumer Wave**
- `GLE-52` (show notes in Driver journey dashboard).
- `GLE-56` (PDF generation service/API from order data).
- `GLE-63` (consume same PDF from journey UI).
Reason: `GLE-52` depends on notes; `GLE-63` depends on `GLE-56`.

3. **Operational Flow Wave**
- `GLE-55` (manual withdraw insertion for retrocompatibility).
- `GLE-36` (order template per customer).
Reason: both affect operator workflow and are easier once core order fields stabilize.

4. **Journey Architecture Wave**
- `GLE-53` (new logistics journey dashboard).
- Align/merge with `GLE-24`, `GLE-25`, `GLE-1`, `GLE-38`.
Reason: these overlap conceptually; do them as one architecture stream to avoid duplicate UI work.

5. **Legacy/Domain Cleanup Wave**
- `GLE-32`, `GLE-6`, `GLE-12`, `GLE-17`, `GLE-5`, `GLE-26`.
- Re-triage very old dashboard lines `GLE-7/8/10/11/13/14/20/21` as “active / obsolete / merge”.
Reason: avoid implementing stale ideas before new logistics/driver flows settle.

## Parallelization opportunities
1. Track A (backend schema/services): notes + fixed date + PDF endpoint.
2. Track B (frontend): map/customer filters + notes rendering + PDF buttons.
3. Track C (process): manual withdraw + template order UX.
These can run in parallel with low merge conflict risk.

## Key optimization decision to take now
1. Treat `GLE-53` as the umbrella for old dashboard issues (`GLE-24`, `GLE-25`, maybe `GLE-22` subtree), or you risk building twice.
2. Confirm whether ADR PDF (`GLE-18/19`) should reuse the same document pipeline as `GLE-56` (recommended).

## Sync update (2026-03-13)
- YouTrack live check completed for project `GLE`.
- Total issues: `64`.
- Current status split: `Completato 36`, `In corso 1`, `Da fare 27`.
- Detailed per-issue status + effort analysis note: [GLE - Issue Status & Effort Analysis - 2026-03-13](GLE%20-%20Issue%20Status%20%26%20Effort%20Analysis%20-%202026-03-13.md).
