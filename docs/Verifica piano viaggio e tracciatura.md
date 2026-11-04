# Piano originale e modifiche operative — ricognizione

Verifica del 2026-10-31. Richiesta: verificare ciò che esiste prima di
progettare GLE-122, senza implementarla. Sorgenti: working tree corrente su
HEAD `d81d26e9f5a773d1671436fea5bfbef171d9a685` con modifiche locali non
committate; librerie effettivamente installate; lettura del solo DB locale.
Non è una verifica di produzione. Nessuna mutazione DB o prova di ripristino.

Chiarimento successivo del PO nella stessa data: l'istante di congelamento
è la prima partenza. Il punto lasciato aperto nella ricognizione è quindi
risolto funzionalmente; disegno tecnico in
`Proposta confine pianificazione-esecuzione.md`. Il ciclo di generazione
documentale è approfondito separatamente in `Ciclo documenti viaggio.md`.

## Conclusione

Esistono già versioni dei record, audit di campo, eventi di processo e
separazioni pianificato/effettivo. Non va introdotto un sistema parallelo
ignorandoli. Non è però emersa una revisione unica e completa che identifichi
il piano consegnato all'autista e coordini viaggio, tappe, collegamenti agli
ordini, gruppi CER, materiali e contenitori. L'assenza di questa baseline
complessiva non equivale all'assenza di storico dei singoli dati.

## Dove il requisito è documentato

- `Logistica E2E Analisi_v03.md`, §5.2 (riga 255): conservare pianificazione
  iniziale separata dai dati effettivi. §5.4 (riga 284): esiti e variazioni di
  materiali, contenitori, quantità, sede e sequenza; autore degli scostamenti.
- `Logistica E2E Implementazione_v01.md`, §2.2 (riga 51): l'effettivo non
  sovrascrive l'originale; vale per date, tappe, materiali, contenitori, pesi,
  destinazioni e sequenze. §4.4 (riga 158): sequenze pianificate/effettive e
  tappe saltate conservate. §5.6 (riga 344): scostamenti registrati, senza
  correzioni retroattive. §8 (riga 431): autore, prima/dopo, origine e contesto.
- `Piano UI autisti.md`, Decisioni di processo (riga 22): chiarimento corrente
  del PO, canovaccio e autonomia dell'autista; separazione esplicita fra
  originale, operativo corrente ed eseguito, più i due ripristini.
- `.ai/logistics/CONTEXT.md`: gerarchia fra analisi, specifica target e codice.
  La specifica target è normativa per progettare, non certifica implementazione.
- `.claude/rules/audit.md`: audit di dominio e di processo; stato descritto
  nella nota non sostituisce le evidenze runtime raccolte qui.

## Meccanismi esistenti

| Meccanismo | Cosa conserva | Copertura e limiti verificati |
| --- | --- | --- |
| `Mpociot\Versionable` | Snapshot post-salvataggio degli attributi del record, data, attore e motivo opzionale | Su Journey, Order, OrderItem, OrderHolder. Non su tappe, collegamenti tappa/ordine o gruppi CER. Snapshot individuali, senza un ID di revisione comune dell'intero piano. Gli attributi possono includere accessor, non sono una serializzazione canonica di tutte le relazioni. |
| `OwenIt\Auditing` tramite `HasDomainAudit` | Diff prima/dopo, attore, istante e metadati richiesta | Whitelist esplicite. Abilitato nella configurazione locale; esclusa console. Le scritture di massa via query non passano dai normali eventi dei model. Non confondere configurazione con copertura dimostrata di ogni comando. |
| `journey_events` | Eventi operativi con viaggio, eventuale tappa, autore, data e payload | Avvio, riordino, completamento, salto, sosta tecnica e dispatch già registrati. Diversi payload descrivono l'azione o il solo nuovo stato, senza snapshot o diff completo. Non emerge un replay completo dell'aggregato. |
| `planned_sequence` / `sequence` | Ordine pianificato e corrente delle tappe | Il riordino autista conserva `planned_sequence`. La modifica logistica in stato creato elimina e ricrea tappe/collegamenti; la sosta tecnica aggiunta operativamente valorizza anche `planned_sequence`. Non equivale a un originale immutabile globale. |
| Allocazioni `source=planned/actual` | Quantità per materiale e posizione sul mezzo separate | Inizializzate all'avvio tramite JourneyWorkspaceInitializer. Snapshot parziale delle quantità positive, non di tutti i dati materiali né dei vuoti da consegnare. Il salvataggio dispatch ammette anche `source=planned`: non sono protette come baseline immutabile. |
| `journey_load_census_items` | Ultimo censimento effettivo per viaggio/riga materiale | Separato dall'ordine e con autore/origine. Aggiornato con updateOrCreate; model privo di audit/versioning, quindi non conserva automaticamente tutte le revisioni del censimento. |
| `plan_version` | Contatore del piano dispatch | Incrementato da JourneyDispatchPlanService::setPlan, non da ogni modifica del viaggio o riordino. Non è oggi una revisione trasversale di tutto il piano. |
| Conferma baseline dispatch | Evento di conferma con autore/data e aggiornamento stato dispatch | `dispatch_baseline_confirmed` conserva evento e note, non un'immagine completa bloccata delle allocazioni/censimento. |
| Documenti ordine versionati | Manifest JSON e documenti con suffisso di versione | Manifest conserva CER, descrizione, contenitore, quantità e peso dichiarato per materiale. Non contiene tutto il piano del viaggio o le quantità vuote da consegnare. È evidenza documentale utile, non baseline applicativa unica. |

### Versionable: salvataggio e ripristino già disponibili

Nel codice installato, `VersionableTrait::versionablePostSave` salva
`serialize($model->attributesToArray())` dopo creazione/modifica significativa.
Sono disponibili `currentVersion`, `previousVersion`, `getVersionModel` e
`Version::revert()`. Non sono emersi consumer applicativi di queste API di
ripristino sotto app/routes/resources: capacità della libreria presente,
workflow utente non implementato.

`revert()` ricostruisce e salva l'intero record: non è un comando selettivo
di ripristino di un campo e non coordina automaticamente gli oggetti collegati.
Va quindi valutato prima di riusarlo per la UI; un ripristino integrale può
coinvolgere anche stato, assegnazioni e altri campi.

I model impostano `keepOldVersions = true`. Nel package installato questo
non sta limitando lo storico a una sola versione: verificati sia il ramo
di purge sia record locali con numerose versioni (fino a 64 per un materiale).
Non assumere un comportamento diverso deducendolo dal solo cast booleano.

### Audit e visualizzazione già presenti

OrderController espone agli amministratori `AuditTrailPresenter::forOrder`
e `AuditCollapse.vue` presenta i diff di ordine/materiali/contenitori. Anche
questa UI va riusata o estesa secondo il contesto, non ricreata ignorandola.
Il presenter raccoglie però gli ID dei figli attualmente non eliminati e
limita gli ultimi audit: la vista non è una ricostruzione storica completa
dell'ordine, specialmente per figli rimossi.

Fra le lacune delle whitelist: Journey non include in audit tutti i campi
di scarico o `plan_version`; JourneyStop non include orari effettivi,
motivazioni e note autista, pur registrandone alcune negli eventi di processo.
Questo non cancella i dati di versione eventualmente coperti da un altro layer.

## Percorsi da coordinare prima di GLE-122

1. `JourneyController::update` consente modifiche solo in stato creato ma
   elimina in massa tappe e collegamenti e li ricrea. Il versioning della
   testata non fotografa automaticamente le relazioni precedenti.
2. `API_DriverJourneyStopsController::reorder` salva la sequenza corrente e
   registra gli ID ordinati. Conserva il pianificato delle tappe già esistenti,
   ma non incrementa `plan_version` né salva il piano intero.
3. `skip` aggiorna direttamente gli ordini via DB e cancella i collegamenti
   tappa/ordine. L'evento conserva gli ID rilasciati, non tutti i dati precedenti.
4. `createTechnical` sposta in massa le sequenze successive e crea una tappa
   con sequenza pianificata: va distinta una nuova tappa operativa da una
   tappa inclusa nella baseline originale.
5. `OrderController::update` aggiorna i singoli model, attivandone gli hook;
   `DriverOrderController::update` usa invece anche update/delete su query di
   relazione. Entrambe le route sono presenti; la vecchia form autista invia
   però a `order.update`. Verificare tutti gli ingressi, non solo la nuova UI.
6. Censimento, allocazioni e istruzioni dispatch vengono aggiornati sul posto;
   alcune allocazioni vengono eliminate. L'evento workspace_saved contiene
   il nome dell'azione, non le revisioni dei dati sostituiti.
7. OrderItemGroup non ha audit/versioning e i gruppi inutilizzati vengono
   eliminati. L'ID del gruppo nello storico di un materiale non garantisce
   da solo la conservazione del contenuto del gruppo.

## Evidenza locale in sola lettura

Campione raccolto il 2026-10-31 alle 18:26:34 UTC. Totali: 602 versioni,
33 audit, 84 eventi viaggio. Delle versioni: 75 Journey, 176 Order,
309 OrderItem, 42 OrderHolder. Fra i model core interrogati gli audit locali
presenti sono quattro OrderItem; nessun audit Journey/JourneyStop osservato.
Non è una prova che i relativi hook web futuri non funzionino: non è stata
eseguita alcuna mutazione per provarli.

Per il viaggio 159:

- 2 versioni della testata, entrambe con attore; nessuna contiene `stops`.
- 3 ordini attualmente collegati: 46 versioni, 30 con attore.
- 7 materiali: 13 versioni; 4 righe contenitori: 4 versioni.
- 5 eventi: un avvio, tre riordini, un completamento.
- Sequenze originali/correnti diverse: tappa 24 da 2 a 1, tappa 25 da 3 a 2,
  tappa 23 da 1 a 3. `plan_version` rimane 1 nonostante i riordini.
- 7 allocazioni planned, nessuna riga di censimento effettivo.
- 7 manifest documentali versionati degli ordini attualmente collegati.

Le date del viaggio sono state spostate a oggi dalla fixture locale richiesta
dal PO; quel comando scrive direttamente le date e conserva il proprio report.
La discrepanza fra date correnti e vecchie versioni non va attribuita per questo
campione al workflow operativo dell'applicazione. Non abbiamo ricostruito
retroattivamente dati originali mancanti né rieseguito comandi business.

## Direzione da discutere, non implementazione decisa

Riutilizzare i meccanismi esistenti con responsabilità esplicite: versioni
per gli attributi storici, audit per i diff/attori, eventi per le operazioni,
planned/actual per il dominio. Prima di scegliere nuove tabelle o snapshot,
concordare:

1. Quale istante identifica il piano originale: creazione, assegnazione,
   consegna all'autista o primo avvio. La prima versione di una testata nasce
   prima di tutte le relazioni e non coincide necessariamente con tale istante.
2. Come collegare a una stessa revisione coerente le versioni dei record e
   conservare le parti non coperte, soprattutto relazioni, tappe e gruppi.
3. Come far passare tutte le mutazioni da comandi transazionali coerenti,
   preservando originale, corrente ed effettivo e rendendo il ripristino una
   nuova operazione tracciata, con gestione dei conflitti.

Non è ancora decisa una nuova architettura. GLE-122 non implementata e nessun
ticket modificato da questa ricognizione. L'unica modifica applicativa
contestuale è quella UI richiesta separatamente: orari/attrezzature prima
delle azioni, titolo “Esito della tappa”; build Vite passata.

## Riferimenti tecnici

- `vendor/mpociot/versionable/src/Mpociot/Versionable/VersionableTrait.php`: 89, 161, 239.
- `vendor/mpociot/versionable/src/Mpociot/Versionable/Version.php`: 48, 69.
- `config/audit.php`; `app/Models/Concerns/HasDomainAudit.php`.
- `app/Models/Journey.php`, `JourneyStop.php`, `Order.php`, `OrderItem.php`, `OrderHolder.php`.
- `app/Support/Audit/AuditTrailPresenter.php`: 49; `app/Http/Controllers/OrderController.php`: 30, 356.
- `app/Http/Controllers/DriverOrderController.php`: 198, 263.
- `app/Http/Controllers/JourneyController.php`: 592, 686, 741.
- `app/Http/Controllers/API_DriverJourneyStopsController.php`: 144, 275, 351.
- `app/Services/Dispatch/JourneyWorkspaceInitializer.php`: 41, 62, 98.
- `app/Services/Dispatch/JourneyDispatchPlanService.php`: 13, 27.
- `app/Http/Controllers/API_LogisticDispatchWorkspaceController.php`: 128, 147, 354, 509.
- `app/Services/OrderDocumentGenerationService.php`: 36, 54, 65.
