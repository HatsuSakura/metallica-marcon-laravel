# Release unificata DEV — QA e Jarvis

## Perimetro approvato

Un solo commit sorgente integra i tre filoni:

- Notifiche operative `journey-execution-update`: esiti tappe cliente/tecniche,
  riaperture, fine viaggio, modifiche Logistica raggruppate e destinatari backend.
  Modifiche intermedie dell'autista silenziose. Confronti deduplicati, paging,
  filtri, stato letto, toolbar e card compatte approvati nel collaudo locale.
- Overlay autista: TAPPA CONCLUSA/SALTATA e PROSSIMA TAPPA con icone inline,
  ragioni sociali normali, truck/Buon viaggio, OK esplicito; sfondo sfocato,
  blocco doppi comandi, reduced motion. Nessuna chiusura automatica.
- Anagrafica CER riservata ai ruoli backoffice autorizzati: creazione/modifica,
  ricerca, disattivazione e conservazione dei riferimenti storici.
- API v1 Bearer per ordini/materiali/viaggi e riferimenti, CER sola lettura;
  identità agenti e token gestibili solo dal ruolo developer, idempotenza,
  versioni concorrenti e Swagger aggiornato a 1.3.0. `is_active` dei CER esposto
  in lettura; nuove righe con CER disattivato rifiutate anche da API.
- Sessioni 720 minuti di inattività, verifica al rientro, login guidato dopo
  401/419 e bozze IndexedDB esplicite con conflitti, isolamento e TTL 24h.

I commit di lavoro restano nella storia dei branch; il commit definitivo è
nel manifest `release.json` del pacchetto e nella history Git, non un SHA
inventato prima del commit. Configurazioni private e dati non sono inclusi.
Le bonifiche di viaggi/notifiche erano esclusivamente locali e non vengono
riprodotte nei due ambienti.

## Verifiche

Verifiche integrate: **154 test backend / 1.104 assertion** su SQLite in memoria,
**22 file di test frontend**, build Vite, lint di **71 file PHP** e cache rotte.
Chrome: 20 combinazioni viewport/tema, recupero sessione/IndexedDB, editor
materiale reale e overlay, con API simulate e storage browser reale.
I risultati sono in [release-checks.json](../evidence/session-recovery-2026-11-20/release-checks.json);
gli hash degli artefatti saranno nei manifest generati dal commit pubblicato. Nessun rilascio runtime è implicito nel pacchetto.
Le evidenze precedenti restano fotografie della versione specificata, non
prove di attivazione del commit finale in QA/Jarvis.

## Migrazioni rispetto alla precedente QA a408e0e

1. `2026_11_17_120000_create_journey_execution_updates`
2. `2026_11_20_160000_add_active_to_cer_codes`
3. `2026_11_20_180000_create_agent_api_tables`

Jarvis aveva già la terza migrazione per le API: eseguire le sole pendenti,
senza ricreare identità/token né importare database da DEV o QA.
Le notifiche sono salvate nella transazione DB, senza dipendenza da Redis o
queue worker; i documenti continuano a richiedere la coda del proprio ambiente.

## QA SiteGround

Dal checkout pulito del commit pubblicato indicato nel manifest:

```sh
./deploy-qa.sh --prepare-only /percorso/pacchetto/qa
./deploy-qa.sh --dry-run
./deploy-qa.sh
```

`--prepare-only` produce archivio applicativo/asset con valori pubblici Vite
letti dall'ambiente QA, manifest e checksum, senza modificare QA. Il deploy
standard ricostruisce lo stesso commit, effettua backup e manutenzione,
trasferimento allowlist, Composer, migrazioni e cache, applica SESSION_LIFETIME
720 e ne verifica il valore effettivo, riavvia la coda e scrive il marker.
Nessun `.env` locale o database viene trasferito. Se il commit cambia, rigenerare
il pacchetto. Non usare `deploy.sh` Docker per SiteGround.

## Jarvis Docker

Installazione: `/home/hatsusakura/apps/metallica-marcon`;
releases: `/home/hatsusakura/apps/metallica-marcon-releases/<commit>`.
URL: <https://mm.argentonmatteo.com>, con Cloudflare Access conservato.

Trasferire l'archivio sorgente e `scripts/prepare-jarvis-release.py` nella
release remota e avviare la preparazione:

```sh
python3 prepare-jarvis-release.py source.tar.gz --commit <SHA_COMPLETO> --sha256 <HASH_ARCHIVIO>
```

La preparazione verifica checksum, estrae sorgenti puliti, recupera solo le
variabili pubbliche VITE dal container attivo, costruisce l'immagine e salva
`prepared.json` e `override.yml`. Non cambia immagini/container attivi né
`.jarvis-active-release.yml`. Il nuovo override imposta anche SESSION_LIFETIME
720 sui tre servizi applicativi: la configurazione `.env` e l'infrastruttura
esistenti restano conservate. Il file JSON è YAML valido per Compose.

Attivazione successiva, separata dalla preparazione:

1. Verificare `migrate:status` e `migrate --pretend --force` usando la nuova
   immagine e la connessione esistente. Verificare le migrazioni attese sopra.
2. Salvare backup privato di database, storage, `.env`, Compose/override e tag
   delle immagini precedenti; verificare checksum e leggibilità degli archivi.
3. Fermare worker/scheduler, attivare manutenzione sull'app corrente.
4. Copiare `override.yml` della release in `.jarvis-active-release.yml`,
   conservando il Compose originale e il relativo symlink automatico.
5. Ricreare solo app, worker e scheduler, senza ricreare DB/Valkey, reti o volumi.
   Eseguire migrate/cache e `session:lifetime 720 --check`, poi riaprire l'app.
6. Verificare HTTP login, API anonime 401, asset, Swagger, storage, DB/coda e
   scrivere `storage/app/deployments/jarvis-release.json` con SHA e prove reali.

Mai `docker compose down -v`. Non stampare `.env`, `docker inspect` completo o
configurazioni risolte: possono contenere credenziali. Il rollback richiede
coerenza di codice/schema/storage/config; non basta cambiare tag immagine.

## Collaudo dopo l'attivazione di ciascun ambiente

- Logistica riceve una sola notifica per conclusione/salto/riapertura e fine
  viaggio; quantità/caricato intermedi non producono allarmi ripetitivi.
- Fine viaggio con tappe aperte resta rifiutata; retry non duplica le notifiche.
- Driver riceve solo modifiche Logistica al proprio viaggio attivo.
- Lettura notifiche, filtri, paging, link, disclosure e overlay mobile/desktop.
- Nuovo ordine con CER pericoloso/HP; CER disattivato non usabile su nuove righe.
- API non anonime, privilegi token limitati, gestione token solo developer;
  Swagger mostra ordini/materiali/viaggi/CER e `is_active`.
- Sessione effettiva 720, login/ritorno, assenza rete e recupero bozze senza replay.
- Documenti generati con worker attivo. Nessun reset dati QA/Jarvis richiesto.
