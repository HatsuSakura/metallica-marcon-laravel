# DB Migration Workspace

This folder tracks the database standardization and migration process.

## Files
- `current_database_schema.sql`: current application schema snapshot.
- `old_database_dump.sql`: legacy source data dump.
- `NAMING_CONVENTIONS.md`: canonical naming rules.
- `SCOPE.md`: in-scope and out-of-scope tables.
- `RENAME_MAP.md`: table-by-table column rename mapping.

## Current Status
- Bullets 1, 2, 3 completed:
  - naming convention defined
  - scope defined
  - complete rename map draft prepared
- Compatibility migration generated and applied:
  - new canonical columns added
  - old/new backfill completed
- Code refactor started (Phase 6, batch 1):
  - see `CODE_REFACTOR_PROGRESS.md`

## Pending Data Sanitation
- normalize legacy customer names with leading/trailing whitespace before final cutover
- specifically sanitize `customers.company_name` for leading/trailing spaces, tabs, CR/LF
