# Context

Metallica Marcon business:
- Collects metal & tech waste at customer sites.
- Sorts and stores materials.
- Resells raw materials (copper, tin, etc.).

Problem:
- Route optimization is an Operations Research problem (VRP/TSP) and can be solved deterministically.
- The biggest operational gain is in *selection*:
  - Logistics manually searches pickups, maps them, and builds feasible trip candidates.
  - Commercial users want quick answers and charts using natural language.

Goal:
- Provide a Natural Language interface to query the operational DB safely.
- Convert user text into structured filters/analytics definitions.
- Keep behavior deterministic, auditable, and testable.

Constraints:
- MySQL is the current DB. Lat/Lng stored for pickup sites.
- Pricing/costs are not fully available yet; analytics will evolve.
- Phase 1: single-shot queries.
- Phase 2: progressive refinement (dialog) is expected.

Non-goals:
- LLM does not solve route optimization.
- LLM does not access DB directly and does not output SQL.