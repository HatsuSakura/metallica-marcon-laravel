# Architecture by Environment (Local + VPS)

## Goals
- Keep the NLP architecture consistent across local and production.
- Change only operational concerns between environments.
- Preserve safety constraints: LLM outputs JSON only, no SQL generation.

## Shared Core Architecture

Services and responsibilities:
- `app` (Laravel API): exposes NLP endpoints and orchestrates parse/execute flow.
- `db` (MySQL): operational data + NLP audit logs.
- `redis`: queues, cache, and rate-limiting backend.
- `worker`: executes queued NLP/analytics jobs and retries transient failures.
- `nginx` (or equivalent reverse proxy): TLS termination and request routing.
- External `LLM API`: OpenAI (via API key), accessed only by backend service.

Core flow (both environments):
1. Client sends natural language query.
2. API calls `Nlp*ParseService` with strict prompt + schema expectation.
3. API validates returned JSON against schema.
4. Deterministic query builder executes DB query:
   - logistics: `planning_sites` / `order_requests` / `hybrid`
   - analytics: aggregations
5. API returns normalized results and logs request/response metadata.

## Local Environment (Docker)

Recommended stack:
- `docker compose` with `app`, `db`, `redis`, `worker`.
- Optional `nginx` container if you want production-like routing locally.

Local configuration:
- Use `.env` for `OPENAI_API_KEY`, DB/Redis credentials, queue config.
- Queue driver: `redis`.
- Keep parse and execute endpoints both enabled:
  - `POST /api/nlp/logistics/parse`
  - `POST /api/nlp/logistics/execute`
  - `POST /api/nlp/analytics/parse`
  - `POST /api/nlp/analytics/execute`

Local operational defaults:
- Lower rate limits for easy testing.
- Verbose logs enabled.
- Mock/fake LLM mode available for integration tests.

## Production Environment (VPS)

Topology on VPS:
- Same container roles as local.
- `nginx` public entrypoint over HTTPS.
- `app`, `worker`, `db`, `redis` in private network.

Production hardening:
- Secrets injected at runtime (not committed `.env` files).
- Strict TLS and firewall rules (only required ports exposed).
- Request timeout and retry policy for LLM provider calls.
- Queue-based execution for heavier operations.
- Idempotency for execute endpoints where possible.
- Per-user/API token rate limits and quotas.

Reliability controls:
- LLM call timeout budget (example: 8-15s).
- Retry only transient errors (429/5xx/timeouts) with exponential backoff.
- Circuit breaker/fallback messaging if provider is degraded.
- Dead-letter handling for repeatedly failed jobs.

## Security and Compliance

Must-have controls:
- Never expose LLM API key to frontend.
- Log raw query text carefully; redact sensitive fields when needed.
- Keep audit tables:
  - `nlp_query_logs` (raw_text, parsed_json, intent, success, latency_ms, user_id).
- Add optional prompt/response snapshots for admins only (access controlled).
- Enforce strict schema and enum validation before query execution.

## Observability

Track these metrics from day one:
- NLP parse success rate.
- Invalid JSON/schema validation failure rate.
- LLM latency (p50, p95) and error rate.
- Execute query latency (DB time).
- Token usage and estimated cost per endpoint/tenant.

Recommended logging fields:
- `request_id`, `user_id`, `intent`, `provider`, `model`, `latency_ms`, `error_code`.

## Deployment Strategy

Use one artifact promoted across environments:
1. Build container image once in CI.
2. Deploy same image to staging/prod with environment-specific config.
3. Run DB migrations before switching traffic.
4. Perform health checks and smoke tests on NLP endpoints.

Rollback strategy:
- Keep previous image tag available.
- Roll back app/worker together if schema contract changes.

## Configuration Model

Introduce an internal provider abstraction:
- `NlpProvider` interface:
  - `parseLogistics(text, context): LogisticsQuery`
  - `parseAnalytics(text, context): AnalyticsQuery`
- `OpenAiNlpProvider` initial implementation.
- Future providers can be added without changing controllers/builders.

Suggested env variables:
- `NLP_PROVIDER=openai`
- `OPENAI_API_KEY=...`
- `OPENAI_MODEL_LOGISTICS=...`
- `OPENAI_MODEL_ANALYTICS=...`
- `NLP_TIMEOUT_MS=...`
- `NLP_MAX_RETRIES=...`

## Scale Triggers (When to Evolve)

Consider architecture upgrades when:
- Parse traffic increases significantly (move to dedicated NLP service).
- Geo query complexity exceeds MySQL performance budget (evaluate PostGIS).
- Multi-turn dialog becomes common (add conversation-state store and patch logic).

## Immediate Next Step (Design)

Design and implement in this order:
1. Provider abstraction (`NlpProvider` + OpenAI adapter).
2. Parse endpoints with schema validation and audit logs.
3. Execute endpoints with deterministic query builders.
4. Queue + retry policy for production stability.
5. Metrics dashboard and alert thresholds.
