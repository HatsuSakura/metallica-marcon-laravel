
> **Nota recuperata — storica, non procedura corrente.** Origine: vecchio vault Obsidian; recupero 2026-10-30. Per lo stato attuale consultare [sviluppi e riconciliazione](../../development-status.md). Non eseguire comandi di deploy, migrazione o modifica dati sulla sola base di questa nota.


## 2026-03-04 - Canonical Logistics Schema + Map NLP MVP

### Decisions locked
- Replaced old Logistics schema with one canonical operative model (no transition path).
- Logistics scenarios are now explicit in schema:
  - `planning_sites`
  - `order_requests`
  - `hybrid`
- This model is now the single source for backend parse/execute and frontend NLP panel behavior.

### Updated project docs (.ai/nlp)
- `SCHEMAS.md`: canonical LogisticsQuery with `scenario`, `reference`, `geo`, `time`, `site_filters`, `order_filters`, `sort`, `limit`.
- `LOGISTICS_ENGINE.md`: deterministic execution split by scenario.
- `README.md`, `ARCHITECTURE.md`, `IMPLEMENTATION_PLAN.md`, `DECISIONS.md`: aligned with unified schema.
- Added: `ARCHITECTURE_ENVIRONMENTS.md`, `SOLUTION_DESIGN.md`, `STATE_MODEL.md`.

### Implemented in frontend (Map)
- Added right-side toggle NLP panel (`NlpAssistantPanel.vue`) in map page.
- Added parse-preview + candidate extraction + apply/reset + journey CTA.
- Parser output now matches canonical Logistics schema.
- Current MVP execution on map applies deterministic filtering on available site data.

### Known MVP boundary
- `order_requests` and full `hybrid` require backend `/api/nlp/logistics/parse` + `/execute` and order-aware candidate extraction.
- Map MVP currently filters site markers only.

### Next implementation step
1. Build backend `NlpProvider` + `/api/nlp/logistics/parse`.
2. Add server-side validator for canonical schema.
3. Implement `/api/nlp/logistics/execute` for scenario-aware deterministic candidates.
4. Wire journey/create to receive and refine NLP state.

## 2026-03-04 - Backend Parse Endpoint Implemented (Local MVP)

### Implemented
- Added `POST /api/nlp/logistics/parse` in authenticated `/api` route group.
- Added backend classes:
  - `API_NlpLogisticsParseController`
  - `NlpLogisticsParseService`
  - `NlpProviderFactory`
  - `NlpProvider` interface
  - `HeuristicNlpProvider` (local provider)
  - `LogisticsQueryValidator` (canonical schema validation)
- Added `config('services.nlp.provider')` with default `heuristic`.

### Frontend wiring
- Map NLP panel now calls backend endpoint via Axios (`/api/nlp/logistics/parse`).
- Removed local parse generation from Vue component.
- Panel still does deterministic local site-subset apply from parsed JSON.

### Validation behavior
- Enforces canonical Logistics schema and constraints (scenario, geo, ranges, sort weights, limits, date ordering).
- Returns structured error payload on schema failure.
- Returns warnings for known MVP boundaries.

### Next immediate step
- Implement `POST /api/nlp/logistics/execute` with deterministic builder for:
  - `planning_sites`
  - `order_requests`
  - `hybrid`


## 2026-03-13 - Proposed Tracking Plan (as-if starting now)

### Goal
Trace the full NLP Evolution in YouTrack from scratch for estimation and planning, regardless of work already started.

### Planning approach
- Create one epic + 17 implementation issues.
- Set initial state to `Da fare` for all items to keep baseline estimation clean.
- Track only skilled developer effort (working days).
- Use this split in execution waves:
- Wave 1: contracts/foundation
- Wave 2: parse flow end-to-end
- Wave 3: execute flow by scenario
- Wave 4: integration, quality, rollout

### Proposed development plan (high level)
1. **Wave 1 - Foundation (3.5d)**
- Canonical schema/versioning, validator contract, provider abstraction.
2. **Wave 2 - Parse pipeline (3.0d)**
- Heuristic provider, parse endpoint, frontend parse wiring, panel behavior stabilization.
3. **Wave 3 - Execute pipeline (6.25d)**
- Execute endpoint and deterministic builders for `planning_sites`, `order_requests`, `hybrid`, then journey handoff.
4. **Wave 4 - Hardening (5.25d)**
- UX errors/warnings, security/rate limiting, observability, tests, docs/runbook.

### Proposed YouTrack issue list (to be created)
| Proposed Key | Parent     | Summary                                                     | Skilled Dev (d) | Notes                                                                    |
| ------------ | ---------- | ----------------------------------------------------------- | --------------: | ------------------------------------------------------------------------ |
| GLE-NLP-00   | -          | NLP Evolution Epic: Canonical Logistics + Map NLP + Execute |             0.5 | Epic setup, scope and acceptance structure.                              |
| GLE-NLP-01   | GLE-NLP-00 | Canonical LogisticsQuery schema v1 + versioning rules       |             1.0 | Lock schema contract (`scenario/reference/geo/time/filters/sort/limit`). |
| GLE-NLP-02   | GLE-NLP-00 | Server-side validator for canonical schema                  |             1.0 | Deterministic validation + structured error model.                       |
| GLE-NLP-03   | GLE-NLP-00 | NlpProvider interface + factory strategy                    |            0.75 | Pluggable provider architecture.                                         |
| GLE-NLP-04   | GLE-NLP-00 | HeuristicNlpProvider baseline implementation                |             1.0 | Local deterministic parser for MVP behavior.                             |
| GLE-NLP-05   | GLE-NLP-00 | API `POST /api/nlp/logistics/parse`                         |             1.0 | Controller/service wiring and auth constraints.                          |
| GLE-NLP-06   | GLE-NLP-00 | Frontend wiring map panel -> parse endpoint                 |            0.75 | Remove local parse, use backend parse response.                          |
| GLE-NLP-07   | GLE-NLP-00 | NLP panel stabilization: preview/candidates/apply/reset     |            0.75 | UX consistency and state transitions.                                    |
| GLE-NLP-08   | GLE-NLP-00 | API `POST /api/nlp/logistics/execute` (skeleton + contract) |             1.0 | Response envelope, warnings, and deterministic execution hook.           |
| GLE-NLP-09   | GLE-NLP-00 | Execute builder for `planning_sites` scenario               |             1.0 | Deterministic candidate generation on planning sites.                    |
| GLE-NLP-10   | GLE-NLP-00 | Execute builder for `order_requests` scenario               |             1.5 | Order-aware extraction and filtering.                                    |
| GLE-NLP-11   | GLE-NLP-00 | Execute builder for `hybrid` scenario                       |             2.0 | Merge logic and ranking across site+order candidates.                    |
| GLE-NLP-12   | GLE-NLP-00 | Journey/Create integration from NLP state                   |             1.5 | Pass, refine and persist selected NLP context.                           |
| GLE-NLP-13   | GLE-NLP-00 | UX for validation errors and boundary warnings              |            0.75 | Clear operator-facing feedback for parse/execute failures.               |
| GLE-NLP-14   | GLE-NLP-00 | Security and operational guardrails                         |            0.75 | Rate limiting, payload limits, permission checks.                        |
| GLE-NLP-15   | GLE-NLP-00 | Observability for NLP parse/execute pipeline                |             1.0 | Logs, counters, latency/error metrics.                                   |
| GLE-NLP-16   | GLE-NLP-00 | Test suite: unit + integration + E2E happy/edge flows       |             1.5 | Contract and regression protection.                                      |
| GLE-NLP-17   | GLE-NLP-00 | Documentation + runbook + rollout checklist                 |            0.75 | Keep `.ai/nlp` and operations docs aligned.                              |

### Estimation summary (skilled dev only)
- Total baseline: **18.0 working days**
- Recommended planning buffer (20%): **+3.6 days**
- Suggested committed estimate: **21.6 skilled-dev days**

### Suggested sequencing/dependencies
1. `01 -> 02 -> 03 -> 04 -> 05 -> 06/07`
2. `08 -> 09 -> 10 -> 11`
3. `12` after `08-11`
4. `13-15` parallel after parse/execute contracts are stable
5. `16-17` final hardening/hand-off


### 2026-03-13 - YouTrack created
Le issue proposte sono state create in YouTrack con tag `NLP`.

| YouTrack ID | Stato      | Note           |
| ----------- | ---------- | -------------- |
| GLE-65      | Da fare    | Epic NLP       |
| GLE-66      | Completato | Già sviluppato |
| GLE-67      | Completato | Già sviluppato |
| GLE-68      | Completato | Già sviluppato |
| GLE-69      | Completato | Già sviluppato |
| GLE-70      | Completato | Già sviluppato |
| GLE-71      | Completato | Già sviluppato |
| GLE-72      | Completato | Già sviluppato |
| GLE-73      | Da fare    | Da sviluppare  |
| GLE-74      | Da fare    | Da sviluppare  |
| GLE-75      | Da fare    | Da sviluppare  |
| GLE-76      | Da fare    | Da sviluppare  |
| GLE-77      | Da fare    | Da sviluppare  |
| GLE-78      | Da fare    | Da sviluppare  |
| GLE-79      | Da fare    | Da sviluppare  |
| GLE-80      | Da fare    | Da sviluppare  |
| GLE-81      | Da fare    | Da sviluppare  |
| GLE-82      | Da fare    | Da sviluppare  |
