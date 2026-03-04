# JSON Schemas

The LLM must output ONLY JSON conforming to these schemas.
Any missing information must be returned as null or omitted, never invented.

## 1) LogisticsQuery schema (canonical)

Purpose:
- Support three operative scenarios with one schema:
  - `planning_sites` (proactive, algorithmic site planning)
  - `order_requests` (reactive, explicit order demand)
  - `hybrid` (both signals together)

Required top-level fields:
- `scenario`: enum (`planning_sites`|`order_requests`|`hybrid`)
- `geo`: object
  - `origin`: enum (`customer`|`site`|`coordinates`)
  - `radius_km`: number|null
- `sort`: object
  - `mode`: enum (`distance`|`risk`|`urgency`|`mixed`)
  - `weights`: object (optional, required for `mixed`)
    - `distance` number [0..1]
    - `risk` number [0..1]
    - `urgency` number [0..1]
- `limit`: object
  - `sites`: int (default 200)
  - `orders`: int (default 200)

Optional top-level fields:
- `reference`: object
  - `customer`: `{ id?: int, name?: string }|null`
  - `site`: `{ id?: int, name?: string }|null`
  - `coordinates`: `{ lat: number, lng: number }|null`
- `time`: object
  - `target_date`: `YYYY-MM-DD`|null
  - `from`: `YYYY-MM-DD`|null
  - `to`: `YYYY-MM-DD`|null
- `site_filters`: object
  - `risk_min`: number|null
  - `risk_max`: number|null
  - `days_to_next_pickup_min`: number|null
  - `days_to_next_pickup_max`: number|null
  - `customer_ids`: array<int>|null
  - `exclude_customer_ids`: array<int>|null
- `order_filters`: object
  - `statuses`: array enum (`requested`|`planned`|`executed`|`closed`)|null
  - `hazardous`: bool|null
  - `material_types`: array<enum>|null
  - `min_weight_kg`: number|null
  - `max_weight_kg`: number|null
  - `requested_from`: `YYYY-MM-DD`|null
  - `requested_to`: `YYYY-MM-DD`|null

Scenario rules:
- `planning_sites`: `site_filters` drives selection; `order_filters` may be null/empty.
- `order_requests`: `order_filters` drives selection; `site_filters` may be null/empty.
- `hybrid`: both filter groups may be active together.

Date rules:
- All relative dates in NL must be resolved to absolute `YYYY-MM-DD`.

## 2) AnalyticsQuery schema (v1)

Required fields:
- `subject`: enum (materials|customer_profit|pickups|trips)
- `metric`: enum (sum_weight_kg|net_margin|gross_revenue|pickup_count|distance_km|avg_price)
- `time_range`: object
  - `from` (YYYY-MM-DD)
  - `to` (YYYY-MM-DD)
- `filters`: object (optional)
  - `customer` { `name`|`id` }
  - `material_type` (enum|null)
  - `hazardous` (bool|null)
  - `status` (array enum|null)
- `group_by`: enum (day|week|month|customer|material_type|none) default "none"
- `viz`: enum (table|bar|line|donut|histogram) default "table"
- `limit`: int default 500

Notes:
- If costs/prices are missing, backend returns a structured "not available" error for that metric.
- FE renders charts using returned dataset + suggested `viz`.
