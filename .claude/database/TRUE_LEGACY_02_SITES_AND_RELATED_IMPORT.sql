-- TRUE LEGACY -> STANDARDIZED
-- STEP 02: import sites + related entities
--
-- Preconditions:
-- - STEP 01 completed (customers imported and clean)
-- - etl_exception_log created by STEP 01
-- - placeholders replaced:
--   OLD_DB = legacy schema name
--   NEW_DB = standardized schema name
--
-- Steps:
--   A. Import sites (encoding-clean, customer-filtered, hardcoded geocoding fixes)
--   B. Create default "Principale" site for customers with no site
--   C. Geocoding patch from etl_site_geocoding_cache
--   D. Import timetables
--   E. Import internal_contacts
--   F. Log all step-2 exceptions into etl_exception_log
--   G. Reports

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Optional cleanup (uncomment only if re-running from scratch)
-- TRUNCATE TABLE `NEW_DB`.`internal_contacts`;
-- TRUNCATE TABLE `NEW_DB`.`timetables`;
-- TRUNCATE TABLE `NEW_DB`.`sites`;

-- Clear step-2 exceptions from previous runs (idempotent)
DELETE FROM `NEW_DB`.`etl_exception_log` WHERE `step` = 2;

-- ============================================================
-- A. IMPORT SITES FROM LEGACY
-- Encoding fix applied inline via CONVERT(BINARY CONVERT(field USING latin1) USING utf8mb4).
-- This reverses double-UTF8 encoding present in legacy dump
-- (e.g. "MansuÃ¨" -> "Mansuè").
-- NULLIF(lat/lng, 0): lat=0 and lng=0 are treated as missing, not valid coordinates.
-- ============================================================

INSERT INTO `NEW_DB`.`sites` (
  `id`, `created_at`, `updated_at`, `deleted_at`,
  `customer_id`, `name`, `site_type`, `is_main`, `address`, `latitude`, `longitude`,
  `calculated_risk_factor`, `days_until_next_withdraw`,
  `has_muletto`, `has_electric_pallet_truck`, `has_manual_pallet_truck`,
  `other_machines`, `has_adr_consultant`
)
SELECT
  s.`id`, s.`created_at`, s.`updated_at`, s.`deleted_at`,
  s.`id_customer`,
  CONVERT(BINARY CONVERT(s.`denominazione` USING latin1) USING utf8mb4),
  CASE
    WHEN LOWER(TRIM(IFNULL(s.`tipologia`, ''))) IN ('1', 'fully_operative', 'fully operative', 'operativa', 'operativo', 'principale') THEN 'fully_operative'
    WHEN LOWER(TRIM(IFNULL(s.`tipologia`, ''))) IN ('2', 'only_legal', 'only legal', 'legal', 'solo_legal') THEN 'only_legal'
    WHEN LOWER(TRIM(IFNULL(s.`tipologia`, ''))) IN ('3', 'only_stock', 'only stock', 'stock', 'solo_stock', 'magazzino') THEN 'only_stock'
    ELSE NULL
  END AS `site_type`,
  1 AS `is_main`,
  CONVERT(BINARY CONVERT(s.`indirizzo` USING latin1) USING utf8mb4),
  CASE
    WHEN s.`id` = 1187 AND (s.`lat` IS NULL OR s.`lat` = 0) THEN 45.7226878
    WHEN s.`id` = 1190 AND (s.`lat` IS NULL OR s.`lat` = 0) THEN 45.78108
    WHEN s.`id` = 1203 AND (s.`lat` IS NULL OR s.`lat` = 0) THEN 45.4084053
    ELSE NULLIF(s.`lat`, 0)
  END AS `latitude`,
  CASE
    WHEN s.`id` = 1187 AND (s.`lng` IS NULL OR s.`lng` = 0) THEN 11.4373643
    WHEN s.`id` = 1190 AND (s.`lng` IS NULL OR s.`lng` = 0) THEN 12.25944
    WHEN s.`id` = 1203 AND (s.`lng` IS NULL OR s.`lng` = 0) THEN 11.8490154
    ELSE NULLIF(s.`lng`, 0)
  END AS `longitude`,
  s.`fattoreRischioCalcolato`, s.`giorniProssimoRitiro`,
  0, NULL, NULL, '', 0
FROM `OLD_DB`.`sites` s
WHERE EXISTS (SELECT 1 FROM `NEW_DB`.`customers` c WHERE c.`id` = s.`id_customer`)
  AND NOT EXISTS (SELECT 1 FROM `NEW_DB`.`sites` x WHERE x.`id` = s.`id`);

-- ============================================================
-- B. CREATE DEFAULT "Principale" SITE FOR CUSTOMERS WITHOUT ANY SITE
-- Address from customers.legal_address. Coordinates left NULL -> geocoding needed.
-- ============================================================

INSERT INTO `NEW_DB`.`sites` (
  `created_at`, `updated_at`,
  `customer_id`, `name`, `site_type`, `is_main`, `address`,
  `latitude`, `longitude`,
  `calculated_risk_factor`, `days_until_next_withdraw`,
  `has_muletto`, `has_electric_pallet_truck`, `has_manual_pallet_truck`,
  `other_machines`, `has_adr_consultant`
)
SELECT
  NOW(), NOW(),
  c.`id`, 'Principale', NULL, 1, c.`legal_address`,
  NULL, NULL,
  0, 0,
  0, NULL, NULL, '', 0
FROM `NEW_DB`.`customers` c
WHERE NOT EXISTS (
  SELECT 1 FROM `NEW_DB`.`sites` s WHERE s.`customer_id` = c.`id`
);

-- ============================================================
-- C. GEOCODING CACHE
-- Populate etl_site_geocoding_cache externally (MCP Google Maps),
-- then re-run this UPDATE block to apply coordinates.
-- ============================================================

CREATE TABLE IF NOT EXISTS `NEW_DB`.`etl_site_geocoding_cache` (
  `legacy_site_id`             bigint unsigned NOT NULL,
  `query_address`              varchar(500) DEFAULT NULL,
  `geocoded_latitude`          double DEFAULT NULL,
  `geocoded_longitude`         double DEFAULT NULL,
  `provider`                   varchar(64) DEFAULT NULL,
  `provider_place_id`          varchar(255) DEFAULT NULL,
  `provider_formatted_address` varchar(500) DEFAULT NULL,
  `geocoded_at`                timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`legacy_site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE `NEW_DB`.`sites` s
JOIN `NEW_DB`.`etl_site_geocoding_cache` g ON g.`legacy_site_id` = s.`id`
SET
  s.`latitude`  = g.`geocoded_latitude`,
  s.`longitude` = g.`geocoded_longitude`
WHERE (s.`latitude` IS NULL OR s.`longitude` IS NULL)
  AND g.`geocoded_latitude`  IS NOT NULL
  AND g.`geocoded_longitude` IS NOT NULL;

-- ============================================================
-- D. IMPORT TIMETABLES
-- arrayOrari is already compatible with hours_json format.
-- ============================================================

INSERT INTO `NEW_DB`.`timetables` (
  `id`, `created_at`, `updated_at`, `site_id`, `hours_json`
)
SELECT
  t.`id`, t.`created_at`, t.`updated_at`, t.`id_site`, t.`arrayOrari`
FROM `OLD_DB`.`timetables` t
WHERE EXISTS (SELECT 1 FROM `NEW_DB`.`sites` s WHERE s.`id` = t.`id_site`)
  AND NOT EXISTS (SELECT 1 FROM `NEW_DB`.`timetables` x WHERE x.`id` = t.`id`);

-- ============================================================
-- E. IMPORT INTERNAL_CONTACTS
-- Derived from legacy customer fields, linked to all sites of the customer.
-- ============================================================

INSERT INTO `NEW_DB`.`internal_contacts` (
  `created_at`, `updated_at`, `name`, `surname`, `phone`, `mobile`, `email`, `role`, `site_id`
)
SELECT DISTINCT
  NOW(), NOW(),
  CASE
    WHEN NULLIF(TRIM(IFNULL(c.`responsabileSmaltimenti`, '')), '') IS NULL THEN 'Contatto'
    WHEN LOCATE(' ', TRIM(c.`responsabileSmaltimenti`)) = 0      THEN TRIM(c.`responsabileSmaltimenti`)
    ELSE SUBSTRING_INDEX(TRIM(c.`responsabileSmaltimenti`), ' ', 1)
  END,
  CASE
    WHEN TRIM(c.`responsabileSmaltimenti`) = ''               THEN NULL
    WHEN LOCATE(' ', TRIM(c.`responsabileSmaltimenti`)) = 0   THEN NULL
    ELSE TRIM(SUBSTRING(TRIM(c.`responsabileSmaltimenti`), LOCATE(' ', TRIM(c.`responsabileSmaltimenti`)) + 1))
  END,
  CASE
    WHEN REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
           TRIM(IFNULL(c.`telefonoPrincipale`, '')),
           ' ',''),'+39',''),'-',''),'.',''),'/',''),'(','') LIKE '3%'
      THEN NULL
    ELSE NULLIF(TRIM(c.`telefonoPrincipale`), '')
  END,
  CASE
    WHEN REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
           TRIM(IFNULL(c.`telefonoPrincipale`, '')),
           ' ',''),'+39',''),'-',''),'.',''),'/',''),'(','') LIKE '3%'
      THEN NULLIF(TRIM(c.`telefonoPrincipale`), '')
    ELSE NULL
  END,
  NULL,
  'smaltimenti',
  s.`id`
FROM `OLD_DB`.`customers` c
JOIN `NEW_DB`.`sites` s ON s.`customer_id` = c.`id`
WHERE NULLIF(TRIM(IFNULL(c.`responsabileSmaltimenti`, '')), '') IS NOT NULL
   OR NULLIF(TRIM(IFNULL(c.`telefonoPrincipale`, '')), '') IS NOT NULL;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- F. EXCEPTION LOG — all step-2 anomalies
-- ============================================================

-- F1. Sites auto-created from legal_address (no legacy site existed)
-- These always have NULL coordinates and need geocoding.
INSERT INTO `NEW_DB`.`etl_exception_log`
  (`step`, `entity_type`, `entity_id`, `exception_type`, `detail`)
SELECT
  2, 'site', s.`id`,
  'site_auto_created',
  CONCAT('customer_id: ', s.`customer_id`, ' | address: ', IFNULL(s.`address`, 'NULL'))
FROM `NEW_DB`.`sites` s
WHERE NOT EXISTS (SELECT 1 FROM `OLD_DB`.`sites` os WHERE os.`id` = s.`id`);

-- F2. Sites missing coordinates (legacy + auto-created, after geocoding cache patch)
INSERT INTO `NEW_DB`.`etl_exception_log`
  (`step`, `entity_type`, `entity_id`, `exception_type`, `detail`)
SELECT
  2, 'site', s.`id`,
  'site_missing_coordinates',
  CONCAT('customer_id: ', s.`customer_id`, ' | address: ', IFNULL(s.`address`, 'NULL'))
FROM `NEW_DB`.`sites` s
WHERE (s.`latitude` IS NULL OR s.`longitude` IS NULL)
  -- Exclude those already logged as auto_created (avoid duplicate rows for same root cause)
  AND NOT EXISTS (
    SELECT 1 FROM `NEW_DB`.`etl_exception_log` el
    WHERE el.`entity_id` = s.`id` AND el.`exception_type` = 'site_auto_created'
  );

-- F3. Sites with no address (cannot geocode at all — needs manual intervention)
INSERT INTO `NEW_DB`.`etl_exception_log`
  (`step`, `entity_type`, `entity_id`, `exception_type`, `detail`)
SELECT
  2, 'site', s.`id`,
  'site_missing_address',
  CONCAT('customer_id: ', s.`customer_id`, ' | name: ', IFNULL(s.`name`, 'NULL'))
FROM `NEW_DB`.`sites` s
WHERE NULLIF(TRIM(IFNULL(s.`address`, '')), '') IS NULL;

-- F4. Customers still without any site (must be 0 — indicates step B failed)
INSERT INTO `NEW_DB`.`etl_exception_log`
  (`step`, `entity_type`, `entity_id`, `exception_type`, `detail`)
SELECT
  2, 'customer', c.`id`,
  'customer_no_site',
  CONCAT('company_name: ', IFNULL(c.`company_name`, 'NULL'))
FROM `NEW_DB`.`customers` c
WHERE NOT EXISTS (
  SELECT 1 FROM `NEW_DB`.`sites` s WHERE s.`customer_id` = c.`id`
);

-- F5. Internal contacts created with fallback name (responsabileSmaltimenti was empty)
INSERT INTO `NEW_DB`.`etl_exception_log`
  (`step`, `entity_type`, `entity_id`, `exception_type`, `detail`)
SELECT
  2, 'contact', ic.`id`,
  'contact_fallback_name',
  CONCAT('site_id: ', ic.`site_id`, ' | phone: ', IFNULL(ic.`phone`, ''), ' | mobile: ', IFNULL(ic.`mobile`, ''))
FROM `NEW_DB`.`internal_contacts` ic
WHERE ic.`name` = 'Contatto' AND ic.`role` = 'smaltimenti';

-- ============================================================
-- G. REPORTS
-- ============================================================

SELECT exception_type, COUNT(*) AS count, status
FROM `NEW_DB`.`etl_exception_log`
WHERE step = 2
GROUP BY exception_type, status
ORDER BY exception_type;
