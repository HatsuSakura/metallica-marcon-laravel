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
- `planning_sites`: "siti critici entro 40 km da Rossi" returns only sites with risk >= 0.85 and distance <= 40
- `planning_sites`: "entro 7 giorni" applies `days_to_next_pickup_max = 7`
- `order_requests`: "ordini richiesti non pericolosi questa settimana" returns order candidates only with requested status/date window/hazardous=false
- `hybrid`: mixed query returns deterministic merged candidates and explicit scoring metadata (`distance/risk/urgency`)

Analytics:
- "rame ultimi tre mesi" returns metric sum_weight_kg with correct time_range
- "istogramma margine Rossi" returns METRIC_NOT_AVAILABLE if costs not present
