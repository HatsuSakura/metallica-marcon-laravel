---
title: GLE - Issue Status & Effort Analysis
date: 2026-03-13
project: GLE
status: historical
source: YouTrack MCP snapshot
recovered_on: 2026-10-30
recovery_status: historical
---

> **Nota recuperata — storica, non procedura corrente.** Origine: vecchio vault Obsidian; recupero 2026-10-30. Per lo stato attuale consultare [sviluppi e riconciliazione](../../../development-status.md). Non eseguire comandi di deploy, migrazione o modifica dati sulla sola base di questa nota.


# GLE Issues - Status & Effort (snapshot 2026-03-13)

## Scope
- Issues analyzed: 64 (`GLE-1` ... `GLE-64`)
- Status summary:
- `Completato`: 36
- `In corso`: 1
- `Da fare`: 27

## Estimation model
- `Normal Dev`: developer with standard domain/context knowledge.
- `Skilled Dev`: senior developer with strong Laravel + domain familiarity.
- Unit: **working days** (1 day ~= 6.5 productive hours).

## Per-issue analysis
| Issue  | Summary                                                                 | Type          | State      | Normal Dev (d) | Skilled Dev (d) | Analysis                                                                         |
| ------ | ----------------------------------------------------------------------- | ------------- | ---------- | -------------: | --------------: | -------------------------------------------------------------------------------- |
| GLE-1  | Inserire possibilitÃƒÂ  di ordinare le tappe di un viaggio              | Funzionalità  | Completato |            2.5 |             1.5 | Richiede logica ordinamento, persistenza e coerenza UI drag/drop.                |
| GLE-2  | Creare entitÃƒÂ  "tappa" per gestire i viaggi                           | Funzionalità  | Completato |            2.0 |            1.25 | Introduzione nuova entitÃƒÂ  e relazioni fondamentali di dominio.                |
| GLE-3  | Associazione Journey - Tappa - Ordine                                   | Funzionalità  | Completato |            1.5 |             1.0 | Legami multi-entitÃƒÂ  con validazioni di integritÃƒÂ  applicativa.              |
| GLE-4  | PossibilitÃƒÂ  di cambiare l'ordine delle tappe                         | Funzionalità  | Completato |            1.5 |             1.0 | Evoluzione puntuale su ordinamento giÃƒÂ  presente nel flusso viaggio.           |
| GLE-5  | Impedire le date antecedenti per peso/classificazione                   | Funzionalità  | Da fare    |           0.75 |             0.5 | Validazione date client+server con gestione edge case timezone.                  |
| GLE-6  | Codici CER per meteriali non dichiarati in ordine                       | Funzionalità  | Da fare    |            2.0 |            1.25 | Gestione fallback CER e regole per materiale non previsto.                       |
| GLE-7  | Dashboard Comerciale                                                    | Funzionalità  | Da fare    |            5.0 |             3.0 | Nuova dashboard end-to-end: query, UI, azioni e permessi.                        |
| GLE-8  | Il commerciale deve poter imputare il codice CER corretto               | Funzionalità  | Da fare    |            2.0 |            1.25 | Modifica flusso business con controlli consistenza codici CER.                   |
| GLE-9  | il materiale aggiuntivo deve essere flaggabile come "non conformitÃƒÂ " | Funzionalità  | Da fare    |            1.5 |             1.0 | Aggiunta flag, logica stato e riflessi in report/processo operativo.             |
| GLE-10 | visualizzaizone per cliente                                             | Funzionalità  | Da fare    |            1.0 |            0.75 | Esposizione dati su vista cliente con minimo impatto backend.                    |
| GLE-11 | visualizzazione per materiale                                           | Funzionalità  | Da fare    |            1.0 |            0.75 | Vista filtro/materiale a complessitÃƒÂ  bassa-moderata.                          |
| GLE-12 | Non conformitÃƒÂ  gestito come explosion                                | Funzionalità  | Da fare    |            2.0 |            1.25 | Richiede modellazione processo non conformitÃƒÂ  e stati derivati.               |
| GLE-13 | Visualizzaizone casse da restituire anche in Dashboard Magazzino        | Funzionalità  | Da fare    |            1.5 |             1.0 | Estensione dashboard con aggregazioni inventariali dedicate.                     |
| GLE-14 | Creazione Dashboard per preparazione Camion                             | Funzionalità  | Da fare    |            6.0 |             4.0 | Funzionalità  ampia: UX operativa, stato carico, checklist e flusso.             |
| GLE-15 | "SFUSO" viene visto come tipo di contenitore e vuole "1"                | Bug           | Completato |           0.75 |             0.5 | Correzione regole input/validazione tipo contenitore.                            |
| GLE-16 | Inserire Description imputata in ordine e non la dscrizione del CER     | Bug           | Completato |           0.75 |             0.5 | Bug mapping campo descrizione con impatto limitato.                              |
| GLE-17 | Modifica Ordine per Autisti: chiudere di default campi iniziali         | Funzionalità  | Da fare    |           1.25 |            0.75 | Miglioria UX con comportamento default e persistenza stato pannelli.             |
| GLE-18 | In presenza di HP/ADR generare un documento per il magazzino            | Funzionalità  | Da fare    |            3.0 |             2.0 | Generazione documento condizionale con regole ADR/HP e template.                 |
| GLE-19 | Definizione campi per documento PDF ADR/HP per Magazzino                | Funzionalità  | Da fare    |            2.0 |            1.25 | Analisi requisiti dati + implementazione payload documento.                      |
| GLE-20 | Notifica ADR/HP durante la preparazione del camion + Borsa attrezzi     | Funzionalità  | Da fare    |            3.5 |            2.25 | Notifiche contestuali + checklist strumenti, piÃƒÂ¹ punti integrazione.          |
| GLE-21 | Scarico diretto (senza viaggio) da parte del cliente esterno            | Funzionalità  | Da fare    |            4.0 |             2.5 | Nuovo flusso operativo alternativo ai viaggi standard.                           |
| GLE-22 | Sistemazione puntamenti dashboard                                       | Funzionalità  | Da fare    |            1.5 |             1.0 | Refine navigazione/linking e coerenza routing dashboard.                         |
| GLE-23 | Dashboard Magazzino                                                     | Funzionalità  | Da fare    |            5.0 |             3.0 | Dashboard completa con viste operative e filtri dedicati.                        |
| GLE-24 | Dashboard Logistica                                                     | Funzionalità  | Da fare    |            5.0 |             3.0 | Dashboard cross-processo con orchestrazione viaggi/stati.                        |
| GLE-25 | Dashboard Autista: ripensare layout (riordino tappe)                    | Funzionalità  | Completato |            3.0 |             2.0 | Revisione UX strutturale con implicazioni sul flusso operativo.                  |
| GLE-26 | Sistema di Audit/Tracking per le modifiche                              | Funzionalità  | Da fare    |            5.0 |             3.5 | Audit trail trasversale con storage eventi e consultazione.                      |
| GLE-27 | Refactoring gestione Dashboard per Profilo utente                       | Funzionalità  | Completato |            3.0 |             2.0 | Refactor architetturale con separazione logica per ruolo.                        |
| GLE-28 | Errore 500 in creazione ordine - Chiavi sporche DB di TEST              | Bug           | Completato |            1.0 |             0.5 | Debug backend + fix robustezza input/constraint.                                 |
| GLE-29 | Creazione Ordine direttamente da Lista Clienti                          | Funzionalità  | Completato |            1.5 |             1.0 | Shortcut UX con precompilazione contesto cliente.                                |
| GLE-30 | Visualizzazione badge ordini in corso / tot                             | Estetico      | Completato |           0.75 |             0.5 | Miglioria UI semplice su conteggi giÃƒÂ  disponibili.                            |
| GLE-31 | Nella scheda cliente non refresha la lista ordini dopo elimina          | Bug           | Completato |            1.0 |             0.5 | Problema refresh stato frontend, fix circoscritto.                               |
| GLE-32 | Aggiungere "Imballo non standard"                                       | Funzionalità  | In corso   |            3.0 |             2.0 | Introduce ramo di business con nuovi campi/regole di validazione.                |
| GLE-33 | Modificare Cod. ONU in Cod. UN                                          | Estetico      | Completato |           0.25 |             0.1 | Rename UI/testi, nessuna complessitÃƒÂ  logica sostanziale.                      |
| GLE-34 | Ordine: Calcolo delle quantitÃƒÂ  contenitori automatico                | Funzionalità  | Completato |            1.5 |             1.0 | Automazione calcolo con regole deterministiche dominio.                          |
| GLE-35 | Ristrutturazione maschera Ordine - CER Padre e righe sottese            | Funzionalità  | Completato |            4.0 |             2.5 | Refactor form complesso con dipendenze fra righe/item.                           |
| GLE-36 | Ordine - Template/Ordine tipo per cliente                               | Funzionalità  | Da fare    |            4.0 |             2.5 | Template persistenti + UX scelta/override in creazione ordine.                   |
| GLE-37 | Creazione Imballo NON standard e gestione delle misure su OrderItem     | Funzionalità  | Completato |            3.0 |             2.0 | Cambiamento dominio+UI su order item con misure variabili.                       |
| GLE-38 | Gestione delle misure in creazione Journey                              | Funzionalità  | Da fare    |            2.5 |             1.5 | Estensione journey su dati dimensionali e validazione.                           |
| GLE-39 | Trasformare /Order/Create in SSOT                                       | Funzionalità  | Completato |            2.5 |             1.5 | Consolidamento fonte dati/logica centrale di creazione ordine.                   |
| GLE-40 | aggiungere anagrafica contenitori                                       | Funzionalità  | Completato |            1.5 |             1.0 | CRUD anagrafica con integrazione lookup nel processo ordine.                     |
| GLE-41 | anagrafica automezzi: targa su tendina rimorchio                        | Bug           | Completato |           0.75 |             0.5 | Correzione binding dati in select anagrafica.                                    |
| GLE-42 | Placeholders per ItemRow in creazione Ordine                            | Bug           | Completato |            0.5 |            0.25 | UI bug a basso impatto, test visuale rapido.                                     |
| GLE-43 | Creazione Ordine: step peso = 10                                        | Funzionalità  | Completato |           0.25 |             0.1 | Parametro input numerico, modifica minima.                                       |
| GLE-44 | Inserire un separatore leggero fra ItemRows                             | Estetico      | Completato |           0.25 |             0.1 | Piccolo intervento CSS/UI.                                                       |
| GLE-45 | creazione viaggio: visualizzare misure imballo non standard             | Funzionalità  | Completato |           0.75 |             0.5 | Esposizione dati esistenti in journey view.                                      |
| GLE-46 | creazione viaggio: migliorare leggibilitÃƒÂ  item di un ordine          | Estetico      | Completato |            0.5 |            0.25 | Tune UI/spacing, rischio regressioni basso.                                      |
| GLE-47 | creazione viaggio: migliorare leggibilitÃƒÂ  spazi a terra              | Funzionalità  | Completato |           0.75 |             0.5 | Miglioria visuale con possibile adattamento calcolo layout.                      |
| GLE-48 | aggiunta creazione Sedi                                                 | Funzionalità  | Completato |            1.0 |            0.75 | CRUD base sede integrato in flusso cliente.                                      |
| GLE-49 | modifica Sede esistente                                                 | Funzionalità  | Completato |            1.0 |            0.75 | Update sede con validazioni standard.                                            |
| GLE-50 | lista sedi in edit Cliente                                              | Funzionalità  | Completato |           0.75 |             0.5 | Rendering elenco correlato e sincronizzazione dati.                              |
| GLE-51 | Aggiunta campi note su entitÃƒÂ  core                                   | Funzionalità  | Completato |            2.5 |             1.5 | Modifica schema/CRUD multipli con coerenza trasversale.                          |
| GLE-52 | Visualizzazione note in dashboard Viaggio lato Driver                   | Funzionalità  | Da fare    |            2.0 |            1.25 | Consumo multi-sorgente note in dashboard driver.                                 |
| GLE-53 | Nuova dashboard gestione Viaggi per Logistica                           | Funzionalità  | Da fare    |            6.0 |             4.0 | Macro-feature ad alta ampiezza funzionale e UI.                                  |
| GLE-54 | Ordini speciali con data fissa (data+ora)                               | Funzionalità  | Da fare    |            2.0 |            1.25 | Nuovo attributo con logica condizionale in create/update.                        |
| GLE-55 | Inserimento manuale ritiro effettuato per retrocompatibilitÃƒÂ          | Funzionalità  | Da fare    |            2.5 |             1.5 | Flusso manuale con auditing e impatto su stati ordine/tappa.                     |
| GLE-56 | Generazione PDF stampabile tabella item ordine                          | Funzionalità  | Da fare    |            3.0 |             2.0 | Pipeline PDF con template dati item/CER.                                         |
| GLE-57 | Esposizione legacy_code in Order Index/Edit                             | Bug           | Completato |            1.0 |             0.5 | Esposizione campo e controlli mapping su due viste.                              |
| GLE-58 | Filtri mappa con pulsante Vai (no reattivo immediato)                   | Funzionalità  | Completato |            1.5 |             1.0 | Rifattorizza trigger filtri e stato query lato UI.                               |
| GLE-59 | Aggiunta note su Cliente                                                | Funzionalità  | Completato |            1.0 |            0.75 | Estensione puntuale CRUD cliente per note.                                       |
| GLE-60 | Aggiunta note su Sede                                                   | Funzionalità  | Completato |            1.0 |            0.75 | Estensione puntuale CRUD sede per note.                                          |
| GLE-61 | Aggiunta note su Ordine                                                 | Funzionalità  | Completato |           1.25 |            0.75 | Estensione ordine con impatto su viste principali.                               |
| GLE-62 | Aggiunta note su Viaggio                                                | Funzionalità  | Completato |           1.25 |            0.75 | Estensione journey con esposizione in contesti operativi.                        |
| GLE-63 | Download PDF tabella item da interfaccia Viaggio                        | Funzionalità  | Da fare    |            1.5 |             1.0 | Integrazione consumer UI su servizio PDF esistente.                              |
| GLE-64 | Filtri Customer Index con pulsante Vai (no reattivo immediato)          | Funzionalità  | Completato |           1.25 |            0.75 | Analogo a GLE-58 su altra vista/filtro.                                          |
| GLE-83 | [P1] Rinominare OrdersState in OrderStatus (allineamento nomenclatura)  | Funzionalità  | Da fare    |            1.5 |             1.0 | Refactor trasversale naming/stati con rischio regressioni su import e type-hint. |
| GLE-84 | [P2] Generazione documenti Ordine asincrona e separata dal salvataggio  | Funzionalità  | Da fare    |            3.5 |             2.5 | Endpoint+job+coda+stato processo; include robustezza error handling/retry.       |
| GLE-85 | [P3] Stato READY e blocco pianificazione ordini senza documenti         | Funzionalità  | Da fare    |            2.5 |             1.5 | Introduzione gate applicativo su planning con enforcement backend/UI.            |
| GLE-86 | [P4] Invalidazione documenti alla modifica ordine READY                 | Funzionalità  | Da fare    |            2.0 |            1.25 | Regole di invalidazione coerenti su update ordine/item e reset metadati docs.    |

## Notes
- Estimations are planning baselines, not timesheet data.
- Open issues with highest expected effort: `GLE-53`, `GLE-14`, `GLE-7`, `GLE-23`, `GLE-24`, `GLE-26`.





## Sync update (2026-03-17)
- YouTrack live sync completed on project `GLE`.
- Current scope: `GLE-1 .. GLE-87` (87 issues total).
- Current status split: `Completato 48`, `Da fare 39`, `In corso 0`.

### Key changes since 2026-03-13 snapshot
- `GLE-52` -> `Completato`.
- `GLE-55` -> `Completato`.
- `GLE-83` -> `Completato`.
- New issue `GLE-87` -> `Completato`.

### New range synced (`GLE-65..GLE-87`)
| Issue | State | Type | Summary |
|---|---|---|---|
| GLE-65 | Da fare | Epica | Evoluzione NLP Logistica: schema canonico, parse/execute e integrazione Journey |
| GLE-66 | Completato | Funzionalità | NLP: definire schema canonico LogisticsQuery v1 e regole di versionamento |
| GLE-67 | Completato | Funzionalità | NLP: validatore server-side per schema canonico |
| GLE-68 | Completato | Funzionalità | NLP: interfaccia provider e factory di selezione |
| GLE-69 | Completato | Funzionalità | NLP: implementazione baseline HeuristicNlpProvider |
| GLE-70 | Completato | Funzionalità | NLP: endpoint API POST /api/nlp/logistics/parse |
| GLE-71 | Completato | Funzionalità | NLP: collegare pannello mappa all'endpoint parse backend |
| GLE-72 | Completato | Funzionalità | NLP: stabilizzazione pannello (preview, candidati, apply/reset) |
| GLE-73 | Da fare | Funzionalità | NLP: endpoint API POST /api/nlp/logistics/execute (skeleton + contratto) |
| GLE-74 | Da fare | Funzionalità | NLP Execute: builder deterministico per scenario planning_sites |
| GLE-75 | Da fare | Funzionalità | NLP Execute: builder deterministico per scenario order_requests |
| GLE-76 | Da fare | Funzionalità | NLP Execute: builder deterministico per scenario ibrido |
| GLE-77 | Da fare | Funzionalità | NLP: integrazione Journey/Create con stato NLP selezionato |
| GLE-78 | Da fare | Problema di usabilità | NLP UX: gestione errori validazione e warning di boundary MVP |
| GLE-79 | Da fare | Attività | NLP Ops: sicurezza e guardrail operativi |
| GLE-80 | Da fare | Attività | NLP Ops: osservabilità pipeline parse/execute |
| GLE-81 | Da fare | Attività | NLP QA: test unitari/integrati/E2E su flussi happy-path ed edge-case |
| GLE-82 | Da fare | Attività | NLP Docs: documentazione tecnica, runbook e checklist di rollout |
| GLE-83 | Completato | Funzionalità | [P1] Allineamento nomenclatura *State in *Status |
| GLE-84 | Da fare | Funzionalità | [P2] Generazione documenti Ordine asincrona e separata dal salvataggio |
| GLE-85 | Da fare | Funzionalità | [P3] Stato READY e blocco pianificazione ordini senza documenti |
| GLE-86 | Da fare | Funzionalità | [P4] Invalidazione documenti alla modifica ordine READY |
| GLE-87 | Completato | Funzionalità | Gestione ritiro direttamente da elenco clienti (con selezione sede) |
