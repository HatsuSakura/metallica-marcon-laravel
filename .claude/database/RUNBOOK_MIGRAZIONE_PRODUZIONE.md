# Runbook — Migrazione dati legacy → produzione

Nota operativa per Claude. Da usare quando il PO chiede di eseguire una nuova migrazione
dati dal DB legacy all'ambiente di produzione VPS.

---

## Prerequisiti da chiedere al PO

1. **Dump del DB legacy aggiornato** — chiedere esplicitamente:
   > "Hai eseguito il dump del DB legacy all'ultima versione? Caricalo in `.claude/database/dumps/dump_legacy.sql`"

2. Verificare che il dump sia presente prima di procedere:
   ```bash
   ls -lh .claude/database/dumps/dump_legacy.sql
   ```

---

## Pipeline completa — step by step

### FASE 1 — Caricamento legacy in locale

```bash
# Carica il dump legacy nel container MySQL locale
docker exec -i metallica-marcon-laravel-mysql-1 \
  mysql -uroot -proot legacy_tmp < .claude/database/dumps/dump_legacy.sql
```

Verificare che `legacy_tmp` contenga: `customers`, `sites`, `timetables`, `withdraws`.

---

### FASE 2 — Bootstrap schema ETL locale

```bash
# Resetta e ricrea marconinertia_etl con schema Laravel + seeder produzione
DB_DATABASE=marconinertia_etl APP_ENV=production php artisan migrate:fresh --seed --force
```

Verificare che gli utenti id=1,2,3,4 siano presenti con ruoli corretti.

---

### FASE 3 — ETL (3 step SQL)

Sostituire `OLD_DB=legacy_tmp` e `NEW_DB=marconinertia_etl` ed eseguire in sequenza:

```bash
OLD_DB="legacy_tmp"; NEW_DB="marconinertia_etl"
DBSCRIPTS=".claude/database"

for STEP in TRUE_LEGACY_01_CUSTOMERS_STAGE_AND_IMPORT \
            TRUE_LEGACY_02_SITES_AND_RELATED_IMPORT \
            TRUE_LEGACY_03_WITHDRAWS_OPTIONAL_IMPORT; do
  sed "s/\`OLD_DB\`/\`${OLD_DB}\`/g; s/\`NEW_DB\`/\`${NEW_DB}\`/g" \
    "${DBSCRIPTS}/${STEP}.sql" | \
    docker exec -i metallica-marcon-laravel-mysql-1 mysql -uroot -proot marconinertia_etl
  echo "=== ${STEP} completato ==="
done
```

Dopo ogni step leggere il report di output e verificare `etl_exception_log`.

---

### FASE 4 — Analisi exception log

```sql
SELECT exception_type, status, COUNT(*) AS count
FROM etl_exception_log
GROUP BY exception_type, status
ORDER BY step, exception_type;
```

Tipi attesi e azioni:

| exception_type | Azione |
|---|---|
| `customer_import_error` | Verificare se sono nuove coppie PIVA duplicate → aggiornare `BONIFICA_POST_MIGRAZIONE.md` |
| `site_auto_created` | Procedere al geocoding (FASE 5) |
| `site_missing_coordinates` | Procedere al geocoding (FASE 5) |
| `contact_fallback_name` | Bonifica redazionale, non bloccante |
| `withdraw_skipped` | Controllare a quale customer sono legati → bonifica o `withdraw_orphan_customer` |
| `withdraw_orphan_customer` | Irrecuperabili, marcare resolved |

---

### FASE 5 — Geocoding (se necessario)

Per ogni site con `latitude IS NULL OR longitude IS NULL`:

```sql
SELECT s.id, s.customer_id, s.name, s.address
FROM sites s
WHERE s.latitude IS NULL OR s.longitude IS NULL;
```

Per ogni indirizzo mancante:
1. Usare MCP Google Maps (`maps_geocode`) per ottenere lat/lng
2. Inserire in `etl_site_geocoding_cache`
3. Rieseguire il blocco UPDATE del STEP 02 (sezione C)
4. Marcare `site_auto_created` e `site_missing_coordinates` come `resolved`

---

### FASE 6 — Bonifica post-migrazione

Eseguire le bonifica documentate in `BONIFICA_POST_MIGRAZIONE.md`.

Se sono emerse nuove coppie PIVA duplicate nello step 01, documentarle e aggiungerle al file
prima di procedere.

Checklist:
- [ ] B1 — GRUPPOVEN (o aggiornamento se situazione cambiata)
- [ ] B2 — JQL TEBO (o aggiornamento)
- [ ] B3 — Tanara Metalli (o aggiornamento)
- [ ] Nuove bonifiche emerse dal run corrente

---

### FASE 7 — Deploy su VPS

```bash
bash .claude/database/scripts/etl_deploy_to_prod.sh
```

Lo script esegue automaticamente:
- Dump di `marconinertia_etl` (esclusa `legacy_customers_stage`)
- Upload sul VPS via SCP
- Drop + recreate + restore di `metallicamarcon` sul container `metallicamarcon-db`
- Verifica conteggi post-restore

Prerequisito: `.claude/database/scripts/etl_deploy_to_prod.conf` presente con `VPS_ROOT_PASS`.

---

## Verifica finale

```bash
ssh -i ~/.ssh/claude_vps root@76.13.137.60 bash << 'EOF'
docker exec metallicamarcon-db mariadb \
  -uroot -p"$(grep VPS_ROOT_PASS .claude/database/scripts/etl_deploy_to_prod.conf | cut -d'"' -f2)" \
  metallicamarcon -e "
SELECT 'customers' AS entity, COUNT(*) FROM customers
UNION ALL SELECT 'sites', COUNT(*) FROM sites
UNION ALL SELECT 'withdraws', COUNT(*) FROM withdraws
UNION ALL SELECT 'users', COUNT(*) FROM users;
"
EOF
```

Testare il login su https://gestionalelogistica.metallicamarcon.it con le credenziali admin.

---

## Note operative

- `SEED_ADMIN_PASSWORD` deve essere presente nel `.env` locale prima del migrate:fresh
- Le variabili `VITE_MAPS_*` devono essere nel `.env` VPS prima del `docker compose build`
- Il file `etl_deploy_to_prod.conf` è gitignored — va ricreato se si cambia macchina
- I dump in `.claude/database/dumps/` sono gitignored — non vengono pushati
