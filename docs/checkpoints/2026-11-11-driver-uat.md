# Checkpoint autisti — 2026-11-11, attesa UAT

## Stato verificato e ripresa

Codice consolidato, committato e inviato a origin/main:
`89b2b22a411e99cbb260aba9318079b70408d5dc`.
Rilasciato in QA il 2026-11-11 con deploy-qa.sh, PHP residente 8.4.27 e
Laravel 11.57.1, debug e maintenance OFF. Nessun rilascio produzione.
Snapshot preventivo codice/vendor/database verificato gzip e checksum:
`~/deploy-backups/metallica-qa/20261111T165339Z-before-89b2b22` sul server QA.
È un backup sullo stesso host, non una protezione offsite indipendente.
Nessuna nuova migrazione; cache ricostruite e segnale queue:restart inviato.
Smoke: login 200, dashboard senza sessione 302, tre entry asset 200.
Marker remoto storage/app/deployments/qa-release.json e locale .last-qa-deploy.

PO: attendere i collaudi UAT e dichiarare conclusa l'attività solo dopo gli esiti.
Non confondere sviluppo, disponibilità QA, accettazione e produzione.

## Funzionalità raggiunte

Dashboard giorno corrente, date italiane, storico/futuri, convoglio e avanzamento;
workspace responsive condiviso, tappe concluse prima della corrente e sempre
recuperabili. Ordini per CER con descrizioni item, quantità e contenitori.
Referenti sede e ruoli, chiamate separate mobile/fisso da 42 px, contatti di tutte
le tappe e gestione referenti nell'anagrafica. Nessun piano/storico tecnico nel driver.

Baseline immutabile alla prima partenza, esecuzioni dedicate nel relazionale,
comandi con autore/diff/revisione. Modifiche materiali/CER/quantità, tappe,
riapertura con fatti conservati, rilascio immediato degli ordini di tappe saltate.
Motrice/rimorchio fissi dopo partenza; allocazioni per item, split su due righe
visive senza duplicare item e checklist persistente. Ritiri/consegne indipendenti;
residui = consegne pianificate iniziali incluse scorte meno consegne effettive.

Note sede inline dopo i contatti, una sezione per sede distinta: modifica
anagrafica condivisa con permesso ristretto al viaggio assegnato attivo,
concorrenza tramite versione, evento con prima/dopo; snapshot congelati invariati.
OP-001 risolto: nessun riordino del pianificato da autista; «Avvia e riordina»
registra partenza effettiva e apre editor solo dopo successo. Annullare il solo
riordino successivamente non annulla la partenza.

Notifiche unificate nella coda globale già esistente, host unico anche fra
layout; precedente feedback driver rimosso. Contratto flash/queueMessage
compatibile. N operazioni generano N messaggi anche identici; nuovi in basso,
ID e timer individuali. Permanenza 3 s + fade 0,5 s, errori/azioni persistenti,
chiusura indipendente dai timer vicini. Grafica precedente con correzioni mobile:
stessa riga per icona/testo/X, chiusura circolare outline 42 px, padding 6/12 px,
margini laterali minimi 12 px e safe area, animazione ridotta rispettata.
Annulla ripristino mantiene i controlli di validità ed elimina callback all'uscita.

## Test e limiti

Suite pertinente completa: 86 PHP / 586 asserzioni. Ultimo controllo precommit:
13 PHP / 101 asserzioni; 12 file frontend passati. Notifiche: 10 casi mirati
su contratto legacy, ordine, duplicati, timer, chiusure, isolamento e azioni.
Build Vite completata; avviso preesistente dimensioni bundle. Smoke QA positivi,
nessuna accettazione manuale touch/UAT documentata.

## Tracker aggiornato via MCP nativo

Query project: GLE tag: ui-autisti, una pagina da 20, 11 risultati,
hasNextPage=false; rilettura dopo aggiornamenti. Tutte GLE-116–126 in «In corso»,
nessuna risolta. Il workflow ammette solo To do / In Progress / Done: non esiste
«In collaudo». Aggiunto commento a ciascuna con funzionalità, commit, deploy,
verifiche e attesa UAT. Fonte: ../evidence/youtrack-driver-2026-11-11.json.

Nove issue funzionali hanno il comportamento principale disponibile in QA;
GLE-116 epica rimane in attesa di accettazione complessiva. GLE-122 non è
integralmente completata rispetto al testo ampio: ripristini disponibili sui
campi materiale, non su consegne/contenitori vuoti, intera struttura/sequenza
o quantitativo di uno split precedente. Residuo esplicitato nel commento MCP;
non cancellato né reinterpretato come accettato. Anche la riconciliazione delle
API documentali operative con la decisione PO sui soli documenti pianificati
resta un tema noto della review, fuori dalle due ultime modifiche implementate.

## Prossima sessione

1. Acquisire esiti UAT per GLE-116–126, registrare difetti e distinguere bug da nuove richieste.
2. Concordare il perimetro residuo GLE-122 e del ciclo documentale senza dichiararlo chiuso.
3. Correggere esiti e rieseguire test pertinenti; dichiarare conclusa l'attività e chiudere le issue solo dopo accettazione.
4. Nessun nuovo sviluppo o deploy automatico è richiesto dalla sola ripresa.

Le conversazioni sulle credenziali Workspace non sono incluse in questo checkpoint.
Dati di test e backup DB restano fuori Git/KB. Reset locale del 159 eseguito il
9 novembre: creato, tre tappe originali, nessuna baseline/esecuzione/evento e
documenti correnti al controllo di allora; non è una lettura dello stato odierno.
