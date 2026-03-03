# Data Model Notes (for Analytics readiness)

Current state:
- DB is structured for logistics.
- Costs/prices not fully available yet (commercial dashboard in progress).

We need a clear data lineage for analytics metrics:
- "Recovered material quantities" should come from a canonical table (e.g., `material_movements`)
- "Net margin per customer" requires:
  - revenue attribution (sales)
  - costs allocation (pickup costs, logistics costs, processing costs)

Recommended approach:
- Add a minimal analytics-ready layer:
  - `material_movements` (material_type, weight_kg, pickup_id, warehouse_event_at)
  - `sales_lines` (material_type, weight_kg, price_total, sold_at)
  - `cost_lines` (type: trip_cost|labor|disposal|processing, amount, date, optional pickup_id/trip_id/customer_id)
- Keep calculations deterministic and explainable.