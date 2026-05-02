# NLP Engine

Documentazione completa in `.ai/nlp/`. Questo file è il riferimento rapido operativo.

## Overview

Il layer NLP interpreta l'intent dell'utente e produce JSON strutturato.
Non genera mai SQL direttamente — il JSON viene passato ai query builder deterministici.

## Due engine separati (D2)

| Engine | Endpoint | Builder |
|--------|----------|---------|
| Logistics NLP | `POST /api/nlp/logistics` | `LogisticsCandidateQueryBuilder` |
| Analytics NLP | `POST /api/nlp/analytics` | `AnalyticsAggregationBuilder` |

Separazione motivata da profili di rischio e query type diversi.

## Constraints

- Output LLM: solo JSON strutturato validato da schema.
- Nessun SQL generato da AI — mai.
- Ogni query NLP viene loggata (audit + debugging + future training).
- Tempo di parsing: < 500 ms. Tempo totale NLP + esecuzione: < 1 s.

## Provider strategy — heuristic-first con escalation opzionale (D12)

Flusso decisionale provider in `NlpLogisticsParseService`:

1. `context.ai === true` → LlmProvider direttamente (toggle esplicito utente)
2. HeuristicProvider → se confidence = alta/media → done
3. Se confidence = bassa:
   - `NLP_AUTO_ESCALATE_ON_LOW_CONFIDENCE=true` → LlmProvider automatico (trasparente)
   - `NLP_AUTO_ESCALATE_ON_LOW_CONFIDENCE=false` (default) → done, UI mostra confidence bassa

**Default = false** → comportamento A garantito (utente controlla esplicitamente via toggle AI).
Il parametro è esposto in `.env` e futuro pannello impostazioni di sistema.

Config: `config/services.nlp.auto_escalate_on_low_confidence` → env `NLP_AUTO_ESCALATE_ON_LOW_CONFIDENCE`.

## Evolution roadmap

- **Phase 1** (attuale): single-shot NLP queries.
- **Phase 2**: dialog-based progressive refinement (multi-turn filter).
- **Phase 3**: predictive analytics e suggestion systems.

## Aggiungere una feature NLP

1. Progettare il prompt e lo schema JSON output.
2. Documentare prompt + esempio input/output in `.ai/nlp/`.
3. Implementare validazione schema lato backend prima di passare al builder.
4. Aggiungere log dell'interazione.
5. Testare edge case e documentarli.
6. Verificare DoD NLP prima di chiudere il task.
