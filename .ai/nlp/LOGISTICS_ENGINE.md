# Logistics NLP Engine

## Purpose
Convert planner natural language into structured constraints for three operative scenarios:
- `planning_sites`: proactive planning from site risk signals.
- `order_requests`: reactive planning from explicit orders.
- `hybrid`: combined mode.

Example user query:
"Trova tutti i ritiri compatibili con Rossi Srl martedì prossimo entro 40 km non pericolosi sopra 300 kg"

## Output contract
Return `LogisticsQuery` JSON only (canonical unified schema in `SCHEMAS.md`).

## Backend execution steps
1) Resolve `reference` (`customer`/`site`/`coordinates`) to geo origin.
2) Validate scenario and corresponding filter blocks.
3) Build deterministic selection:
   - `planning_sites`: filter sites by `risk_*`, `days_to_next_pickup_*`, geo, customer filters.
   - `order_requests`: filter orders by status/material/hazardous/weight/time + geo (through site).
   - `hybrid`: build both sets and merge/rank deterministically.
4) Apply sort policy:
   - `distance`, `risk`, `urgency`, or weighted `mixed`.
5) Apply independent limits for sites and orders.
6) Return normalized candidate result with traceable scoring metadata.

## Deterministic scoring (optional)
Score = weighted sum of:
- normalized distance (lower is better)
- site risk (higher is better)
- urgency (higher is better, based on order due/request windows and/or days_to_next_pickup)
All weights configurable.

## Notes about "progressive refinement"
Phase 1: single-shot.
Phase 2: dialog refinement:
- FE sends previous `LogisticsQuery` + refinement instruction.
- LLM returns a merged/updated `LogisticsQuery` (no SQL).
- Backend re-executes deterministically.
