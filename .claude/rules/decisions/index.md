# Decisions Index

Aggiornare questo file ogni volta che si aggiunge o modifica una decisione architetturale.

## Core decisions (D1–D11)

Vedere `001-core.md`.

| ID | Slug | Data |
|----|------|------|
| D1 | AI output solo JSON, nessun SQL | iniziale |
| D2 | Endpoint NLP separati (logistics / analytics) | iniziale |
| D3 | Log tutte le query NLP | iniziale |
| D4 | MySQL mantenuto, PostGIS come migration path | iniziale |
| D5 | Frontend renderizza chart, backend restituisce dataset | iniziale |
| D6 | Single-shot NLP prima, dialog in Phase 2 | iniziale |
| D7 | Nessun componente Vue compilato a runtime | iniziale |
| D8 | Dispatch come modulo dedicato GLE-53 | 2026-03-17 |
| D9 | Branch logistic-dispatch-board isolato da GLE-55-56-63 | 2026-03-17 |
| D10 | Naming: OrderDocumentsStatus / documents_status | 2026-03-17 |
| D11 | Tutti i file sorgente in UTF-8 | 2026-03-17 |

## ADR dettagliate

| File | Argomento | Data | Status |
|------|-----------|------|--------|
| `002-logistic-dispatch-strategy.md` | Implementazione dispatch GLE-53 | 2026-03-17 | Accepted |
| `003-logistic-dispatch-scaffold.md` | PR-01 scaffold dispatch board | 2026-03-17 | Accepted |
| `004-authorization-report.md` | Matrice autorizzativa completa | 2026-03-27 | Accepted |
| `005-guard-hardening.md` | Piano hardening guard layer | 2026-03-27 | Planned |
| `006-session-context-2026-03-27.md` | Completamenti sessione security | 2026-03-27 | Completed |
