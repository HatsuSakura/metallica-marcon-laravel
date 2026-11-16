# Convenzione UX/UI della web-app — proposta 0.1

Data: 2026-11-16. **Proposta iniziale approvata con successive eccezioni: la convenzione operativa è ora in [DESIGN.md](../../DESIGN.md).**

Le osservazioni e i rendering qui sotto descrivono lo stato precedente all’applicazione. Le raccomandazioni iniziali su header e chiamate sono superate dalle eccezioni del PO: header con icone pulite, chiamate sempre success.
Superficie pilota: dashboard autisti, con verifica dei componenti condivisi del
workspace viaggio. Modalità operativa: completare attività e leggere dati con
pochi passaggi, su telefono, tablet e PC. Non è un progetto di rebranding.

La proposta preserva le decisioni del PO e le regole già presenti in AGENTS.md;
le nuove scelte qui indicate non modificano automaticamente tali istruzioni.
Nessuna modifica applicativa, dipendenza o migrazione eseguita per questa analisi.

## Vincoli verificati

Stack effettivamente installato: DaisyUI **4.12.24**, Tailwind **3.4.18**,
Vue **3.4.38**, Laravel **11.57.1**. Restano Font Awesome, Inertia, i temi
`lighttheme` e `darktheme` e la famiglia tipografica corrente.

Usare componenti DaisyUI 4 e utility Tailwind 3 già disponibili. Nessun
`btn-soft`, `fieldset` della v5, `@theme`, `@plugin` o configurazione Tailwind 4.
Non introdurre librerie di icone, validazione o animazione come conseguenza
delle raccomandazioni generiche delle skill.

## Esito dell’analisi

Metodo: due valutazioni indipendenti con Impeccable (A: ux_review, B: ui_evidence),
ricerche locali UI UX Pro Max, lettura dei componenti e rendering aggiornati.
A ha completato la valutazione del sorgente e poi verificato le schermate;
non ha ricevuto i risultati del detector B.

Punti da conservare:

- Dashboard leggibile: titolo e conteggio, selezione del giorno, viaggi con
  orari e avanzamento; azione Riprendi riconoscibile per il viaggio attivo.
- Badge di stato centralizzati, quantità reali e distinzione zero/assente/sfuso.
- Note sede prima dei referenti, operazioni confermate con controlli di validità,
  protezione da invii ripetuti, ripristino e ritorno alle informazioni.
- La selezione di una tappa su mobile già porta il focus al dettaglio; il
  riordino offre frecce oltre al trascinamento.

| Priorità | Evidenza attuale | Intervento proposto |
| --- | --- | --- |
| P2 | Matita neutra outline; apertura Aggiungi materiale primary piena, anche quando diventa Chiudi aggiunta | Uniformare le aperture di funzioni; riservare il pieno alle conferme |
| P2 | Header con icone ghost, comandi locali con bordo | Bordo visibile anche per le icone globali; modifica nel componente condiviso |
| P2 | Comandi a 42 e 48 px | Un riferimento di 48 px per i comandi operativi, conservando compattezza tramite disposizione e spaziatura |
| P2 | Card e selettore data scuri poco separati; icona calendario poco evidente nella fixture | Verificare contrasto e color-scheme dei controlli nativi nei temi esistenti, prima di intervenire sui token condivisi |
| P2 | Nel workspace tablet, note/referenti/esiti compaiono sia nella card sia nel dettaglio | Conservare accesso nei due punti richiesti dal PO, riducendo cornici, istruzioni e ripetizioni decorative; non eliminare comandi o contenuti concordati |

Non sono stati dimostrati difetti P0/P1. Il detector, eseguito su sei sorgenti
Vue, restituisce zero segnalazioni; su file non HTML usa corrispondenze testuali,
quindi non certifica DOM, contrasto o qualità UX.

Valutazione euristica orientativa: visibilità stato 3/4; linguaggio 3/4;
controllo 3/4; coerenza 2/4; prevenzione errori 3/4; riconoscibilità 3/4;
efficienza 3/4; essenzialità 2/4; recupero errori 3/4. Totale **25/36**.
Sistema di aiuto non valutato. È un giudizio del progetto, non un test con utenti.

## 1. Regola fondamentale: stessa funzione, stesso trattamento

Il trattamento dipende dall’effetto immediato del comando. Aprire un editor è
un’azione diversa dal confermare una scrittura. Colore, forma, testo e stato
restano gli stessi a ogni dimensione dello schermo.

| Ruolo | Componente/classi DaisyUI 4 | Esempi |
| --- | --- | --- |
| Aprire una funzione di modifica o aggiunta | `btn btn-primary btn-outline` | Modifica, Aggiungi materiale / CER, Modifica contenitori, Aggiungi tappa |
| Confermare una modifica locale | `btn btn-primary` | Salva, Aggiungi all’ordine, conferma nuova consegna |
| Navigare, espandere/chiudere contenuti o annullare una bozza | `btn btn-outline` | Indietro, Oggi, Mostra tappe concluse, Chiudi, Annulla |
| Ripristino reversibile di un dato | `btn btn-primary btn-outline` | Ripristina materiale |
| Avanzare nel processo operativo | `btn btn-success` | Inizia viaggio, Completa tappa, Termina viaggio |
| Confermare una deviazione dal processo | `btn btn-warning` dopo selezione del motivo | Conferma salto; il comando che apre il motivo resta outline |
| Rimozione reversibile | `btn btn-warning btn-outline` | Rimuovi materiale recuperabile |
| Cancellazione tecnica irreversibile | Apertura `btn btn-error btn-outline`; conferma `btn btn-error` | Solo quando il dato viene davvero eliminato in modo irreversibile |

Decisione concreta sui due pulsanti discussi: **matita primary outline e
Aggiungi materiale / CER primary outline**. Matita circolare; aggiunta con
icona più e testo. Aggiungi all’ordine, dentro il modulo, resta primary pieno.
La forma differente serve a distinguere comando puntuale e azione di sezione,
non assegna significati diversi al colore.

Il nome «Elimina definitivamente» oggi nasconde un record dalla UI e non
cancella lo storico: mantenerlo nella prima applicazione, come concordato,
senza attribuirgli automaticamente il rosso della cancellazione tecnica.
Un’eventuale rinomina richiede una decisione del PO.

Le chiamate ai referenti sono azioni funzionali, non stati di successo:
proposta primary outline mantenendo icone mobile/fisso e area di tocco.

Il pieno evidenzia la conferma disponibile in quel contesto, non tutti i
controlli della pagina. Una conferma principale per ciascun piccolo modulo;
mai nascondere Salva solo perché esiste Completa nella stessa schermata.

## 2. Anatomia dei comandi

- Base comune: 48 px di altezza minima, anche su tablet touch e desktop.
  Icone: 48×48 px, `btn-circle` per modifica/salva/annulla puntuali,
  `btn-square` per frecce di navigazione e strumenti strutturali.
- Il riferimento di 48 px è una convenzione proposta, non l’affermazione che
  i 42 px attuali siano automaticamente una violazione di accessibilità.
  Gli eventuali controlli compatti da 44 px vanno previsti come variante
  condivisa e verificati; non cambiare dimensioni per singola pagina.
- Nome visibile per aggiunte, avanzamenti e comandi poco ovvi. Icona sola per
  azioni familiari e vicine al dato (matita, chiudi, floppy), con nome accessibile.
- Nessuna icona interattiva ghost come unico segnale di cliccabilità.
- Stato focus visibile; stato disabled reale; durante richiesta stato loading
  e protezione dai doppi invii senza cambiare larghezza del pulsante.
- Salva si abilita se il valore normalizzato differisce dal riferimento
  effettivamente mostrato/salvato e se è valido; ripristinare il valore iniziale
  disabilita Salva. È lo stesso contratto in tutta la web-app.

## 3. Colore e stati

Solo token DaisyUI: primary, secondary, success, warning, info, error, neutral,
base-100/200/300 e relativi content. Nessuna nuova palette.

- `primary`: funzione disponibile, conferma generica e selezione corrente.
- `success`: esito positivo o avanzamento operativo. Preservare la mappatura
  JourneyStatusBadge: attivo/completato success, saltato warning,
  pianificato/da iniziare neutral pieno, annullato/sconosciuto neutral outline.
- `warning`: attenzione o deviazione; `error`: errore/pericolosità/cancellazione
  irreversibile. L’articolo pericoloso conserva il CER rosso senza testo visivo
  aggiuntivo, con indicazione accessibile; ADR resta un’informazione distinta.
- `neutral` e `base-*`: organizzazione, navigazione e separazione, non dati
  inventati o equiparazione di zero e assenza.
- Conteggio viaggi positivo success; zero neutral, secondo la regola esistente.
- La selezione di una riga usa ring/indicatore primary, separato dallo stato
  business; non cambiare il colore dello stato per indicare il focus.

Verificare contrasto nei due temi sul risultato renderizzato, in particolare
primary outline nel tema scuro, bordi dei pannelli, disabled e input nativi.
Obiettivi di progetto: testo ordinario 4.5:1, testo grande 3:1, controlli e
indicatori significativi 3:1; colore mai unico mezzo di comunicazione.
Le soglie sono criteri da verificare, non risultati di questa analisi.

## 4. Tipografia, densità e superfici

Mantenere la famiglia sans attuale: nessun font nuovo da scaricare.

| Ruolo | Riferimento Tailwind 3 |
| --- | --- |
| Titolo pagina | `text-2xl font-bold` |
| Titolo sezione | `text-lg font-semibold` |
| Nome cliente, contenitore o elemento principale | `text-base font-semibold` |
| Testo operativo, label e pulsanti | `text-sm` o base coerente con il componente |
| Valori dei campi di inserimento | Almeno `text-base`; numeri quantitativi più evidenti |
| Metadati secondari | `text-xs`, mai unico posto per istruzioni o dati critici |

Dati numerici allineati e tabulari. Etichette non tutte maiuscole. Usare titoli
HTML secondo la gerarchia, non scegliere h4/h5 per la sola dimensione grafica.

Scala di spaziatura 4/8/12/16/24 px. Tra dato e comando: 8 px; tra gruppi
correlati: 12–16 px; tra sezioni: 16–24 px. Compattare mettendo azioni accanto
ai dati, non riducendo la leggibilità o l’area di tocco.

`card` per una vera unità di lavoro (viaggio, ordine), non ogni riga. All’interno
usare divider/bordi leggeri invece di una sequenza di card dentro altre card.
Usare `base-100` per il contenuto e `base-200` per strumenti/secondo livello;
ombre solo per elementi sovrapposti che ne hanno bisogno.

## 5. Una convenzione per tutti gli schermi

Stessi componenti, termini, significati e ordine delle azioni; cambiano soltanto
colonne, disposizione e larghezza. Non esistono una grafica mobile e una grafica PC.

| Spazio disponibile | Composizione |
| --- | --- |
| Telefono / base | Una colonna, controlli accessibili al pollice, azioni di sezione con testo; nessun affidamento sull’hover |
| Tablet / md 768 px | Due colonne solo per gruppi realmente leggibili; tablet verticale può conservare una colonna; nessuna riduzione automatica dei target touch |
| PC / lg 1024 px e oltre | Lista e dettaglio affiancati quando utili; filtri/toolbar in riga; stessi comandi e stessi stati |

Conservare i breakpoint Tailwind correnti (sm 640, md 768, lg 1024, xl 1280).
Non rilevare un “tipo di dispositivo”: usare lo spazio disponibile.

Gutter: 12–16 px su telefono, 24 su tablet, 24–32 su PC. Contenuto operativo
centrato e limitato in larghezza; tabelle dense possono usare più spazio.
Le tabelle di backoffice possono avere colonne prioritarie e scorrimento locale
con indicazione chiara; evitare tabelle illeggibili o overflow dell’intera pagina.

Header, menu e navigazione restano nel layout comune. Menu con voce attiva,
voci coerenti con le capability autorizzate e nomi italiani uniformi. Focus
mai coperto da header, notifiche o overlay. Non aggiungere nuove barre fisse
per aumentare la visibilità dei comandi.

## 6. Moduli, feedback e sicurezza delle operazioni

- DaisyUI 4: `form-control`, `input input-bordered`, `select select-bordered`,
  `textarea textarea-bordered`, `toggle`, `checkbox`, `radio` secondo il tipo
  di informazione. Non presentare una preferenza come un pulsante di processo.
- Label associata e leggibile; per controlli compatti equivalenti usare un nome
  accessibile (es. data). Placeholder non sostituisce la label.
- Espansioni tramite collapse/details; modifiche locali inline; dialog nativo
  con focus gestito solo per decisioni che richiedono conferma o form complessi.
- Annulla ripristina la bozza, Ripristina riguarda dati già salvati. Non confondere
  i due significati. Conservare le bozze quando il salvataggio fallisce.
- Stessa coda globale di feedback, stesso host sotto header e chiusura evidente.
  Rispettare la durata già concordata (successi brevi, errori e azioni persistenti).
  I problemi di campo vanno segnalati anche vicino al campo; nessun dettaglio
  tecnico, credenziale o messaggio SQL nel testo per l’utente.
- Quantità: previsto conservato, effettivo modificabile, zero distinto da assenza,
  limiti di disponibilità applicati dal backend; residui e consegne coerenti.
- Loading con `skeleton` o `loading` DaisyUI nel contesto; layout stabile,
  errore con Riprova, vuoto con indicazione del prossimo passo.

## 7. Prima applicazione: dashboard e workspace autisti

Dashboard iniziale:

1. Conservare titolo con conteggio accanto e layout delle card viaggio.
2. Compattare il selettore data: giorno della settimana e Oggi su una riga;
   sotto frecce e input data. Evitare una seconda data estesa grande che
   ripete la data già presente nel controllo. Su PC la toolbar può andare
   in una riga; giorno selezionato sempre esplicito e accessibile.
3. Riprendi viaggio resta primary pieno come azione dominante del viaggio attivo.
   Apri viaggio/Vedi riepilogo restano consultazione outline; non avviano il viaggio.
4. Viaggi in corso di altra data restano visibili, separati dal conteggio del
   giorno; niente metriche decorative o cruscotto aggiuntivo.
5. Conservare orario previsto/effettivo e avanzamento, con bordi leggibili anche
   in darktheme. Il selettore nativo segue la localizzazione del browser.

Workspace, nello step successivo:

1. Applicare la matrice dei pulsanti a matite, aggiunte, salvataggi e chiamate.
2. Preservare note prima dei referenti in lista e dettaglio e le due posizioni
   dei comandi Salta/Completa richieste dal PO. Compattare la presentazione
   ripetuta del blocco esito, non rimuovere le azioni concordate.
3. Conservare il flag della testata per singolo viaggio e il testo concordato.
4. Mantenere quantità e Salva sulla stessa area operativa; niente ritorno di
   campi “da consegnare” modificabili dal driver.

## 8. Adozione incrementale

1. **Confermare questa proposta.** Solo dopo promuoverla a convenzione operativa.
2. **Componenti comuni:** piccola API di presentazione (azione funzionale,
   conferma, navigazione, annulla, warning, danger), taglie e stato loading.
   Preferire un wrapper AppAction a ripetere combinazioni di classi per pagina.
   Mutazioni e autorizzazioni restano nei servizi/controller attuali.
3. **Dashboard autisti:** primo pilota. Le modifiche all’header devono avvenire
   nel componente comune ed essere controllate anche nelle altre route.
4. **Workspace autisti:** componenti condivisi ordine/tappa, inclusi i consumer
   Logistica e magazzino; preservare contratti e feedback esistenti.
5. **Interfacce d’ufficio:** ordini, viaggi, anagrafiche, magazzino, una alla volta.
   Il lavoro specifico di ridisegno dell’anagrafica sede resta in pausa.
6. **Refactoring futuro:** aggiornare la mappatura tecnica dei componenti quando
   verranno aggiornati Tailwind/Laravel/DaisyUI; il significato delle azioni non cambia.

Per ogni passaggio: controlli mirati, viewport 320/390/768/1024/1440, due temi,
zoom 200%, tastiera, dati lunghi, stati vuoto/loading/errore, dirty/revert,
nessuna mutazione duplicata e verifica degli altri consumer dei componenti comuni.

## Evidenze e limiti

Sorgenti principali: Dashboard/Driver.vue, DriverJourneyCard.vue, AppHeader.vue,
MainLayout.vue, Driver/Journey/Workspace.vue, JourneyExecutionOrderEditor.vue,
JourneySiteNotesEditor.vue e tailwind.config.cjs.

Rendering dei componenti Vue aggiornati con dati sintetici, icone Font Awesome
reali e shell che riproduce header e margini comuni. API, router e sessione
simulati: nessun accesso a dati personali, QA o produzione. Non è un test E2E
né una prova sul telefono fisico. Campioni: 390×844, 768×1024, 1440×1000,
lighttheme/darktheme, nessun overflow orizzontale o errore JavaScript osservato.

- [Dashboard telefono attuale](evidence/2026-11-16/dashboard-phone.png)
- [Dashboard PC, tema scuro attuale](evidence/2026-11-16/dashboard-desktop-dark.png)
- [Workspace tablet attuale](evidence/2026-11-16/workspace-tablet.png)
- [Misure di rendering](evidence/2026-11-16/render-observations.json)

UI UX Pro Max ha restituito anche suggerimenti da landing page, nuove palette,
font e regole per stack più recenti. Sono stati scartati perché non pertinenti
al gestionale esistente o incompatibili col vincolo di versione. Applicati
soltanto i principi verificati di gerarchia, accessibilità, feedback e responsive.
Il giudizio non deriva da una palette generata automaticamente.

Skill consultate: `~/.agents/skills/impeccable/SKILL.md` (Operate e valutazione
indipendente) e `~/.agents/skills/ui-ux-pro-max/SKILL.md` (ricerche locali UX e
stack, con filtro rispetto alle versioni installate).
