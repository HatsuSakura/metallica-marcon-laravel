# OLD -> STANDARDIZED ETL (Operational Runbook)

## Files
- SQL operativo: `.ai/db_migration/OLD_TO_STANDARDIZED_ETL.sql`
- Mapping di riferimento: `.ai/db_migration/OLD_TO_STANDARDIZED_MAPPING_FIRST_PASS.md`

## Prerequisiti
- Il dump vecchio e il DB nuovo devono essere accessibili nello stesso server MySQL.
- Devi conoscere i due schema name reali:
  - `OLD_DB` = schema sorgente (legacy)
  - `NEW_DB` = schema target (standardized)

## Come eseguire
1. Apri `.ai/db_migration/OLD_TO_STANDARDIZED_ETL.sql`.
2. Sostituisci globalmente:
   - `OLD_DB` con il nome schema legacy.
   - `NEW_DB` con il nome schema standardized.
3. Valuta se abilitare il blocco `TRUNCATE` (è commentato di default).
4. Esegui lo script in staging.
5. Verifica i conteggi tabella per tabella.
6. Esegui `php artisan migrate --force` per applicare eventuali normalizzazioni dati lato Laravel (inclusa `sites.site_type`).

## Verifica minima consigliata
- Confronto row count:
```sql
SELECT 'orders' AS t,
  (SELECT COUNT(*) FROM OLD_DB.orders) AS old_cnt,
  (SELECT COUNT(*) FROM NEW_DB.orders) AS new_cnt
UNION ALL
SELECT 'order_items',
  (SELECT COUNT(*) FROM OLD_DB.order_items),
  (SELECT COUNT(*) FROM NEW_DB.order_items)
UNION ALL
SELECT 'journeys',
  (SELECT COUNT(*) FROM OLD_DB.journeys),
  (SELECT COUNT(*) FROM NEW_DB.journeys);
```

## Note
- Lo script carica anche tabelle di sistema Laravel (cache/jobs/sessions/migrations ecc.).  
  Se vuoi migrare solo dominio, commenta gli `INSERT` non necessari.
- Le FK vengono temporaneamente disabilitate (`SET FOREIGN_KEY_CHECKS = 0`) durante il load.
- Per `sites.site_type` lo script ETL normalizza i valori legacy verso il dominio canonico:
  - `1 -> fully_operative`
  - `2 -> only_legal`
  - `3 -> only_stock`
  - valori vuoti/sconosciuti -> `NULL`.
