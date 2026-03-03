# Architecture

## High-level design

Two independent verticals:

1) Logistics NLP (Operational Search)
- Input: short NL query (filters + geo + date constraints)
- Output: `LogisticsQuery` JSON (strict schema)
- Backend: deterministic query builder -> candidate pickups
- Optional: scoring (distance/priority/fill-factor) is deterministic

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
- Rate-limiting and max time range for analytics.
- Store all raw queries + parsed output for audit/debug.

## Geo abstraction

Introduce a `GeoProvider` interface:
- Implementation A: MySQL + Haversine distance calculation
- Future Implementation B: Postgres + PostGIS

This allows migration without rewriting NLP logic.