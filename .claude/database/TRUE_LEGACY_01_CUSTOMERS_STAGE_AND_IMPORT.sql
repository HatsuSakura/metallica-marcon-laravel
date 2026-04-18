-- TRUE LEGACY -> STANDARDIZED
-- STEP 01: stage + import customers (with exception handling)
--
-- Replace placeholders:
--   OLD_DB = legacy schema name
--   NEW_DB = standardized schema name

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- ETL EXCEPTION LOG (owned by STEP 01 — created + truncated here)
-- Steps 02 and 03 delete their own rows before reinserting,
-- so each step is independently re-runnable.
--
-- exception_type values:
--   customer_import_error     — staging row blocked, reason in detail
--   site_auto_created         — site created from legal_address (needs geocoding)
--   site_missing_coordinates  — site without lat/lng after import
--   site_missing_address      — site without address (cannot geocode)
--   customer_no_site          — customer with no site after step 02 (must be 0)
--   contact_fallback_name     — internal_contact created with name='Contatto'
--   withdraw_skipped          — withdraw not imported, reason in detail
-- ============================================================

CREATE TABLE IF NOT EXISTS `NEW_DB`.`etl_exception_log` (
  `id`             bigint unsigned NOT NULL AUTO_INCREMENT,
  `step`           tinyint unsigned NOT NULL COMMENT '1=customers, 2=sites, 3=withdraws',
  `entity_type`    varchar(32) NOT NULL,
  `entity_id`      bigint unsigned DEFAULT NULL COMMENT 'ID in NEW_DB (NULL if not yet created)',
  `legacy_id`      bigint unsigned DEFAULT NULL COMMENT 'ID in OLD_DB',
  `exception_type` varchar(64) NOT NULL,
  `detail`         text DEFAULT NULL,
  `status`         enum('pending','resolved') NOT NULL DEFAULT 'pending',
  `logged_at`      timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at`    timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_type_status` (`exception_type`, `status`),
  KEY `idx_entity` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Full truncate owned by step 01 (fresh run)
TRUNCATE TABLE `NEW_DB`.`etl_exception_log`;

-- ============================================================
-- STAGING TABLE
-- ============================================================

CREATE TABLE IF NOT EXISTS `NEW_DB`.`legacy_customers_stage` (
  `id`                     bigint unsigned NOT NULL,
  `is_occasional_customer` tinyint(1) DEFAULT NULL,
  `seller_id`              bigint unsigned DEFAULT NULL,
  `company_name`           varchar(191) DEFAULT NULL,
  `vat_number`             varchar(191) DEFAULT NULL,
  `tax_code`               varchar(191) DEFAULT NULL,
  `legal_address`          varchar(191) DEFAULT NULL,
  `sdi_code`               varchar(191) DEFAULT NULL,
  `business_type`          varchar(191) DEFAULT NULL,
  `sales_email`            varchar(191) DEFAULT NULL,
  `administrative_email`   varchar(191) DEFAULT NULL,
  `certified_email`        varchar(191) DEFAULT NULL,
  `created_at`             timestamp NULL DEFAULT NULL,
  `updated_at`             timestamp NULL DEFAULT NULL,
  `deleted_at`             timestamp NULL DEFAULT NULL,
  `import_error`           text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

TRUNCATE TABLE `NEW_DB`.`legacy_customers_stage`;

-- ============================================================
-- STAGE LOAD
-- ============================================================

INSERT INTO `NEW_DB`.`legacy_customers_stage` (
  `id`, `is_occasional_customer`, `seller_id`,
  `company_name`, `vat_number`, `tax_code`, `legal_address`, `sdi_code`,
  `business_type`, `sales_email`, `administrative_email`, `certified_email`,
  `created_at`, `updated_at`, `deleted_at`
)
SELECT
  c.`id`,
  c.`customerOccasionale`,
  c.`id_seller`,
  c.`ragioneSociale`,
  c.`partitaIva`,
  c.`codiceFiscale`,
  c.`indirizzoLegale`,
  c.`codiceSdi`,
  CASE WHEN c.`jobType` = 0 THEN NULL ELSE CAST(c.`jobType` AS CHAR) END,
  c.`emailCommerciale`,
  c.`emailAmministrativa`,
  c.`pec`,
  c.`created_at`,
  c.`updated_at`,
  c.`deleted_at`
FROM `OLD_DB`.`customers` c;

-- ============================================================
-- SANITIZE
-- ============================================================

-- Strip leading/trailing whitespace and control chars from company_name
UPDATE `NEW_DB`.`legacy_customers_stage`
SET `company_name` = TRIM(
    REPLACE(REPLACE(REPLACE(`company_name`, '\r', ''), '\n', ''), '\t', '')
);

-- ============================================================
-- REMAP
-- Legacy commercial user IDs -> new system IDs (46->2, 47->3, 48->4).
-- Prerequisite: users with id 2, 3, 4 must exist in target before this step.
-- ============================================================

UPDATE `NEW_DB`.`legacy_customers_stage`
SET `seller_id` = CASE
    WHEN `seller_id` = 46 THEN 2
    WHEN `seller_id` = 47 THEN 3
    WHEN `seller_id` = 48 THEN 4
    ELSE `seller_id`
END
WHERE `seller_id` IN (46, 47, 48);

-- ============================================================
-- VALIDATION — flag staging errors
-- ============================================================

-- Seller not found in target users
UPDATE `NEW_DB`.`legacy_customers_stage` s
LEFT JOIN `NEW_DB`.`users` u ON u.`id` = s.`seller_id`
SET s.`import_error` = CONCAT_WS(' | ', s.`import_error`, 'seller_id_not_found')
WHERE s.`seller_id` IS NOT NULL AND u.`id` IS NULL;

-- Duplicate vat_number inside staging
UPDATE `NEW_DB`.`legacy_customers_stage` s
JOIN (
  SELECT `vat_number`
  FROM `NEW_DB`.`legacy_customers_stage`
  WHERE `vat_number` IS NOT NULL AND TRIM(`vat_number`) <> ''
  GROUP BY `vat_number`
  HAVING COUNT(*) > 1
) d ON d.`vat_number` = s.`vat_number`
SET s.`import_error` = CONCAT_WS(' | ', s.`import_error`, 'duplicate_vat_in_legacy');

-- VAT already present in target
UPDATE `NEW_DB`.`legacy_customers_stage` s
JOIN `NEW_DB`.`customers` c ON c.`vat_number` = s.`vat_number`
SET s.`import_error` = CONCAT_WS(' | ', s.`import_error`, 'vat_already_exists_in_target');

-- ============================================================
-- EXCEPTION LOG — customer import errors
-- ============================================================

DELETE FROM `NEW_DB`.`etl_exception_log` WHERE `step` = 1;

INSERT INTO `NEW_DB`.`etl_exception_log`
  (`step`, `entity_type`, `legacy_id`, `exception_type`, `detail`)
SELECT
  1,
  'customer',
  s.`id`,
  'customer_import_error',
  CONCAT(
    'company_name: ', IFNULL(s.`company_name`, 'NULL'),
    ' | vat_number: ', IFNULL(s.`vat_number`, 'NULL'),
    ' | error: ', s.`import_error`
  )
FROM `NEW_DB`.`legacy_customers_stage` s
WHERE s.`import_error` IS NOT NULL;

-- ============================================================
-- IMPORT — clean rows only
-- ============================================================

INSERT INTO `NEW_DB`.`customers` (
  `id`, `created_at`, `updated_at`, `deleted_at`,
  `is_occasional_customer`, `seller_id`,
  `company_name`, `vat_number`, `tax_code`, `legal_address`, `sdi_code`,
  `business_type`, `sales_email`, `administrative_email`, `certified_email`
)
SELECT
  s.`id`, s.`created_at`, s.`updated_at`, s.`deleted_at`,
  s.`is_occasional_customer`, s.`seller_id`,
  s.`company_name`, s.`vat_number`, s.`tax_code`, s.`legal_address`, s.`sdi_code`,
  s.`business_type`, s.`sales_email`, s.`administrative_email`, s.`certified_email`
FROM `NEW_DB`.`legacy_customers_stage` s
WHERE s.`import_error` IS NULL
  AND NOT EXISTS (SELECT 1 FROM `NEW_DB`.`customers` c WHERE c.`id` = s.`id`);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- REPORT STEP 01
-- ============================================================

SELECT
  'customers_imported'       AS check_name, COUNT(*) AS check_value FROM `NEW_DB`.`customers`
UNION ALL
SELECT
  'customers_blocked_in_staging', COUNT(*) FROM `NEW_DB`.`legacy_customers_stage`
  WHERE `import_error` IS NOT NULL;

-- Detail of blocked customers (review before proceeding to step 02)
SELECT
  `id`, `company_name`, `vat_number`, `import_error`
FROM `NEW_DB`.`legacy_customers_stage`
WHERE `import_error` IS NOT NULL
ORDER BY `id`;
