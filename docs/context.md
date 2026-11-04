# Contesto corrente — Metallica Marcon

Aggiornato: 2026-11-04. Sintesi di sessione; non sostituisce il tracker,
le decisioni di dominio o le prove di rilascio.

## Preparazione commit/push per QA — 2026-11-04

Richiesta PO: versionare e pubblicare gli sviluppi autista/esecuzione attuali,
continuando sul branch corrente. Il branch verificato è `main`; nessuna
chiusura/merge, deploy QA/PROD o modifica del tracker richiesta. PHP/Docker
sono gestiti separatamente dal PO. Configurazione locale `.codex/config.toml`
e documento sorgente ODT esclusi dal commit applicativo.

Materiale per il collaudo e limiti: [pacchetto QA](qa/2026-11-04-driver-ui.md).
Le vecchie annotazioni «codice nel working tree non committato» e il checkpoint
del 31 ottobre descrivono la fotografia precedente a questo consolidamento.
Non confondere commit pubblicato, deploy e accettazione dei tester.

## Split del materiale e checklist carico — 2026-11-02

Implementata la successiva richiesta del PO: split a due righe **visive**
dello stesso item, con CER, descrizione e peso complessivi non duplicati.
`JourneyItemLoadPlacement` apre un editor locale: totale sul mezzo previsto
e zero sull'altro; per a capienza proposta motrice/totale, rimorchio/zero,
senza salvataggio implicito. Modificando un valore intero, l'altro è il
complemento al totale; Annulla scarta, Salva invia entrambe le quantità in
un unico comando revisionato. Dopo il salvataggio restano due righe visive.
Disponibile assegnazione dell'intero carico a un solo mezzo per uscire dallo
split. Niente split quantitativo per sfuso, singolo contenitore o convoglio
senza rimorchio.

DB: un solo `journey_execution_items` per materiale; le righe figlie
`journey_execution_allocations` contengono zona e conteggio, uniche per
item/zona. Le allocazioni originali del pianificato non vengono modificate;
quelle esecutive hanno FK all'item esecutivo anche per nuovi materiali senza
OrderItem sorgente. Conservate entrambe le allocazioni, compreso zero.
Migrazione `2026_11_02_180000_add_execution_load_allocations` applicata al DB
locale; aggiunge anche `is_loaded` con default false.

Checklist per singolo item, nel punto dove compariva «Da ritirare»: toggle
a sinistra inizialmente; a destra «Caricato» in grassetto e toggle success.
Gruppi CER con più item mantengono una spunta distinta per materiale. La
spunta è persistente, revisionata e tracciata con attore, senza inventare
quantità effettive o completare la tappa. Riapertura conserva spunta e split.
Variazioni di quantità/distribuzione richiedono nuova verifica (spunta false);
la sola descrizione non la invalida. Il salto totale non è consentito quando
un materiale risulta caricato.

Il totale per lo split usa gli effettivi se registrati, altrimenti la quantità
corrente. Cambiare il totale **non** ribilancia le allocazioni: avviso visibile,
nuova conferma richiesta, blocco di Caricato e completamento finché la somma
non torna coerente. Ripristino della zona a mezzo singolo e relativo undo
gestiscono anche le righe di allocazione; dopo modifiche serve riverifica.

Verifica: 78 test backend / 521 asserzioni e 9 file frontend passati; successivo
caso aggiunto undo di ripristino con spunta già attiva verificato nella suite
split/ripristini (14 test / 99 asserzioni). Build Vite riuscita. Nessun viaggio
locale resettato o alterato come fixture.

Successivo miglioramento approvato e implementato: `UI/QuantityCounter`,
contatore riutilizzabile «− numero +» con pulsanti 42 px, input diretto e
tastiera numerica. Usato nello split, nei materiali/ritiri effettivi e nelle
consegne; freccette native nascoste. Limiti zero/totale, null distinto da zero,
campo editabile. Negli split il complemento continua ad aggiornarsi solo
su intervento dell'utente: un vecchio valore oltre il nuovo totale resta
visibile fino alla riconciliazione. Pesi a inserimento diretto. Messaggio
di attivazione checklist aggiornato in «Carico aggiornato.».
Verifica aggiuntiva: 10 file frontend passati e build Vite riuscita.

## Carico per materiale e semplificazione UI operativa — 2026-11-02

Prima modifica richiesta: scelta motrice/rimorchio per ciascun OrderItem,
direttamente accanto alla tessera contenitori; pulsanti icona mutuamente
esclusivi, salvataggio immediato tramite `update_item`, feedback condiviso.
Nessuna variazione implicita di quantità o collocazione degli altri item.
Rimorchio non disponibile disabilitato e rifiutato anche dal servizio/API.
Ripristini originale/precedente coerenti con il default previsto del materiale.

`ItemCargoLocation` deriva il previsto dalle allocazioni per item congelate
alla partenza (fallback collegamenti cargo storici, poi zona ordine, poi
motrice). Ordini `fulfill`/a capienza senza preselezione automatica; due
allocazioni su mezzi diversi non vengono falsamente rappresentate come un
singolo default. La scelta operativa viene conservata sul singolo item di
esecuzione; baseline e ordini sorgente restano intatti. Nessuna migrazione.
Lo split quantitativo motrice/rimorchio è esplicitamente rinviato.

Rimosso dal dettaglio operativo il pannello generazione/aggiornamento
documenti e l'editor «Note dell'ordine e zona di carico». Il PO chiarisce che
i documenti stampati prima del viaggio rappresentano il pianificato. Questa
prima modifica rimuove i comandi dalla UI: servizi/API documentali esecutivi
preesistenti non sono ancora ridisegnati e le precedenti specifiche di
rigenerazione esecutiva vanno riconciliate prima di ulteriori sviluppi su
quel ciclo. Le note ordine rimangono consultabili. Richiesta futura: note
della sede aggiornabili dall'autista (pedane, sbarre, accesso); non ancora
implementata in questo passaggio.

Referenti driver: scelta finale una chiamata diretta per recapito, cellulare
per primo; pulsanti success 42×42 px, icone 24 px (mobile/cornetta), distanza
verticale 4 px. Eliminati selezione del numero, CTA unica e prefissi visibili
«Cell./Tel.»; destinazione esplicita nelle etichette accessibili. In assenza
di numeri validi rimane il pulsante grigio disabilitato.

Verifica: suite backend pertinente 70 test / 463 asserzioni, poi test aggiunto
salvataggio/rilettura API (suite cargo 5 test / 24 asserzioni); 8 file frontend
passati. Build Vite verificata. Nessuna scrittura di fixture nel DB locale.

## Gestione referenti di sede — 2026-11-02

Scheda cliente/sede: `SiteInternalContacts` consente inserimento e modifica
tramite modale, mantenendo sola lettura negli ordini. Capability per sede
derivata da `SitePolicy::update`, verificata anche dalle API annidate nella
sede; un referente di un'altra sede non è modificabile passando un ID diverso.
Nome e categoria obbligatori, cognome/telefono/cellulare/email facoltativi con
validazione server. Salvataggio Eloquent mantiene l'audit di dominio esistente.

Categorie già predefinite da `InternalContactRole`: Responsabile smaltimenti,
Segreteria, Magazzino, Intermediario; opzioni e label del dropdown derivano
dall'enum backend. Dropdown categoria in testa ai campi, senza preselezione;
anche i referenti precedenti senza categoria devono sceglierla al salvataggio.
Conservato il valore storico `inrtermediario` nel DB;
la UI espone la dicitura corretta. Nessuna migrazione richiesta.

Rubrica nella UI operativa: i viaggi con esecuzione mostrano i contatti correnti
della sede, caricati in blocco nel read model. La modifica di un numero non
riscrive baseline, dati di esecuzione congelati o proiezione documentale.
Ricaricare il viaggio per recuperare i nuovi recapiti.

Verifica: 42 test backend / 277 asserzioni, inclusi permessi, validazioni,
vincolo sede e immutabilità baseline/esecuzione; 8 file frontend e build Vite
passati. Nessun contatto di prova inserito automaticamente nel DB locale.

## Contenitori residui e tessere compatte — 2026-11-02

Decisione del PO: la dotazione iniziale coincide con le consegne pianificate
alla partenza, comprese le scorte extra già indicate dalla Logistica negli
ordini. Per ciascun tipo, residuo = dotazione congelata alla partenza meno
consegne effettivamente registrate. Esempio: 6 casse caricate, 4 consegnate,
2 ancora disponibili. I ritiri non alimentano questo saldo di vuoti.

`ContainerBalance` calcola il saldo nel read model condiviso; la testata
mostra «Contenitori residui» usando lo stesso componente di ritiri/consegne.
Correzioni delle consegne aggiornano il saldo; riapertura e ricompletamento
non duplicano le sottrazioni. Tappe saltate e ripianificazione dell'ordine
non cambiano la dotazione congelata. Modificare una consegna prevista non
crea scorte: conta la consegna effettiva. Quantità mancanti restano distinte
da zero; eventuali saldi negativi restano visibili con avviso. Disponibile
per viaggi con baseline di esecuzione, senza nuovi campi o migrazioni.

Tessere leggermente ridotte: spaziatura Tailwind `18` definita a 4.5rem,
`min-w-18 shrink-0`, padding e numero più piccoli; disposizione flex con
ritorno a capo conservata.

## Visibilità del piano e dello storico — 2026-11-02

Il PO riserva «Piano originale e storico modifiche» alla Logistica e ai ruoli
di controllo developer/admin, non all'autista. La capability di dominio
`viewExecutionHistory` guida sia UI condivisa sia API; l'endpoint storico
restituisce 403 al driver. Entrambe le pagine di consultazione/modifica
ricevono il permesso, senza condizioni sul nome della pagina o duplicazioni.

Nella UI driver nascosti anche i confronti generali «Piano alla partenza»
nell'ordine; rimangono le azioni operative e il ripristino puntuale dei valori
necessario a correggere il lavoro. Logistica/controllo conservano i confronti
anche in sola lettura. Nessuna modifica alla conservazione o alla tracciatura
dei dati: si distingue l'interfaccia operativa dalla consultazione analitica.

## Aggiornamento UI — 2026-11-02

Messaggi brevi orientati all'operazione, senza spiegazioni su baseline o
tracciatura: «Materiale CER 020107 aggiunto correttamente all’ordine» oppure
«Materiale CER 020107 modificato correttamente». Il CER viene ricavato dal
materiale salvato nella risposta, mantenendo gli zeri iniziali; fallback senza
CER se il dato non è disponibile. Feedback fixed in alto sotto l'header e X
circolare con bordo più visibile. Le vecchie indicazioni di feedback in basso
si riferiscono alla UI del 31 ottobre e sono superate da questa scelta.

Ritiri e consegne usano `JourneyContainerQuantities`: tessere flex con numero
più grande sopra e tipo sotto, contenuto centrato verticalmente. Componente
comune in testata viaggio, elenco tappe e dettaglio ordine/materiali. Aggregati
per tipo con la stessa logica esistente; zero effettivo distinto da assente,
sfuso senza conteggio inventato. I valori reali non vengono troncati se un
aggregato supera due cifre. Confronti storici restano disponibili.

Verifica: 7 file di test frontend passati, inclusi riepiloghi condivisi,
zero/assenza, feedback CER e posizione/chiusura; build Vite riuscita e diff
senza errori. Nessuna modifica al DB, alle API o alle autorizzazioni.

## Ripresa della prossima sessione — checkpoint 2026-10-31

**Partire da [questo checkpoint](checkpoints/2026-10-31-driver-ux.md).** Contiene
stato applicativo, working tree, prove, decisioni e percorso di ripresa.
Il codice è ancora non committato; il checkpoint ha un commit documentale
separato per la provenienza KB. Nessuna pubblicazione o release implicita.

Ultimo punto concordato: matita sulla riga del materiale; durante l'editing
X per scartare/chiudere e floppy per salvare, attivo solo con modifiche.
Tipo e numero contenitori sono contigui; sfuso li svuota/disabilita. UI compatta
verticalmente, pulsanti circolari riconoscibili senza hover, tema DaisyUI.

Motrice/rimorchio fissi dopo partenza. OP-001 sul riordino pre-partenza rimane
aperto per la call. Il 159 ha superato lo stato legacy dopo reset richiesto e
nuovo avvio: non ripetere automaticamente il reset e rileggere il DB alla ripresa.

Verifiche disponibili: 54 test PHP/346 asserzioni prima degli ultimi ritocchi
solo UI; 6 file frontend e build passati anche dopo quei ritocchi. Il collaudo
successivo con il PO riparte da aggiunta/CER, editing, salva/annulla e riapertura.
Il salvataggio ha creato il solo commit documentale
`9a32fb58c181a0efeedff515a02d9696f5c24ce6`. Sintesi KB proposta salvata in
`~/knowledge/projects/personal/metallica-marcon/driver-execution-session.md`,
con indice/log aggiornati e lint passato. Le modifiche KB restano locali e
non committate; codice applicativo e altri documenti restano nel working tree.
Nessun push, deploy o sincronizzazione JARVIS effettuato.

Per gli sviluppi già concordati il PO ha autorizzato di procedere senza
ripetere conferme operative; le decisioni di dominio aperte, come OP-001,
restano invece da chiarire con il cliente.

Le sezioni sottostanti conservano la cronologia; i vecchi conteggi tracker e lo
stato legacy del 159 non prevalgono su questo checkpoint.

## Convenzioni concordate

- Usare la skill globale `developer-platform` per organizzazione della
  conoscenza, recupero note, secret, configurazione MCP e pubblicazione KB.
- “Salva nel contesto” richiede il salvataggio del contesto utile nel progetto
  e l'aggiornamento della sintesi KB quando aggiunge conoscenza durevole.
- Le richieste “su YouTrack” o relative a YouTrack usano il collegamento MCP;
  nessun fallback REST/browser silenzioso. Il progetto corrente usa la key GLE.

## Risultati verificati

- Il normale avvio di Codex carica YouTrack attraverso `.codex/config.toml` e
  l'helper condiviso in Developer Platform. Verificato su Codex 0.166.0 con
  23 strumenti MCP disponibili. Nessun override di shell o hub Docker.
- Il vault originale completo è preservato privatamente con checksum e
  verifica di rilettura di tutti i 129 file; il vecchio disco è intatto.
- Le 20 note Markdown del progetto sono recuperate come evidenza storica,
  sanificate e indicizzate in [MIGRATION.md](recovery/MIGRATION.md).
- YouTrack letto via MCP: 115 issue, 41 aperte e 74 chiuse. La
  [riconciliazione](development-status.md) distingue stato tracker, codice,
  verifica applicativa e deployment.

## Punti aperti

- Decisione PO 2026-10-31: la prima partenza congela il piano originale.
  Prima solo Logistica aggiorna la pianificazione di ordini/viaggio; dopo
  autista e Logistica modificano l'esecuzione con differenze tracciate.
  La produzione, secondo il PO, usa composizione journey e documenti, senza
  operatività digitale successiva; post-partenza/magazzino ridisegnabili.
  Decisione recepita in analisi, specifica e contesto logistico. Ricognizione
  AS-IS completata; proposta organica in
  `docs/Proposta confine pianificazione-esecuzione.md`, non ancora implementata.
  Documenti: rigenerazione abilitata da modifica dell'ordine collegato
  **oppure del viaggio**; download/ristampa senza effetti business. Il reset
  a creato individuato nell'AS-IS riguarda Order durante generazione, non
  Journey durante ristampa. Review in `docs/Ciclo documenti viaggio.md`.

- UI autisti (2026-10-31): create via MCP GLE-116 (epica responsive) e
  GLE-117–126 (dieci requisiti funzionali), assegnate a `matteo.argenton`.
  [Piano locale](Piano%20UI%20autisti.md): originale conservato, viaggio
  operativo adattabile dall'autista e dati eseguiti distinti; audit e due
  ripristini; casse ritirate/lasciate indipendenti; smartphone portrait e tablet.
  Pianificazione iniziale, nessun codice o rilascio. Sub-issue da definire.
  Tag `ui-autisti` creato dal PO e applicato a tutte le undici issue; rimosso
  il prefisso `Vista autista —` dai titoli. Regola in
  `.claude/rules/youtrack.md`: titoli puliti e raggruppamento tramite tag;
  se manca un tag non creabile via MCP, il PO provvede alla creazione.
  Decisione successiva: unico ingresso in `/dashboard-driver` (“I miei
  viaggi”); lista desktop ritirata e vecchia route rediretta alla dashboard.
  Workspace mobile unico per gestione e storico in sola lettura. Primo
  incremento contenitori GLE-117/125 disponibile, storico originale e
  registrazione effettivo autista ancora da realizzare con GLE-122.

- GLE-53 resta aperta: distinguere dispatch as-is consegnato dai gap
  appoggi/trasbordi GLE-90/92/93.
- GLE-73–82 sono aperte benché esistano endpoint execute, builder e altri
  componenti: verificare criteri, test e rilascio prima di cambiare stati.
- GLE-26 e GLE-95–99 sono aperte; audit infrastrutturale presente nei sorgenti,
  validazione operativa e copertura end-to-end non dimostrate qui.
- Il refactor controller ordini resta sospeso dal cliente secondo le note;
  non riavviarlo automaticamente.
- Le checkbox milestone e alcuni inventari di deploy sono storicamente
  incoerenti: esiste documentazione del deploy PROD del 15 aprile e del fix
  TLS del 15 settembre, ma questa sessione non ha verificato il runtime PROD.
- Il token YouTrack è ancora in deposito transitorio esterno al repository;
  Bitwarden non è stato configurato come fonte per questa connessione.
- Snapshot locale più vecchio disco non completano una strategia cifrata
  offsite 3-2-1. Servono destinazione, automazione e prova di ripristino.

## Prossima ripresa

Pubblicazione del 2026-10-30: evidenze di progetto, skill Developer Platform e
KB sanificata sono su GitHub. Commit KB pubblicato:
`d30e7f32af97c9195770ad7a1016e672038b3f77`.
Il primo tentativo LAN del 2026-10-30 è fallito per timeout SSH. Il 2026-10-31
la sincronizzazione è stata completata fuori casa via `jarvis-remote`:
34 documenti, 144 vettori; fingerprint degli embedding aggiornati e campione
vettori verificato da QMD Doctor. Il comando di ripresa remoto utilizzato è:

```bash
cd /home/matteo/knowledge
KNOWLEDGE_PUBLISH_SSH_TARGET=jarvis-remote ./scripts/publish.sh --sync-only d30e7f32af97c9195770ad7a1016e672038b3f77
```

Il backup cifrato offsite è rimandato esplicitamente dal proprietario:
non avviarlo come parte di questa sincronizzazione.

Partire da questa pagina e da `development-status.md`; le note in
`recovery/legacy-obsidian/` sono storia, non istruzioni correnti.
La KB centrale conserva sintesi `proposed` con fonte e commit del progetto.
Eventuali chiusure ticket, deployment e cancellazioni delle fonti recuperate
richiedono un task specifico; non sono effetti del salvataggio del contesto.

## 2026-10-31 — Piano congelato alla partenza: implementazione locale

Autorizzata dal PO e implementata la soluzione relazionale baseline + esecuzioni
dedicate. Piano ed evidenze in `docs/Piano operativo esecuzione viaggi.md`.
Prima della partenza pianifica Logistica; prima partenza atomica; dopo, comandi
esecutivi tracciati con revisione attesa e diff in journey_events. Nessun clone
di Journey/Order commerciali. Salto totale libera subito l'ordine; una nuova
pianificazione non cambia la vecchia esecuzione. Riapertura protetta da
riassegnazioni e ricezioni a magazzino. Documenti dipendenti da ordine e viaggio,
ristampa senza transizioni business e job obsoleti scartati.

UI condivisa mobile con editor materiali/consegne, gestione tappe, ripristini,
originale/storico e documenti. Ricezione minima dedicata ai ruoli magazzino;
il redesign completo di giacenze/esplosioni/trasbordi resta una fase successiva.
Corretto anche il titolo tappa: sola ragione sociale, senza prefisso Cliente né
ID; caricamento delle relazioni corretto anche per anagrafiche archiviate.

Migrazione nuova applicata esclusivamente al DB locale. 40 ha piano strutturato;
38 richiede completamento tappe; 159 è legacy attivo senza baseline e rimane
in sola lettura. Nessuna partenza automatica e nessun deploy. Stato locale del
codice e stato YouTrack rimangono distinti; nessuna issue chiusa qui.

Verifica finale dell'incremento: 34 test PHP/189 asserzioni passati, 3 file di
test frontend passati, build Vite riuscita e diff senza errori. Eseguito il
segnale di ricaricamento dei worker locali. Restano verifica visuale touch
reale e ampliamento del processo di magazzino; dettaglio nel piano operativo.

## GLE-122 — completamento menu e annullamenti

Verificati i criteri tramite MCP YouTrack: issue ancora Da fare al controllo,
sviluppo locale distinto dallo stato del tracker. Completati anteprima server
originale/precedente, accorpamento di valori uguali, assenza esplicita
dell'originale sugli aggiunti, feedback mobile con Annulla e annullamento
tracciato del ripristino. Le modifiche concorrenti impediscono l'annullamento
obsoleto; le bozze non salvate non vengono scartate da aggiornamenti di altri
materiali. Documento: `docs/GLE-122 Modifiche operative e ripristini.md`.

Chiarito dal PO: l'operativo è l'esecuzione del pianificato, inizialmente uguale
nei contenuti; se non ci sono variazioni, i contenuti finali coincidono. Stati,
orari e fatti di esecuzione si aggiungono; «effettivo» non è una terza copia.

Verifica aggiuntiva DB richiesta dal PO: descrizioni specifiche dei due item
nell'ordine MMM_2506_0004 entrambe NULL; descrizioni anagrafiche CER presenti.
La UI non ha perso descrizioni compilate. Nessuna modifica a quei dati.
Esito: 41 test PHP/232 asserzioni, 5 file di test frontend e build passati.


## Punto aperto OP-001 e vincolo del convoglio

Il PO chiede di chiarire in call con Metallica Marcon il riordino delle tappe
da autista prima della partenza: registrato in `docs/open-points.md`, con
conseguenza sul pianificato e dubbio sull'assegnazione iniziale da conservare.
Per ora solo Logistica modifica prima della partenza; autista modifica
l'esecuzione dopo. Non anticipare l'abilitazione in assenza della decisione.

Vincolo chiarito dal PO: motrice e rimorchio sono fissi dopo la partenza.
Modificabili nella pianificazione prima dell'avvio, non nell'operativo.
Rimosso il cambio convoglio introdotto erroneamente nella UI esecutiva e
bloccato anche nel comando/API, compreso rimorchio nullo. Il controllo locale
sui viaggi gestiti non ha rilevato sostituzioni già registrate.

## GLE-118 e reset locale del 159 — esito

Riapertura completata: la tappa ritorna corrente, l'eventuale corrente precedente
torna pianificata; quantità/pesi/consegne e primo inizio restano conservati.
Ricompletamento sugli stessi record senza duplicazioni. Guard e motivi visibili
per ricezione a magazzino, ripianificazione, ordine archiviato, viaggio chiuso
e altro viaggio attivo. Audit con transizioni e tappe rinviate; baseline intatta.
Riferimento `docs/GLE-118 Riapertura tappa.md`.

Su richiesta esplicita del PO, il 159 è stato riportato a creato con partenza
prevista 31/10/2026 22:20 Europe/Rome e documenti dei tre ordini aggiornati.
Tre tappe pianificate; cinque eventi vecchi conservati più evento development_reset;
backup sul disco local, escluso da Git. Un controllo successivo ha rilevato
il 159 di nuovo attivo con revisione esecutiva: le note precedenti di viaggio
legacy in sola lettura sono superate per questo record locale.

Verifica complessiva: 52 test PHP/315 asserzioni, 5 file frontend e build
passati. Regola convoglio ora protetta anche contro richieste dirette e
rimorchio nullo; nessuna sostituzione operativa trovata nei dati locali.
OP-001 rimane aperto per la call, senza cambi di autorizzazione pre-partenza.

## Materiali/CER operativi — azioni rese esplicite

Su segnalazione del PO, rimosso il pannello generico «Modifica movimentazioni».
Nella scheda ordine sono subito visibili «Aggiungi materiale / CER» e
«Modifica materiale» per ciascun item. Stessa rappresentazione CER/item per
lettura ed editing, senza liste duplicate; campi comuni riutilizzati tra
aggiunta e modifica. I salvataggi modificano l'esecuzione, con originale e
storico conservati; per una tappa conclusa serve prima riaprirla.
Salvataggio parziale dei soli campi cambiati, bozze conservate e feedback
errori visibile su mobile. Il comando add_item ora conserva anche gli actuals
esplicitamente forniti, incluso zero. Dettaglio in
`docs/GLE-122 Modifiche operative e ripristini.md`.

Verifica dell'aggiornamento materiali: 54 test PHP/346 asserzioni, 6 file
frontend e build passati. Nessuna modifica diretta ai dati locali.

Verifica visibilità per ruolo: 55 test PHP/369 asserzioni passati, 8 file
frontend passati, build Vite e controllo diff riusciti. La matrice verifica
il permesso di storico e la capability UI per driver, magazzino, Logistica,
manager, developer e admin. I flussi operativi del driver continuano a passare.
