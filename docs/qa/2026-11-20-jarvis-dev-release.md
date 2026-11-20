# Jarvis — deploy della release DEV approvata, 2026-11-20

Attivato il sorgente **74b7687dd0730beefc45cd7650bf7eed59e6697a** su
<https://mm.argentonmatteo.com>, immagine `metallica-marcon-jarvis:74b7687dd073`.
Include notifiche/fine viaggio, CER, API agenti e sessioni/bozze locali.
QA è gestita separatamente dal PO: questo documento non ne certifica il deploy.

## Operazioni e controlli effettivi

- Backup privato DB/storage/config e tag rollback:
  `/home/hatsusakura/apps/metallica-marcon-backups/20261120T210155Z`.
  Verificati integrità gzip, leggibilità archivio storage e SHA-256; non eseguito
  un ripristino completo su database separato.
- Manutenzione, stop worker/scheduler, sostituzione dei soli tre servizi app.
  Database MariaDB/Valkey, identificativi container, mount e volumi conservati.
- Applicate le due migrazioni pendenti: ledger notifiche e is_active CER.
  Migrazione agenti già presente; nessuna migrazione residua.
- Runtime: Laravel 11.57.1, DB/Redis raggiungibili, viste scrivibili, sessioni
  effettive 720 minuti, OpenAPI 1.3.0 con is_active CER. Hash dei file principali
  corrispondenti al sorgente esportato. Tre servizi app attivi, zero restart
  rilevati al controllo finale.
- Login HTTP 200, pagine protette redirette al login, API v1 e probe sessione
  anonimi 401. Quattro asset frontend e fallback storage pubblico HTTP 200.
  HTTPS pubblico raggiungibile con Cloudflare Access conservato.

## Correzione permessi durante il rilascio

Il contesto estratto da Python con filtro data aveva directory influenzate
all'umask privata 077: leggibili da root ma non dall'utente web. Rilevato nel
controllo post-deploy, corretto nel container per ripristinare l'accesso e poi
**ricostruita l'immagine** con directory sorgente 0755 e file delle sole variabili
pubbliche VITE leggibile. Ricreati i tre servizi e il link storage, ripetuti i
controlli. Non è una correzione affidata soltanto a un container mutabile.

Lo script di preparazione nel repository normalizza ora esplicitamente i
permessi e verifica la leggibilità di bootstrap/app.php e .env.production
come www-data prima di dichiarare pronta l'immagine. Nessun dato runtime
privato reso pubblico; lo storage persistente resta invariato.

Marker e prove runtime: directory release remota
`/home/hatsusakura/apps/metallica-marcon-releases/74b7687dd0730beefc45cd7650bf7eed59e6697a/`,
`release.json`, `verification.json` e marker applicativo
`storage/app/deployments/jarvis-release.json`.
Il collaudo funzionale degli operatori resta distinto dagli smoke test tecnici.

## Warning Composer segnalato dal PO durante il deploy QA

Riprodotto localmente sul lock di 74b7687: Composer 2.10.3 lo considera valido,
Composer 2.11.1 (versione fissata nello script QA) segnala content-hash obsoleto.
Il warning da solo non indica un'installazione fallita: controllare l'esito
finale di install/deploy. Non eseguire un update generico dei pacchetti.

Riallineato **soltanto content-hash** con l'API Locker::updateHash di Composer
2.11.1. Confronto strutturale di tutto il resto del lock prima/dopo identico:
nessuna versione, dipendenza, riferimento o checksum pacchetto cambiato.
Validazione Composer 2.11.1 superata. Usare il Composer fissato dallo script;
un Composer diverso può nuovamente giudicare diversamente il fingerprint.

Questa manutenzione del lock e dello script è un commit successivo al sorgente
applicativo 74b7687 rilasciato: non dichiarare implicitamente quel commit
successivo come già presente nei container o nel deploy QA avviato dal PO.
