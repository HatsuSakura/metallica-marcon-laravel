# DB Migration Scripts (PowerShell)

Scripts in this folder execute the true-legacy migration flow in controlled steps.
Legacy source is loaded from SQL dump file into a temporary local schema (`old_db` in profile).

## Required parameters
- No mandatory runtime parameters if profile is configured in `db_profiles.json`.

Use `-Profile <name>` only if you want to skip interactive profile selection.

## Execution order
1. `00-backup-dev-db.ps1`
2. `00-load-legacy-dump-into-tempdb.ps1`
3. `00-truncate-customers-sites.ps1`
4. `01-import-customers.ps1`
5. `02-show-customer-exceptions.ps1`
6. fix customer exceptions if any
7. `03-import-sites-timetables-contacts.ps1`
8. optional: `04-import-withdraws-optional.ps1`
9. `php artisan migrate --force` (applica anche normalizzazioni di sicurezza come `sites.site_type`)

## One-shot helper
- `05-run-domain-flow.ps1` runs:
  - load legacy dump into temporary schema (unless `-SkipLoadLegacyDump`)
  - truncate (unless `-SkipTruncate`)
  - customer import
  - exception report
  - then stops intentionally before sites import

## Domain sync helpers (dev -> QA / any target profile)
- `06-validate-customers-sites.ps1`
  - Run domain consistency checks on target `new_db`:
    - totals (`customers`, `sites`, `timetables`, `internal_contacts`)
    - orphan sites (`sites.customer_id` without `customers.id`)
    - duplicate `customers.vat_number` / `customers.tax_code`
    - invalid `sites.site_type`
    - sites missing coordinates
- `07-export-customers-sites.ps1`
  - Export data-only dump of:
    - `customers`, `sites`, `timetables`, `internal_contacts`
  - Output default folder: `.ai/db_migration/exports`
- `08-import-customers-sites.ps1`
  - Import a dump produced by step 07 into selected profile `new_db`.
  - Optional `-TruncateBeforeImport` to clean domain tables before import.
- `09-open-qa-db-tunnel.ps1`
  - Opens SSH tunnel using `plink` + Pageant key.
  - Writes tunnel process PID to `.qa-db-tunnel.pid` (default).
- `10-close-qa-db-tunnel.ps1`
  - Stops tunnel process from PID or PID file.
- `11-run-qa-domain-sync.ps1`
  - Parent orchestrator:
    1) open tunnel
    2) validate local source
    3) export local domain data
    4) backup QA
    5) import QA
    6) validate QA
    7) close tunnel in `finally` block

### Suggested QA alignment flow
1. Backup QA DB (with `00-backup-dev-db.ps1` on QA profile).
2. Validate local source:
   - `.\06-validate-customers-sites.ps1 -Profile <local_profile>`
3. Export local domain data:
   - `.\07-export-customers-sites.ps1 -Profile <local_profile>`
4. Import into QA:
   - `.\08-import-customers-sites.ps1 -Profile <qa_profile> -InputFile <export_file> -TruncateBeforeImport`
5. Validate QA target:
   - `.\06-validate-customers-sites.ps1 -Profile <qa_profile>`

### Tunnelized one-shot flow (Pageant)
```powershell
.\11-run-qa-domain-sync.ps1 `
  -LocalProfile docker_dev_marconinertia `
  -QaProfile qa_siteground_tunnel `
  -SshUser <ssh_user> `
  -SshHost <ssh_host> `
  -SshPort <ssh_port> `
  -TunnelLocalPort 13306 `
  -TunnelRemoteDbHost 127.0.0.1 `
  -TunnelRemoteDbPort 3306 `
  -PlinkExe "C:\Program Files\PuTTY\plink.exe"
```

Notes:
- Ensure Pageant is running and the private key is loaded.
- `qa_siteground_tunnel` profile should use:
  - `execution_mode: cli`
  - `mysql_host: 127.0.0.1`
  - `mysql_port: 13306` (or same `-TunnelLocalPort`)

## Profile config
- File: `db_profiles.json`
- `old_db` is a temporary local schema name (example: `legacy_tmp`), not a remote/production DB.
- `legacy_dump_file` points to the SQL dump file to be loaded in step 1.
  - Relative paths are resolved against project root as well (for example `.ai/db_migration/old_database_dump.sql`).
- Supported execution modes:
  - `docker`: runs mysql inside container via `docker exec -i <container> mysql ...`
  - `cli`: runs local mysql client (`mysql` or path from `mysql_exe`)

## Example
```powershell
# from repo root (Windows PowerShell)
# powershell -ExecutionPolicy Bypass -File .\.ai\db_migration\scripts\00-load-legacy-dump-into-tempdb.ps1
.\00-backup-dev-db.ps1
.\00-load-legacy-dump-into-tempdb.ps1
.\00-truncate-customers-sites.ps1
.\01-import-customers.ps1
.\02-show-customer-exceptions.ps1
.\03-import-sites-timetables-contacts.ps1
# optional
.\04-import-withdraws-optional.ps1
```
