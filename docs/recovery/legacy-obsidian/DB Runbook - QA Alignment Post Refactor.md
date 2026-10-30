---
title: DB Runbook - QA Alignment Post Refactor
date: 2026-03-13
project: GLE
status: historical
source:
  - .ai/db_migration/CODE_REFACTOR_PROGRESS.md
  - .ai/db_migration/DEPLOY_DATA_MIGRATION_PLAN.md
  - .ai/db_migration/TRUE_LEGACY_TO_STANDARDIZED_ETL_DOMAIN_ONLY_README.md
  - .ai/db_migration/scripts/README.md
recovered_on: 2026-10-30
recovery_status: historical
---

> **Nota recuperata — storica, non procedura corrente.** Origine: vecchio vault Obsidian; recupero 2026-10-30. Per lo stato attuale consultare [sviluppi e riconciliazione](../../development-status.md). Non eseguire comandi di deploy, migrazione o modifica dati sulla sola base di questa nota.


# Goal
Align QA data for `customers/sites` safely after DB refactor deploy, minimizing QA break risk.

# Preconditions
- Refactor code is deployed on QA.
- QA schema migrations are completed (`php artisan migrate --force`).
- `db_profiles.json` contains both local and QA profiles.

# Canonical checks to protect QA
- No orphan sites (`sites.customer_id` must exist in `customers.id`).
- No duplicate customer fiscal identifiers (`vat_number`, `tax_code`).
- `sites.site_type` values must be canonical:
  - `fully_operative`
  - `only_legal`
  - `only_stock`
  - `NULL`

# Operational sequence (recommended)
1. Freeze QA write operations (short window).
2. Create full QA backup (checkpoint before import).
3. Build/deploy code and run migrations on QA.
4. Validate local source domain (`customers/sites`) before export.
5. Export local domain data.
6. Import data into QA with controlled truncate of domain tables.
7. Run post-import validation on QA.
8. Smoke-test key flows (Customer index/edit, Site index/edit, Journey view dependencies).
9. Keep QA backup until sign-off.

# Scripted flow (coherent with existing migration scripts)
From `./.ai/db_migration/scripts`:

```powershell
# 1) Backup QA
.\00-backup-dev-db.ps1 -Profile <qa_profile>

# 2) Validate local source
.\06-validate-customers-sites.ps1 -Profile <local_profile>

# 3) Export local domain data
.\07-export-customers-sites.ps1 -Profile <local_profile>

# 4) Import into QA (truncate domain tables first)
.\08-import-customers-sites.ps1 -Profile <qa_profile> -InputFile .ai/db_migration/exports/<file>.sql -TruncateBeforeImport

# 5) Validate QA target
.\06-validate-customers-sites.ps1 -Profile <qa_profile>
```

# Fallback / rollback
- Rollback = restore QA from backup created at step 1.
- Do not proceed with smoke tests if `orphan_sites > 0`.

# Traceability
- Core refactor completion reference: `.ai/db_migration/CODE_REFACTOR_PROGRESS.md` (Batch 7/8).
- Data migration guardrails reference: `.ai/db_migration/DEPLOY_DATA_MIGRATION_PLAN.md`.
- Domain ETL business rules reference: `.ai/db_migration/TRUE_LEGACY_TO_STANDARDIZED_ETL_DOMAIN_ONLY_README.md`.
- Script catalog: `.ai/db_migration/scripts/README.md`.

## Automation update (2026-03-13)
Added helper scripts for tunnelized execution from local machine:
- `./.ai/db_migration/scripts/09-open-qa-db-tunnel.ps1`
- `./.ai/db_migration/scripts/10-close-qa-db-tunnel.ps1`
- `./.ai/db_migration/scripts/11-run-qa-domain-sync.ps1`

One-shot execution example:
```powershell
cd .ai\db_migration\scripts
.\11-run-qa-domain-sync.ps1 `
  -LocalProfile docker_dev_marconinertia `
  -QaProfile qa_siteground_tunnel `
  -SshUser <ssh_user> `
  -SshHost <ssh_host> `
  -SshPort <ssh_port> `
  -TunnelLocalPort 13306 `
  -TunnelRemoteDbHost 127.0.0.1 `
  -TunnelRemoteDbPort 3306
```
