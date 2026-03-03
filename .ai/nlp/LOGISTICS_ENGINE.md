# Logistics NLP Engine

## Purpose
Convert planner natural language into structured constraints to build a candidate pickup set for trip planning.

Example user query:
"Trova tutti i ritiri compatibili con Rossi Srl martedì prossimo entro 40 km non pericolosi sopra 300 kg"

## Output contract
Return `LogisticsQuery` JSON only.

## Backend execution steps
1) Resolve `reference_customer` to customer_id (exact match, or disambiguation flow later).
2) Determine origin lat/lng.
3) Build MySQL query:
   - time filter by `target_date` or a date window (if specified)
   - status filter (default: requested)
   - hazardous, weight, material type filters
   - geo filter:
     - compute distance_km via Haversine
     - apply `radius_km`
4) Sort + limit.
5) Return list of candidate pickups with computed distance_km.

## Deterministic scoring (optional)
Score = weighted sum of:
- normalized distance (lower is better)
- pickup priority (higher is better)
- fill factor (closer to vehicle capacity is better)
All weights configurable.

## Notes about "progressive refinement"
Phase 1: single-shot.
Phase 2: dialog refinement:
- FE sends previous `LogisticsQuery` + refinement instruction.
- LLM returns a merged/updated `LogisticsQuery` (no SQL).
- Backend re-executes deterministically.