# Anagrafiche: form e punti di modifica — 16 novembre 2026

Tracker: [GLE-136](https://metallicamarcon.youtrack.cloud/issue/GLE-136), In corso.

Stato: implementazione locale, non rilasciata. Il PO ha comunicato il deploy
QA del precedente commit `e615ac8`; questo lavoro è successivo. Nessuna
modifica di dipendenze, migrazione o mutazione del tracker.

## Struttura tecnica concordata

- `RegistryPage` fornisce il contenitore comune delle anagrafiche;
  `RegistryForm` gestisce invio, processing, confronto con il riferimento
  salvato e riepilogo errori con focus e collegamenti ai campi.
- Creazione e modifica cliente riusano `CustomerFields`: una sola definizione
  di campi, etichette ed errori; mappe e operazioni restano nei chiamanti.
- La modifica cliente riguarda il cliente. Il collegamento «Gestisci sedi e
  referenti» porta alla sua scheda, dove si aggiungono e modificano le sedi.
- `CustomerSiteEditor` è l’unico modulo di creazione/modifica sede: nome,
  indirizzo, sede principale, dotazioni/consulenza, altri macchinari e note.
  Riusa gli endpoint Site esistenti. Conserva `site_type`, senza trasformare
  il lavoro grafico in una nuova gestione delle tipologie.
- Rimosso dalla scheda sede l’editor inline che mescolava dati e booleani.
  «Modifica sede» apre ora effettivamente il modulo della sede. Referenti e
  orari hanno editor dedicati, con responsabilità esplicite.
- Creazione/eliminazione sede usano flag ottenuti dai Gate esistenti;
  le policy non cambiano. Il divieto UI di eliminare la sede principale è
  conservato. Le eliminazioni hanno conferma contestuale e blocco duplicati.
- Annulla/riapertura ricostruiscono i valori dal record selezionato. Dopo una
  modifica, una nuova sede parte vuota. Le nuove props Inertia aggiornano la
  scheda cliente. Corretta anche la chiusura asincrona del dialog: un evento
  di chiusura precedente non smonta il modulo appena riaperto.
- La geocodifica sede viene richiesta dopo la modifica dell’indirizzo,
  conservando le coordinate all’apertura e al ripristino del valore iniziale.
  Le coordinate del vecchio indirizzo non accompagnano un indirizzo diverso;
  risposte relative a un altro indirizzo o a un editor chiuso sono ignorate.
- Su richiesta successiva del PO, `LocationPicker` fornisce la mappa nei
  moduli cliente e sede. Il marker segue le coordinate geocodificate;
  trascinandolo cambiano soltanto latitudine/longitudine, senza reverse
  geocodifica né sostituzione dell’indirizzo. La posizione manuale prevale
  sulle risposte tardive della geocodifica fino a una nuova modifica
  dell’indirizzo. Il drag è disabilitato durante il salvataggio. Le coordinate
  a zero sono valide e distinte da quelle assenti.
- Anche utenti, mezzi, rimorchi, cassoni, contenitori, tipologie attività,
  catalogo e intestazione ricette adottano il form comune. I nodi ricetta
  confrontano la composizione corrente con l’ultimo salvataggio, conservando
  un ripristino coerente dopo salvataggi successivi.
- Corretto `AppAction`: i link attivi non ricevono `disabled="false"`, che
  veniva interpretato dal CSS come presenza dell’attributo disabled.

L’area principale è consultabile. Il vecchio editor la proponeva come campo
modificabile ma `API_SiteBooleanUpdateController` non la validava/persistiva:
non è stata introdotta una nuova funzionalità di assegnazione delle aree.

### Affinamenti successivi concordati

- Le sedi sono nuovamente uno schedario a tabs, non accordion. Linguette
  sempre presenti, scorrimento orizzontale negli spazi ridotti, navigazione
  con frecce/Home/End e relazione accessibile tab/pannello. I pannelli
  inattivi sono nascosti senza perdere le modifiche locali degli editor.
  Variante DaisyUI `tabs-lifted`, raccordata al bordo del pannello, senza
  distacco verticale o duplicazione del contenitore interno.
- La sede principale è riconoscibile dalla casa nella linguetta, con testo
  accessibile; eliminato il badge testuale che duplicava spesso il nome.
- Mappa di consultazione accanto ai dati della sede nella parte alta da md,
  sotto i dati sul telefono. Gli orari occupano tutta la larghezza.
  L’indirizzo è dentro i dati della sede, nella colonna accanto alla mappa;
  non viene ripetuto nella testata delle azioni.
- Dicitura comandi: «Modifica dati sede» e «Modifica orari sede».

## Decisioni grafiche già richieste e prossima fase

La lista clienti usa nome, metadati e indirizzo in gruppi distinti. Nome
16/24 px; dati e indirizzo 14/20 px; icone lista/segnaposto da 16 px;
padding verticale e spazio tra card 8 px. Le azioni non vengono spinte
singolarmente a capo dal testo lungo su desktop. Regola in `DESIGN.md`.

I booleani della sede sono informazioni di stato: colore e testo esplicito
insieme. Nei moduli titolo, toggle e valore restano nello stesso gruppo.

Il PO ha chiesto di completare prima la parte tecnica. Successivamente ha
approvato per la lettura degli orari una tabella HTML DaisyUI con testata
Mattina/Pomeriggio e sottocolonne Apertura/Chiusura, sette righe compatte e
giorni abbreviati negli spazi ridotti. DataTables non è necessario per questa
settimana fissa. Su indicazione esplicita del PO, `00:00` è visualizzato come
«Chiuso», senza trasformare i valori memorizzati. Il PO ha poi approvato
anche la stessa struttura tabellare in modifica: quattro input time per
riga/giorno e «Copia al successivo» nella sesta colonna, sulla stessa riga.
Negli spazi ridotti scorre soltanto la tabella di modifica, conservando
controlli leggibili e target di tocco adeguati; Annulla e Salva restano fuori
dallo scorrimento orizzontale.

## Verifiche e limiti

- 16 file di test frontend passati, inclusa la regressione dei link AppAction
  e il contratto RegistryForm (confronto normalizzato, zero e identificativi).
- `SiteContactsTest`: 6 test, 52 asserzioni; controllo sintattico controller.
- Build Vite riuscita; resta l’avviso preesistente sulle dimensioni del bundle.
- Fixture browser con pagine Vue reali, dati sintetici, router/Inertia/API e
  mappe simulati: invio invariato bloccato, dirty/revert, invio singolo,
  riferimento dopo successo, errori/focus, conferma eliminazione, annulla e
  riapertura sede, conservazione flag principale, nuova sede vuota e ripristino
  della composizione ricetta. Anche il ripristino tecnico degli orari è verificato.
- 27 schermate in cinque viewport (320/390/768/1024/1440 px), due temi:
  270 combinazioni senza overflow pagina o errori di rendering osservati.
  Controllata anche la non regressione di dashboard/workspace autisti.
- Interazioni cartografiche con provider simulato: cambio indirizzo → nuove
  coordinate, drag → sole coordinate, payload PUT con indirizzo conservato.

Le fixture non provano consegna email, geocodifica Google, CRUD reale in QA,
integrazione autenticata o accessibilità completa su dispositivo fisico.
Evidenze in [registry-forms](evidence/2026-11-16-registry-forms/).

Le schermate sono catture delle diverse iterazioni: `customer-detail-desktop-dark.png`
precede il ripristino delle tabs; `site-tabs-desktop-dark.png` mostra le tabs lifted,
prima degli ultimi ritocchi alla barra azioni e al bordo. I log build e frontend
sono stati aggiornati dopo le modifiche finali, prima del commit per QA.
