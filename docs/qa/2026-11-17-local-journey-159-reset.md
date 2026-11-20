# Dev locale — reset viaggio 159 per prove notifiche, 2026-11-17

Autorizzazione PO: annullare le modifiche delle prove, riportare il viaggio
all'inizio e impostare la data odierna. Solo database locale 127.0.0.1,
APP_ENV=local; QA e produzione non coinvolti. Nessun commit/push/deploy.

Stato iniziale: attivo, revisione 27, baseline 2, tre ordini esecutivi, una tappa
conclusa, una corrente e una pianificata; sette interventi del ledger notifiche.
Ripristinati gli ordini sorgente 8, 40, 168 e le tre tappe dal piano congelato,
annullando anche il riordino di prova. Rimosse le copie esecutive, allocazioni,
consegne e baseline attiva; viaggio creato e revisione 0. Eliminata anche una
data effettiva di ritiro storica presente già nella vecchia baseline.

Data di partenza portata al 17 novembre mantenendo orario italiano e durata:
22:20:03–23:20:03 Europe/Rome (21:20:03–22:20:03 UTC). Convoglio e dati del piano
conservati, impegni ordini planned, nessuna partenza automatica.

Backup privato escluso da Git, prima delle modifiche:
`storage/app/private/development-backups/journey-159-20261117-172539-49032d/`.
Record completi, baseline/ledger compresi, più 70 file documentali.
SHA-256 database.json: `d0ba1dd10b75195dc5368fc8550215421fa511aed8281d275e0f234e7eec9ef3`.
File 0600, directory 0700; verificati lettura e checksum. Nessun restore di prova.
Dati personali e documenti originali non riportati nel repo.

Ledger del 159 azzerato dopo backup per evitare confronti col precedente esito
delle prove; notifiche personali già emesse conservate. Storico JourneyEvent
preservato (27 precedenti + development_reset), nessuna nuova notifica emessa.
Documenti correnti rigenerati: ordine 8 versione 6, 40 versione 5, 168 versione 17.

Verifica indipendente: data italiana odierna, creato, nessun orario effettivo,
nessuna baseline/copia esecutiva/ledger attivo, tre tappe pianificate, tre ordini
agli stati prepartenza senza ritiro effettivo, tre impegni planned, documenti
correnti e checksum backup valido. Nessun altro viaggio esecutivo attivo per
l'autista secondo la guardia applicativa. Ricaricare la pagina prima delle prove.
