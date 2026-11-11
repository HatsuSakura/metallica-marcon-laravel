# Gestione viaggi autista — riconciliazione GLE, codice e QA

Verifica: 2026-11-09. Fonte tracker: MCP YouTrack nativo, progetto GLE.
Fonte codice: `91bb597e0a8ebc5f1ed056f9e7daee70bff60b42`; implementazione
funzionale autista consolidata in `8c5bba8b0cf9cd9daf3792341b4d664fc097fcfe`.
I successivi commit riguardano runtime, deploy e documentazione, senza nuove
modifiche al flusso autista esaminato. Nessun intervento sui file `agents/`.

[Snapshot sanificato delle issue](../evidence/youtrack-driver-2026-11-09.json).
Ricerca `project: GLE tag: ui-autisti`, esaurita in una pagina; controllo del
backlog GLE in sette pagine da 20, ultima senza pagina successiva. Totale
progetto 126 issue, 74 risolte e 52 aperte. Perimetro autisti: 11 issue,
**zero risolte, 5 In corso e 6 Da fare**. Gli aggiornamenti di queste undici
issue risalgono al 31 ottobre, precedenti al rilascio QA del 4 novembre.

Il PO riferisce che il collaudo è iniziato. Non sono stati acquisiti risultati
puntuali dei tester: implementato, rilasciato e accettato restano stati diversi.
Nessuna modifica a stati, descrizioni, tag o commenti YouTrack in questa verifica.

## Riepilogo

Le dieci issue funzionali hanno tutte una realizzazione nel pacchetto QA.
Per nove è presente il comportamento principale concordato, da accettare con
il collaudo. GLE-122 ha una copertura ampia ma un perimetro di ripristino più
ristretto rispetto alla formulazione generale del ticket. L'epica GLE-116
richiede ancora l'accettazione responsive/touch sull'intero percorso.
Non si tratta quindi di undici funzionalità ancora da costruire.

## Matrice per issue

| GLE / stato tracker | Riscontro nel codice e nel pacchetto QA | Residuo o verifica di accettazione |
|---|---|---|
| [116](https://metallicamarcon.youtrack.cloud/issue/GLE-116) — Da fare, epica | Dashboard e workspace condiviso, header comune, componenti mobile-first, selezioni ricercabili, feedback, contatori touch, split e checklist. | Completare collaudo portrait/tablet/landscape/desktop, tastiera virtuale e almeno un dispositivo touch reale. Nessuna accettazione finale documentata. |
| [117](https://metallicamarcon.youtrack.cloud/issue/GLE-117) — In corso | Contenitori per materiale/gruppo CER; riepiloghi condivisi; dati correnti prima della conclusione ed effettivi dopo; zero distinto da assente/sfuso; baseline conservata. | Collaudare aggiornamenti e riepiloghi dopo salvataggio/completamento/riapertura. Il confronto originale è riservato ai ruoli di controllo per successiva decisione PO: va aggiornato il criterio, non reintrodotto nel driver. |
| [118](https://metallicamarcon.youtrack.cloud/issue/GLE-118) — Da fare | Riapertura tracciata, dati conservati, ritorno a tappa corrente, blocchi su ordini riassegnati o fasi magazzino, niente duplicazioni. | Collaudo completo e verifica dei motivi di blocco; non risulta un nuovo sviluppo necessario sul comportamento principale. |
| [119](https://metallicamarcon.youtrack.cloud/issue/GLE-119) — In corso | Contatti della sede nel dettaglio, ruoli con icone, recapiti aggiornati dall'anagrafica, chiamate distinte mobile/fisso, placeholder. Gestione referenti disponibile nella scheda sede ai ruoli autorizzati. | Collaudo chiamata su dispositivo reale e sedi multiple. Cornetta grigia disabilitata in assenza di numero è scelta successiva del PO, diversa dal testo iniziale del criterio. |
| [120](https://metallicamarcon.youtrack.cloud/issue/GLE-120) — Da fare | Riordino dopo partenza con su/giù e trascinamento, salvataggio persistente e tracciato; nuova tappa corrente, concluse conservate. | Collaudo rientro e sequenza; OP-001 sul riordino prima della partenza resta decisione aperta, non una mancanza del riordino operativo già disponibile. |
| [121](https://metallicamarcon.youtrack.cloud/issue/GLE-121) — Da fare | Tappe concluse recuperabili e mantenute nel loro ordine sopra la corrente, anche dopo reload; dati esecutivi leggibili. | Eseguire con i tester completamento → uscita → rientro, incluso viaggio tutto completato. |
| [122](https://metallicamarcon.youtrack.cloud/issue/GLE-122) — Da fare | Baseline alla prima partenza; esecuzioni dedicate; materiali/CER/quantità modificabili, rimozione materiale, aggiunta tappe, salto corrente; audit, revisioni e autorizzazioni; ripristini originali/precedenti sui campi materiale, con undo del ripristino. | Copertura parziale della formulazione più ampia: ripristini delle consegne/contenitori vuoti e dell'intera struttura/sequenza viaggio non esposti; manca ripristino puntuale quantitativo di uno split precedente. Rimozione futura gestibile riordinando e saltando la corrente, senza comando autonomo di annullamento di una futura. Definire il perimetro residuo prima dell'accettazione complessiva. |
| [123](https://metallicamarcon.youtrack.cloud/issue/GLE-123) — In corso | Referenti e chiamate nelle schede delle tappe da eseguire, senza aprire l'ordine; selezione tappa separata dal link telefonico, corrente evidenziata e concluse recuperabili. | Collaudo chiamate su tappe future, ordine e focus dopo riordino/avanzamento. Concluse sopra la corrente è scelta PO successiva al primo testo del ticket. |
| [124](https://metallicamarcon.youtrack.cloud/issue/GLE-124) — In corso | Giorno italiano corrente, storico/futuri/Oggi, progressivo giornaliero, ID stabile, partenze prevista/effettiva, convoglio, stato e avanzamento. Attivi di altre date esposti separatamente, anche oltre mezzanotte. | Collaudo visuale e caso oltre mezzanotte; il caso tecnico è già gestito dal controller e dai test, non ancora da implementare. |
| [125](https://metallicamarcon.youtrack.cloud/issue/GLE-125) — In corso | Sintesi contenitori nelle schede tappa e testata, stesso componente e aggregazioni del dettaglio; accesso al dettaglio per materiale. | Collaudo coerenza riepilogo/dettaglio, prima e dopo completamento, inclusi dati parziali. |
| [126](https://metallicamarcon.youtrack.cloud/issue/GLE-126) — Da fare | Ritiri dei materiali e consegne vuoti separati, previsti/effettivi indipendenti; aggiornamento consegne anche di tappe future; residuo per tipo calcolato da consegne pianificate alla partenza meno consegne effettive, comprese scorte aggiunte prima di partire. Il vecchio ordine calcola il default 1:1 per tipo con delta manuale. | Collaudare ritirate 6/lasciate 4 e ritirate 6/lasciate 8, modifiche tappe future, residuo, riapertura e ripianificazione. I valori negativi sono segnalati e non nascosti; non c'è scelta automatica del cliente da penalizzare. |

## Sviluppi aggiunti durante il confronto, già nel pacchetto

- Scelta motrice/rimorchio per singolo materiale e split quantitativo con due
  righe visive e un solo materiale, con allocazioni figlie.
- Checklist Caricato persistente, separata dall'esito tappa; variazioni di
  quantità/distribuzione richiedono riverifica.
- Contatore condiviso − / numero / + con pulsanti da 42 px e input numerico.
- Gestione anagrafica referenti con modale, categorie obbligatorie e icone.
- Aggiunta tappa da ordine/cliente/sede con ricerca, riepilogo richiudibile,
  note viaggio consultabili, comandi split compatti e matita nascosta nello split.

Sono incrementi riconducibili soprattutto a GLE-116/122/126, senza nuove
issue dedicate nel tracker corrente. Non vanno conteggiati come ancora assenti.

## Lavoro tecnico o decisionale ancora aperto

1. **Note sede modificabili dall'autista.** Richiesta esplicita emersa nella
   sessione; oggi vengono mostrate, mentre l'aggiornamento anagrafico è
   riservato ai ruoli di controllo da SitePolicy. Serve un comando mirato alle
   note della sede del viaggio, con autorizzazione e audit coerenti, senza
   estendere all'autista l'intera modifica dell'anagrafica. Non sostituirle
   con note ordine, che il PO vuole consultabili.
2. **Backend documenti.** Il pannello di generazione è stato tolto dalla UI
   operativa. Rimangono API e servizi che possono generare documenti delle
   esecuzioni: la regola successiva del PO «documenti stampati del pianificato»
   richiede ancora riconciliazione backend. Nascondere il pannello non è
   equivalente a completare quel cambiamento di processo.
3. **Estensione o delimitazione GLE-122.** Decidere quali ripristini offrire
   oltre ai campi materiale: consegne/vuoti, ripartizione quantitativa dello
   split, sequenza e tappe aggiunte/rimosse. Il salto della corrente è già
   presente; l'annullamento diretto di una futura non è un comando distinto.
   Non confondere il precedente del singolo campo con un undo globale del viaggio.
4. **OP-001.** Chiarire con il cliente il riordino da autista prima della
   partenza e quale piano conservare. In attesa, solo Logistica prima
   dell'avvio. Evitare di anticipare una decisione non presa.
5. **Accettazione QA e riallineamento tracker.** Raccogliere esiti e difetti
   per GLE e per dispositivo; aggiornare criteri superati dalle decisioni PO,
   prima di decidere le chiusure. Non risultano ticket di difetti del nuovo
   collaudo né esiti di accettazione nelle undici issue al controllo del 9 novembre.

## Backlog storico da non confondere con il pacchetto attuale

- **GLE-17**, ancora Da fare: descrive i primi campi della vecchia maschera
  ordine autista. Il nuovo workspace apre i campi materiale su matita; la
  vecchia route Ordine esiste ma richiede privilegi di controllo. Rivedere
  l'issue rispetto alla nuova UX prima di sviluppare nuovamente quel form.
- **GLE-6**, ancora Da fare: richiede CER *fittizi* per materiali non dichiarati
  trovati a magazzino e gestione dalla dashboard commerciale. L'aggiunta di
  un CER reale da catalogo durante il viaggio non copre quel requisito.
- **GLE-9**, ancora Da fare: il flag esplicito «non conformità» per materiale
  aggiuntivo non è presente nell'editor di esecuzione esaminato.
- **GLE-26/98/99**: eventi e storico dell'esecuzione coprono il viaggio, ma
  non dimostrano automaticamente completamento dell'intero audit generale,
  di tutti gli eventi documentali e delle altre entità.
- **GLE-100/101/102**, già Completato: riordino/stato corrente, rilascio ordine
  dopo salto e macchinari/orari restano presenti nel nuovo flusso. Per GLE-101
  verificare il testo della conferma: ora esplicita esclusione della tappa,
  motivo e pulsante Conferma salto, senza ripetere tutto l'avviso storico su
  ordine non caricato e riutilizzabile. È una differenza di copy da riesaminare,
  non una prova di mancato rilascio: il rilascio è implementato e testato.

## Evidenze tecniche e verifiche

Rieseguiti il 2026-11-09, senza usare il database QA o quello di sviluppo:
**78 test PHP / 522 asserzioni passati**, più **10 file frontend passati**.
Le fixture di dominio usano SQLite isolato. Nessun nuovo test di browser o
dispositivo reale eseguito in questa ricognizione; non è un nuovo deploy.

File principali confrontati: DriverDashboardController, DriverJourneyReadModel,
JourneyExecutionService, ExecutionRestoration, StopReopening, ItemLoad,
ItemCargoLocation, ContainerBalance, SitePolicy e API_ExecutionDocumentsController;
workspace, pannello tappe, editor materiali/consegne, selezione tappa, contatti,
riepiloghi, checklist e contatori condivisi.

Evidenza del rilascio: [esito QA del 4 novembre](../qa/2026-11-04-deploy-result.md)
e [guida di collaudo](../qa/2026-11-04-driver-ui.md). Il marker locale
.last-qa-deploy punta successivamente a `690bfe5ac77b8f766a57882680b944b9d11cf377`.
Non è stata interrogata nuovamente la configurazione live QA in questa verifica.

## Seguito autorizzato dal PO — 2026-11-09

Dopo questa fotografia sono state implementate localmente le note della sede
nel driver e la conferma «Avvia e riordina»; OP-001 è risolto. Nessuna modifica
al pianificato autorizzata all'autista. Questa estensione non è ancora in QA.
Dettagli e nuove verifiche: [pacchetto aggiuntivo](../qa/2026-11-09-driver-notes-reorder.md).
La matrice sopra descrive il pacchetto QA precedente, non questa estensione.
