# Ordini e gestione viaggi — 2026-11-16

Implementazione pronta per il rilascio QA da parte del responsabile prodotto.
DaisyUI 4 e Tailwind 3 conservati; nessun upgrade delle dipendenze. Pulsanti
standard invariati, con eccezione esplicita a 32px per le azioni delle schede ordine.

## Applicazione della convenzione

Filtro locale della ragione sociale in linea con Ordini Aperti, separato dalle
schede con pb-2. Ignora maiuscole, accenti e separatori: sail trova S.A.I.L.;
più parole possono essere cercate anche in ordine diverso. La lista originale
del draggable resta completa: si nascondono le schede non corrispondenti senza
filtrare o sostituire l'array di ordini. Applicato a Create/Edit.

Correzione modalità affiancata: il container del layout imponeva ancora un
massimo di larghezza. AppPage espone ora `fluid`, comunicato al layout tramite
provide/inject e ripristinato all'uscita. Create/Edit lo attivano solo con
mappa visibile e affiancata. Nessuna condizione sul nome della pagina nel layout.
Verificato con MainLayout reale a 3072px: main oltre 3000px, nessun overflow,
ritorno al container passando a finestra. La fixture precedente riproduceva
la shell e non verificava questo limite del layout reale.

Mappa pianificazione Create/Edit: selettore Finestra/Affiancata, scelta salvata
localmente; default finestra sotto 2200px, affiancata sopra. Finestra non bloccante,
trascinabile dalla testata e ridimensionabile dall'angolo su desktop; pannello
a tutto schermo su telefono. Minimizza/ripristina e chiusura conservano il
componente montato, anche nel passaggio tra modalità. Controlli ordine matita,
occhio e mappa ridotti a 32px tramite eccezione esplicita AppAction size sm.
Crea viaggio parte disabilitato: confronto dello stato iniziale inclusi campi,
ordini assegnati e tappe, con disattivazione al ripristino dei valori iniziali.

Wizard mezzi implementato dopo conferma: bordo warning e blocco carico fino a
motrice+cassone validi; rimorchio facoltativo con cassone obbligatorio, coppia
lungo+corto, area rimorchio inattiva per sponda/furgone. Regole condivise Create/Edit
e guardia backend in store/update. [Analisi e verifiche](../reviews/2026-11-16-journey-vehicle-configuration.md).

Se si rimuove il rimorchio con ordini ancora assegnati, il componente condiviso
DetachedTrailerOrders offre Sposta in motrice e Riporta agli ordini aperti.
Il primo richiede motrice+cassone validi; entrambi rispettano processing.
Lo spostamento include gli ordini a riempimento e ricalcola il carico con gli
avvisi di capacità esistenti. Verificato nel browser il trasferimento diretto,
lo svuotamento della lista rimorchio e lo sblocco della configurazione.

Richiesta di tracciamento a fine sessione: due GLE “Adeguamento UX/UI interfaccia”,
una ordini e una viaggi, includendo tutte le modifiche. Creazione autorizzata
dal responsabile prodotto dopo commit e push della sessione.

Elenco viaggi: cestino disabilitato per stato attivo (In corso), con motivo
nel title e guardia sul callback di conferma. La protezione server esistente
PlanningWriteBoundary respinge già la cancellazione con 409, anche per admin.
JourneyDeletionTest verifica il rifiuto e l'assenza di modifiche a viaggio e ordini
per logistica e amministratore (6 asserzioni).

Data richiesta: etichetta visibile abbreviata in Richiesta, a sinistra del
datepicker, con campo limitato a 14rem e adattabile allo spazio disponibile.
Nome accessibile completo e associazione label/input conservati in Create/Edit.

Riepilogo Cliente e sede su tre colonne da tablet: Cliente (rinominato da
Produttore), Sede di carico con rischio, Consulente ADR; su mobile una sola colonna.
Fattore di rischio accanto al titolo Sede di carico, con ritorno a capo se necessario.
I dati dentro ciascuna colonna seguono un flusso orizzontale con ritorno a
capo automatico. Consulente ADR ha un titolo identico alle altre colonne e,
sotto, icona e stato in linea senza riquadro. Il riepilogo usa una presentazione
dedicata per rispettare questa gerarchia.
Componente condiviso tra creazione e modifica ordine.

Blocco materiale compattato: con spazio disponibile di almeno 64rem, prima
riga gruppo, sfuso, quantità, contenitore, peso e magazzino; seconda riga
descrizione, HP e ADR all'estrema destra. Su larghezze inferiori la griglia
si adatta senza comprimere i campi. Dettagli ADR visibili solo se attivo.
Build, 60 controlli responsive e interazioni ripetuti con esito positivo.

Ritorno prima del titolo tramite slot `back` condiviso: affiancato da sm,
su riga precedente da mobile. Applicato alle schermate già basate su
AppPage/RegistryPage, incluse anagrafiche e form amministrativi con ritorno.
Destinazioni, `return_to`, backHref e callback esistenti conservati.
Build, suite frontend e matrice responsive ripetuti dopo questa modifica.

- Ordini Create/Edit: testata e form condivisi, griglie responsive, comandi
  coerenti, etichette associate ai campi e riepilogo errori navigabile.
  Apri tutto/Chiudi tutto sono neutral outline e conservano le bozze.
- Dotazioni: estratti SiteOperationalParameter e SiteEquipmentSummary dalla
  presentazione della sede, riutilizzati da clienti, ordini e informazioni
  ordine nei viaggi. Disponibile success, assente neutral, sempre con testo;
  rimossi i pollici verdi/rossi. Consulente ADR usa lo stesso linguaggio.
- Note sede in lettura prima dei referenti; note ordine distinte e modificabili.
  Data fissa con toggle adiacente all'etichetta e controlli non compressi su mobile.
- Viaggi Create/Edit/Index/Show: struttura AppPage, comandi coerenti, badge
  di stato centralizzati, gestione mappa neutra, disposizione adattiva dei
  pannelli motrice/rimorchio/carichi e sidebar. PlanningColumns considera lo
  spazio effettivo del contenitore. Documenti, tappe e schede ordine seguono
  la stessa convenzione delle azioni.
- RegistryPage e SiteBooleanParameter restano alias compatibili dei componenti
  condivisi. RegistryForm normalizza la baseline dopo l'inizializzazione e
  permette di escludere post_action dal confronto; gli snapshot distinguono Date.

Correzioni circoscritte emerse durante il lavoro: rimozione contenitore con
type button, aggiunta contenitore senza conversione dell'evento click in ID,
orari sede tolleranti a JSON assente/malformato e corretta selezione domenica.
Non sono stati riscritti calcoli di carico, raggruppamenti CER, payload di
pianificazione o vincoli di generazione documenti.

## Verifica

- Backend mirato: 7 test passati / 57 asserzioni, incluse configurazioni mezzi,
  guardie store/update, confini convoglio in esecuzione e cancellazione viaggio attivo.
- Build Vite riuscita; rimane il warning sulle dimensioni dei bundle.
- Suite frontend: 19 file passati, inclusi snapshot Date e orari sede.
- Chrome locale con componenti reali e dati sintetici: sei schermate di ordini
  e viaggi, cinque larghezze (320–1440), due temi: 60 controlli senza overflow
  di pagina o errori JavaScript.
- Interazioni: apertura/chiusura senza invio o perdita bozza; inserimento CER,
  materiale e contenitore; sincronizzazione data fissa; singolo invio ordine;
  rimozione senza submit; modifica/ripristino ordine e viaggio; visibilità ADR;
  invio viaggio con ordini e tappe conservati; mappa aperta senza overflow mobile.

Router, API e mappe simulati: non è un test E2E sul backend né su dispositivi
fisici. Il controller viaggio reindirizza all'elenco dopo il salvataggio;
il test controlla l'invio, senza simulare quel ciclo di navigazione completo.
Da verificare in QA: mappe reali, trascinamento touch, documenti e autorizzazioni.

[Misure responsive](evidence/2026-11-16-planning/responsive.json),
[interazioni](evidence/2026-11-16-planning/interactions.json),
[suite frontend](evidence/2026-11-16-planning/frontend-tests.txt).

## Proposte puntuali successive

1. Aggiungere una legenda testuale alle tre righe delle capacità nei viaggi:
   le sole icone camion/calcolatrice/carrello richiedono apprendimento.
2. Affiancare al trascinamento un comando accessibile per assegnare un ordine
   a motrice, rimorchio o riempimento, utile anche da tastiera e su telefono.
3. Tradurre gli stati tecnici del pannello documenti in etichette per l'operatore,
   mantenendo i codici invariati nei contratti API.

Sono proposte, non funzionalità implementate in questa passata.
