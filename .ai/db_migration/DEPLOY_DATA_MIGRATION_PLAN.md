# Deploy Data Migration Plan (Post-Standardization)

This plan is for the moment when DB standardization is completed and we are ready to move existing data into the final canonical schema.

## 1. Freeze Target Schema
- Finalize and lock the compact migration set.
- Ensure migrations describe only the canonical current structure.
- No legacy columns in target schema.

## 2. Backup Existing Data
- Create a full dump of the current quality environment database.
- Keep a restore checkpoint before any destructive operation.

## 3. Recreate Clean Database
- Deploy the new build with compact migrations.
- Create the database from scratch in final canonical form.
- Validate schema integrity (tables, indexes, FKs, enums).

## 4. Run ETL Data Migration Routine
- Source: old SQL dump / old schema data.
- Transform: map old columns to canonical columns, normalize values, handle invalid records by agreed rules.
- Load: insert data in dependency order (parents before children) to preserve referential integrity.

## 5. Verify and Sign Off
- Compare row counts old vs new (per table).
- Check FK/unique/null constraints.
- Run key business queries and smoke test critical flows.
- Approve cutover only after verification passes.

