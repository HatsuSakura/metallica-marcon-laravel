# Allineamento Jarvis al rilascio QA — 20 novembre 2026

## Versione e perimetro

Distribuito su Jarvis il commit `a408e0e5bb062c1d07249ce1936e989fa68a8fdb`, verificato nel marker `storage/app/deployments/qa-release.json` di SiteGround QA (rilascio del 17 novembre 2026, ore 16:19:19 UTC).

Sorgenti esportati con `git archive` dal commit: nessuna modifica non committata della working tree è inclusa. In particolare, la funzionalità notifiche in sviluppo rimane esclusa.

Endpoint Jarvis: <https://mm.argentonmatteo.com/>, protetto da Cloudflare Access.

## Infrastruttura mantenuta

- Database MariaDB e Valkey: stessi container, reti e volumi, verificati per identificativo e mount prima/dopo.
- Storage applicativo: stesso bind mount e contenuto persistente; database Jarvis conservato, senza importare dati QA.
- Configurazione ambiente, tunnel Cloudflare e routing esistenti conservati.
- Ricreati soltanto applicazione, worker e scheduler con immagine `metallica-marcon-jarvis:a408e0e`.
- Laravel 11.57.1; runtime Docker PHP 8.4.27 previsto dal Dockerfile del commit.

La build usa un contesto pulito separato dal deployment precedente. Le sole variabili pubbliche `VITE_*` sono state preservate per il frontend, senza copiare la configurazione server nel contesto di build. Gli asset sono ricostruiti per Jarvis: il commit sorgente coincide con QA, non si pretende identità binaria degli asset dei due ambienti.

## Migrazioni applicate

1. `2026_10_31_200000_create_journey_execution_context`
2. `2026_11_02_180000_add_execution_load_allocations`
3. `2026_11_13_160000_add_driver_uat_preferences`

Verificati stato iniziale e simulazione prima della manutenzione. Nessuna migrazione pendente dopo il rilascio; assente la migrazione delle notifiche del 17 novembre.

## Backup e ripristino

Backup privato sul server, fuori dalla webroot:

`/home/hatsusakura/apps/metallica-marcon-backups/20261120T151909Z`

Contiene dump database compresso, archivio storage, configurazione precedente e riferimenti alle tre immagini precedenti, conservate con tag di rollback. Verificate integrità gzip del dump, leggibilità dell'archivio storage e checksum SHA-256. Non è stata eseguita una prova completa di ripristino su un database separato.

In caso di rollback, mettere in manutenzione e fermare worker/scheduler; ripristinare insieme database, storage e immagini/configurazione precedenti. Il solo cambio immagine non ripristina lo schema precedente. Nessuna operazione sui volumi tramite `down -v`.

## Gestione dei riavvii

Directory operativa: `/home/hatsusakura/apps/metallica-marcon`.

Il Compose originale resta invariato. `.jarvis-active-release.yml` seleziona immagine e contesto di build dei soli servizi applicativi; `docker-compose.override.yml` è un collegamento a questo file, caricato automaticamente dai normali comandi Compose nella directory operativa.

```sh
cd /home/hatsusakura/apps/metallica-marcon
docker compose up -d --no-build
docker compose ps
```

Un comando che usa esplicitamente `-f docker-compose.yml` deve includere anche `-f .jarvis-active-release.yml`, perché la selezione esplicita dei file esclude l'override automatico. Non usare lo script di deploy Git precedente su questa installazione priva di `.git`.

Sorgenti puliti, log privati di build/deploy e marker sono nella directory server:

`/home/hatsusakura/apps/metallica-marcon-releases/a408e0e5bb062c1d07249ce1936e989fa68a8fdb`

Marker applicativo: `storage/app/deployments/jarvis-release.json`.

## Verifiche eseguite

- Applicazione, worker, scheduler, database e Valkey attivi, senza restart rilevati al controllo finale.
- Hash di servizio esecuzione, controller ordini, pagine creazione ordine/viaggio e `composer.lock` corrispondenti all'export del commit QA.
- Connessioni database e Redis funzionanti, manutenzione disattivata, storage viste scrivibile dall'utente web.
- Origine: login HTTP 200; rotte riservate reindirizzano al login; quattro asset della pagina login serviti con HTTP 200.
- Endpoint HTTPS pubblico raggiungibile, con autenticazione Cloudflare Access conservata.
- Configurazione Compose predefinita risolve la nuova immagine per tutti e tre i servizi applicativi.

Non eseguiti test funzionali autenticati di ordini/viaggi: resta il collaudo operatore.

## Intervento separato da pianificare

Nel preflight è stato esposto accidentalmente nell'output del comando un parametro di autenticazione Valkey. Il valore non è riportato in questo documento. La rotazione coordinata della credenziale e dei client resta da effettuare separatamente dal rilascio.
