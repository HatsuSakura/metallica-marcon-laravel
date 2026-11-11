# Piano operativo — congelamento alla partenza ed esecuzioni dedicate

Avvio: 2026-10-31. Autorizzazione PO: documentare e implementare senza ulteriori
conferme operative. Unico database relazionale; nessuna pubblicazione/deploy
inclusa. Preservare le modifiche locali della sessione.

## Regole e scelte implementative

- Prima partenza: transazione unica, baseline completa immutabile e creazione
  di esecuzioni ordine/materiali/contenitori dedicate; Journey non viene clonato.
- Prima della partenza solo Logistica/ruoli di controllo pianificano. Autista
  legge e avvia il viaggio assegnato. Dopo, entrambi modificano l'esecuzione.
- Richiesta Order e sue righe rimangono distinte dall'esecuzione in quel viaggio.
- L'impegno attivo di un ordine è unico. Il salto totale rilascia l'impegno
  immediatamente, conservando la vecchia esecuzione e la baseline.
- Revisione attesa, lock del viaggio e transazioni proteggono ogni comando.
  Originale/precedente si ripristinano come nuove modifiche, mai cancellando storia.
- Riutilizzare journey_events per eventi/diff di revisione; audit/versioning
  esistenti continuano a coprire la pianificazione. Nessun database aggiuntivo.
- Modifica ordine oppure viaggio abilita rigenerazione documenti; download e
  ristampa non modificano stati. Job ancorato alla revisione richiesta.
- Nessun originale retroattivamente inventato per viaggi già avviati: record
  legacy restano identificabili; niente backfill distruttivo automatico.

## Incrementi e verifica

- [x] 1. Schema e model: baseline, esecuzioni dedicate, impegni unici e revisioni.
- [x] 2. Comando unico di avvio; eliminazione degli avvii/aggiornamenti alternativi
  che saltano il congelamento; guard di fase sulle scritture del pianificato.
- [x] 3. Comandi esecutivi: materiali, consegne, carico, sequenza, esiti,
  rilascio/ripresa e ripristini tracciati con conflitti espliciti.
- [x] 4. Read model e UI mobile condivisi per autista/Logistica, storico isolato
  dalle successive modifiche di Order e comandi basati sulla revisione attesa.
- [x] 5. Ciclo documentale: dipendenza da ordine e viaggio, nessuna retrocessione
  business, ristampa in sola lettura, tentativi concorrenti/obsoleti gestiti.
- [x] 6. Integrazione/guard dei consumer dispatch-magazzino: niente mutazioni del
  pianificato congelato tramite i vecchi ingressi; contratto esecutivo esplicito.
- [x] 7. Test isolati: autorizzazioni, prima partenza/idempotenza, immutabilità,
  modifiche concorrenti, salto e ripianificazione prima della fine del viaggio,
  ripristini, source Order cambiato, due trigger e job documentali obsoleti.
- [x] 8. Build, migrazione locale non distruttiva, evidenze e riferimenti GLE locali.

## Criteri di accettazione prioritari

Originale alla prima partenza invariato dopo qualunque comando. Un ordine
saltato su J1 può essere pianificato su J2 immediatamente mentre J1 continua;
la modifica di J2 non cambia lo storico di J1. Un ordine non può avere due
impegni attivi. Nessuna ristampa/rigenerazione riporta ordini o viaggi a creato.
Il client non può sovrascrivere una revisione che non ha letto.

## Evidenze e limiti finali

Implementazione locale verificata; risultati e limiti nelle sezioni seguenti. Nessun rilascio in produzione.

## Implementazione locale — 2026-10-31

### Struttura e ingressi

Migrazione `2026_10_31_200000_create_journey_execution_context.php` applicata
al DB locale. Baseline JSON versionata e checksum canonico SHA-256 in
`journey_baselines`; tabelle relazionali dedicate per esecuzioni di ordini,
materiali, consegne e impegni unici. Nessun clone dei record commerciali.
La baseline comprende anagrafiche operative, tappe, ordini, gruppi CER,
contenitori, configurazione del convoglio e allocazioni/pivot di carico.

`JourneyExecutionService` è l'ingresso unico per partenza e comandi. La prima
partenza verifica la corrispondenza ordine/tappa e i documenti correnti,
congela e inizializza tutto nella stessa transazione. L'avvio ripetuto restituisce
il contesto esistente. Lock del viaggio e revisione attesa proteggono le modifiche;
richieste obsolete ottengono 409. Salvataggi invariati non generano revisioni.

`PlanningWriteBoundary` coordina CRUD pianificazione e prenotazioni; il guard
sui model protegge anche scritture fuori da quei controller. Le vecchie API di
magazzino/dispatch non possono aggiornare il pianificato dopo la partenza.
Ordini sorgente mantengono il ruolo di richiesta commerciale: associazione al
viaggio e stato di evasione possono cambiare tramite comandi espliciti, mentre
materiali e dettagli della vecchia esecuzione restano indipendenti.

Contratto autenticato e autorizzato per risorsa:

- `POST /api/driver/journeys/{id}/start`;
- `GET /api/journeys/{id}/execution` e `/execution/history`;
- `POST /api/journeys/{id}/execution/commands`, corpo `{revision, operation, input}`;
- documenti in `/api/journeys/{id}/execution/orders/{executionOrder}/documents`.

Comandi: riordino, aggiunta tappa da ordine disponibile o da cliente/sede,
soste tecniche, note/convoglio, note/zona ordine, aggiunta/modifica/rimozione
materiali, consegne indipendenti, completamento/salto/riapertura e fine viaggio.
Il salto totale rifiuta movimentazioni parziali, rilascia subito l'impegno e
conserva esecuzione e baseline. La riapertura rifiuta ordini ripianificati e
materiali già ricevuti a magazzino. La redistribuzione delle casse resta una
registrazione esplicita delle quantità indipendenti per tappa; non si introduce
una disponibilità automatica di magazzino non ancora definita con il PO.

### UI e magazzino

Workspace mobile unico per autista e Logistica; anche la consultazione del
magazzino usa lo stesso read model. Componenti riutilizzabili in
`resources/js/Components/Journey/` per strumenti viaggio, editor ordini,
documenti e ricezione. I nomi delle tappe mostrano la sola ragione sociale,
anche da anagrafiche archiviate; il caricamento degli ordini non scarta più
cliente e attività tecnica già caricati sulla tappa.

Ogni materiale conserva originale e storico. Ripristino alla partenza e
annullamento delle modifiche precedenti sono nuove revisioni, senza popup
ripetuti; comandi testuali leggibili anche touch. Si ripristinano quantità,
pesi, descrizione, CER, tipo contenitore, sfuso, zona e rimozione del materiale.
La sezione «Piano alla partenza» rimane leggibile anche dopo il completamento.

Ricezione minima sul nuovo contesto: `warehouse_item` registra magazzino,
lordo/tara/netto, note e ricevuto/pesato/classificato sui materiali eseguiti.
Richiede ruolo magazzino/controllo, distinto dall'autista. `close` richiede
viaggio terminato e classificazione di tutti i materiali non rimossi.
Rettifiche dopo ricezione/chiusura e il ridisegno completo delle esplosioni,
trasbordi, giacenze e disponibilità sono evoluzioni successive: non vengono
simulate attraverso le vecchie tabelle del pianificato.

### Documenti

Fingerprint degli input di ordine **e** viaggio; generazione invariata non
riaccodata. Job con hash richiesto, lock e controllo di obsolescenza; un job
superato non pubblica documenti come correnti. Generazione e download non
cambiano stati business. Modulo e ADR/HP vengono selezionati dalla stessa
versione. I file predisposti prima di partire restano validi per l'esecuzione
iniziale identica; le rigenerazioni esecutive hanno una cartella per esecuzione,
così il riutilizzo dell'ordine su un altro viaggio non sovrascrive i vecchi file.
Dopo il ritiro i nuovi documenti usano le quantità effettive registrate.

### Dati locali e compatibilità verificati

- Viaggio 40: pianificazione con una tappa/un ordine, snapshot strutturalmente
  valido. Le vecchie generazioni senza fingerprint vanno rigenerate dalla
  Logistica prima della prima partenza nel nuovo processo.
- Viaggio 38: pianificazione senza tappe strutturate. La Logistica deve
  completarla nella UI di pianificazione prima dell'avvio.
- Viaggio 159: attivo precedente alla migrazione, tre tappe, nessuna baseline.
  Resta consultabile e segnalato come legacy in sola lettura. Non si inventa
  un originale né si riavvia automaticamente. I controlli sul singolo viaggio
  attivo considerano i nuovi contesti gestiti, senza bloccare i test per il 159.
- Nessuna partenza o movimentazione di questi tre viaggi è stata registrata
  automaticamente. Migrazione e verifica di lettura sono distinte dai test
  eseguiti su fixture SQLite isolate.

### Verifiche

Evidenze automatizzate in `tests/Feature/JourneyExecutionTest.php`: prima
partenza atomica/idempotente; baseline immutabile; conflitti di revisione e
impegno; salto/ripianificazione con viaggio ancora attivo; indipendenza dello
storico dal sorgente; ripristini; quantità ritiro/consegna indipendenti;
completamento/fine/riapertura; ricezione/chiusura; autorizzazioni e guard delle
vecchie API; generazione XLSX reale e job obsoleti; no-op e ragione sociale
archiviata. `LogisticDispatchWorkspaceApiTest` è stato aggiornato al nuovo confine: vecchie
API in sola lettura sui viaggi avviati, comandi esecutivi per Logistica e
ricezione dedicata al magazzino. Fixture condivisa isolata in
`tests/Support/JourneyExecutionTestCase.php`; i vecchi test non scrivono più nel
DB di sviluppo.

La verifica visuale touch autenticata e il carico concorrente reale MySQL non
sono stati automatizzati in questa sessione. Nessun deploy o cambio di stato
YouTrack è incluso in questa implementazione locale.

Risultato finale delle verifiche locali:

- **34 test PHP, 189 asserzioni, tutti passati** sulle sei suite mirate:
  JourneyExecution, LogisticDispatchWorkspaceApi, DriverJourneyNavigation,
  DriverDashboard, MoveDriverJourneysToToday, DevApiDocs.
- **3 file di test frontend passati**, inclusi consegna effettiva indipendente,
  zero esplicito e quantità non ancora registrata.
- **Build Vite riuscita**; rimane l'avviso già presente sulla dimensione del
  bundle principale (circa 3,25 MB, gzip 1,05 MB).
- Sintassi PHP e `git diff --check` senza errori. Nuovi file PHP formattati con Pint.
- Segnale `queue:restart` inviato nell'ambiente locale per far ricaricare ai
  worker il servizio documentale aggiornato; non equivale a verificare la
  presenza o l'esecuzione continuativa di un worker.

## Completamento GLE-122 nella stessa sessione

Menu con anteprima dei valori, duplicati accorpati, feedback mobile con
annullamento del ripristino e protezione delle bozze non salvate completati.
La terminologia è stata chiarita con il PO: l'operativo è l'esecuzione del
pianificato; i fatti effettivi ne costituiscono il risultato, senza una terza
copia del viaggio. Dettaglio e prove in
[GLE-122](GLE-122%20Modifiche%20operative%20e%20ripristini.md).
Suite complessive aggiornate: 41 test PHP/232 asserzioni, 5 file frontend e
build passati. Nessuna nuova migrazione o modifica dei viaggi locali inclusa.

## Correzioni concordate e preparazione GLE-118

**Convoglio:** motrice e rimorchio sono modificabili dalla Logistica prima
della partenza; dopo l'avvio rimangono fissi. Le precedenti indicazioni di
modifica del convoglio durante l'esecuzione erano un'estensione errata e sono
superate da questo vincolo. Rimossi controlli UI e lookup inutili, vietati i
campi nelle API e nel comando di dominio, incluso rimorchio nullo; dashboard,
read model e documenti non applicano override del convoglio esecutivo.
Il controllo dei viaggi locali gestiti non ha rilevato override già salvati.

**Riordino pre-partenza dell'autista:** [OP-001](open-points.md) risolto il
2026-11-09: nessuna modifica del pianificato; «Avvia e riordina» registra
la partenza reale e apre il riordino operativo solo se l’avvio riesce.

**159:** le indicazioni precedenti di sola lettura descrivono lo stato prima
del reset esplicitamente richiesto dal PO. È stato preparato per un nuovo
avvio locale con backup e storico conservato; successivamente risulta avviato
con baseline. Vedi [GLE-118](GLE-118%20Riapertura%20tappa.md) per evidenze e
semantica definitiva della riapertura.
