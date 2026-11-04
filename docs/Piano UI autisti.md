# Piano UI autisti — mobile-first

Data: 2026-10-31. Stato: proposta di sequenza realizzativa; requisiti derivati
dai chiarimenti del PO e dalle issue create via MCP. Nessun codice modificato,
test applicativo o rilascio eseguito durante questa pianificazione.

## Fonti e riferimenti

- [Analisi del processo](Logistica%20E2E%20Analisi_v03.md).
- [Specifica target](Logistica%20E2E%20Implementazione_v01.md).
- [Contesto logistico](../.ai/logistics/CONTEXT.md).
- Documento utente: `20261005_ROGRAMMA TEO- cosa da modificare.odt`, prima lista.
- [GLE-116](https://metallicamarcon.youtrack.cloud/issue/GLE-116): epica responsive.

Le dieci issue funzionali sono correlate all'epica; le future sub-issue
implementative saranno definite dopo la revisione del piano, senza duplicare
questi requisiti. Tutte assegnate a `matteo.argenton`, stato iniziale Da fare,
priorità del progetto invariata. Il punto annotazioni richiede spiegazione al
cliente, non una issue. Tag `ui-autisti` creato dal PO e applicato alle undici
issue; titoli senza prefisso tematico, secondo la regola di progetto.

## Decisioni di processo consolidate con il PO

Chiarimento del 2026-10-31: il confine è la prima partenza. Prima, solo
Logistica modifica il pianificato; dopo, sia Logistica sia autista modificano
l'esecuzione rispetto all'originale congelato. Questa regola vale anche per
gli ordini collegati e completa le indicazioni di autonomia qui sotto.

Il viaggio logistico è un canovaccio ottimizzato sui dati disponibili.
L'autista adatta il viaggio operativo alla realtà: materiali aggiuntivi,
quantità diverse, contenitori, sequenza e aggiunta/rimozione delle tappe.
Non occorre una nuova approvazione logistica per ogni variazione.

Conservare il piano originale e la sua esecuzione operativa corrente, con i
fatti eseguiti registrati al suo interno: non esiste una terza copia del
viaggio chiamata «effettivo». Una nuova tappa non è un movimento già eseguito; una tappa rimossa
dal percorso rimane nella storia. Le modifiche conservano autore, istante,
contesto e valori prima/dopo. Autonomia operativa non significa privilegi
amministrativi né modifiche silenziose dei dati originali.

Sono richiesti sia ripristino dell'originale sia annullamento dell'ultima
modifica, come nuove operazioni tracciate. Proposta touch: menu Ripristina
con valori di destinazione, applicazione immediata e feedback con Annulla.
Nessuna informazione essenziale affidata al mouseover. Definire la semantica
degli annullamenti successivi e dei valori originariamente assenti.

Quantità di casse ritirate e casse vuote lasciate indipendenti. La sostituzione
prevista è normalmente 1:1 salvo indicazioni; l'effettivo può differire.
L'autista può usare scorte o ridistribuire casse tra tappe: il sistema rende
visibili disponibilità e impegni, senza scegliere il cliente da penalizzare.

## Ricognizione iniziale: evidenze e limiti

- `resources/js/Pages/Dashboard/Driver.vue`: launcher verso lista viaggi.
- `Driver/Journey/Index.vue`: correnti/storico e chiamata API di riordino.
- `DriverJourneyController::index`: correnti filtrati per stato, storico
  paginato; non è ancora la dashboard centrata sulla data richiesta.
- `Driver/Journey/Components/JourneyOrdersList.vue`: timeline con larghezza
  minima `22rem` e riepilogo materiali che non espone gli imballi richiesti.
- `API_DriverJourneyStopsController`: comandi di avvio, riordino,
  completamento, salto e aggiunta tecnica; il riordino aggiorna `sequence` e
  registra un evento. Questo non dimostra snapshot originale e ripristino
  completo per tutti i dati.
- `Driver/Journey/Show.vue` ed `Edit.vue`: viste distinte da considerare insieme
  per evitare comportamento divergente; le directory `Journey copy` non sono
  prova di percorsi attivi.

Non verificati: comportamento runtime, riproduzione del bug delle completate,
copertura audit effettiva, intero contratto dati dei contatti/contenitori,
rilascio in produzione. Non assumere che basti modificare il CSS.

## Sequenza proposta

| Fase | Risultato verificabile | Issue e dipendenze |
| --- | --- | --- |
| 0 — Contratti e gap | Mappa delle route attive, payload, stati, contatti, imballi e audit; riproduzione bug; disegno originale/operativo/eseguito | GLE-122, GLE-121; verificare sovrapposizioni con backlog audit GLE-26/95–99 e GLE-17 prima delle sub-issue |
| 1 — Flussi e componenti touch | Wireframe portrait di dashboard, viaggio, tappa e modifica; adattamenti tablet; navigazione e azioni condivise | GLE-116; definire con il PO prima del codice dei flussi |
| 2 — Dashboard oggi | Lista viaggi con data, mezzo, partenza, avanzamento, ID, storico/futuri e ritorno a oggi | GLE-124; richiede contratto query per data e gestione viaggio attivo oltre mezzanotte |
| 3 — Panoramica viaggio | Prossima tappa evidenziata, successive chiamabili, completate compatte e recuperabili; contatti persistiti e caricati | GLE-119/123/121; stesso modello dati tra consultazione ed esecuzione |
| 4 — Dettagli e riepiloghi | Contenitori visibili prima e dentro l'ordine; previsto prima del completamento, effettivo dopo, confronto disponibile | GLE-117/125; selettori e aggregazioni condivisi, niente duplicazione dei calcoli |
| 5 — Modifiche operative | Materiali/CER/quantità, variazioni tappe, riordino touch, ripristini e riapertura | GLE-122/120/118; richiede conservazione originale e audit verificati nella fase 0 |
| 6 — Casse vuote | Due quantità indipendenti, redistribuzione tra tappe e disponibilità residua leggibile | GLE-126; richiede origine del carico iniziale e storico delle movimentazioni |
| 7 — Collaudo integrato | Flusso completo e regressioni, touch reale, portrait/tablet/landscape | GLE-116 e criteri delle singole issue |

Le fasi 2–4 possono avanzare sul percorso di consultazione mentre si completa
la fondazione delle mutazioni. Non rendere operative modifiche che perdono il
piano originale. Le dipendenze sono funzionali: nessuna stima temporale ancora.

## Struttura UX proposta

1. Dashboard: data corrente e lista compatta dei viaggi; nessuna tabella larga.
2. Viaggio: prossima tappa in evidenza con Chiama; successive sotto; completate
   raccolte e consultabili. Nessun effetto fisheye dinamico necessario.
3. Tappa: sede/contatto, istruzioni, contenitori/materiali, effettivo e azioni.
4. Modifica: campi touch e tastiera appropriata, differenze riconoscibili,
   stato di salvataggio esplicito, accesso ai due ripristini.

Telefono portrait a colonna singola; tablet può affiancare elenco e dettaglio.
La navigazione conserva viaggio, tappa e data al ritorno. Gestire errori di
rete e salvataggi senza dichiarare dati salvati prima della risposta; capacità
offline completa non ancora concordata e non implicita nella responsività.

## Verifiche di accettazione da pianificare

- Dimensioni rappresentative proposte: 360/390/430 px in portrait, tablet
  768/820 px, landscape e desktop; verificare anche zoom e tastiera virtuale.
- Lettura e operazioni senza scorrimento orizzontale, comandi di circa 44 px
  come obiettivo progettuale, etichette accessibili e focus visibile.
- Completare → uscire → rientrare → consultare/riaprire; nessuna tappa persa.
- Riordinare → ricaricare → chiamare la prossima tappa corretta.
- Modificare più volte → originale/precedente → verificare storia e dati.
- Ritiro 6 casse con consegna di 4 oppure 8 vuote; ridistribuzione verso tappe
  successive e confronto con l'originale.
- Doppio tocco, errore di salvataggio e modifica concorrente: niente movimenti
  duplicati né perdita silenziosa di variazioni.
- Test funzionali di dominio per audit/ripristini e lifecycle; verifiche UI
  sui flussi, senza test che rispecchiano soltanto dettagli del CSS.

## Prossime decisioni

### Ingresso unico e primo incremento contenitori — 2026-10-31

Decisione PO: unico ingresso autista in `/dashboard-driver`, con titolo
“I miei viaggi”. Rimosso il link “Tutti i viaggi”; `/driver/journey` rimane
solo un redirect compatibile alla dashboard, mantenendo `date` se valida.
Eliminate la lista desktop e le sue componenti non più usate. La nuova
`Driver/Journey/Workspace.vue` serve sia gestione sia consultazione storica,
con comandi nascosti nella modalità di sola lettura e nei viaggi conclusi.
Ritorni e redirect delle operazioni puntano alla dashboard. Il possibile
riuso analitico lato Logistica è un lavoro separato, non implementato qui.

Il caricamento condiviso `DriverJourneyReadModel` è usato dalle viste iniziali
e dalle risposte dei comandi API, per mantenere contatti, imballi e materiali
coerenti dopo le operazioni. Primo incremento GLE-117/125: riepiloghi degli
imballi nelle tappe e negli ordini, dettaglio per materiale; previsti dalle
righe ordine e, dopo completamento, effettivi dal censimento del viaggio.
Quantità zero distinta da dato assente; censimento parziale non presentato
come totale completo; confronto col previsto se differente. Nessuna scrittura
di piano o dati effettivi introdotta da questo incremento.

Limite da risolvere con GLE-122: le righe ordine correnti non sono una prova
di snapshot immutabile del piano originale. Il confronto corrente è con il
previsto disponibile, non una dichiarazione di storico originale completo.
Restano da implementare registrazione autonoma dell'effettivo da parte
dell'autista, conservazione iniziale e ripristini tracciati; GLE-117/125 non
sono chiuse. Anche la granularità del censimento è attualmente viaggio/riga
materiale, non un nuovo evento di movimentazione per singola visita.

Verifica read model sul DB locale per viaggio 159: 3 tappe, 7 materiali con
tipologia contenitore, nessuna riga di censimento effettivo. Nessun dato
personale riportato. Test isolati PHP: 9 passati/54 asserzioni; test JS per
contatti e conteggi previsti/effettivi passati; build Vite passata. Nessun
deploy. Prossima fondazione: GLE-122, prima di riaperture e modifiche estese.

### Incremento GLE-124 — 2026-10-31

Implementazione locale della dashboard giornaliera in `DriverDashboardController`,
`Dashboard/Driver.vue` e `DriverJourneyCard.vue`. Giorno e orari Europe/Rome,
navigazione per data, lista dei soli viaggi assegnati, schede con mezzo,
partenze previste/effettive e avanzamento. I viaggi attivi fuori dalla data
selezionata rimangono in una sezione separata; il giorno della lista segue la
partenza pianificata. Completate conteggiate separatamente da saltate/annullate.
Ritorno dalla gestione viaggio alla data selezionata. La prima intestazione
compatta limitata alla dashboard è stata sostituita dall'intestazione comune
responsive descritta sotto. Test isolati: 5 passati, 37 asserzioni (dashboard
e comando fixture), build Vite passata con warning dimensione bundle.
GLE-124 in corso: accettazione visuale/interattiva mobile e tablet da svolgere
con il PO. Nessun deployment. Le viste interne del viaggio restano nel backlog.

### Incremento gestione viaggio e layout condiviso — 2026-10-31

Su richiesta del PO, `Driver/Journey/Edit.vue` (Riprendi viaggio) passa a
colonna singola su mobile e pannelli affiancati su desktop: riepilogo mezzo,
tappe con testi non troncati, concluse recuperabili, selezione con accesso al
dettaglio, comandi touch, orari/attrezzature espandibili e materiali in schede.
Il riordino conserva il trascinamento e aggiunge pulsanti su/giù; usa il
contratto API esistente, senza dichiarare risolto il requisito di audit e
conservazione del piano originale di GLE-120/122. Errori e salvataggi espliciti;
il badge Tappa corrente dipende dalla tappa realmente selezionata.

Il PO richiede sviluppo organico e modulare: regola registrata in `AGENTS.md`.
Eliminate le condizioni per singola pagina dalla barra superiore. Ora
`AppHeader.vue` è montata nel layout comune su tutte le route, compresa la lista
`/driver/journey`; accesso a navigazione, tema, notifiche e account condiviso.
`JourneyStopsPanel.vue` separa la presentazione e gli eventi dalle mutazioni
coordinate dalla pagina; `driverStopPresentation.js` centralizza le etichette.

Build Vite passata; 5 test PHP/37 asserzioni passati. Rendering e ispezione
visuale a 390 e 1280 px con dati sintetici, non sessione autenticata del PO;
icone globali sostituite da segnaposto nel campione statico. Da validare le
interazioni touch reali sul viaggio 159. Nessun rilascio, nessuna chiusura di
issue. La lista viaggi e le altre viste restano da adattare nel contenuto:
condividono già l'intestazione responsive.

Consolidare i wireframe della dashboard e del viaggio come primo incremento.
Precisare viaggio attivo oltre mezzanotte, origine/qualità della disponibilità
iniziale di casse e annullamenti successivi. Definire poi le sub-issue tecniche
dell'epica con dipendenze e criteri verificabili. Nessuna di queste questioni
rimette in discussione l'autonomia operativa dell'autista concordata.

## Fondazione esecutiva locale — 2026-10-31

Le indicazioni sopra sul precedente contratto API descrivono gli incrementi UI
antecedenti al ridisegno. Il workspace ora usa i comandi dell'esecuzione con
revisione attesa, baseline, storico, editor e ripristini. Dettaglio ed evidenze:
[Piano operativo](Piano%20operativo%20esecuzione%20viaggi.md). GLE-122/118/120/126
hanno ora implementazione locale da verificare funzionalmente; nessuno stato
YouTrack o rilascio viene dedotto automaticamente da questa nota.

### GLE-122: anteprima e feedback di ripristino

Completato l'incremento locale con valori precedente/originale visibili prima
della scelta, una sola azione a valori uguali, feedback con Annulla sempre
raggiungibile su mobile e conservazione delle bozze non salvate. Server e UI
usano una sola logica di selezione della storia. Evidenze e limiti:
[GLE-122](GLE-122%20Modifiche%20operative%20e%20ripristini.md).

### GLE-118 e vincoli del processo

Riapertura completata e verificata nel workspace condiviso: corrente unica,
fatti conservati, avanzamento ricalcolato e blocchi motivati. Dettagli e reset
locale del 159 in [GLE-118](GLE-118%20Riapertura%20tappa.md).
Il convoglio è fisso dopo la partenza: nessun cambio motrice/rimorchio nell'UI
esecutiva o nelle API. Il riordino da autista prima della partenza è invece
un punto aperto da chiarire in call: [OP-001](open-points.md).

### Accesso diretto a inserimento CER e modifica item

Sostituito «Modifica movimentazioni» con azioni visibili nella scheda ordine:
«Aggiungi materiale / CER» e «Modifica materiale» su ogni item. Form condiviso
per CER, contenitore, quantità, peso e descrizione; originali conservati e
modifiche esecutive tracciate. Il riepilogo non viene duplicato nell'editor.
