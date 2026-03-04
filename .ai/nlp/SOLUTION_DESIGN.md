# NLP Solution Design (Laravel)

## Scope
- Implement Phase 1 and Phase 2 defined in `IMPLEMENTATION_PLAN.md`.
- Preserve strict safety model:
  - LLM parses text to JSON only.
  - Backend validates JSON and executes deterministic query builders.

## API Surface

Endpoints:
- `POST /api/nlp/logistics/parse`
- `POST /api/nlp/logistics/execute`
- `POST /api/nlp/analytics/parse`
- `POST /api/nlp/analytics/execute`

Request payload (common):
- `query` (string, required)
- `context` (object, optional): locale, timezone, user role, tenant/customer scope

Response patterns:
- Parse endpoints:
  - `ok: true`, `parsed`, `warnings[]`
  - `ok: false`, `error`
- Execute endpoints:
  - `ok: true`, `parsed`, `result`, `meta`
  - `ok: false`, `error`

## Laravel Component Design

### Controllers
- `App\Http\Controllers\Nlp\LogisticsParseController`
- `App\Http\Controllers\Nlp\LogisticsExecuteController`
- `App\Http\Controllers\Nlp\AnalyticsParseController`
- `App\Http\Controllers\Nlp\AnalyticsExecuteController`

Controller responsibilities:
- Validate HTTP input.
- Call parse service.
- Optionally call execute/query builder.
- Return standardized response objects.

### Services
- `NlpLogisticsParseService`
- `NlpAnalyticsParseService`
- `NlpExecutionLogService`

Responsibilities:
- Build prompt context (date, locale, schema, enum hints).
- Invoke provider client.
- Decode JSON and run schema-level validator.
- Emit warnings (ambiguous entity, missing optional filters).
- Persist query logs.

### Provider Abstraction
- Interface: `App\Services\Nlp\Providers\NlpProvider`
- Initial impl: `OpenAiNlpProvider`

Interface methods:
- `parseLogistics(string $query, array $context): array`
- `parseAnalytics(string $query, array $context): array`

Benefits:
- Swap model/provider without touching controllers or builders.
- Centralize timeout/retry/circuit-breaker behavior.

### Validation Layer
- `LogisticsQueryValidator`
- `AnalyticsQueryValidator`

Checks:
- Required keys and type checks.
- Enum checks against internal whitelists.
- Date normalization and absolute date enforcement.
- Boundaries:
  - logistics limit max (e.g. 200)
  - analytics time range max (e.g. 24 months)
  - analytics limit max (e.g. 500)

### Deterministic Query Builders
- `LogisticsCandidateQueryBuilder`
- `AnalyticsQueryBuilder`

Logistics builder:
- Resolve `reference` (`customer`/`site`/`coordinates`) => origin.
- Execute scenario-specific logic:
  - `planning_sites`: site risk + days-to-next-pickup + geo filters.
  - `order_requests`: order status/material/hazardous/weight/time + geo filters.
  - `hybrid`: combine both candidate sets with deterministic ranking.
- Compute `distance_km` with Haversine expression where geo applies.
- Apply scenario-aware limits (`sites`, `orders`).

Analytics builder:
- Resolve subject/metric mapping to canonical tables.
- Apply time range + filters.
- Apply group_by and deterministic aggregation.
- Return normalized dataset:
  - `columns`
  - `rows`
  - `meta` (metric, group_by, suggested_viz)

## Data and Persistence

### Audit Table
Create `nlp_query_logs`:
- `id`
- `user_id` nullable
- `intent` (`logistics`|`analytics`)
- `operation` (`parse`|`execute`)
- `raw_text` text
- `parsed_json` json nullable
- `provider` string nullable
- `model` string nullable
- `success` bool
- `error_code` string nullable
- `latency_ms` int nullable
- `token_usage` json nullable
- timestamps

Indexes:
- `(intent, operation, created_at)`
- `(user_id, created_at)`
- `(success, created_at)`

### Optional Support Tables (Phase 2+)
- `nlp_conversation_states` for progressive refinement.
- Analytics readiness tables from `DATA_MODEL_NOTES.md`.

## Prompting Strategy

Per-intent system prompt requirements:
- Explicit JSON-only output.
- Embed schema constraints and enum values.
- Force absolute dates (`YYYY-MM-DD`).
- Prohibit SQL generation and explanatory prose.

Runtime prompt context:
- Today date, timezone, locale.
- Known enum values.
- Optional customer aliases for better entity extraction.

## Error Model

Standard error payload:
- `code`
- `message`
- `details` (optional structured metadata)

Core codes:
- `NLP_PROVIDER_ERROR`
- `NLP_INVALID_JSON`
- `NLP_SCHEMA_VALIDATION_FAILED`
- `NLP_ENTITY_NOT_FOUND`
- `NLP_ENTITY_AMBIGUOUS`
- `METRIC_NOT_AVAILABLE`
- `NLP_RATE_LIMITED`

## Queue and Reliability

Recommended:
- Parse can run sync (fast path), execute can be sync for MVP.
- Introduce queue for heavy analytics or provider latency spikes.

Provider call policy:
- Timeout budget per call.
- Retry transient failures only.
- Exponential backoff with max retry cap.
- Log each failure attempt for diagnostics.

## Security Controls
- Keep API keys server-side only.
- Apply auth + policy checks on all NLP endpoints.
- Rate-limit per user and per IP.
- Sanitize/redact sensitive data in logs where required.
- Enforce tenant/customer scope in query builders.

## Testing Design

Unit:
- Validator tests for each schema edge case.
- Query builder tests for filters, distance, group_by.
- Error code mapping tests.

Integration:
- Parse -> validate -> execute with mocked provider.
- Endpoints with authenticated/unauthenticated users.
- Limits/time range/rate-limit behavior.

Acceptance:
- Use examples from `TEST_PLAN.md`.
- Add golden JSON fixtures for representative NL prompts.

## Phased Delivery Plan

Phase A (Foundations):
1. `NlpProvider` interface + `OpenAiNlpProvider`
2. Validators + error model
3. `nlp_query_logs` migration + logging service

Phase B (Logistics MVP):
1. Logistics parse endpoint
2. Logistics execute endpoint + deterministic builder
3. Logistics integration tests

Phase C (Analytics MVP):
1. Analytics parse endpoint
2. Analytics execute endpoint + deterministic aggregation
3. `METRIC_NOT_AVAILABLE` handling + tests

Phase D (Production Hardening):
1. Queue-based execution for heavy requests
2. Metrics dashboards and alerting
3. Retry/circuit-breaker tuning

## Definition of Done
- All four endpoints implemented and protected.
- Schema validation rejects malformed provider outputs.
- No SQL from LLM path at any layer.
- Audit logs populated for success and failure cases.
- Test suite covers core parse/execute scenarios.
