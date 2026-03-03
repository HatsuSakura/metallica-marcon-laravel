# Test Plan

## Unit tests
- JSON schema validation:
  - missing required fields
  - invalid enums
  - invalid date formats
- Query builders:
  - geo filter correctness (distance calculations)
  - status/material/weight filters

## Integration tests
- parse -> execute pipeline with mocked LLM responses
- performance sanity checks:
  - logistics: typical radius queries return < 200 rows
  - analytics: group_by queries within time limits

## Acceptance tests (examples)
Logistics:
- "entro 40 km" returns only pickups with computed distance <= 40
- "non pericolosi" excludes hazardous pickups

Analytics:
- "rame ultimi tre mesi" returns metric sum_weight_kg with correct time_range
- "istogramma margine Rossi" returns METRIC_NOT_AVAILABLE if costs not present