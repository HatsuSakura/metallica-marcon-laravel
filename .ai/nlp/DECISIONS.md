# Decisions

## D1 - Two separate endpoints
We use separate endpoints and schemas:
- logistics vs analytics
Reason: different risk profiles and different query builders.

## D2 - LLM outputs JSON only (no SQL)
Reason: safety, determinism, and testability.

## D3 - Store NLP query logs
Reason: auditing, debugging, quality metrics, future training/prompt tuning.

## D4 - MySQL first, abstract geo provider
Reason: avoid premature migration; keep a clean path to PostGIS if/when needed.

## D5 - Charts rendered in FE
BE returns normalized datasets + suggested viz.
Reason: decouple from charting libraries and keep BE stable.