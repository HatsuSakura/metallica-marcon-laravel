-- TRUE LEGACY -> STANDARDIZED
-- STEP 01: stage + import customers (with exception handling)
--
-- Replace placeholders:
--   OLD_DB = legacy schema
--   NEW_DB = standardized schema

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `NEW_DB`.`legacy_customers_stage` (
  `id` bigint unsigned NOT NULL,
  `is_occasional_customer` tinyint(1) DEFAULT NULL,
  `seller_id` bigint unsigned DEFAULT NULL,
  `company_name` varchar(191) DEFAULT NULL,
  `vat_number` varchar(191) DEFAULT NULL,
  `tax_code` varchar(191) DEFAULT NULL,
  `legal_address` varchar(191) DEFAULT NULL,
  `sdi_code` varchar(191) DEFAULT NULL,
  `business_type` varchar(191) DEFAULT NULL,
  `sales_email` varchar(191) DEFAULT NULL,
  `administrative_email` varchar(191) DEFAULT NULL,
  `certified_email` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `import_error` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

TRUNCATE TABLE `NEW_DB`.`legacy_customers_stage`;

INSERT INTO `NEW_DB`.`legacy_customers_stage` (
  `id`,
  `is_occasional_customer`,
  `seller_id`,
  `company_name`,
  `vat_number`,
  `tax_code`,
  `legal_address`,
  `sdi_code`,
  `business_type`,
  `sales_email`,
  `administrative_email`,
  `certified_email`,
  `created_at`,
  `updated_at`,
  `deleted_at`
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

-- Seller missing in target users
UPDATE `NEW_DB`.`legacy_customers_stage` s
LEFT JOIN `NEW_DB`.`users` u ON u.`id` = s.`seller_id`
SET s.`import_error` = CONCAT_WS(' | ', s.`import_error`, 'seller_id_not_found')
WHERE u.`id` IS NULL;

-- Duplicate vat_number inside stage
UPDATE `NEW_DB`.`legacy_customers_stage` s
JOIN (
  SELECT `vat_number`
  FROM `NEW_DB`.`legacy_customers_stage`
  WHERE `vat_number` IS NOT NULL AND TRIM(`vat_number`) <> ''
  GROUP BY `vat_number`
  HAVING COUNT(*) > 1
) d ON d.`vat_number` = s.`vat_number`
SET s.`import_error` = CONCAT_WS(' | ', s.`import_error`, 'duplicate_vat_in_legacy');

-- Vat already present in target
UPDATE `NEW_DB`.`legacy_customers_stage` s
JOIN `NEW_DB`.`customers` c ON c.`vat_number` = s.`vat_number`
SET s.`import_error` = CONCAT_WS(' | ', s.`import_error`, 'vat_already_exists_in_target');

-- Review exceptions
SELECT * FROM `NEW_DB`.`legacy_customers_stage`
WHERE `import_error` IS NOT NULL
ORDER BY `id`;

-- Import only clean rows
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
