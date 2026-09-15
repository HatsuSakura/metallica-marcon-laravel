-- PRE-PROCESSING: fix raw Latin-1 bytes stored in legacy_tmp utf8mb4 columns
--
-- Context: the legacy application sometimes inserted data via a latin1 DB connection
-- into utf8mb4 columns. MySQL stored the raw latin1 bytes as-is (e.g. 0xE0 for à),
-- which are valid latin1 but INVALID utf8mb4. The mysqldump exports them verbatim.
-- When the ETL tries to SELECT→INSERT these bytes into a strict utf8mb4 InnoDB table,
-- MySQL rejects them with "Invalid utf8mb4 character string".
--
-- This script fixes them using binary-mode REPLACE so that:
--   - Raw latin1 bytes (type 3) → replaced with correct UTF-8 sequences  ✓
--   - Already-correct UTF-8 (type 1) → unaffected (target bytes not present) ✓
--   - Mojibake (type 2) → unaffected (mojibake uses 0xC3+x sequences, not 0xE0 etc.) ✓
--
-- Run this AFTER loading the dump into legacy_tmp and BEFORE the ETL steps 01-03.
-- Columns covered: all text fields used by ETL steps 01-03.
--
-- Replace placeholder:
--   OLD_DB = legacy schema name (e.g. legacy_tmp)

SET NAMES binary;
SET FOREIGN_KEY_CHECKS = 0;
SET SESSION sql_mode = '';

-- ============================================================
-- CUSTOMERS table
-- ============================================================

UPDATE `OLD_DB`.`customers` SET
  `ragioneSociale` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                     REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                     `ragioneSociale`,
                     UNHEX('F9'), UNHEX('C3B9')),   -- ù
                     UNHEX('F2'), UNHEX('C3B2')),   -- ò
                     UNHEX('EC'), UNHEX('C3AC')),   -- ì
                     UNHEX('E9'), UNHEX('C3A9')),   -- é
                     UNHEX('E8'), UNHEX('C3A8')),   -- è
                     UNHEX('E0'), UNHEX('C3A0')),   -- à
                     UNHEX('D9'), UNHEX('C399')),   -- Ù
                     UNHEX('D2'), UNHEX('C392')),   -- Ò
                     UNHEX('CC'), UNHEX('C38C')),   -- Ì
                     UNHEX('C9'), UNHEX('C389')),   -- É
                     UNHEX('C8'), UNHEX('C388')),   -- È
                     UNHEX('C0'), UNHEX('C380')),   -- À
  `indirizzoLegale` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                      REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                      `indirizzoLegale`,
                      UNHEX('F9'), UNHEX('C3B9')),
                      UNHEX('F2'), UNHEX('C3B2')),
                      UNHEX('EC'), UNHEX('C3AC')),
                      UNHEX('E9'), UNHEX('C3A9')),
                      UNHEX('E8'), UNHEX('C3A8')),
                      UNHEX('E0'), UNHEX('C3A0')),
                      UNHEX('D9'), UNHEX('C399')),
                      UNHEX('D2'), UNHEX('C392')),
                      UNHEX('CC'), UNHEX('C38C')),
                      UNHEX('C9'), UNHEX('C389')),
                      UNHEX('C8'), UNHEX('C388')),
                      UNHEX('C0'), UNHEX('C380')),
  `responsabileSmaltimenti` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                              REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                              `responsabileSmaltimenti`,
                              UNHEX('F9'), UNHEX('C3B9')),
                              UNHEX('F2'), UNHEX('C3B2')),
                              UNHEX('EC'), UNHEX('C3AC')),
                              UNHEX('E9'), UNHEX('C3A9')),
                              UNHEX('E8'), UNHEX('C3A8')),
                              UNHEX('E0'), UNHEX('C3A0')),
                              UNHEX('D9'), UNHEX('C399')),
                              UNHEX('D2'), UNHEX('C392')),
                              UNHEX('CC'), UNHEX('C38C')),
                              UNHEX('C9'), UNHEX('C389')),
                              UNHEX('C8'), UNHEX('C388')),
                              UNHEX('C0'), UNHEX('C380'));

-- ============================================================
-- SITES table
-- ============================================================

UPDATE `OLD_DB`.`sites` SET
  `denominazione` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                    `denominazione`,
                    UNHEX('F9'), UNHEX('C3B9')),
                    UNHEX('F2'), UNHEX('C3B2')),
                    UNHEX('EC'), UNHEX('C3AC')),
                    UNHEX('E9'), UNHEX('C3A9')),
                    UNHEX('E8'), UNHEX('C3A8')),
                    UNHEX('E0'), UNHEX('C3A0')),
                    UNHEX('D9'), UNHEX('C399')),
                    UNHEX('D2'), UNHEX('C392')),
                    UNHEX('CC'), UNHEX('C38C')),
                    UNHEX('C9'), UNHEX('C389')),
                    UNHEX('C8'), UNHEX('C388')),
                    UNHEX('C0'), UNHEX('C380')),
  `indirizzo` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                `indirizzo`,
                UNHEX('F9'), UNHEX('C3B9')),
                UNHEX('F2'), UNHEX('C3B2')),
                UNHEX('EC'), UNHEX('C3AC')),
                UNHEX('E9'), UNHEX('C3A9')),
                UNHEX('E8'), UNHEX('C3A8')),
                UNHEX('E0'), UNHEX('C3A0')),
                UNHEX('D9'), UNHEX('C399')),
                UNHEX('D2'), UNHEX('C392')),
                UNHEX('CC'), UNHEX('C38C')),
                UNHEX('C9'), UNHEX('C389')),
                UNHEX('C8'), UNHEX('C388')),
                UNHEX('C0'), UNHEX('C380'));

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;

-- Quick verification: show rows that still contain Ã (mojibake — handled by ETL CONVERT)
-- and confirm no raw high bytes remain (should return 0 invalid rows after this script)
SELECT 'customers_with_mojibake' AS check_name,
       COUNT(*) AS count
FROM `OLD_DB`.`customers`
WHERE `ragioneSociale` LIKE '%Ã%'
   OR `indirizzoLegale` LIKE '%Ã%';

SELECT 'sites_with_mojibake' AS check_name,
       COUNT(*) AS count
FROM `OLD_DB`.`sites`
WHERE `denominazione` LIKE '%Ã%'
   OR `indirizzo` LIKE '%Ã%';
