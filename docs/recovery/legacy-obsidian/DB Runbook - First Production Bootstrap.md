---
title: DB Runbook - First Production Bootstrap
date: 2026-03-13
project: GLE
status: historical
source:
  - .ai/db_migration/CODE_REFACTOR_PROGRESS.md
  - .ai/db_migration/DEPLOY_DATA_MIGRATION_PLAN.md
  - .ai/db_migration/TRUE_LEGACY_TO_STANDARDIZED_ETL_DOMAIN_ONLY_README.md
  - .ai/db_migration/README.md
recovered_on: 2026-10-30
recovery_status: historical
---

> **Nota recuperata — storica, non procedura corrente.** Origine: vecchio vault Obsidian; recupero 2026-10-30. Per lo stato attuale consultare [sviluppi e riconciliazione](../../development-status.md). Non eseguire comandi di deploy, migrazione o modifica dati sulla sola base di questa nota.


# Goal
First production go-live from legacy data to canonical data model, without historical migration noise and with deterministic ETL + reconciliation.

# Strategic decisions already aligned with project notes
- Use compact baseline migrations for first production bootstrap (no full historical chain).
- Keep full migration history in git for traceability, but production starts from canonical structure.
- Legacy import must be ETL-driven and repeatable.

# Phase 1 - Baseline package
1. Freeze canonical schema definition (post-refactor).
2. Build compact migration set representing current canonical DB.
3. Validate schema bootstrap in a fresh DB from zero.

# Phase 2 - Legacy to canonical ETL rehearsal
1. Prepare legacy dump snapshot.
2. Load into temporary schema (`old_db`) in controlled environment.
3. Run domain ETL in dependency order:
   - customers
   - sites + timetables + internal_contacts
   - optional withdraws
4. Enforce normalization rules (`sites.site_type`, business type rules, sentinel strategy where applicable).
5. Produce exception report and resolve before next step.

# Phase 3 - Reconciliation gates (must pass)
- Table counts: legacy vs target (for migrated scope).
- `sites -> customers` referential integrity (no orphan sites).
- Duplicate fiscal identifiers on customers.
- Null/format sanity on critical fields.
- Smoke tests on critical business flows.

# Phase 4 - Production cutover run
1. Backup production DB (pre-cutover checkpoint).
2. Deploy app build.
3. Bootstrap DB from compact baseline migrations.
4. Execute ETL from approved legacy dump.
5. Run reconciliation report.
6. Open traffic only after explicit sign-off.

# Rollback strategy
- If any gate fails, rollback to pre-cutover backup and keep legacy system as source of truth.

# Current script/tool mapping
Existing scripts (already in repo):
- `./.ai/db_migration/scripts/00-load-legacy-dump-into-tempdb.ps1`
- `./.ai/db_migration/scripts/01-import-customers.ps1`
- `./.ai/db_migration/scripts/02-show-customer-exceptions.ps1`
- `./.ai/db_migration/scripts/03-import-sites-timetables-contacts.ps1`
- `./.ai/db_migration/scripts/05-run-domain-flow.ps1`
- `./.ai/db_migration/scripts/06-validate-customers-sites.ps1`

Use these scripts as the base for staging rehearsals; if production needs stricter auditing, extend with report export (CSV/JSON) before final cutover.

# Open items before prod execution
1. Decide final compact migration strategy (single baseline vs minimal chained set).
2. Define exact reconciliation SQL set and acceptance thresholds.
3. Dry-run full cutover at least once in staging with timing log.
4. Lock a written cutover window + owner + rollback operator.
