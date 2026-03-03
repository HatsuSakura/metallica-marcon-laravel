# NLP Queries (Logistics + Analytics)

This folder contains the operational documentation to implement Natural Language Queries (NLP) for Metallica Marcon.

We support two separate NLP flows:
- Logistics NLP: builds a candidate set of pickups for trip planning (filters + geo constraints).
- Analytics NLP: builds analytical queries (aggregations, metrics, breakdowns) used by the commercial dashboard.

Key rule:
- The LLM never generates SQL.
- The LLM only outputs validated JSON following strict schemas.
- Backend builds deterministic queries and returns results to the UI.

Entry points:
- `/api/nlp/logistics/parse` -> returns LogisticsQuery JSON
- `/api/nlp/analytics/parse`  -> returns AnalyticsQuery JSON
- Optional: `/api/nlp/*/execute` -> parse + execute + return results