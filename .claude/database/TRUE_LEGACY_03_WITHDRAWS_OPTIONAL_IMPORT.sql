-- TRUE LEGACY -> STANDARDIZED
-- STEP 03 (OPTIONAL): import withdraws
--
-- Preconditions:
-- - STEP 01 (customers) and STEP 02 (sites) completed
-- - etl_exception_log created by STEP 01
-- - placeholders replaced:
--   OLD_DB = legacy schema name
--   NEW_DB = standardized schema name
--
-- Business rule: import only withdraws whose customer AND site exist in target.
-- Skipped rows are logged into etl_exception_log with exception_type = 'withdraw_skipped'.
-- After a complete STEP 02 run (all customers have a site), the skip count MUST be 0.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Optional cleanup (uncomment only if re-running from scratch)
-- TRUNCATE TABLE `NEW_DB`.`withdraws`;

-- Clear step-3 exceptions from previous runs (idempotent)
DELETE FROM `NEW_DB`.`etl_exception_log` WHERE `step` = 3;

-- No sentinel records needed: withdraws.vehicle_id and driver_id are nullable.
-- Legacy id_vehicle=0/NULL and id_driver=0/NULL are mapped directly to NULL below.

-- ============================================================
-- EXCEPTION LOG — skipped withdraws
-- ============================================================

INSERT INTO `NEW_DB`.`etl_exception_log`
  (`step`, `entity_type`, `legacy_id`, `exception_type`, `detail`)
SELECT
  3,
  'withdraw',
  w.`id`,
  'withdraw_skipped',
  CONCAT(
    'reason: ',
    CASE
      WHEN NOT EXISTS (SELECT 1 FROM `NEW_DB`.`customers` c WHERE c.`id` = w.`id_customer`)
        THEN 'customer_not_in_target'
      WHEN NOT EXISTS (SELECT 1 FROM `NEW_DB`.`sites` s WHERE s.`id` = w.`id_site`)
        THEN 'site_not_in_target'
      ELSE 'unknown'
    END,
    ' | customer_id: ', w.`id_customer`,
    ' | site_id: ', w.`id_site`,
    ' | withdrawn_at: ', w.`dataRitiro`
  )
FROM `OLD_DB`.`withdraws` w
WHERE NOT EXISTS (SELECT 1 FROM `NEW_DB`.`customers` c WHERE c.`id` = w.`id_customer`)
   OR NOT EXISTS (SELECT 1 FROM `NEW_DB`.`sites`     s WHERE s.`id` = w.`id_site`);

-- ============================================================
-- IMPORT WITHDRAWS
-- ============================================================

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
  NULLIF(w.`id_vehicle`, 0),
  NULLIF(w.`id_driver`, 0),
  w.`id_user`,
  w.`insManuale`
FROM `OLD_DB`.`withdraws` w
WHERE EXISTS (SELECT 1 FROM `NEW_DB`.`customers` c WHERE c.`id` = w.`id_customer`)
  AND EXISTS (SELECT 1 FROM `NEW_DB`.`sites`     s WHERE s.`id` = w.`id_site`)
  AND NOT EXISTS (SELECT 1 FROM `NEW_DB`.`withdraws` x WHERE x.`id` = w.`id`);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- REPORTS
-- ============================================================

SELECT exception_type, COUNT(*) AS count, status
FROM `NEW_DB`.`etl_exception_log`
WHERE step = 3
GROUP BY exception_type, status;

SELECT
  'withdraws_imported' AS check_name,
  COUNT(*) AS check_value
FROM `NEW_DB`.`withdraws`;
