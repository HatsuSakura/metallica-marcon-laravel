# Development Context

## Development Philosophy

- Deterministic core logic.
- AI as interpreter, never decision-maker.
- Strict schema validation.
- Auditability over magic.

## AI Usage Principles

1. LLM outputs structured JSON only.
2. LLM never generates SQL.
3. Backend validates all outputs.
4. Every NLP interaction is logged.

## Expected Evolution

Phase 1:
- Single-shot NLP queries.

Phase 2:
- Progressive refinement (dialog-based filters).

Phase 3:
- Predictive analytics and suggestion systems.

## Performance Expectations

- < 500 ms parsing time
- < 1 sec total NLP + execution
- Queries capped by server-side safety limits