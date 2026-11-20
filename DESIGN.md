# Convenzione UX/UI — Metallica Marcon v1

**Approvata dal PO il 16 novembre 2026.** Riferimento comune per la web-app,
mobile first e coerente su tablet e PC. Prima applicazione locale: dashboard e
workspace autisti, componenti comuni di navigazione, azioni, contatti e quantità.
L’adozione sulle altre schermate è incrementale, non già completata.

Implementazione ed evidenze del pilota: [nota QA locale](docs/qa/2026-11-16-driver-convention.md).

Eccezioni esplicite del PO: **barra menu con icone pulite senza cerchi/bordi**;
**pulsanti di chiamata sempre success verdi**, richiamando il telefono.

## Vincoli verificati

Stack effettivamente installato: DaisyUI **4.12.24**, Tailwind **3.4.18**,
Vue **3.4.38**, Laravel **11.57.1**. Restano Font Awesome, Inertia, i temi
`lighttheme` e `darktheme` e la famiglia tipografica corrente.

Usare componenti DaisyUI 4 e utility Tailwind 3 già disponibili. Nessun
`btn-soft`, `fieldset` della v5, `@theme`, `@plugin` o configurazione Tailwind 4.
Non introdurre librerie di icone, validazione o animazione come conseguenza
delle raccomandazioni generiche delle skill.

La [proposta iniziale e le evidenze di analisi](docs/design/ux-ui-convention-proposal.md)
restano documentazione storica; il presente file è l’autorità operativa.

## 1. Regola fondamentale: stessa funzione, stesso trattamento

Il trattamento dipende dall’effetto immediato del comando. Aprire un editor è
un’azione diversa dal confermare una scrittura. Colore, forma, testo e stato
restano gli stessi a ogni dimensione dello schermo.

| Ruolo | Componente/classi DaisyUI 4 | Esempi |
| --- | --- | --- |
| Aprire una funzione di modifica o aggiunta | `btn btn-primary btn-outline` | Modifica, Aggiungi materiale / CER, Modifica contenitori, Aggiungi tappa |
| Confermare una modifica locale | `btn btn-success` | Salva, Aggiungi all’ordine, conferma nuova consegna |
| Navigare o espandere/chiudere contenuti | `btn btn-outline` | Indietro, Oggi, Mostra tappe concluse, Chiudi |
| Annullare/scartare modifiche | `btn btn-error btn-outline` | Annulla modifica, Annulla riordino |
| Ripristino reversibile di un dato | `btn btn-primary btn-outline` | Ripristina materiale |
| Avanzare nel processo operativo | `btn btn-success` | Inizia viaggio, Completa tappa, Termina viaggio |
| Confermare una deviazione dal processo | `btn btn-warning` dopo selezione del motivo | Conferma salto; il comando che apre il motivo resta outline |
| Rimozione reversibile | `btn btn-error btn-outline` | Rimuovi materiale recuperabile |
| Cancellazione tecnica irreversibile | Apertura `btn btn-error btn-outline`; conferma `btn btn-error` | Solo quando il dato viene davvero eliminato in modo irreversibile |

Decisione concreta sui due pulsanti discussi: **matita primary outline e
Aggiungi materiale / CER primary outline**. Matita circolare; aggiunta con
icona più e testo. Aggiungi all’ordine, dentro il modulo, è success pieno come ogni conferma di salvataggio.
La forma differente serve a distinguere comando puntuale e azione di sezione,
non assegna significati diversi al colore.

Il nome «Elimina definitivamente» oggi nasconde un record dalla UI e non
cancella lo storico: mantenerlo nella prima applicazione, come concordato,
con apertura della conferma in error outline, distinguendo l’occultamento UI dalla cancellazione tecnica nello storico.
Un’eventuale rinomina richiede una decisione del PO.

Le chiamate ai referenti fanno eccezione alla matrice generale: sempre `btn-success`, con icone mobile/fisso e target di 48 px. Il verde richiama il comando di chiamata del telefono e non rappresenta lo stato del viaggio.

Il pieno evidenzia la conferma disponibile in quel contesto, non tutti i
controlli della pagina. Una conferma principale per ciascun piccolo modulo;
mai nascondere Salva solo perché esiste Completa nella stessa schermata.

## 2. Anatomia dei comandi

- Base comune: 48 px di altezza minima, anche su tablet touch e desktop.
  Icone: 48×48 px, `btn-circle` per modifica/salva/annulla puntuali,
  `btn-square` per frecce di navigazione e strumenti strutturali.
- Il riferimento di 48 px è la convenzione approvata, non l’affermazione che
  i 42 px attuali siano automaticamente una violazione di accessibilità.
  Gli eventuali controlli compatti da 44 px vanno previsti come variante
  condivisa e verificati; non cambiare dimensioni per singola pagina.
- Nome visibile per aggiunte, avanzamenti e comandi poco ovvi. Icona sola per
  azioni familiari e vicine al dato (matita, chiudi, floppy), con nome accessibile.
- Nessuna icona interattiva ghost come unico segnale di cliccabilità, eccetto la barra menu comune esplicitamente richiesta dal PO. Nella barra: icone pulite con target 48 px e focus visibile, senza cerchi o bordi permanenti.
- Stato focus visibile; stato disabled reale; durante richiesta stato loading
  e protezione dai doppi invii senza cambiare larghezza del pulsante.
- Salva si abilita se il valore normalizzato differisce dal riferimento
  effettivamente mostrato/salvato e se è valido; ripristinare il valore iniziale
  disabilita Salva. È lo stesso contratto in tutta la web-app.

## 3. Colore e stati

Solo token DaisyUI: primary, secondary, success, warning, info, error, neutral,
base-100/200/300 e relativi content. Nessuna nuova palette per pagina. Le tinte e i content dei token sono definiti centralmente; il primary scuro può essere schiarito per rendere leggibili anche le azioni outline.

- `primary`: funzione disponibile, selezione corrente e navigazione principale del viaggio attivo.
- `success`: salvataggio/conferma, esito positivo o avanzamento operativo. Preservare la mappatura
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
| Dati anagrafici e indirizzi nei riepiloghi | `text-sm leading-5` (14/20 px), valori `font-medium` |
| Annotazioni accessorie | `text-xs`, mai unico posto per istruzioni o dati critici |

Nelle liste anagrafiche la gerarchia è costante: nome/ragione sociale in
`text-base font-semibold leading-6` (16/24 px), seguito da una riga logica di
dati anagrafici e da una riga logica di indirizzo, entrambe a 14/20 px.
Le righe sono gruppi semantici distinti, non interruzioni HTML forzate:
i metadati si dispongono con flex-wrap, l’indirizzo va a capo naturalmente.
Non comprimere il carattere al diminuire dello schermo e non troncare il nome.
Le etichette restano regolari e i valori medium, senza rendere tutta la riga bold.

I gruppi dati e indirizzo hanno un’icona informativa da 16 px (lista
`list-ul` e segnaposto), allineata alla prima riga, con 8 px di distanza
dal testo e `aria-hidden`: il contenuto testuale resta sufficiente.
Nessun cerchio/bordo: queste icone non sono comandi. Il contenuto ha
`min-w-0 flex-1`, le azioni `shrink-0`: su PC è il testo ad andare a capo,
senza spingere un singolo pulsante sulla riga successiva; sotto il breakpoint
lg le azioni passano come gruppo sotto il contenuto e possono disporsi su più
righe solo quando richiesto dalla larghezza disponibile.

Le card di riepilogo anagrafico usano 8 px di padding verticale e 12 px
orizzontale (16 px da sm); tra contenuto e azioni 8 px. Il contenitore
si compatta senza ridurre i target dei pulsanti da 48 px. Questo formato
vale per le liste, mentre i moduli mantengono lo spazio necessario ai campi.
Tra card consecutive della stessa lista: 8 px (`gap-2`).

Nei moduli con indirizzo e coordinate mostrare una mappa di riscontro.
L’indirizzo è il dato anagrafico; il marker può rappresentare l’ingresso o
il punto operativo utile. La geocodifica aggiorna il marker, mentre il drag
aggiorna solo le coordinate, senza riscrivere l’indirizzo. Un aiuto testuale
esplicita questa distinzione. Usare il componente condiviso `LocationPicker`.

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

## 7. Applicazione pilota: dashboard e workspace autisti

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

## 8. Adozione incrementale e priorità concordate

1. **Fondazione condivisa e autisti:** prima applicazione locale completata.
   `AppAction` gestisce variant, forma, disabled e busy; i componenti restano
   di presentazione, senza mutazioni o autorizzazioni al loro interno.
2. **Gestione anagrafiche:** prossima area da analizzare e uniformare, prima
   dei flussi Logistica. Una schermata alla volta, riusando la convenzione.
   La fondazione tecnica locale è descritta nella [nota sui form anagrafici](docs/qa/2026-11-16-registry-forms.md):
   campi cliente condivisi, un solo editor sede dalla scheda cliente, editor
   distinti per referenti e orari. Su indicazione del PO la prossima fase
   grafica, in particolare gli orari settimanali, richiede confronto prima
   di ulteriori modifiche alla presentazione.
   Il PO ha poi approvato la vista di lettura degli orari: tabella DaisyUI
   compatta con gruppi Mattina/Pomeriggio e Apertura/Chiusura solo in testata;
   `00:00` viene mostrato come «Chiuso». L’editor rimane uno stato separato.
   In modifica usa la stessa tabella, con quattro input time e un comando
   «Copia al successivo» sulla riga del giorno. Sui telefoni scorre la sola
   tabella di modifica; Salva/Annulla restano esterni allo scorrimento.
   Le sedi si selezionano con tabs sempre presenti, senza accordion, e la
   sede principale è indicata da una casa con alternativa testuale accessibile.
   Variante DaisyUI 4 `tabs-lifted`: la linguetta attiva si raccorda al bordo
   del pannello senza spazio verticale né una seconda card interna. Scorre
   orizzontalmente la sola barra delle linguette, non il contenuto della sede.
3. **Logistica:** successivamente creazione ordini/viaggi e gestione ordini/viaggi.
4. **Altre interfacce:** estensione progressiva e controllata dei componenti.
5. **Refactoring futuro:** aggiornare la mappatura tecnica quando verranno
   aggiornati Tailwind/Laravel/DaisyUI; il significato delle azioni non cambia.

Per ogni passaggio: controlli mirati, viewport 320/390/768/1024/1440, due temi,
zoom 200%, tastiera, dati lunghi, stati vuoto/loading/errore, dirty/revert,
nessuna mutazione duplicata e verifica degli altri consumer dei componenti comuni.
Nessuna modifica di processo o permesso è implicita nella revisione grafica.

### Contratto AppAction

`variant`: `function` (primary outline), `confirm` (success pieno), `neutral`
(navigazione/annulla), `success`, `warning`, `cancel` e `remove` (error outline), `danger` (error pieno).
`shape`: `default`, `circle`, `square`. `type` è button di default: nei moduli
indicare sempre submit per i comandi di conferma. `as` mantiene il tag o il
componente di navigazione del chiamante. Attributi ARIA, form e listener sono
conservati; disabled/busy bloccano anche il click sulle varianti non native.
Le eccezioni header/chiamata restano nei rispettivi componenti condivisi.

## Ordini e pianificazione viaggi

### Selezione data e ora

Usare `AppDateTimePicker` per tutti i campi data+ora attivi. Mantiene Vue
Datepicker 10 e un flusso calendario → orario, con i soli comandi nativi a icona
per passare tra le due viste. Ore/minuti devono essere evidenti. I minuti
proposti sono arrotondati per difetto al multiplo di 5 (17:24 → 17:20), con
secondi azzerati; frecce e griglia avanzano di 5 minuti. Le date già salvate
non vengono modificate aprendo o annullando il selettore: l'arrotondamento
rimane nella bozza fino alla conferma. I default creati dall'app devono usare
la stessa funzione `floorToFiveMinutes`.

Clic o Enter/Spazio su un valore già presente riaprono direttamente il picker.
Annulla a sinistra (X, error outline), Conferma a destra (check, success outline),
entrambi size sm/32px: è una conferma del campo, distinta dal salvataggio del
form. Non aggiungere pulsanti testuali “1 Data / 2 Orario”. Mantenere nomi,
formati del modello e semantica temporale dei consumatori. Stili del menu
isolati tramite `app-datetime-menu`, funzionanti anche con teleport.

I campi di sola data, sola ora (orari sede) e i filtri per giorno conservano
il loro formato specifico: non introdurre una scelta dell'orario dove assente.

La prima riga segue la regola `[back] titolo — [funzioni]`: collocare i comandi
della pagina nello slot `actions`, evitando barre separate sotto il titolo.
Il comando di ritorno occupa lo slot `back` di AppPage/RegistryPage, prima del
titolo a sinistra; le azioni della pagina rimangono a destra. Da mobile il
ritorno precede il titolo su una riga dedicata; da sm sono affiancati. Usare
neutral outline e una destinazione esplicita. La pagina conserva la logica
di destinazione, inclusi ritorni parametrici e `return_to`: il layout non
deduce né modifica la navigazione.

`AppPage` contiene la struttura e gli stili comuni dei form; `RegistryPage`
ne rimane un alias compatibile. Non duplicare gli override dei controlli nei
singoli form. Le dimensioni correnti DaisyUI 4 restano invariate.

`SiteEquipmentSummary` e `SiteOperationalParameter` presentano le dotazioni
con icona, nome e valore testuale: disponibile in success, non disponibile
in neutral. L'assenza di una dotazione non è un errore. Riutilizzarli nella
scheda sede, negli ordini e nelle informazioni ordine dei viaggi.

`SectionVisibilityActions` usa comandi neutral outline: aprire o chiudere
sezioni non conferma né annulla dati. `useDisclosureSections` coordina solo
la visibilità; i pannelli chiusi conservano i campi montati e le bozze.
`PlanningColumns` passa a due colonne in base allo spazio del contenitore,
così la mappa laterale non comprime la pianificazione.

Le note della sede sono informazioni di contesto in lettura, separate dalle
note modificabili dell'ordine. Il colore non sostituisce il valore testuale.

## Evidenze e limiti

Evidenze precedenti all’applicazione (non screenshot dello stato finale).
Sorgenti principali: Dashboard/Driver.vue, DriverJourneyCard.vue, AppHeader.vue,
MainLayout.vue, Driver/Journey/Workspace.vue, JourneyExecutionOrderEditor.vue,
JourneySiteNotesEditor.vue e tailwind.config.cjs.

Rendering dei componenti Vue aggiornati con dati sintetici, icone Font Awesome
reali e shell che riproduce header e margini comuni. API, router e sessione
simulati: nessun accesso a dati personali, QA o produzione. Non è un test E2E
né una prova sul telefono fisico. Campioni: 390×844, 768×1024, 1440×1000,
lighttheme/darktheme, nessun overflow orizzontale o errore JavaScript osservato.

- [Dashboard telefono attuale](docs/design/evidence/2026-11-16/dashboard-phone.png)
- [Dashboard PC, tema scuro attuale](docs/design/evidence/2026-11-16/dashboard-desktop-dark.png)
- [Workspace tablet attuale](docs/design/evidence/2026-11-16/workspace-tablet.png)
- [Misure di rendering](docs/design/evidence/2026-11-16/render-observations.json)

UI UX Pro Max ha restituito anche suggerimenti da landing page, nuove palette,
font e regole per stack più recenti. Sono stati scartati perché non pertinenti
al gestionale esistente o incompatibili col vincolo di versione. Applicati
soltanto i principi verificati di gerarchia, accessibilità, feedback e responsive.
Il giudizio non deriva da una palette generata automaticamente.

Skill consultate: `~/.agents/skills/impeccable/SKILL.md` (Operate e valutazione
indipendente) e `~/.agents/skills/ui-ux-pro-max/SKILL.md` (ricerche locali UX e
stack, con filtro rispetto alle versioni installate).

## Sessione scaduta e bozze locali

Gestire 401/419 nel servizio sessione e nell’host condiviso. Verifica al rientro
senza heartbeat, stato offline distinto, login con ritorno sicuro e nessun
replay di azioni. IndexedDB conserva solo campi espliciti, isolati per utenza
e risorsa, con Recupera/Scarta e confronto baseline/server per campo.
Mostrare errori di storage e conflitti, preservare zero e assenza distinti.
Dettagli e copertura: [GLE-145](docs/qa/2026-11-20-session-recovery.md).
