-- TRUE LEGACY -> STANDARDIZED
-- STEP 03 (OPTIONAL): import withdraws
--
-- Preconditions:
-- - customers/sites already imported
-- - placeholders replaced:
--   OLD_DB = legacy schema
--   NEW_DB = standardized schema

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Optional cleanup
-- TRUNCATE TABLE `NEW_DB`.`withdraws`;

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
  CASE WHEN w.`id_vehicle` IS NULL OR w.`id_vehicle` = 0 THEN 9999 ELSE w.`id_vehicle` END,
  CASE WHEN w.`id_driver` IS NULL OR w.`id_driver` = 0 THEN 9999 ELSE w.`id_driver` END,
  w.`id_user`,
  w.`insManuale`
FROM `OLD_DB`.`withdraws` w
WHERE EXISTS (SELECT 1 FROM `NEW_DB`.`customers` c WHERE c.`id` = w.`id_customer`)
  AND EXISTS (SELECT 1 FROM `NEW_DB`.`sites` s WHERE s.`id` = w.`id_site`)
  AND NOT EXISTS (SELECT 1 FROM `NEW_DB`.`withdraws` x WHERE x.`id` = w.`id`);

SET FOREIGN_KEY_CHECKS = 1;
