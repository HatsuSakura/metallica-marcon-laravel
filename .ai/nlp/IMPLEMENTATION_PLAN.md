# Implementation Plan

## Phase 1 (MVP - Logistics parse + execute)
1) Create endpoints:
   - POST `/api/nlp/logistics/parse`
   - POST `/api/nlp/logistics/execute`
2) Implement:
   - LLM call -> LogisticsQuery JSON
   - JSON validation (canonical schema with scenario)
   - CandidateQueryBuilder (MySQL + Haversine) for:
     - planning_sites
     - order_requests
     - hybrid
3) Persist logs:
   - nlp_query_logs (raw_text, parsed_json, user_id, intent, success, latency_ms)
4) UI:
   - map side toggle panel (parse + preview + apply)
   - results list/map markers for selected candidates
   - CTA to `journey/create` with NLP query payload

Current local progress:
- `POST /api/nlp/logistics/parse` implemented.
- Canonical schema validator implemented server-side.
- Map panel wired to backend parse endpoint.
- Provider currently set to `heuristic` for local development.

## Phase 2 (Analytics parse + execute)
1) Create endpoints:
   - POST `/api/nlp/analytics/parse`
   - POST `/api/nlp/analytics/execute`
2) Implement:
   - Aggregation builder
   - dataset normalization
   - FE basic chart rendering

## Phase 3 (Progressive refinement / dialog)
- Store a "conversation state" as last JSON + last results summary.
- Refinements become JSON patch/merge operations (still validated).
- Add disambiguation for customer names ("Rossi Srl" vs "Rossi SRL Milano").
- Add refinement directly in `journey/create` side panel with conflict handling on manual selections.

## Observability
- Track parse failures, invalid JSON, missing fields, ambiguous entities.
- Add "copy JSON" debug feature for admins.
