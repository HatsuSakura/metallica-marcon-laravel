# System Architecture

## Core Layers

### 1. Domain Layer
- Customers
- Pickups
- Trips
- Materials
- Warehouse movements

### 2. AI Interpretation Layer
- Logistics NLP Engine
- Analytics NLP Engine

### 3. Deterministic Query Builders
- LogisticsCandidateQueryBuilder
- AnalyticsAggregationBuilder

### 4. Scoring Layer (Logistics)
Weighted scoring based on:
- Distance
- Priority
- Fill factor

### 5. Presentation Layer
- Backend returns normalized datasets.
- Frontend handles chart rendering.

## Separation of Concerns

- AI layer interprets intent.
- Domain layer enforces business logic.
- Data layer performs optimized queries.
- Frontend visualizes results.

## Geo Strategy

Current:
- MySQL + Haversine calculation.

Future:
- Replace GeoProvider with PostGIS implementation if needed.