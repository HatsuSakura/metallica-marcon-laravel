# Deploy QA UI autisti — esito 2026-11-04

Codice rilasciato: `8c5bba8b0cf9cd9daf3792341b4d664fc097fcfe`.
Ambiente: https://testgestionalelogistica.metallicamarcon.it/ (SiteGround,
PHP residente 8.2.35, Laravel 11.21.0; queue/cache su database).
Deploy richiesto esplicitamente dal PO; nessun intervento in produzione.

## Operazioni eseguite

- Destinazione verificata tramite percorso applicativo e APP_URL. Il valore
  APP_ENV del QA è `production`: è un'etichetta di configurazione, non indica
  il server di produzione; il controllo di destinazione ha usato l'URL QA.
- Snapshot di codice e database creati in directory privata dell'account,
  fuori dalla web root, verificati con gzip e impronte SHA-256. Sono copie
  preventive sullo stesso host, non un backup offsite o un test di ripristino.
- Sorgenti estratti dal commit, asset compilati usando soltanto le variabili
  frontend VITE di QA; nessun .env trasferito o valore pubblicato.
- Trasferimento rsync sull'allowlist del processo QA esistente. Preservati
  .env, storage, vendor, .htaccess/.user.ini, template mail pubblicati,
  file non applicativi e vecchi asset hashed. Rimossi 10 file applicativi
  obsoleti, inclusi controller/pagine autista sostituiti.
- Breve manutenzione, Composer dal lockfile invariato, pulizia cache,
  migrazioni esecuzione viaggi e split/checklist, rinnovo config/route/view,
  segnale queue:restart e riapertura dell'app.
- Nessun aggiornamento del runtime PHP, reset di viaggio, seeder o modifica
  manuale di dati di collaudo.

## Verifiche riuscite

- Login HTTP 200; dashboard, vecchia lista e API esecuzione senza sessione
  reindirizzano al login. Nessuna autenticazione aggirata via endpoint web.
- Tutti e quattro gli asset del manifest restituiscono HTTP 200 e coincidono
  byte per byte con la build locale del rilascio; il login usa il nuovo JS.
- Checksum di route, manifest e servizio ItemLoad coerenti con il pacchetto.
- Migrazioni registrate e schema is_loaded/allocazioni presente.
- Tre viaggi esistenti letti e serializzati tramite DriverJourneyReadModel.
- Modalità manutenzione OFF. Marker privato del rilascio scritto in
  `storage/app/deployments/qa-release.json`; `.last-qa-deploy` locale aggiornato.

Evidenza sanificata: [JSON dei controlli](evidence/2026-11-04-driver-deploy.json).

## Collaudo ancora da eseguire

Seguire [gli scenari UI autisti](2026-11-04-driver-ui.md), inclusi telefono
portrait, tablet e desktop. Le prove HTTP/CLI non attestano accettazione
funzionale né prove touch autenticate.

Dopo le migrazioni QA contiene zero baseline: per provare il nuovo flusso
occorre pianificare e avviare un viaggio nuovo con documenti pronti. I vecchi
viaggi attivi privi di baseline restano in sola lettura. Non sono state
create fixture o modificati viaggi esistenti durante il deploy.

Restano aperti i punti descritti nella guida QA: riordino pre-partenza,
aggiornamento note sede da autista e riconciliazione del backend documentale.
Nessuna issue YouTrack è stata modificata o dichiarata accettata.
