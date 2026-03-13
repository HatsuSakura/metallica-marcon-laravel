-- TRUE LEGACY -> STANDARDIZED
-- STEP 02: import sites + related entities
--
-- Preconditions:
-- - customers already imported/cleaned
-- - placeholders replaced:
--   OLD_DB = legacy schema
--   NEW_DB = standardized schema

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Optional cleanup
-- TRUNCATE TABLE `NEW_DB`.`internal_contacts`;
-- TRUNCATE TABLE `NEW_DB`.`timetables`;
-- TRUNCATE TABLE `NEW_DB`.`sites`;

-- sites (only if customer exists in target)
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
FROM `OLD_DB`.`sites` s
WHERE EXISTS (SELECT 1 FROM `NEW_DB`.`customers` c WHERE c.`id` = s.`id_customer`)
  AND NOT EXISTS (SELECT 1 FROM `NEW_DB`.`sites` x WHERE x.`id` = s.`id`);

-- timetables (only for imported sites)
INSERT INTO `NEW_DB`.`timetables` (
  `id`, `created_at`, `updated_at`, `site_id`, `hours_json`
)
SELECT
  t.`id`, t.`created_at`, t.`updated_at`, t.`id_site`, t.`arrayOrari`
FROM `OLD_DB`.`timetables` t
WHERE EXISTS (SELECT 1 FROM `NEW_DB`.`sites` s WHERE s.`id` = t.`id_site`)
  AND NOT EXISTS (SELECT 1 FROM `NEW_DB`.`timetables` x WHERE x.`id` = t.`id`);

-- internal_contacts derived from legacy customer fields, linked to imported sites
INSERT INTO `NEW_DB`.`internal_contacts` (
  `created_at`, `updated_at`, `name`, `surname`, `phone`, `mobile`, `email`, `role`, `site_id`
)
SELECT DISTINCT
  NOW(),
  NOW(),
  CASE
    WHEN NULLIF(TRIM(IFNULL(c.`responsabileSmaltimenti`, '')), '') IS NULL THEN 'Contatto'
    WHEN LOCATE(' ', TRIM(c.`responsabileSmaltimenti`)) = 0 THEN TRIM(c.`responsabileSmaltimenti`)
    ELSE SUBSTRING_INDEX(TRIM(c.`responsabileSmaltimenti`), ' ', 1)
  END,
  CASE
    WHEN TRIM(c.`responsabileSmaltimenti`) = '' THEN NULL
    WHEN LOCATE(' ', TRIM(c.`responsabileSmaltimenti`)) = 0 THEN NULL
    ELSE TRIM(SUBSTRING(TRIM(c.`responsabileSmaltimenti`), LOCATE(' ', TRIM(c.`responsabileSmaltimenti`)) + 1))
  END,
  CASE
    WHEN REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(IFNULL(c.`telefonoPrincipale`, '')), ' ', ''), '+39', ''), '-', ''), '.', ''), '/', ''), '(', '') LIKE '3%'
      THEN NULL
    ELSE NULLIF(TRIM(c.`telefonoPrincipale`), '')
  END,
  CASE
    WHEN REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(IFNULL(c.`telefonoPrincipale`, '')), ' ', ''), '+39', ''), '-', ''), '.', ''), '/', ''), '(', '') LIKE '3%'
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

-- Report: contacts generated with fallback name
SELECT
  COUNT(*) AS `fallback_contacts_count`
FROM `NEW_DB`.`internal_contacts` ic
WHERE ic.`name` = 'Contatto'
  AND ic.`role` = 'smaltimenti';

SELECT
  ic.`id`,
  ic.`created_at`,
  ic.`site_id`,
  s.`customer_id`,
  s.`name` AS `site_name`,
  ic.`surname`,
  ic.`phone`,
  ic.`mobile`,
  ic.`email`,
  ic.`role`
FROM `NEW_DB`.`internal_contacts` ic
JOIN `NEW_DB`.`sites` s ON s.`id` = ic.`site_id`
WHERE ic.`name` = 'Contatto'
  AND ic.`role` = 'smaltimenti'
ORDER BY ic.`site_id`, ic.`id`;
