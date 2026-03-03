# Analytics NLP Engine

## Purpose
Allow commercial users to query operational results with natural language and receive datasets ready for charts/tables.

Example queries:
- "Dammi tutte le quantità di rame recuperate negli ultimi tre mesi"
- "Costruiscimi un istogramma del guadagno netto portato a casa con il cliente Rossi Srl"

## Output contract
Return `AnalyticsQuery` JSON only.

## Backend execution steps
1) Validate schema and enums.
2) Enforce safety limits:
   - max time range (e.g., 24 months)
   - max result rows (limit)
3) Build deterministic aggregation query:
   - choose base tables (materials movements, pickups, trips, invoices/costs when available)
   - apply filters
   - apply group_by
4) Return normalized dataset:
   - `columns`: array<string>
   - `rows`: array<array>
   - `meta`: { metric, group_by, time_range, suggested_viz }

## Handling missing "costs/prices"
If `metric` requires costs/prices not available yet:
- return an error object:
  - `code`: "METRIC_NOT_AVAILABLE"
  - `message`: human-readable
  - `missing`: ["costs", "sales_prices"]

## Chart responsibility split
- BE returns normalized dataset + suggested viz.
- FE renders charts using its own library (Chart.js / ECharts).
No BE coupling to a charting library.