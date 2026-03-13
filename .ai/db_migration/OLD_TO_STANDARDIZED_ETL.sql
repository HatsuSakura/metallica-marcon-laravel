-- OLD -> STANDARDIZED ETL SQL
-- Generated automatically from:
--   old: .ai/db_migration/old_database_dump.sql
--   new: .ai/db_migration/current_database_schema_STANDARDIZED.sql
--
-- Usage:
--   1) Replace OLD_DB and NEW_DB with actual schema names.
--   2) Review TRUNCATE section before enabling.
--   3) Run in a transaction-like controlled window on staging first.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Optional cleanup (uncomment if target tables must be emptied first)
-- TRUNCATE TABLE `NEW_DB`.`areas`;
-- TRUNCATE TABLE `NEW_DB`.`cargos`;
-- TRUNCATE TABLE `NEW_DB`.`cer_codes`;
-- TRUNCATE TABLE `NEW_DB`.`holders`;
-- TRUNCATE TABLE `NEW_DB`.`catalog_items`;
-- TRUNCATE TABLE `NEW_DB`.`trailers`;
-- TRUNCATE TABLE `NEW_DB`.`vehicles`;
-- TRUNCATE TABLE `NEW_DB`.`customers`;
-- TRUNCATE TABLE `NEW_DB`.`users`;
-- TRUNCATE TABLE `NEW_DB`.`warehouses`;
-- TRUNCATE TABLE `NEW_DB`.`workers`;
-- TRUNCATE TABLE `NEW_DB`.`sites`;
-- TRUNCATE TABLE `NEW_DB`.`timetables`;
-- TRUNCATE TABLE `NEW_DB`.`internal_contacts`;
-- TRUNCATE TABLE `NEW_DB`.`area_site`;
-- TRUNCATE TABLE `NEW_DB`.`user_warehouse`;
-- TRUNCATE TABLE `NEW_DB`.`worker_warehouse`;
-- TRUNCATE TABLE `NEW_DB`.`journey_stops`;
-- TRUNCATE TABLE `NEW_DB`.`journey_stop_actions`;
-- TRUNCATE TABLE `NEW_DB`.`journeys`;
-- TRUNCATE TABLE `NEW_DB`.`journey_stop_orders`;
-- TRUNCATE TABLE `NEW_DB`.`journey_events`;
-- TRUNCATE TABLE `NEW_DB`.`journey_cargos`;
-- TRUNCATE TABLE `NEW_DB`.`orders`;
-- TRUNCATE TABLE `NEW_DB`.`order_item_groups`;
-- TRUNCATE TABLE `NEW_DB`.`order_items`;
-- TRUNCATE TABLE `NEW_DB`.`order_holders`;
-- TRUNCATE TABLE `NEW_DB`.`journey_cargo_order_item`;
-- TRUNCATE TABLE `NEW_DB`.`order_item_images`;
-- TRUNCATE TABLE `NEW_DB`.`withdraws`;
-- TRUNCATE TABLE `NEW_DB`.`recipes`;
-- TRUNCATE TABLE `NEW_DB`.`recipe_nodes`;
-- TRUNCATE TABLE `NEW_DB`.`order_item_explosions`;
-- TRUNCATE TABLE `NEW_DB`.`order_counters`;
-- TRUNCATE TABLE `NEW_DB`.`notifications`;
-- TRUNCATE TABLE `NEW_DB`.`versions`;
-- TRUNCATE TABLE `NEW_DB`.`sessions`;
-- TRUNCATE TABLE `NEW_DB`.`password_reset_tokens`;
-- TRUNCATE TABLE `NEW_DB`.`failed_jobs`;
-- TRUNCATE TABLE `NEW_DB`.`jobs`;
-- TRUNCATE TABLE `NEW_DB`.`job_batches`;
-- TRUNCATE TABLE `NEW_DB`.`cache`;
-- TRUNCATE TABLE `NEW_DB`.`cache_locks`;
-- TRUNCATE TABLE `NEW_DB`.`migrations`;

-- areas
INSERT INTO `NEW_DB`.`areas` (
  `id`, `created_at`, `updated_at`, `name`, `polygon`
)
SELECT
  `id`, `created_at`, `updated_at`, `name`, `polygon`
FROM `OLD_DB`.`areas`;

-- cargos
INSERT INTO `NEW_DB`.`cargos` (
  `id`, `created_at`, `updated_at`, `name`, `description`, `is_cargo`, `is_long`, `total_count`, `length`, `casse`, `crate_count`, `spazi_casse`, `crate_slots`, `spazi_bancale`, `pallet_slots`
)
SELECT
  `id`, `created_at`, `updated_at`, `name`, `description`, `is_cargo`, `is_long`, `total_count`, `length`, `casse`, `casse` AS `crate_count`, `spazi_casse`, `spazi_casse` AS `crate_slots`, `spazi_bancale`, `spazi_bancale` AS `pallet_slots`
FROM `OLD_DB`.`cargos`;

-- cer_codes
INSERT INTO `NEW_DB`.`cer_codes` (
  `id`, `created_at`, `updated_at`, `deleted_at`, `code`, `description`, `is_dangerous`
)
SELECT
  `id`, `created_at`, `updated_at`, `deleted_at`, `code`, `description`, `is_dangerous`
FROM `OLD_DB`.`cer_codes`;

-- holders
INSERT INTO `NEW_DB`.`holders` (
  `id`, `created_at`, `updated_at`, `name`, `is_custom`, `description`, `volume`, `equivalent_holder_id`, `equivalent_units`
)
SELECT
  `id`, `created_at`, `updated_at`, `name`, `is_custom`, `description`, `volume`, `equivalent_holder_id`, `equivalent_units`
FROM `OLD_DB`.`holders`;

-- catalog_items
INSERT INTO `NEW_DB`.`catalog_items` (
  `id`, `name`, `type`, `code`, `parent_catalog_item_id`, `is_active`, `created_at`, `updated_at`, `deleted_at`
)
SELECT
  `id`, `name`, `type`, `code`, `parent_catalog_item_id`, `is_active`, `created_at`, `updated_at`, `deleted_at`
FROM `OLD_DB`.`catalog_items`;

-- trailers
INSERT INTO `NEW_DB`.`trailers` (
  `id`, `created_at`, `updated_at`, `name`, `description`, `plate`, `is_front_cargo`, `load_capacity`
)
SELECT
  `id`, `created_at`, `updated_at`, `name`, `description`, `plate`, `is_front_cargo`, `load_capacity`
FROM `OLD_DB`.`trailers`;

-- vehicles
INSERT INTO `NEW_DB`.`vehicles` (
  `id`, `created_at`, `updated_at`, `name`, `description`, `plate`, `type`, `driver_id`, `trailer_id`, `has_trailer`, `load_capacity`
)
SELECT
  `id`, `created_at`, `updated_at`, `name`, `description`, `plate`, `type`, `driver_id`, `trailer_id`, `has_trailer`, `load_capacity`
FROM `OLD_DB`.`vehicles`;

-- customers
INSERT INTO `NEW_DB`.`customers` (
  `id`, `created_at`, `updated_at`, `deleted_at`, `is_occasional_customer`, `seller_id`, `company_name`, `vat_number`, `tax_code`, `legal_address`, `sdi_code`, `business_type`, `sales_email`, `administrative_email`, `certified_email`
)
SELECT
  `id`, `created_at`, `updated_at`, `deleted_at`, `customer_occasionale` AS `is_occasional_customer`, `seller_id`, `ragione_sociale` AS `company_name`, `partita_iva` AS `vat_number`, `codice_fiscale` AS `tax_code`, `indirizzo_legale` AS `legal_address`, `codice_sdi` AS `sdi_code`, `job_type` AS `business_type`, `email_commerciale` AS `sales_email`, `email_amministrativa` AS `administrative_email`, `pec` AS `certified_email`
FROM `OLD_DB`.`customers`;

-- users
INSERT INTO `NEW_DB`.`users` (
  `id`, `name`, `surname`, `user_code`, `email`, `email_verified_at`, `password`, `avatar`, `remember_token`, `created_at`, `updated_at`, `is_admin`, `customer_id`, `is_crane_operator`, `can_login`, `role`
)
SELECT
  `id`, `name`, `surname`, `user_code`, `email`, `email_verified_at`, `password`, `avatar`, `remember_token`, `created_at`, `updated_at`, `is_admin`, `customer_id`, `is_ragnista` AS `is_crane_operator`, `can_login`, `role`
FROM `OLD_DB`.`users`;

-- warehouses
INSERT INTO `NEW_DB`.`warehouses` (
  `id`, `created_at`, `updated_at`, `name`, `address`, `latitude`, `longitude`, `notes`
)
SELECT
  `id`, `created_at`, `updated_at`, `denominazione` AS `name`, `indirizzo` AS `address`, `lat` AS `latitude`, `lng` AS `longitude`, `note` AS `notes`
FROM `OLD_DB`.`warehouses`;

-- workers
INSERT INTO `NEW_DB`.`workers` (
  `id`, `name`, `surname`, `created_at`, `updated_at`
)
SELECT
  `id`, `name`, `surname`, `created_at`, `updated_at`
FROM `OLD_DB`.`workers`;

-- sites
INSERT INTO `NEW_DB`.`sites` (
  `id`, `created_at`, `updated_at`, `deleted_at`, `customer_id`, `name`, `site_type`, `is_main`, `address`, `latitude`, `longitude`, `calculated_risk_factor`, `days_until_next_withdraw`, `has_muletto`, `has_electric_pallet_truck`, `has_manual_pallet_truck`, `other_machines`, `has_adr_consultant`
)
SELECT
  `id`, `created_at`, `updated_at`, `deleted_at`, `customer_id`, `denominazione` AS `name`,
  CASE
    WHEN LOWER(TRIM(IFNULL(`tipologia`, ''))) IN ('1', 'fully_operative', 'fully operative', 'operativa', 'operativo', 'principale') THEN 'fully_operative'
    WHEN LOWER(TRIM(IFNULL(`tipologia`, ''))) IN ('2', 'only_legal', 'only legal', 'legal', 'solo_legal') THEN 'only_legal'
    WHEN LOWER(TRIM(IFNULL(`tipologia`, ''))) IN ('3', 'only_stock', 'only stock', 'stock', 'solo_stock', 'magazzino') THEN 'only_stock'
    WHEN NULLIF(TRIM(IFNULL(`tipologia`, '')), '') IS NULL THEN NULL
    ELSE NULL
  END AS `site_type`,
  `is_main`, `indirizzo` AS `address`, `lat` AS `latitude`, `lng` AS `longitude`, `fattore_rischio_calcolato` AS `calculated_risk_factor`, `giorni_prossimo_ritiro` AS `days_until_next_withdraw`, `has_muletto`, `has_transpallet_el` AS `has_electric_pallet_truck`, `has_transpallet_ma` AS `has_manual_pallet_truck`, `other_machines`, `has_adr_consultant`
FROM `OLD_DB`.`sites`;

-- timetables
INSERT INTO `NEW_DB`.`timetables` (
  `id`, `created_at`, `updated_at`, `site_id`, `hours_json`
)
SELECT
  `id`, `created_at`, `updated_at`, `site_id`, `hours_array` AS `hours_json`
FROM `OLD_DB`.`timetables`;

-- internal_contacts
INSERT INTO `NEW_DB`.`internal_contacts` (
  `id`, `created_at`, `updated_at`, `name`, `surname`, `phone`, `mobile`, `email`, `role`, `site_id`
)
SELECT
  `id`, `created_at`, `updated_at`, `name`, `surname`, `phone`, `mobile`, `email`, `role`, `site_id`
FROM `OLD_DB`.`internal_contacts`;

-- area_site
INSERT INTO `NEW_DB`.`area_site` (
  `id`, `created_at`, `updated_at`, `site_id`, `area_id`, `is_preferred`
)
SELECT
  `id`, `created_at`, `updated_at`, `site_id`, `area_id`, `is_preferred`
FROM `OLD_DB`.`area_site`;

-- user_warehouse
INSERT INTO `NEW_DB`.`user_warehouse` (
  `id`, `user_id`, `warehouse_id`, `created_at`, `updated_at`
)
SELECT
  `id`, `user_id`, `warehouse_id`, `created_at`, `updated_at`
FROM `OLD_DB`.`user_warehouse`;

-- worker_warehouse
INSERT INTO `NEW_DB`.`worker_warehouse` (
  `id`, `worker_id`, `warehouse_id`, `created_at`, `updated_at`
)
SELECT
  `id`, `worker_id`, `warehouse_id`, `created_at`, `updated_at`
FROM `OLD_DB`.`worker_warehouse`;

-- journey_stops
INSERT INTO `NEW_DB`.`journey_stops` (
  `id`, `journey_id`, `kind`, `customer_id`, `customer_visit_index`, `technical_action_id`, `description`, `planned_sequence`, `sequence`, `status`, `location_lat`, `location_lng`, `address_text`, `started_at`, `completed_at`, `notes`, `reason_code`, `reason_text`, `driver_notes`, `created_at`, `updated_at`
)
SELECT
  `id`, `journey_id`, `kind`, `customer_id`, `customer_visit_index`, `technical_action_id`, `description`, `planned_sequence`, `sequence`, `status`, `location_lat`, `location_lng`, `address_text`, `started_at`, `completed_at`, `notes`, `reason_code`, `reason_text`, `driver_notes`, `created_at`, `updated_at`
FROM `OLD_DB`.`journey_stops`;

-- journey_stop_actions
INSERT INTO `NEW_DB`.`journey_stop_actions` (
  `id`, `code`, `label`, `requires_location`, `is_active`, `created_at`, `updated_at`
)
SELECT
  `id`, `code`, `label`, `requires_location`, `is_active`, `created_at`, `updated_at`
FROM `OLD_DB`.`journey_stop_actions`;

-- journeys
INSERT INTO `NEW_DB`.`journeys` (
  `id`, `created_at`, `updated_at`, `deleted_at`, `planned_start_at`, `planned_end_at`, `actual_start_at`, `actual_end_at`, `is_double_load`, `is_temporary_storage`, `primary_warehouse_id`, `primary_warehouse_download_at`, `secondary_warehouse_id`, `secondary_warehouse_download_at`, `vehicle_id`, `vehicle_cargo_id`, `trailer_id`, `trailer_cargo_id`, `driver_id`, `logistics_user_id`, `status`, `plan_version`
)
SELECT
  `id`, `created_at`, `updated_at`, `deleted_at`, `dt_start` AS `planned_start_at`, `dt_end` AS `planned_end_at`, `real_dt_start` AS `actual_start_at`, `real_dt_end` AS `actual_end_at`, `is_double_load`, `is_temporary_storage`, `warehouse_id_1` AS `primary_warehouse_id`, `warehouse_download_dt_1` AS `primary_warehouse_download_at`, `warehouse_id_2` AS `secondary_warehouse_id`, `warehouse_download_dt_2` AS `secondary_warehouse_download_at`, `vehicle_id`, `cargo_for_vehicle_id` AS `vehicle_cargo_id`, `trailer_id`, `cargo_for_trailer_id` AS `trailer_cargo_id`, `driver_id`, `logistic_id` AS `logistics_user_id`, `state` AS `status`, `plan_version`
FROM `OLD_DB`.`journeys`;

-- journey_stop_orders
INSERT INTO `NEW_DB`.`journey_stop_orders` (
  `id`, `journey_id`, `journey_stop_id`, `order_id`, `created_at`, `updated_at`
)
SELECT
  `id`, `journey_id`, `journey_stop_id`, `order_id`, `created_at`, `updated_at`
FROM `OLD_DB`.`journey_stop_orders`;

-- journey_events
INSERT INTO `NEW_DB`.`journey_events` (
  `id`, `journey_id`, `journey_stop_id`, `status`, `payload`, `created_by_user_id`, `created_at`, `updated_at`
)
SELECT
  `id`, `journey_id`, `journey_stop_id`, `state` AS `status`, `payload`, `created_by_user_id`, `created_at`, `updated_at`
FROM `OLD_DB`.`journey_events`;

-- journey_cargos
INSERT INTO `NEW_DB`.`journey_cargos` (
  `id`, `created_at`, `updated_at`, `deleted_at`, `cargo_id`, `journey_id`, `cargo_location`, `is_grounded`, `warehouse_id`, `download_sequence`, `status`
)
SELECT
  `id`, `created_at`, `updated_at`, `deleted_at`, `cargo_id`, `journey_id`, `truck_location` AS `cargo_location`, `is_grounding` AS `is_grounded`, `warehouse_id`, `download_sequence`, `state` AS `status`
FROM `OLD_DB`.`journey_cargos`;

-- orders
INSERT INTO `NEW_DB`.`orders` (
  `id`, `legacy_code`, `created_at`, `updated_at`, `deleted_at`, `is_urgent`, `requested_at`, `customer_id`, `site_id`, `logistics_user_id`, `journey_id`, `status`, `cargo_location`, `expected_withdraw_at`, `actual_withdraw_at`, `has_crane`, `crane_operator_user_id`, `machinery_time_minutes`
)
SELECT
  `id`, `legacy_code`, `created_at`, `updated_at`, `deleted_at`, `is_urgent`, `requested_at`, `customer_id`, `site_id`, `logistic_id` AS `logistics_user_id`, `journey_id`, `state` AS `status`, `truck_location` AS `cargo_location`, `expected_withdraw_dt` AS `expected_withdraw_at`, `real_withdraw_dt` AS `actual_withdraw_at`, `has_ragno` AS `has_crane`, `ragnista_id` AS `crane_operator_user_id`, `machinery_time` AS `machinery_time_minutes`
FROM `OLD_DB`.`orders`;

-- order_item_groups
INSERT INTO `NEW_DB`.`order_item_groups` (
  `id`, `order_id`, `cer_code_id`, `label`, `created_at`, `updated_at`
)
SELECT
  `id`, `order_id`, `cer_code_id`, `label`, `created_at`, `updated_at`
FROM `OLD_DB`.`order_item_groups`;

-- order_items
INSERT INTO `NEW_DB`.`order_items` (
  `id`, `created_at`, `updated_at`, `updated_by_user_id`, `deleted_at`, `order_id`, `cer_code_id`, `order_item_group_id`, `holder_id`, `holder_quantity`, `is_bulk`, `custom_l_cm`, `custom_w_cm`, `custom_h_cm`, `description`, `weight_declared`, `weight_gross`, `weight_tare`, `weight_net`, `adr`, `has_adr`, `adr_un_code`, `adr_hp`, `adr_lotto`, `adr_lot_code`, `adr_volume`, `warehouse_id`, `warehouse_notes`, `is_holder_dirty`, `total_dirty_holders`, `is_holder_broken`, `total_broken_holders`, `is_warehouse_added`, `is_not_found`, `has_non_conformity`, `has_exploded_children`, `warehouse_non_conformity`, `warehouse_manager_id`, `warehouse_download_worker_id`, `warehouse_download_at`, `warehouse_weighing_worker_id`, `warehouse_weighing_dt`, `warehouse_selection_worker_id`, `warehouse_selection_dt`, `has_selection`, `selection_duration_minutes`, `is_crane_eligible`, `machinery_time_fraction`, `machinery_time_share`, `is_machinery_time_manual`, `is_transshipment`, `recognized_price`, `recognized_weight`, `adr_totale`, `is_adr_total`, `adr_esenzione_totale`, `has_adr_total_exemption`, `adr_esenzione_parziale`, `has_adr_partial_exemption`, `status`
)
SELECT
  `id`, `created_at`, `updated_at`, `updated_by_user_id`, `deleted_at`, `order_id`, `cer_code_id`, `order_item_group_id`, `holder_id`, `holder_quantity`, `is_bulk`, `custom_l_cm`, `custom_w_cm`, `custom_h_cm`, `description`, `weight_declared`, `weight_gross`, `weight_tare`, `weight_net`, `adr`, `adr` AS `has_adr`, `adr_onu_code` AS `adr_un_code`, `adr_hp`, `adr_lotto`, `adr_lotto` AS `adr_lot_code`, `adr_volume`, `warehouse_id`, `warehouse_notes`, `is_holder_dirty`, `total_dirty_holders`, `is_holder_broken`, `total_broken_holders`, `is_warehouse_added`, `is_not_found`, `has_non_conformity`, `has_exploded_children`, `warehouse_non_conformity`, `warehouse_manager_id`, `warehouse_downaload_worker_id` AS `warehouse_download_worker_id`, `warehouse_downaload_dt` AS `warehouse_download_at`, `warehouse_weighing_worker_id`, `warehouse_weighing_dt`, `warehouse_selection_worker_id`, `warehouse_selection_dt`, `has_selection`, `selection_time` AS `selection_duration_minutes`, `is_ragnabile` AS `is_crane_eligible`, `machinery_time_fraction`, `machinery_time_fraction` AS `machinery_time_share`, `is_machinery_time_manual`, `is_transshipment`, `recognized_price`, `recognized_weight`, `adr_totale`, `adr_totale` AS `is_adr_total`, `adr_esenzione_totale`, `adr_esenzione_totale` AS `has_adr_total_exemption`, `adr_esenzione_parziale`, `adr_esenzione_parziale` AS `has_adr_partial_exemption`, `state` AS `status`
FROM `OLD_DB`.`order_items`;

-- order_holders
INSERT INTO `NEW_DB`.`order_holders` (
  `id`, `created_at`, `updated_at`, `deleted_at`, `order_id`, `holder_id`, `filled_holders_count`, `empty_holders_count`, `total_holders_count`
)
SELECT
  `id`, `created_at`, `updated_at`, `deleted_at`, `order_id`, `holder_id`, `holder_piene` AS `filled_holders_count`, `holder_vuote` AS `empty_holders_count`, `holder_totale` AS `total_holders_count`
FROM `OLD_DB`.`order_holders`;

-- journey_cargo_order_item
INSERT INTO `NEW_DB`.`journey_cargo_order_item` (
  `id`, `journey_cargo_id`, `order_item_id`, `is_double_load`, `download_warehouse_id`, `created_at`, `updated_at`
)
SELECT
  `id`, `journey_cargo_id`, `order_item_id`, `is_double_load`, `warehouse_download_id` AS `download_warehouse_id`, `created_at`, `updated_at`
FROM `OLD_DB`.`journey_cargo_order_item`;

-- order_item_images
INSERT INTO `NEW_DB`.`order_item_images` (
  `id`, `order_item_id`, `path`, `original_name`, `mime_type`, `size`, `created_at`, `updated_at`
)
SELECT
  `id`, `order_item_id`, `path`, `original_name`, `mime_type`, `size`, `created_at`, `updated_at`
FROM `OLD_DB`.`order_item_images`;

-- withdraws
INSERT INTO `NEW_DB`.`withdraws` (
  `id`, `created_at`, `updated_at`, `deleted_at`, `withdrawn_at`, `residue_percentage`, `customer_id`, `site_id`, `vehicle_id`, `driver_id`, `created_by_user_id`, `is_manual_entry`
)
SELECT
  `id`, `created_at`, `updated_at`, `deleted_at`, `withdraw_date` AS `withdrawn_at`, `residue_percentage`, `customer_id`, `site_id`, `vehicle_id`, `driver_id`, `user_id` AS `created_by_user_id`, `manual_insert` AS `is_manual_entry`
FROM `OLD_DB`.`withdraws`;

-- recipes
INSERT INTO `NEW_DB`.`recipes` (
  `id`, `name`, `version`, `is_active`, `created_at`, `updated_at`, `deleted_at`, `catalog_item_id`
)
SELECT
  `id`, `name`, `version`, `is_active`, `created_at`, `updated_at`, `deleted_at`, `catalog_item_id`
FROM `OLD_DB`.`recipes`;

-- recipe_nodes
INSERT INTO `NEW_DB`.`recipe_nodes` (
  `id`, `recipe_id`, `parent_node_id`, `catalog_item_id`, `is_override`, `sort`, `suggested_ratio`, `created_at`, `updated_at`
)
SELECT
  `id`, `recipe_id`, `parent_node_id`, `catalog_item_id`, `is_override`, `sort`, `suggested_ratio`, `created_at`, `updated_at`
FROM `OLD_DB`.`recipe_nodes`;

-- order_item_explosions
INSERT INTO `NEW_DB`.`order_item_explosions` (
  `id`, `order_item_id`, `parent_explosion_id`, `catalog_item_id`, `explosion_source`, `recipe_id`, `recipe_version`, `weight_net`, `notes`, `sort`, `created_at`, `updated_at`
)
SELECT
  `id`, `order_item_id`, `parent_explosion_id`, `catalog_item_id`, `explosion_source`, `recipe_id`, `recipe_version`, `weight_net`, `notes`, `sort`, `created_at`, `updated_at`
FROM `OLD_DB`.`order_item_explosions`;

-- order_counters
INSERT INTO `NEW_DB`.`order_counters` (
  `year`, `counter`, `created_at`, `updated_at`
)
SELECT
  `year`, `counter`, `created_at`, `updated_at`
FROM `OLD_DB`.`order_counters`;

-- notifications
INSERT INTO `NEW_DB`.`notifications` (
  `id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`
)
SELECT
  `id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`
FROM `OLD_DB`.`notifications`;

-- versions
INSERT INTO `NEW_DB`.`versions` (
  `version_id`, `versionable_id`, `versionable_type`, `user_id`, `model_data`, `reason`, `created_at`, `updated_at`
)
SELECT
  `version_id`, `versionable_id`, `versionable_type`, `user_id`, `model_data`, `reason`, `created_at`, `updated_at`
FROM `OLD_DB`.`versions`;

-- sessions
INSERT INTO `NEW_DB`.`sessions` (
  `id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`
)
SELECT
  `id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`
FROM `OLD_DB`.`sessions`;

-- password_reset_tokens
INSERT INTO `NEW_DB`.`password_reset_tokens` (
  `email`, `token`, `created_at`
)
SELECT
  `email`, `token`, `created_at`
FROM `OLD_DB`.`password_reset_tokens`;

-- failed_jobs
INSERT INTO `NEW_DB`.`failed_jobs` (
  `id`, `uuid`, `connection`, `queue`, `payload`, `exception`, `failed_at`
)
SELECT
  `id`, `uuid`, `connection`, `queue`, `payload`, `exception`, `failed_at`
FROM `OLD_DB`.`failed_jobs`;

-- jobs
INSERT INTO `NEW_DB`.`jobs` (
  `id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at`
)
SELECT
  `id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at`
FROM `OLD_DB`.`jobs`;

-- job_batches
INSERT INTO `NEW_DB`.`job_batches` (
  `id`, `name`, `total_jobs`, `pending_jobs`, `failed_jobs`, `failed_job_ids`, `options`, `cancelled_at`, `created_at`, `finished_at`
)
SELECT
  `id`, `name`, `total_jobs`, `pending_jobs`, `failed_jobs`, `failed_job_ids`, `options`, `cancelled_at`, `created_at`, `finished_at`
FROM `OLD_DB`.`job_batches`;

-- cache
INSERT INTO `NEW_DB`.`cache` (
  `key`, `value`, `expiration`
)
SELECT
  `key`, `value`, `expiration`
FROM `OLD_DB`.`cache`;

-- cache_locks
INSERT INTO `NEW_DB`.`cache_locks` (
  `key`, `owner`, `expiration`
)
SELECT
  `key`, `owner`, `expiration`
FROM `OLD_DB`.`cache_locks`;

-- migrations
INSERT INTO `NEW_DB`.`migrations` (
  `id`, `migration`, `batch`
)
SELECT
  `id`, `migration`, `batch`
FROM `OLD_DB`.`migrations`;

SET FOREIGN_KEY_CHECKS = 1;
