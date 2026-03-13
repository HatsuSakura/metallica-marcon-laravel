# YouTrack Project Specs

Scope: `https://metallicamarcon.youtrack.cloud` (project `GLE`).

## Issue Creation Rules

1. Language:
- `summary` and `description` must be in Italian.

2. Assignee:
- Default assignee is `matteo.argenton` unless explicitly specified otherwise.

3. Type:
- Default type is `Funzionalità`.
- Before creating an issue, always confirm with the user whether the type is:
  - `Funzionalità`
  - `Bug`
  - `Estetico`

4. Priority:
- Keep the project default priority unless explicitly requested.

5. Summary workflow:
- The user describes the issue.
- Codex proposes the `summary`.
- Codex creates the issue only after user approval of the summary.

6. Create completed issues for tracking:
- If requested, create the issue and then set state to `Completato` (or equivalent resolved state in project workflow).
- If a completion note is provided, add it as description or a comment.

## Nested Issues (Parent/Subtask) Workflow

When a task must be split:

1. Create parent issue first with high-level objective.
2. Create child issues as separate issues.
3. Link each child to parent with link type `subtask of` (child -> parent).
4. Keep parent open until all subtasks are completed.
5. Optionally add a parent checklist in description for status visibility.

## Operational Defaults For Codex

- Project key default: `GLE`.
- Ask confirmation before creation if type is missing.
- If assignee is not provided, assign to `matteo.argenton`.
