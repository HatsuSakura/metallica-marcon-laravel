# QA — reset esecuzione viaggio 41, 2026-11-17

Operazione dati eseguita via SSH nel solo ambiente testgestionalelogistica.
Autorizzazione PO: azzerare esecuzione conservando il piano; conferma specifica
successiva di ripristinare anche i due ordini già archiviati.

## Stato iniziale

Viaggio legacy attivo, revisione esecutiva 0, nessuna baseline o copia esecutiva;
quattro tappe concluse e quattro ordini collegati (8, 20, 26, 35), due archiviati.
Dispatch managed e quattordici eventi storici. Sei materiali e cinque righe
contenitori; allocazioni e proposte di trasbordo del piano conservate.

## Esecuzione

Transazione con lock autista/viaggio e controllo di stato/associazioni prima
della modifica. Copia privata dei record interessati e 21 file documentali,
prima di ogni mutazione, fuori dalla directory web e dalla retention dei deploy.
Backup sul server QA: `~/manual-data-backups/metallica-qa/journey-41-20261117-155702-5eaf5f/`.
SHA-256 database.json: `be072819c0ecec97a3ab190531faac63f622ca16f2c86639113cdf317230b56f`.
File 0600 e directory backup 0700. Copia verificata per lettura e checksum;
nessun restore eseguito come prova. Dati e documenti originali non copiati nel repo.

Viaggio a creato, tempi esecutivi/scarico/dispatch azzerati e dispatch pending.
Quattro tappe a planned, orari/esiti rimossi; sequenza attuale mantenuta e usata
come sequenza pianificata. Quattro ordini a pianificato, ripristinati 8 e 20,
data ritiro effettiva rimossa; stati materiali e dati operativi di magazzino
azzerati. Nessun materiale/quantità pianificata eliminato. Impegni ordini planned.

Conservati convoglio, autista, note di piano, assegnazioni magazzino, sequenza,
allocazioni e date previste originali: 2026-03-31 16:27–17:27. Le date non sono
state spostate ad oggi. Storico precedente conservato; aggiunto qa_execution_reset
con riferimento al backup e all'autorizzazione (15 eventi totali).

Documenti rigenerati sincronicamente: versioni ordine 8→7, 20→2, 26→9, 35→2;
nessuna dipendenza da coda, nessuna partenza automatica.

## Verifica indipendente dopo commit transazione

Tutti i controlli positivi: creato, revisione 0, tempi vuoti, non più legacy,
nessuna baseline, quattro tappe pianificate e quattro ordini ripristinati,
nessun ritiro effettivo, quattro impegni planned, documenti correnti con file
richiesti presenti e checksum backup valido. Nessun altro viaggio esecutivo
attivo per l'autista secondo la stessa guardia applicativa dell'avvio.

Nessun deploy o modifica di codice QA. Lo sviluppo notifiche non è coinvolto.
