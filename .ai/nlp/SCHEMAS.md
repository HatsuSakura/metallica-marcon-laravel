# JSON Schemas

The LLM must output ONLY JSON conforming to these schemas.
Any missing information must be returned as null or omitted, never invented.

## 1) LogisticsQuery schema (v1)

Required fields:
- `reference_customer`: object
  - `name` (string) OR `id` (int)
- `target_date`: ISO date (YYYY-MM-DD) or null
- `radius_km`: number or null
- `filters`: object (all optional)
  - `hazardous` (bool|null)
  - `min_weight_kg` (number|null)
  - `max_weight_kg` (number|null)
  - `status` (array of enum: requested|planned|executed|closed)
  - `material_types` (array of enum)
  - `exclude_customer_ids` (array<int>|null)
- `sort`: enum (distance|priority|weight_fill|mixed) default "distance"
- `limit`: int default 200

Notes:
- `reference_customer` is used as geo origin (lat/lng) by backend.
- If target_date is "next Tuesday", LLM must output absolute date.

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