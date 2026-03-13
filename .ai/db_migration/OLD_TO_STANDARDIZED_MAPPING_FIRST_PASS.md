# OLD Dump -> STANDARDIZED DB Mapping (First Pass)

## Scope and Inputs
- Source dump (old): `.ai/db_migration/old_database_dump.sql`
- Target schema (standardized): `.ai/db_migration/current_database_schema_STANDARDIZED.sql`
- Reference rename map: `.ai/db_migration/RENAME_MAP.md`

## Method
- Compared `CREATE TABLE` table-by-table and column-by-column.
- Considered as **certain mapping** only:
  - exact same table and exact same column name (`1:1`), or
  - explicit old->new rename already formalized in `RENAME_MAP.md`, or
  - explicit migration evidence (`orders.truck_location -> orders.cargo_location`).
- Checked FK structure to detect possible relation inversions.

## Result (First Check)
- Tables detected in old dump: `44`
- Tables detected in standardized schema: `44`
- Old columns detected: `434`
- Old columns mapped with certainty: `434`
- Coverage: **100%**

## Certain Non-1:1 Mappings
Only changed/renamed mappings are listed here. All other columns are direct `1:1`.

### cargos
- `casse -> crate_count`
- `spazi_casse -> crate_slots`
- `spazi_bancale -> pallet_slots`

### customers
- `customer_occasionale -> is_occasional_customer`
- `ragione_sociale -> company_name`
- `partita_iva -> vat_number`
- `codice_fiscale -> tax_code`
- `indirizzo_legale -> legal_address`
- `codice_sdi -> sdi_code`
- `email_commerciale -> sales_email`
- `email_amministrativa -> administrative_email`
- `pec -> certified_email`
- `job_type -> business_type`

### journeys
- `dt_start -> planned_start_at`
- `dt_end -> planned_end_at`
- `real_dt_start -> actual_start_at`
- `real_dt_end -> actual_end_at`
- `warehouse_id_1 -> primary_warehouse_id`
- `warehouse_download_dt_1 -> primary_warehouse_download_at`
- `warehouse_id_2 -> secondary_warehouse_id`
- `warehouse_download_dt_2 -> secondary_warehouse_download_at`
- `cargo_for_vehicle_id -> vehicle_cargo_id`
- `cargo_for_trailer_id -> trailer_cargo_id`
- `logistic_id -> logistics_user_id`
- `state -> status`

### journey_cargos
- `truck_location -> cargo_location`
- `is_grounding -> is_grounded`
- `state -> status`

### journey_cargo_order_item
- `warehouse_download_id -> download_warehouse_id`

### journey_events
- `state -> status`

### orders
- `logistic_id -> logistics_user_id`
- `state -> status`
- `expected_withdraw_dt -> expected_withdraw_at`
- `real_withdraw_dt -> actual_withdraw_at`
- `has_ragno -> has_crane`
- `ragnista_id -> crane_operator_user_id`
- `machinery_time -> machinery_time_minutes`
- `truck_location -> cargo_location` (confirmed by migration `2026_03_05_030500_add_cargo_location_to_orders_hotfix.php`)

### order_holders
- `holder_piene -> filled_holders_count`
- `holder_vuote -> empty_holders_count`
- `holder_totale -> total_holders_count`

### order_items
- `adr -> has_adr`
- `adr_onu_code -> adr_un_code`
- `adr_lotto -> adr_lot_code`
- `warehouse_downaload_worker_id -> warehouse_download_worker_id`
- `warehouse_downaload_dt -> warehouse_download_at`
- `is_ragnabile -> is_crane_eligible`
- `selection_time -> selection_duration_minutes`
- `machinery_time_fraction -> machinery_time_share`
- `adr_totale -> is_adr_total`
- `adr_esenzione_totale -> has_adr_total_exemption`
- `adr_esenzione_parziale -> has_adr_partial_exemption`
- `state -> status`

### sites
- `denominazione -> name`
- `tipologia -> site_type`
- `indirizzo -> address`
- `lat -> latitude`
- `lng -> longitude`
- `fattore_rischio_calcolato -> calculated_risk_factor`
- `giorni_prossimo_ritiro -> days_until_next_withdraw`
- `has_transpallet_el -> has_electric_pallet_truck`
- `has_transpallet_ma -> has_manual_pallet_truck`

### timetables
- `hours_array -> hours_json`

### users
- `is_ragnista -> is_crane_operator`

### warehouses
- `denominazione -> name`
- `indirizzo -> address`
- `lat -> latitude`
- `lng -> longitude`
- `note -> notes`

### withdraws
- `withdraw_date -> withdrawn_at`
- `manual_insert -> is_manual_entry`
- `user_id -> created_by_user_id`

## Unmapped Fields (old -> new)
At this first check: **none**.

## Relation Check (FK / Possible Inversions)
- No inverted relation pattern was detected from naming/structure.
- `old` dump exposes `71` FK constraints; `STANDARDIZED` exposes `61`.
- Missing FK constraints in `STANDARDIZED` are all on legacy/renamed columns and are consistent with renamed FKs (example: `orders.logistic_id` replaced by `orders.logistics_user_id`).
- This is a **schema-constraint gap**, not a data-mapping gap.

## Open Items For Next Pass (if you want)
- Add a second MD with executable ETL rules (`INSERT ... SELECT`) per table using this mapping.
- Validate enum/value-domain compatibility on renamed status/location columns before full load.
