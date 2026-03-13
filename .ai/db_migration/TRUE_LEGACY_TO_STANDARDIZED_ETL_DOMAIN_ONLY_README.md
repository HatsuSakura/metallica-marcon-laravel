# True Legacy -> Standardized ETL (Domain-Only)

## File
- `.ai/db_migration/TRUE_LEGACY_TO_STANDARDIZED_ETL_DOMAIN_ONLY.sql`
- `.ai/db_migration/TRUE_LEGACY_01_CUSTOMERS_STAGE_AND_IMPORT.sql`
- `.ai/db_migration/TRUE_LEGACY_02_SITES_AND_RELATED_IMPORT.sql`

## Included tables
- `customers`
- `sites`
- `timetables`
- `internal_contacts` (derived from legacy customer fields)
- `withdraws`

## Excluded tables
- Legacy infra/system (`oauth_*`, `telescope_*`, etc.)
- Legacy `users` migration (explicitly excluded by decision)

## Applied business rules
1. Legacy users with profile `CUSTOMER` are not migrated.
2. Legacy users with profile `ADMIN/ADM` are not migrated (already present in new system).
3. `customers.jobType`:
   - `0 -> NULL`
   - other values cast to string.
4. `withdraws` null/zero FK strategy:
   - `id_vehicle NULL/0 -> vehicle_id 9999`
   - `id_driver  NULL/0 -> driver_id 9999`
   - script auto-creates sentinel user/vehicle with id `9999` if missing.
5. `customers.responsabileSmaltimenti` + `telefonoPrincipale` -> `internal_contacts`
   - role: `smaltimenti`
   - linked to all sites of the customer
   - number classification heuristic:
     - normalized starts with `3` => `mobile`
     - otherwise => `phone`
6. Site geocoding is supported through `NEW_DB.etl_site_geocoding_cache`.
   - ETL applies cached coordinates only when `sites.latitude/longitude` are missing.
   - Cache can be populated via MCP Google Maps.
7. `sites.tipologia -> sites.site_type` normalization:
   - `'1'` / legacy aliases -> `fully_operative`
   - `'2'` / legacy aliases -> `only_legal`
   - `'3'` / legacy aliases -> `only_stock`
   - empty/unknown -> `NULL` (prevents invalid enum cast values in runtime).
8. Referential integrity rule (mandatory):
   - a `site` must always reference an existing `customer` (including soft-deleted records).
   - orphan `sites` (`customer_id` without matching `customers.id`) are not allowed in target dataset.

## Timetable compatibility
- Legacy `timetables.arrayOrari` is copied to `timetables.hours_json`.
- Format is compatible with current frontend usage (`orarioApM`, `orarioChM`, `orarioApP`, `orarioChP`).

## Geocoding fixes included
- Legacy dump had 3 `sites` with missing coordinates (`lat/lng` null or zero).
- ETL now auto-fills them during `sites` import:
  - `site_id=1187` -> `45.7226878, 11.4373643` (`Via Monte Pasubio 144 36010 Zanè VI`)
  - `site_id=1190` -> `45.78108, 12.25944` (`Piazza Luciano Rigo 44 31024 Spresiano TV`)
  - `site_id=1203` -> `45.4084053, 11.8490154` (`Via Antonio Pacinotti 24 35030 Rubano PD`)

## Before running
1. Replace placeholders `OLD_DB` and `NEW_DB`.
2. Run on staging first.
3. Optionally enable TRUNCATE block if target data must be reset.
4. Run coherence check before sign-off:
```sql
SELECT s.id, s.customer_id
FROM `NEW_DB`.`sites` s
LEFT JOIN `NEW_DB`.`customers` c ON c.id = s.customer_id
WHERE c.id IS NULL;
```
Expected result: `0` rows.
If rows exist: remove orphan `sites` (and related dependent data) from migration output before go-live.

## Recommended execution order
1. Run `TRUE_LEGACY_01_CUSTOMERS_STAGE_AND_IMPORT.sql`.
2. Review `NEW_DB.legacy_customers_stage` rows with `import_error IS NOT NULL`.
3. Fix/decide customer exceptions.
4. Run `TRUE_LEGACY_02_SITES_AND_RELATED_IMPORT.sql` only after customer import is stable.
5. Run Laravel migrations (`php artisan migrate --force`) so DB-level normalization migrations are applied as safeguard.

## MCP Google Maps geocoding flow (sites without coordinates)
1. Run the ETL script once (creates `etl_site_geocoding_cache` and imports domain data).
2. Extract sites still missing coordinates:
```sql
SELECT
  s.id AS legacy_site_id,
  s.address AS query_address
FROM `NEW_DB`.`sites` s
WHERE (s.latitude IS NULL OR s.longitude IS NULL)
  AND NULLIF(TRIM(s.address), '') IS NOT NULL
ORDER BY s.id;
```
3. Geocode each `query_address` via MCP Google Maps and upsert into `NEW_DB.etl_site_geocoding_cache`:
```sql
INSERT INTO `NEW_DB`.`etl_site_geocoding_cache`
(`legacy_site_id`, `query_address`, `geocoded_latitude`, `geocoded_longitude`, `provider`, `provider_place_id`, `provider_formatted_address`, `geocoded_at`)
VALUES
-- (... one row per geocoded site ...)
ON DUPLICATE KEY UPDATE
  `query_address` = VALUES(`query_address`),
  `geocoded_latitude` = VALUES(`geocoded_latitude`),
  `geocoded_longitude` = VALUES(`geocoded_longitude`),
  `provider` = VALUES(`provider`),
  `provider_place_id` = VALUES(`provider_place_id`),
  `provider_formatted_address` = VALUES(`provider_formatted_address`),
  `geocoded_at` = VALUES(`geocoded_at`);
```
4. Re-run ETL (or only the geocoding `UPDATE` block) to patch coordinates in `NEW_DB.sites`.
