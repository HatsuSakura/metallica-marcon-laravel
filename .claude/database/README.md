# DB Migration — Legacy → Laravel

Workspace per la migrazione dati dal DB legacy al nuovo schema canonico Laravel.

## Scope dati migrati

- `customers` → staging table con tracking errori (`legacy_customers_stage`)
- `sites` + `timetables` + `internal_contacts` (derivati da campi cliente)
- `withdraws` (opzionale, step 04)

Tutto il resto (orders, journeys, vehicles, users, ...) viene ricreato da zero nel nuovo sistema.

## Struttura

```
.claude/database/
├── dumps/                          ← dump legacy (gitignored)
├── scripts/                        ← script PowerShell
│   ├── _common.ps1                 ← libreria condivisa
│   ├── db_profiles.json            ← profili DB (dev Docker, prod VPS)
│   ├── 00-backup-dev-db.ps1
│   ├── 00-load-legacy-dump-into-tempdb.ps1
│   ├── 00-truncate-customers-sites.ps1
│   ├── 01-import-customers.ps1
│   ├── 02-show-customer-exceptions.ps1
│   ├── 03-import-sites-timetables-contacts.ps1
│   ├── 04-import-withdraws-optional.ps1
│   ├── 05-run-domain-flow.ps1      ← orchestratore one-shot
│   ├── 06-validate-customers-sites.ps1
│   ├── 07-export-customers-sites.ps1
│   ├── 08-import-customers-sites.ps1
│   ├── 09-open-prod-db-tunnel.ps1
│   ├── 10-close-prod-db-tunnel.ps1
│   └── 11-run-prod-domain-sync.ps1
├── TRUE_LEGACY_01_CUSTOMERS_STAGE_AND_IMPORT.sql
├── TRUE_LEGACY_02_SITES_AND_RELATED_IMPORT.sql
└── TRUE_LEGACY_03_WITHDRAWS_OPTIONAL_IMPORT.sql
```

## Ordine di esecuzione

```powershell
cd .claude\database\scripts

# 1. Backup del DB di sviluppo (sicurezza)
.\00-backup-dev-db.ps1

# 2. Carica dump legacy in schema temporaneo
.\00-load-legacy-dump-into-tempdb.ps1

# 3. Azzera tabelle domini nel target (se necessario)
.\00-truncate-customers-sites.ps1

# 4. Importa clienti (con staging + errori)
.\01-import-customers.ps1

# 5. Verifica eccezioni cliente
.\02-show-customer-exceptions.ps1
# → risolvi manualmente le righe con import_error, poi continua

# 6. Importa siti, timetables, internal_contacts
.\03-import-sites-timetables-contacts.ps1

# 7. (Opzionale) Importa withdraws
.\04-import-withdraws-optional.ps1

# 8. Validazione coerenza dominio
.\06-validate-customers-sites.ps1
```

One-shot fino a step 5 (con pausa per eccezioni):
```powershell
.\05-run-domain-flow.ps1
```

## Sync verso produzione VPS

```powershell
.\11-run-prod-domain-sync.ps1 `
  -LocalProfile docker_dev_marconinertia `
  -QaProfile prod_vps_tunnel `
  -SshUser <ssh_user> `
  -SshHost 76.13.137.60 `
  -SshPort 22 `
  -TunnelLocalPort 13306 `
  -TunnelRemoteDbHost 127.0.0.1 `
  -TunnelRemoteDbPort 3306
```

Assicurarsi che Pageant sia attivo con la chiave SSH VPS caricata.

## Business rules ETL

### Remapping commerciali (seller_id)

I commerciali nel DB legacy hanno id 46, 47, 48.
Nel nuovo sistema vengono ricreati con id 2, 3, 4.
Il remapping avviene automaticamente in `TRUE_LEGACY_01` dopo la sanitizzazione campi.

| Legacy ID | Nuovo ID |
|-----------|----------|
| 46        | 2        |
| 47        | 3        |
| 48        | 4        |

**Prerequisito**: gli utenti con id 2, 3, 4 devono esistere nel target prima di eseguire il customer import.

### Sanitizzazione company_name

Spazi/tab/CR/LF rimossi via TRIM + REPLACE prima della validazione.

### site_type normalization

| Valore legacy | Canonico        |
|---------------|-----------------|
| 1             | fully_operative |
| 2             | only_legal      |
| 3             | only_stock      |
| vuoto/altro   | NULL            |

### Sentinel strategy (withdraws)

`vehicle_id` e `driver_id` NULL/0 → id 9999 (record sentinel creato automaticamente).

## Checks di validazione (script 06)

- Totali: customers, sites, timetables, internal_contacts
- Orphan sites (sites.customer_id senza customer corrispondente)
- Duplicati vat_number / tax_code
- site_type non canonici
- Sites senza coordinate
