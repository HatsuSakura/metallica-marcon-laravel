# Definition of Done (DoD)

A task is complete when:

- Code is deterministic.
- JSON schema is validated.
- Unit tests exist (where applicable).
- Performance impact considered.
- DECISIONS.md updated if architectural change occurred.
- No AI-generated SQL exists.
- Logs are stored if NLP involved.
- No runtime-compiled Vue components are introduced (`template: '...'` or similar in JS objects).
- Reusable UI blocks are implemented as standard `.vue` components and imported by parent pages.

For NLP features:
- Prompt documented.
- Example input/output documented.
- Edge cases tested.
