# OLD -> STANDARDIZED ETL (Domain-Only)

## Files
- SQL: `.ai/db_migration/OLD_TO_STANDARDIZED_ETL_DOMAIN_ONLY.sql`
- Mapping base: `.ai/db_migration/OLD_TO_STANDARDIZED_MAPPING_FIRST_PASS.md`

## Scope
- Migrates only domain/business tables.
- Excludes system/infra tables from legacy stack (`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `migrations`, `sessions`, `password_reset_tokens`, `notifications`, `versions`).

## Run
1. Open `OLD_TO_STANDARDIZED_ETL_DOMAIN_ONLY.sql`.
2. Replace placeholders:
   - `OLD_DB` -> legacy schema
   - `NEW_DB` -> standardized schema
3. Optional: uncomment `TRUNCATE` block if target tables must be emptied.
4. Execute in staging first.

## Important note
- In old dump, `orders.truck_location` is present and populated (`vehicle`, `trailer`, `fulfill`).
- Mapping is intentionally kept as:
  - `orders.truck_location -> orders.cargo_location`

