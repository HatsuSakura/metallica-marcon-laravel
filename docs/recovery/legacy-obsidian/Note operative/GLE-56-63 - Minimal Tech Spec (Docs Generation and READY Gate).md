---
title: GLE-56-63 - Minimal Tech Spec (Docs Generation and READY Gate)
date: 2026-03-14
project: GLE
status: historical
scope:
  - GLE-56
  - GLE-63
note: Excludes enum/class rename step (handled separately)
recovered_on: 2026-10-30
recovery_status: historical
---

> **Nota recuperata — storica, non procedura corrente.** Origine: vecchio vault Obsidian; recupero 2026-10-30. Per lo stato attuale consultare [sviluppi e riconciliazione](../../../development-status.md). Non eseguire comandi di deploy, migrazione o modifica dati sulla sola base di questa nota.


# Objective
Introduce explicit, asynchronous document generation for Orders, and enforce planning gate so only document-ready orders can be planned into journeys.

# Point 2 - Document generation mode (async)
## Decision
- Document generation MUST be decoupled from Order save/create.
- Generation is triggered explicitly by user action: `Generate documents`.
- Backend execution is asynchronous (queue job), idempotent, retryable.

## Minimal backend components
1. Endpoint to request generation for one order:
- `POST /api/orders/{order}/generate-documents`
- Auth + authorization checks (same domain policy family as order edit/planning).

2. Queue job:
- `GenerateOrderDocumentsJob(orderId)`
- Loads order snapshot + related entities required for PDF/doc generation.
- Produces output files in storage (deterministic naming/versioning).
- Writes generation outcome on order fields (see Point 3).

3. Optional polling/status endpoint (if needed by UI):
- `GET /api/orders/{order}/document-status`

## Failure handling
- On failure set generation state to failed and store error summary.
- Allow manual retrigger (`Generate documents` again).

# Point 3 - READY source of truth
## Decision
- Order is `READY` only after successful document generation.
- Planning in journey is allowed only for `READY` orders.

## Minimal DB fields (orders)
1. `documents_state` enum/string:
- values: `not_generated`, `generating`, `generated`, `failed`
- default: `not_generated`

2. `documents_generated_at` datetime nullable
3. `documents_error` text nullable
4. `documents_version` integer default 0 (increment on each successful generation)

## Status transition rules
- On successful generation:
  - `documents_state = generated`
  - `documents_generated_at = now`
  - `documents_error = null`
  - `documents_version += 1`
  - `order status -> READY`

- On generation start:
  - `documents_state = generating`

- On generation failure:
  - `documents_state = failed`
  - `documents_error` populated
  - order must NOT become READY

## Journey planning guard
- In all planning entry points (UI + backend APIs), require:
  - `order.status == READY`
- If not READY, return/visualize blocking message:
  - "Ordine non pianificabile: documenti non generati"

# Point 4 - Regeneration policy after edits
## Decision (recommended strict coherence)
Policy B:
- If an order in `READY` is modified in fields that affect documents, force fallback to `CREATED` and require regeneration.

## Minimal invalidation rule
On order update (and order item/holder updates), if order was `READY`:
- set `status = CREATED`
- set `documents_state = not_generated`
- set `documents_generated_at = null`
- keep `documents_version` as historical counter (no decrement)

## Why this policy
- Guarantees docs and order content coherence.
- Avoids stale docs being used for planning/operations.

# UI implications (minimal)
1. Order Create/Edit pages
- Keep Save separate from Generate documents.
- Add visible document state badge.
- Add `Generate documents` button.

2. Order Index row actions
- Add row-level `Generate documents` action.
- Show planning availability only when READY.

3. Journey assignment UI
- Hide/disable non-READY orders or show blocking indicator.

# Acceptance checks
1. Saving an order never generates docs automatically.
2. Generating docs sets order to READY only on success.
3. Editing a READY order resets it to CREATED + not_generated.
4. Non-READY order cannot be planned into journey (backend enforced).
5. Same model reused by GLE-63 consumer screens.
