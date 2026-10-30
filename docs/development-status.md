# Stato sviluppi — Metallica Marcon / GLE

Verifica: **2026-10-30**, YouTrack MCP. Stato di questa sintesi: **proposed**;
le discrepanze richiedono verifica di accettazione, non aggiornamenti automatici.

## Stato del tracker

**115 issue: 41 aperte (`Da fare`), 74 chiuse (`Completato`), nessuna `In corso`.**
Ricerca paginata completa: `project: GLE sort by: {issue id} asc`, 20 per pagina.
`resolvedAt` determina risolto/non risolto; corrisponde allo State per tutte le
115 issue. [Snapshot minimo](evidence/youtrack-2026-10-30.json) acquisito via MCP,
senza descrizioni complete, reporter o indirizzi personali.

## Risultati e discrepanze

| Area | YouTrack al controllo | Evidenza documentale / sorgente | Interpretazione operativa |
|---|---|---|---|
| Note entità core, viste driver, sedi | GLE-48–52 e GLE-59–62 completate | Backlog storico, snapshot corrente | Le vecchie liste di task aperti sono superate. |
| Date fisse e widget | GLE-54 e GLE-94 completate | Nota refactor 2026-03-26 | Coerente; refactor controller separato e sospeso dal cliente. |
| Documenti ordine e gate READY | GLE-55/56/63 e GLE-83–86 completate | Specifica recuperata, codice e note correnti | GLE-63 è oggi XLSX: non assumere PDF dalla formulazione iniziale. |
| Dispatch | GLE-53 aperta; GLE-24/25, 88/89/91 completate; 90/92/93 aperte | `.claude/tasks.md` spunta M2; servizi Dispatch presenti | As-is implementato, copertura dell'issue madre ancora aperta. Non dichiarare completato tutto il dispatch. |
| NLP | GLE-66–72 completate; epica 65 e GLE-73–82 aperte | Controller execute, builder multi-scenario, log e provider presenti; release notes pending | Esiste implementazione oltre lo stato tracker; verificare contratto, test, rollout e criteri prima di proporre chiusure. |
| Audit | GLE-26 e GLE-95–99 aperte | `.claude/rules/audit.md` descrive fondazione/trait/model; sorgenti presenti | Codice infrastrutturale presente; runtime, migrazioni, processo e UI non provati da questa revisione. |
| Validazioni frontend | GLE-104/105 aperte | Tracker corrente | Restano backlog; nessuna inferenza di completamento. |
| Tipologia attività | GLE-110–115 completate | Tracker corrente | Aggiunta successiva alle vecchie note di marzo. |
| Deploy | Milestone M4 non spuntata ma sezione M4 e storico release registrano PROD il 15 aprile; fix TLS il 15 settembre | `.claude/tasks.md`, `.claude/release-notes.md` | Documenti internamente incoerenti; non riscrivere i fatti come “mai deployato”. Nessuna verifica live PROD svolta qui. |
| Queue | Compose definisce worker/Valkey; M5 ancora non spuntata | `docker-compose.prod.yml`, KB queue evidence | Configurazione e presenza sorgente non provano salute runtime o rilascio. |

### Verifiche puntuali

- GLE-53: descrizione generale di dashboard, nessun commento, ancora `Da fare`;
  GLE-90 esplicita il gap operativo dopo dispatch e il legame con GLE-53.
- GLE-73: ancora `Da fare`, ma commento del 2026-05-02 registra la strategia
  provider D12 e cita i file implementati. Il codice corrente contiene
  `API_NlpLogisticsExecuteController::execute` e `LogisticsCandidateQueryBuilder`
  con `planning_sites`, `order_requests` e `hybrid`. Non è una prova E2E.
- Audit: il trait `HasDomainAudit` e il presenter esistono; la documentazione
  distingue audit di dominio da audit di processo. Nessuna chiusura automatica.
- Risk: `routes/console.php` definisce `risk:global`, `risk:customer` e `risk:site`;
  la sezione iniziale della nota recuperata che nega gli ultimi due è superata.

## Backlog aperto completo

Tutti i seguenti ticket risultano `Da fare`; questa è una fotografia datata,
non una nuova prioritizzazione approvata.

| Issue | Titolo |
|---|---|
| [GLE-5](https://metallicamarcon.youtrack.cloud/issue/GLE-5) | Impedire le date antecedenti per peso/classificazione |
| [GLE-6](https://metallicamarcon.youtrack.cloud/issue/GLE-6) | Codici CER per meteriali non dichiarati in ordine |
| [GLE-7](https://metallicamarcon.youtrack.cloud/issue/GLE-7) | Dashboard Comerciale |
| [GLE-8](https://metallicamarcon.youtrack.cloud/issue/GLE-8) | Il commerciale deve poter imputare il codice CER corretto |
| [GLE-9](https://metallicamarcon.youtrack.cloud/issue/GLE-9) | il materiale aggiuntivo deve essere flaggabile come "non conformità" |
| [GLE-10](https://metallicamarcon.youtrack.cloud/issue/GLE-10) | visualizzaizone per cliente |
| [GLE-11](https://metallicamarcon.youtrack.cloud/issue/GLE-11) | visualizzazione per materiale |
| [GLE-12](https://metallicamarcon.youtrack.cloud/issue/GLE-12) | Non conformità gestito come explosion |
| [GLE-13](https://metallicamarcon.youtrack.cloud/issue/GLE-13) | Visualizzaizone casse da restituire anche in Dashboard Magazzino |
| [GLE-14](https://metallicamarcon.youtrack.cloud/issue/GLE-14) | Creazione Dashboard per preparazione Camion |
| [GLE-17](https://metallicamarcon.youtrack.cloud/issue/GLE-17) | Modifica Ordine per Autisti: chiudere di default campi iniziali |
| [GLE-19](https://metallicamarcon.youtrack.cloud/issue/GLE-19) | Definizione campi per documento PDF ADR/HP per Magazzino |
| [GLE-20](https://metallicamarcon.youtrack.cloud/issue/GLE-20) | Notifica ADR/HP durante la preparazione del camion + Borsa attrezzi |
| [GLE-21](https://metallicamarcon.youtrack.cloud/issue/GLE-21) | Scarico diretto (senza viaggio) da parte del cliente esterno |
| [GLE-22](https://metallicamarcon.youtrack.cloud/issue/GLE-22) | Sistemazione puntamenti dashboard |
| [GLE-23](https://metallicamarcon.youtrack.cloud/issue/GLE-23) | Dashboard Magazzino |
| [GLE-26](https://metallicamarcon.youtrack.cloud/issue/GLE-26) | Sistema di Audit/Tracking per le modifiche |
| [GLE-36](https://metallicamarcon.youtrack.cloud/issue/GLE-36) | Ordine - Template/Ordine tipo per cliente |
| [GLE-38](https://metallicamarcon.youtrack.cloud/issue/GLE-38) | Gestione delle misure in creazione Journey |
| [GLE-53](https://metallicamarcon.youtrack.cloud/issue/GLE-53) | Nuova dashboard gestione Viaggi per Logistica |
| [GLE-65](https://metallicamarcon.youtrack.cloud/issue/GLE-65) | Evoluzione NLP Logistica: schema canonico, parse/execute e integrazione Journey |
| [GLE-73](https://metallicamarcon.youtrack.cloud/issue/GLE-73) | NLP: endpoint API POST /api/nlp/logistics/execute (skeleton + contratto) |
| [GLE-74](https://metallicamarcon.youtrack.cloud/issue/GLE-74) | NLP Execute: builder deterministico per scenario planning_sites |
| [GLE-75](https://metallicamarcon.youtrack.cloud/issue/GLE-75) | NLP Execute: builder deterministico per scenario order_requests |
| [GLE-76](https://metallicamarcon.youtrack.cloud/issue/GLE-76) | NLP Execute: builder deterministico per scenario ibrido |
| [GLE-77](https://metallicamarcon.youtrack.cloud/issue/GLE-77) | NLP: integrazione Journey/Create con stato NLP selezionato |
| [GLE-78](https://metallicamarcon.youtrack.cloud/issue/GLE-78) | NLP UX: gestione errori validazione e warning di boundary MVP |
| [GLE-79](https://metallicamarcon.youtrack.cloud/issue/GLE-79) | NLP Ops: sicurezza e guardrail operativi |
| [GLE-80](https://metallicamarcon.youtrack.cloud/issue/GLE-80) | NLP Ops: osservabilità pipeline parse/execute |
| [GLE-81](https://metallicamarcon.youtrack.cloud/issue/GLE-81) | NLP QA: test unitari/integrati/E2E su flussi happy-path ed edge-case |
| [GLE-82](https://metallicamarcon.youtrack.cloud/issue/GLE-82) | NLP Docs: documentazione tecnica, runbook e checklist di rollout |
| [GLE-90](https://metallicamarcon.youtrack.cloud/issue/GLE-90) | Appoggi & Trasbordi - Gestione a posteriori |
| [GLE-92](https://metallicamarcon.youtrack.cloud/issue/GLE-92) | Trasbordi - gestione come Journey dedicati |
| [GLE-93](https://metallicamarcon.youtrack.cloud/issue/GLE-93) | Appoggi - conferma avvenuto scarico |
| [GLE-95](https://metallicamarcon.youtrack.cloud/issue/GLE-95) | [Audit] Infrastruttura base audit di dominio con OwenIt\Auditing |
| [GLE-96](https://metallicamarcon.youtrack.cloud/issue/GLE-96) | [Audit] Model core logistici e ordini |
| [GLE-97](https://metallicamarcon.youtrack.cloud/issue/GLE-97) | [Audit] Anagrafiche e configurazioni operative |
| [GLE-98](https://metallicamarcon.youtrack.cloud/issue/GLE-98) | [Audit] Eventi di processo su documenti ordine e lifecycle journey |
| [GLE-99](https://metallicamarcon.youtrack.cloud/issue/GLE-99) | [Audit] UI consultazione storico modifiche |
| [GLE-104](https://metallicamarcon.youtrack.cloud/issue/GLE-104) | [Order Create] Validazioni FE campi obbligatori (es. HP se attivato) |
| [GLE-105](https://metallicamarcon.youtrack.cloud/issue/GLE-105) | [Journey Create] Validazioni FE campi obbligatori |

## Prossimo lavoro consigliato

1. Verificare i criteri di GLE-53 rispetto ai figli e alla release realmente
   consegnata, mantenendo aperti i gap appoggi/trasbordi.
2. Riconciliare GLE-73–82 con implementazione, test e ambiente di rilascio.
3. Verificare GLE-95–99 distinguendo fondazione presente da audit operativo.
4. Riordinare le checkbox milestone e gli inventari di deployment con evidenza
   di release; non eseguire deployment come effetto della revisione documentale.
5. Riprendere il refactor controller solo quando il cliente lo riattiva.

## Fonti e limiti

- [Note recuperate e provenienza](recovery/MIGRATION.md).
- Snapshot tracker completo e controlli puntuali GLE-53, 73, 26, 90 via MCP.
- Sorgenti presenti nel checkout e documentazione `.claude/`, `.ai/`.
- Nessun test applicativo, migrazione DB o controllo remoto di produzione è
  stato eseguito: non confondere stato tracker, presenza codice e rilascio.
- La KB centrale contiene la sintesi con i riferimenti al commit sorgente;
  queste evidenze complete restano nel repository del progetto.
