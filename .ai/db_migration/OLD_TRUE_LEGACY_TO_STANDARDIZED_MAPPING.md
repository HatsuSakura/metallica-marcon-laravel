# True Legacy -> Standardized Mapping (First Real Pass)

## Input Compared
- Legacy source: `.ai/db_migration/old_database_dump.sql` (true old system)
- Target: `.ai/db_migration/current_database_schema_STANDARDIZED.sql`

## High-Level Outcome
- Legacy tables found: `14`
- Standardized tables found: `44`
- Common tables: `6`
  - `customers`, `sites`, `timetables`, `users`, `withdraws`, `migrations`
- Legacy-only tables (not in new app):
  - `oauth_access_tokens`
  - `oauth_auth_codes`
  - `oauth_clients`
  - `oauth_personal_access_clients`
  - `oauth_refresh_tokens`
  - `telescope_entries`
  - `telescope_entries_tags`
  - `telescope_monitoring`

## Certain Mappings (Only 100% Safe)

### `customers` -> `customers`
- `id -> id`
- `created_at -> created_at`
- `updated_at -> updated_at`
- `deleted_at -> deleted_at`
- `customerOccasionale -> is_occasional_customer`
- `id_seller -> seller_id`
- `ragioneSociale -> company_name`
- `partitaIva -> vat_number`
- `codiceFiscale -> tax_code`
- `indirizzoLegale -> legal_address`
- `codiceSdi -> sdi_code`
- `emailCommerciale -> sales_email`
- `emailAmministrativa -> administrative_email`
- `pec -> certified_email`

### `sites` -> `sites`
- `id -> id`
- `created_at -> created_at`
- `updated_at -> updated_at`
- `deleted_at -> deleted_at`
- `id_customer -> customer_id`
- `denominazione -> name`
- `tipologia -> site_type`
- `indirizzo -> address`
- `lat -> latitude`
- `lng -> longitude`
- `fattoreRischioCalcolato -> calculated_risk_factor`
- `giorniProssimoRitiro -> days_until_next_withdraw`

### `timetables` -> `timetables`
- `id -> id`
- `created_at -> created_at`
- `updated_at -> updated_at`
- `id_site -> site_id`
- `arrayOrari -> hours_json`

### `users` -> `users`
- `id -> id`
- `name -> name`
- `email -> email`
- `email_verified_at -> email_verified_at`
- `password -> password`
- `id_customer -> customer_id`
- `remember_token -> remember_token`
- `created_at -> created_at`
- `updated_at -> updated_at`

### `withdraws` -> `withdraws`
- `id -> id`
- `created_at -> created_at`
- `updated_at -> updated_at`
- `deleted_at -> deleted_at`
- `dataRitiro -> withdrawn_at`
- `percentualeResidua -> residue_percentage`
- `id_customer -> customer_id`
- `id_site -> site_id`
- `id_user -> created_by_user_id`
- `insManuale -> is_manual_entry`

## Uncertain / Not Mapped Yet (Needs Decision)

### `customers`
- `jobType` -> candidate `business_type`, but source is numeric and target is text.
  - Not mapped now (semantic transform required).
- `responsabileSmaltimenti` -> no direct target column.
- `telefonoPrincipale` -> no direct target column.

### `sites`
- New columns with no legacy source:
  - `is_main`
  - `has_muletto`
  - `has_electric_pallet_truck`
  - `has_manual_pallet_truck`
  - `other_machines`
  - `has_adr_consultant`

### `timetables`
- Legacy has `deleted_at`; target table does not.

### `users`
- `profile` -> target likely `role` and/or `is_admin`, but values differ (`ADMIN`, `ADM`, `CUSTOMER`) vs new enum (`manager`, `logistic`, `driver`, ...).
  - Not mapped now (role conversion matrix required).
- New columns with no legacy source:
  - `surname`
  - `user_code`
  - `avatar`
  - `is_admin` (maybe derivable from `profile`, but not applied without rule)
  - `is_crane_operator`
  - `can_login`
  - `role`

### `withdraws`
- `id_vehicle -> vehicle_id` is semantically plausible but **not safe yet**:
  - legacy dataset uses nullable/zero conventions;
  - target `vehicle_id` is required FK.
- `id_driver -> driver_id` same issue:
  - legacy dataset uses nullable/zero conventions;
  - target `driver_id` is required FK.

## FK/Model Differences That Impact ETL
- Legacy is MyISAM-based for business tables (weak/no FK enforcement).
- Standardized schema is InnoDB with strong FK constraints.
- This means an ETL must include:
  - normalization of nullable/zero foreign keys,
  - role conversion for users,
  - optional staged loads and defaulting strategy for required FKs.

## Decisions Needed Before Final ETL
1. `users.profile` conversion rules -> `users.role` and `users.is_admin`.
2. `customers.jobType` conversion rules -> `customers.business_type`.
3. `withdraws.id_vehicle` and `withdraws.id_driver` strategy when `NULL/0`:
   - allow temporary nullable in target?
   - map to sentinel/default entities?
   - or skip invalid rows.
4. destination for `customers.responsabileSmaltimenti` and `customers.telefonoPrincipale`:
   - drop, or store in a new/notes field.
