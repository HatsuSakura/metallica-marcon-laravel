# QA — ripristino viaggio 48 prima della partenza, 2026-11-17

Richiesta PO: annullare le prove e ripristinare il viaggio come mai partito.
Operazione dati via SSH sul solo ambiente QA, senza deploy o modifiche al codice.

Stato iniziale: attivo, revisione 14, baseline 3, quattro copie ordine esecutive,
tre tappe concluse e una in corso. Ordini sorgente 59, 62, 63, 64. Nessun ordine
archiviato, assegnazione a viaggio diverso o generazione documentale in corso.

Backup privato di tutti i record interessati, baseline/copie comprese, e 20 file
documentali sul server QA fuori dal webroot:
`~/manual-data-backups/metallica-qa/journey-48-20261117-161622-6e1a71/`.
SHA-256 database.json: `52fd5f6a2500bd1b40c9da3e9fb838444b410a7d21165a5b3f11a195e5f66d08`.
Directory 0700, file 0600; verificati lettura e checksum. Nessun restore di prova
eseguito; dati originali e documenti non copiati nel repository.

Transazione con lock autista/viaggio, controllo revisione e checksum della baseline.
Eliminate soltanto le copie esecutive del 48 in ordine di dipendenza
(allocazioni, materiali, consegne, ordini esecutivi, baseline attiva), dopo backup.
Ripristinati materiali/contenitori e tappe dal piano congelato alla partenza;
ordini riportati agli stati originari, impegni planned. Viaggio creato, revisione 0,
nessun dato esecutivo o orario effettivo. Convoglio e date conservati:
18 novembre 2026, 06:00–07:00. Nessuna partenza automatica.

Documenti sorgente rigenerati e verificati correnti (versione 2 per tutti i quattro
ordini). Conservati 14 eventi di prova; aggiunto qa_execution_reset con riferimento
al backup e checksum originale, totale 15 eventi. Nessun altro viaggio modificato.

Verifica indipendente successiva: tutti i controlli positivi su stato, assenza
baseline/copie, quattro tappe pianificate, ordini agli stati prepartenza, quattro
impegni planned, documenti e checksum backup. Nessun altro viaggio esecutivo attivo
per lo stesso autista secondo la guardia applicativa dell'avvio.
