# Architecture

## High-level design

Two independent verticals:

1) Logistics NLP (Operational Search)
- Input: short NL query for one scenario:
  - `planning_sites`
  - `order_requests`
  - `hybrid`
- Output: `LogisticsQuery` JSON (strict schema)
- Backend: deterministic query builder -> site candidates, order candidates, or both
- Optional: scoring (`distance`/`risk`/`urgency`/`mixed`) is deterministic

2) Analytics NLP (BI / Commercial Dashboard)
- Input: NL query about quantities, margins, charts, breakdowns
- Output: `AnalyticsQuery` JSON (strict schema)
- Backend: deterministic aggregation builder -> dataset -> FE renders charts

## Services

- `NlpLogisticsParseService`
- `NlpAnalyticsParseService`
- `LogisticsCandidateQueryBuilder` (MySQL + Haversine geo filter)
- `AnalyticsQueryBuilder` (aggregations)

## Safety & determinism

- Strict JSON schema validation server-side.
- Field whitelist only (no free-form SQL).
- Enum validation for material types, statuses, etc.
- Scenario validation (`planning_sites`/`order_requests`/`hybrid`) and field compatibility checks.
- Rate-limiting and max time range for analytics.
- Store all raw queries + parsed output for audit/debug.

## Geo abstraction

Introduce a `GeoProvider` interface:
- Implementation A: MySQL + Haversine distance calculation
- Future Implementation B: Postgres + PostGIS

This allows migration without rewriting NLP logic.
