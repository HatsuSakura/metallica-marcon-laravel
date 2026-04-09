# Metallica Marcon — Logistics Platform

Piattaforma verticale per la gestione logistica di ritiro rifiuti industriali (metalli e rifiuti tecnologici).

## Stack tecnico

- **Backend**: Laravel (PHP) — API + domain logic
- **Frontend**: Vue 3 + Inertia.js + Tailwind CSS
- **Database**: MySQL (Haversine per geo; PostGIS come migration path)
- **LLM**: OpenAI API — strato NLP per interpretazione intent (solo output JSON strutturato)
- **Issue tracker**: YouTrack — progetto `GLE` su `metallicamarcon.youtrack.cloud`

## Agentic Dev Team

### Ruoli e responsabilità

**PO (Matteo)** — definisce requisiti, approva decisioni architetturali, prioritizza backlog.

**Tech Lead (Claude — sessione principale)** — fa breakdown, identifica impatti, coordina i ruoli specializzati. Non esegue senza conferma del PO per task che toccano più di un file.

**Laravel Architect** — mantiene separation of concerns, impedisce SQL generato da AI, valida architettura domini e performance. Aggiorna `.claude/rules/decisions/` ad ogni decisione strutturale.

**AI Integration Engineer** — progetta prompt, valida schemi JSON, monitora token usage, logga performance NLP. Non introduce mai SQL generato da LLM.

**Data Model Guardian** — mantiene schema analytics-ready, previene definizioni metriche ambigue, garantisce tracciabilità materiali e margini.

### Flusso di lavoro (PO → Dev Team)

1. Il PO descrive il task o la feature in linguaggio naturale.
2. Il Tech Lead fa breakdown tecnico, identifica file impattati e aspetta conferma prima di eseguire.
3. I ruoli specializzati vengono attivati per dominio (architettura → Laravel Architect, NLP → AI Integration Engineer, ecc.).
4. Ogni decisione architetturale viene registrata in `.claude/rules/decisions/`.
5. Il task si chiude solo dopo verifica completa della DoD.

### Regole generali per tutti gli agenti

- Non bypassare mai la validazione.
- Non permettere SQL dinamico generato da AI.
- Ogni change deve essere testabile.
- Aggiornare `.claude/rules/decisions/index.md` ad ogni nuova decisione strutturale.
- Correggere le cause, non i sintomi: preferire fix nella generation/domain logic, non mascherature UI.

## Philosophy di sviluppo

- Core logic deterministico — AI è interprete, mai decision-maker.
- LLM produce solo JSON strutturato. Mai SQL generato da AI.
- Il backend valida tutti gli output LLM.
- Ogni interazione NLP viene loggata.
- Auditabilità sopra ogni cosa.

## Definition of Done

Un task è completo quando:

- [ ] Il codice è deterministico (nessun output AI non validato in produzione).
- [ ] Lo schema JSON è validato (dove NLP è coinvolto).
- [ ] I test unitari esistono (dove applicabile).
- [ ] L'impatto sulle performance è stato considerato (< 500 ms parsing NLP, < 1 s totale).
- [ ] `.claude/rules/decisions/index.md` aggiornato se c'è una decisione architetturale.
- [ ] Nessun SQL generato da AI nel codice.
- [ ] I log NLP sono presenti (se coinvolto NLP).
- [ ] Nessun componente Vue compilato a runtime (`template: '...'` in oggetti JS).
- [ ] I blocchi UI riutilizzabili sono `.vue` SFC standard importati dai parent.
- [ ] Tutti i file sorgente salvati in UTF-8.

Per feature NLP aggiungere:

- [ ] Prompt documentato in `.ai/nlp/`.
- [ ] Input/output di esempio documentato.
- [ ] Edge case testati.

## Decisioni architetturali — sommario rapido

Dettaglio completo in `.claude/rules/decisions/001-core.md`.

| ID | Decisione |
|----|-----------|
| D1 | AI output solo JSON — nessun SQL da AI |
| D2 | Endpoint NLP separati: `/api/nlp/logistics`, `/api/nlp/analytics` |
| D3 | Tutte le query NLP vengono loggato |
| D4 | MySQL mantenuto; abstraction layer per futura migrazione PostGIS |
| D5 | Frontend gestisce chart rendering; backend restituisce dataset normalizzati |
| D6 | Single-shot NLP prima; dialog refinement pianificato per Phase 2 |
| D7 | Nessun componente Vue compilato a runtime |
| D8 | Dispatch come modulo dedicato (`Pages/LogisticDispatch/*`) — GLE-53 |
| D9 | Branch `feature/logistic-dispatch-board` isolato da GLE-55-56-63 |
| D10 | Naming convention: `OrderDocumentsStatus` / `documents_status` |
| D11 | Tutti i file sorgente in UTF-8 |

## Active tasks
@.claude/tasks.md

## Riferimenti

| Argomento | File |
|-----------|------|
| Architettura sistema (layer, geo, separazione) | `.claude/rules/architecture.md` |
| Layer audit domini e processi (GLE-26) | `.claude/rules/audit.md` |
| NLP engine (logistics + analytics) | `.claude/rules/nlp.md` |
| Workflow YouTrack (issue creation, subtask, defaults) | `.claude/rules/youtrack.md` |
| Decisioni architetturali dettagliate (ADR) | `.claude/rules/decisions/` |
| Migration DB (ETL, SQL, naming, script) | `.ai/db_migration/` |
| Template documenti ordine | `.ai/docs_templates/` |
| NLP engine docs completi | `.ai/nlp/` |
| Authorization matrix (generata) | `.ai/decisions/generated/` |
| Note operative e sessioni | Obsidian → `Metallica Marcon/` |
