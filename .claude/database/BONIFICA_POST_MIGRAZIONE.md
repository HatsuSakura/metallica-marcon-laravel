# Bonifica post-migrazione — decisioni note

Questo file raccoglie le operazioni da eseguire **a posteriori** sul DB di produzione,
dopo che l'ETL è stato eseguito con successo.
Ogni voce ha una decisione già presa e le istruzioni SQL da eseguire manualmente (o via script).

---

## B1 — GRUPPOVEN s.r.l. — duplicato PIVA `05583390264`

**Situazione**: due record nel legacy con la stessa PIVA.
- `id=741` — vecchio record, soft-deleted 26/02/2026, porta 68 withdraw storici → **non importato**
- `id=1563` — record attivo (creato 04/02/2026) → **importato**

**Decisione**: `id=1563` è il customer vincitore. I 68 withdraw di `id=741` vanno riassegnati a
`customer_id=1563`, `site_id=1505`.

**Verifica eseguita (2026-04-18)**:
- Legacy site `692` (customer 741): Via A.G. Longhin 1, Treviso — coordinate **errate** (45.7712, 12.2521 → ~12 km a nord)
- Legacy site `1505` (customer 1563): stesso indirizzo — coordinate **corrette** (45.6654, 12.2415, ROOFTOP Google)
- Stessa sede fisica confermata → nessun site aggiuntivo da importare. Tutti i withdraw di 741 puntano a site 692, che va rimappato a 1505.

**SQL da eseguire sul DB prod dopo ETL:**
```sql
-- Step 1: verifica che customer 1563 e site 1505 esistano in target
SELECT id, company_name, deleted_at FROM customers WHERE id = 1563;
SELECT id, customer_id, name, address FROM sites WHERE id = 1505;

-- Step 2: importa i withdraw di 741 riassegnandoli a customer 1563 / site 1505
INSERT INTO `withdraws` (
  `id`, `created_at`, `updated_at`, `deleted_at`,
  `withdrawn_at`, `residue_percentage`,
  `customer_id`, `site_id`, `vehicle_id`, `driver_id`,
  `created_by_user_id`, `is_manual_entry`
)
SELECT
  w.`id`, w.`created_at`, w.`updated_at`, w.`deleted_at`,
  w.`dataRitiro`, w.`percentualeResidua`,
  1563,  -- customer: 741 → 1563
  1505,  -- site: 692 → 1505 (stessa sede fisica, coordinate corrette)
  NULLIF(w.`id_vehicle`, 0),
  NULLIF(w.`id_driver`, 0),
  w.`id_user`,
  w.`insManuale`
FROM `legacy_tmp`.`withdraws` w
WHERE w.`id_customer` = 741
  AND NOT EXISTS (SELECT 1 FROM `withdraws` x WHERE x.`id` = w.`id`);

-- Step 3: aggiorna etl_exception_log
UPDATE `etl_exception_log`
SET status = 'resolved', resolved_at = NOW()
WHERE step = 3 AND exception_type = 'withdraw_skipped'
  AND detail LIKE '%customer_id: 741 %';
```

---

## B2 — JQL TEBO Tecnologie S.p.a. — duplicato PIVA `00599851201`

**Situazione**:
- `id=328` — record storico (senza `created_at`), porta 37 withdraw → **non importato**
- `id=1280` — creato e soft-deleted lo stesso giorno (20/08/2024, durato 49 minuti), 0 withdraw → **non importato**

**Decisione**: `id=328` è il record reale. Va importato forzatamente ignorando il duplicato 1280
(che è palesemente un errore di inserimento). I 37 withdraw seguono `id=328`.

**SQL da eseguire sul DB prod dopo ETL:**
```sql
-- Step 1: importa il customer 328 bypassando il check PIVA (1280 è spazzatura)
-- Prima rimuovi 1280 dalla staging o marcalo come ignorabile
INSERT INTO `customers` (
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
FROM `legacy_customers_stage` s
WHERE s.`id` = 328
  AND NOT EXISTS (SELECT 1 FROM `customers` c WHERE c.`id` = 328);

-- Step 2: importa i site di 328 (da legacy)
INSERT INTO `sites` (
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
  NULL, 1,
  CONVERT(BINARY CONVERT(s.`indirizzo` USING latin1) USING utf8mb4),
  NULLIF(s.`lat`, 0), NULLIF(s.`lng`, 0),
  s.`fattoreRischioCalcolato`, s.`giorniProssimoRitiro`,
  0, NULL, NULL, '', 0
FROM `legacy_tmp`.`sites` s
WHERE s.`id_customer` = 328
  AND NOT EXISTS (SELECT 1 FROM `sites` x WHERE x.`id` = s.`id`);

-- Step 3: importa i withdraw di 328
INSERT INTO `withdraws` (
  `id`, `created_at`, `updated_at`, `deleted_at`,
  `withdrawn_at`, `residue_percentage`,
  `customer_id`, `site_id`, `vehicle_id`, `driver_id`,
  `created_by_user_id`, `is_manual_entry`
)
SELECT
  w.`id`, w.`created_at`, w.`updated_at`, w.`deleted_at`,
  w.`dataRitiro`, w.`percentualeResidua`,
  w.`id_customer`, w.`id_site`,
  NULLIF(w.`id_vehicle`, 0), NULLIF(w.`id_driver`, 0),
  w.`id_user`, w.`insManuale`
FROM `legacy_tmp`.`withdraws` w
WHERE w.`id_customer` = 328
  AND NOT EXISTS (SELECT 1 FROM `withdraws` x WHERE x.`id` = w.`id`);

-- Step 4: aggiorna etl_exception_log
UPDATE `etl_exception_log`
SET status = 'resolved', resolved_at = NOW()
WHERE step IN (1, 3)
  AND (legacy_id IN (328, 1280) OR detail LIKE '%customer_id: 328 %');
```

---

## B3 — Tanara Metalli s.r.l. — duplicato PIVA `06428420159`

**Situazione**:
- `id=1565` — record attivo, 1 withdraw → **non importato**
- `id=1566` — creato e soft-deleted lo stesso giorno (42 secondi di vita) — errore accidentale → **non importato**

**Decisione**: `id=1565` è il record reale. Va importato ignorando 1566.

**SQL da eseguire sul DB prod dopo ETL:**
```sql
-- Step 1: importa il customer 1565
INSERT INTO `customers` (
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
FROM `legacy_customers_stage` s
WHERE s.`id` = 1565
  AND NOT EXISTS (SELECT 1 FROM `customers` c WHERE c.`id` = 1565);

-- Step 2: importa i site di 1565
INSERT INTO `sites` (
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
  NULL, 1,
  CONVERT(BINARY CONVERT(s.`indirizzo` USING latin1) USING utf8mb4),
  NULLIF(s.`lat`, 0), NULLIF(s.`lng`, 0),
  s.`fattoreRischioCalcolato`, s.`giorniProssimoRitiro`,
  0, NULL, NULL, '', 0
FROM `legacy_tmp`.`sites` s
WHERE s.`id_customer` = 1565
  AND NOT EXISTS (SELECT 1 FROM `sites` x WHERE x.`id` = s.`id`);

-- Step 3: importa il withdraw di 1565
INSERT INTO `withdraws` (
  `id`, `created_at`, `updated_at`, `deleted_at`,
  `withdrawn_at`, `residue_percentage`,
  `customer_id`, `site_id`, `vehicle_id`, `driver_id`,
  `created_by_user_id`, `is_manual_entry`
)
SELECT
  w.`id`, w.`created_at`, w.`updated_at`, w.`deleted_at`,
  w.`dataRitiro`, w.`percentualeResidua`,
  w.`id_customer`, w.`id_site`,
  NULLIF(w.`id_vehicle`, 0), NULLIF(w.`id_driver`, 0),
  w.`id_user`, w.`insManuale`
FROM `legacy_tmp`.`withdraws` w
WHERE w.`id_customer` = 1565
  AND NOT EXISTS (SELECT 1 FROM `withdraws` x WHERE x.`id` = w.`id`);

-- Step 4: aggiorna etl_exception_log
UPDATE `etl_exception_log`
SET status = 'resolved', resolved_at = NOW()
WHERE step IN (1, 3)
  AND (legacy_id IN (1565, 1566) OR detail LIKE '%customer_id: 1565 %');
```

---

## Checklist esecuzione bonifica

- [ ] B1 — GRUPPOVEN: withdraw 741 → 1563, verifica site compatibili
- [ ] B2 — JQL TEBO: importa customer 328 + site + 37 withdraw
- [ ] B3 — Tanara Metalli: importa customer 1565 + site + 1 withdraw
- [ ] Verifica finale: `etl_exception_log` status tutti `resolved` (eccetto `contact_fallback_name` — redazionale)
