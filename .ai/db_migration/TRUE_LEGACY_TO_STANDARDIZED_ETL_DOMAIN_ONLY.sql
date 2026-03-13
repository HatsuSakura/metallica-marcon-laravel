-- TRUE LEGACY -> STANDARDIZED ETL (DOMAIN-ONLY)
-- Source: .ai/db_migration/old_database_dump.sql (true old system)
-- Target: .ai/db_migration/current_database_schema_STANDARDIZED.sql
--
-- IMPORTANT:
-- 1) Replace OLD_DB and NEW_DB placeholders.
-- 2) This script does NOT migrate legacy users (CUSTOMER/ADMIN/ADM).
-- 3) Sentinel strategy for withdraw FKs:
--    - withdraws.vehicle_id NULL/0 -> 9999
--    - withdraws.driver_id  NULL/0 -> 9999
-- 4) customers.jobType:
--    - 0 -> NULL
--    - else cast numeric to string
-- 5) internal_contacts are generated from customers.responsabileSmaltimenti + telefonoPrincipale.
-- 6) Optional geocoding patch:
--    - fill missing site coordinates from NEW_DB.etl_site_geocoding_cache
--    - cache expected to be populated externally (e.g. MCP Google Maps).

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Optional cleanup (uncomment if target needs reset)
-- TRUNCATE TABLE `NEW_DB`.`internal_contacts`;
-- TRUNCATE TABLE `NEW_DB`.`withdraws`;
-- TRUNCATE TABLE `NEW_DB`.`timetables`;
-- TRUNCATE TABLE `NEW_DB`.`sites`;
-- TRUNCATE TABLE `NEW_DB`.`customers`;

-- Sentinel user for withdraws.driver_id
INSERT INTO `NEW_DB`.`users`
(`id`, `name`, `surname`, `email`, `password`, `created_at`, `updated_at`, `is_admin`, `can_login`, `role`)
SELECT
  9999, 'Unknown', 'Driver', NULL, NULL, NOW(), NOW(), 0, 0, NULL
WHERE NOT EXISTS (SELECT 1 FROM `NEW_DB`.`users` u WHERE u.`id` = 9999);

-- Sentinel vehicle for withdraws.vehicle_id
INSERT INTO `NEW_DB`.`vehicles`
(`id`, `created_at`, `updated_at`, `name`, `description`, `plate`, `type`, `driver_id`, `trailer_id`, `has_trailer`, `load_capacity`)
SELECT
  9999, NOW(), NOW(), 'UNKNOWN VEHICLE', 'Legacy sentinel vehicle', 'UNKNOWN-9999', 'unknown', NULL, NULL, 0, 0
WHERE NOT EXISTS (SELECT 1 FROM `NEW_DB`.`vehicles` v WHERE v.`id` = 9999);

-- customers
INSERT INTO `NEW_DB`.`customers` (
  `id`, `created_at`, `updated_at`, `deleted_at`,
  `is_occasional_customer`, `seller_id`,
  `company_name`, `vat_number`, `tax_code`, `legal_address`, `sdi_code`,
  `business_type`, `sales_email`, `administrative_email`, `certified_email`
)
SELECT
  c.`id`, c.`created_at`, c.`updated_at`, c.`deleted_at`,
  c.`customerOccasionale`, c.`id_seller`,
  c.`ragioneSociale`, c.`partitaIva`, c.`codiceFiscale`, c.`indirizzoLegale`, c.`codiceSdi`,
  CASE
    WHEN c.`jobType` = 0 THEN NULL
    ELSE CAST(c.`jobType` AS CHAR)
  END AS `business_type`,
  c.`emailCommerciale`, c.`emailAmministrativa`, c.`pec`
FROM `OLD_DB`.`customers` c;

-- sites
INSERT INTO `NEW_DB`.`sites` (
  `id`, `created_at`, `updated_at`, `deleted_at`,
  `customer_id`, `name`, `site_type`, `is_main`, `address`, `latitude`, `longitude`,
  `calculated_risk_factor`, `days_until_next_withdraw`,
  `has_muletto`, `has_electric_pallet_truck`, `has_manual_pallet_truck`,
  `other_machines`, `has_adr_consultant`
)
SELECT
  s.`id`, s.`created_at`, s.`updated_at`, s.`deleted_at`,
  s.`id_customer`, s.`denominazione`,
  CASE
    WHEN LOWER(TRIM(IFNULL(s.`tipologia`, ''))) IN ('1', 'fully_operative', 'fully operative', 'operativa', 'operativo', 'principale') THEN 'fully_operative'
    WHEN LOWER(TRIM(IFNULL(s.`tipologia`, ''))) IN ('2', 'only_legal', 'only legal', 'legal', 'solo_legal') THEN 'only_legal'
    WHEN LOWER(TRIM(IFNULL(s.`tipologia`, ''))) IN ('3', 'only_stock', 'only stock', 'stock', 'solo_stock', 'magazzino') THEN 'only_stock'
    WHEN NULLIF(TRIM(IFNULL(s.`tipologia`, '')), '') IS NULL THEN NULL
    ELSE NULL
  END AS `site_type`,
  1 AS `is_main`, s.`indirizzo`,
  CASE
    WHEN s.`id` = 1187 AND (s.`lat` IS NULL OR s.`lat` = 0) THEN 45.7226878
    WHEN s.`id` = 1190 AND (s.`lat` IS NULL OR s.`lat` = 0) THEN 45.78108
    WHEN s.`id` = 1203 AND (s.`lat` IS NULL OR s.`lat` = 0) THEN 45.4084053
    ELSE s.`lat`
  END AS `latitude`,
  CASE
    WHEN s.`id` = 1187 AND (s.`lng` IS NULL OR s.`lng` = 0) THEN 11.4373643
    WHEN s.`id` = 1190 AND (s.`lng` IS NULL OR s.`lng` = 0) THEN 12.25944
    WHEN s.`id` = 1203 AND (s.`lng` IS NULL OR s.`lng` = 0) THEN 11.8490154
    ELSE s.`lng`
  END AS `longitude`,
  s.`fattoreRischioCalcolato`, s.`giorniProssimoRitiro`,
  0 AS `has_muletto`, NULL AS `has_electric_pallet_truck`, NULL AS `has_manual_pallet_truck`,
  '' AS `other_machines`, 0 AS `has_adr_consultant`
FROM `OLD_DB`.`sites` s;

-- geocoding cache (optional, external feed via MCP Google Maps)
CREATE TABLE IF NOT EXISTS `NEW_DB`.`etl_site_geocoding_cache` (
  `legacy_site_id` BIGINT UNSIGNED NOT NULL,
  `query_address` VARCHAR(500) NULL,
  `geocoded_latitude` DOUBLE NULL,
  `geocoded_longitude` DOUBLE NULL,
  `provider` VARCHAR(100) NULL,
  `provider_place_id` VARCHAR(191) NULL,
  `provider_formatted_address` VARCHAR(500) NULL,
  `geocoded_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`legacy_site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- apply geocoded coordinates only where target site coordinates are missing
UPDATE `NEW_DB`.`sites` s
JOIN `NEW_DB`.`etl_site_geocoding_cache` g ON g.`legacy_site_id` = s.`id`
SET
  s.`latitude` = COALESCE(s.`latitude`, g.`geocoded_latitude`),
  s.`longitude` = COALESCE(s.`longitude`, g.`geocoded_longitude`),
  s.`updated_at` = NOW()
WHERE
  (s.`latitude` IS NULL OR s.`longitude` IS NULL)
  AND g.`geocoded_latitude` IS NOT NULL
  AND g.`geocoded_longitude` IS NOT NULL;

-- timetables (JSON format already compatible: orarioApM/orarioChM/orarioApP/orarioChP)
INSERT INTO `NEW_DB`.`timetables` (
  `id`, `created_at`, `updated_at`, `site_id`, `hours_json`
)
SELECT
  t.`id`, t.`created_at`, t.`updated_at`, t.`id_site`, t.`arrayOrari`
FROM `OLD_DB`.`timetables` t;

-- internal_contacts from legacy customer fields:
-- - role fixed to 'smaltimenti'
-- - attach to all sites of the customer
-- - phone classifier:
--   normalized starts with '3' => mobile
--   otherwise => phone
INSERT INTO `NEW_DB`.`internal_contacts` (
  `created_at`, `updated_at`, `name`, `surname`, `phone`, `mobile`, `email`, `role`, `site_id`
)
SELECT DISTINCT
  NOW() AS `created_at`,
  NOW() AS `updated_at`,
  CASE
    WHEN TRIM(c.`responsabileSmaltimenti`) = '' THEN NULL
    WHEN LOCATE(' ', TRIM(c.`responsabileSmaltimenti`)) = 0 THEN TRIM(c.`responsabileSmaltimenti`)
    ELSE SUBSTRING_INDEX(TRIM(c.`responsabileSmaltimenti`), ' ', 1)
  END AS `name`,
  CASE
    WHEN TRIM(c.`responsabileSmaltimenti`) = '' THEN NULL
    WHEN LOCATE(' ', TRIM(c.`responsabileSmaltimenti`)) = 0 THEN NULL
    ELSE TRIM(SUBSTRING(TRIM(c.`responsabileSmaltimenti`), LOCATE(' ', TRIM(c.`responsabileSmaltimenti`)) + 1))
  END AS `surname`,
  CASE
    WHEN REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(IFNULL(c.`telefonoPrincipale`, '')), ' ', ''), '+39', ''), '-', ''), '.', ''), '/', ''), '(', '') LIKE '3%'
      THEN NULL
    ELSE NULLIF(TRIM(c.`telefonoPrincipale`), '')
  END AS `phone`,
  CASE
    WHEN REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(IFNULL(c.`telefonoPrincipale`, '')), ' ', ''), '+39', ''), '-', ''), '.', ''), '/', ''), '(', '') LIKE '3%'
      THEN NULLIF(TRIM(c.`telefonoPrincipale`), '')
    ELSE NULL
  END AS `mobile`,
  NULL AS `email`,
  'smaltimenti' AS `role`,
  s.`id` AS `site_id`
FROM `OLD_DB`.`customers` c
JOIN `NEW_DB`.`sites` s ON s.`customer_id` = c.`id`
WHERE NULLIF(TRIM(IFNULL(c.`responsabileSmaltimenti`, '')), '') IS NOT NULL
   OR NULLIF(TRIM(IFNULL(c.`telefonoPrincipale`, '')), '') IS NOT NULL;

-- withdraws
INSERT INTO `NEW_DB`.`withdraws` (
  `id`, `created_at`, `updated_at`, `deleted_at`,
  `withdrawn_at`, `residue_percentage`,
  `customer_id`, `site_id`, `vehicle_id`, `driver_id`,
  `created_by_user_id`, `is_manual_entry`
)
SELECT
  w.`id`, w.`created_at`, w.`updated_at`, w.`deleted_at`,
  w.`dataRitiro`, w.`percentualeResidua`,
  w.`id_customer`, w.`id_site`,
  CASE
    WHEN w.`id_vehicle` IS NULL OR w.`id_vehicle` = 0 THEN 9999
    ELSE w.`id_vehicle`
  END AS `vehicle_id`,
  CASE
    WHEN w.`id_driver` IS NULL OR w.`id_driver` = 0 THEN 9999
    ELSE w.`id_driver`
  END AS `driver_id`,
  w.`id_user`,
  w.`insManuale`
FROM `OLD_DB`.`withdraws` w;

SET FOREIGN_KEY_CHECKS = 1;
