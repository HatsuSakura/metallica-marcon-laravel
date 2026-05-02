# ADR 2026-05-02 — NLP Provider Strategy: Heuristic-First + LLM Escalation

## Status
Accepted

## Context
Il layer NLP logistics usa un `HeuristicNlpProvider` pseudo-deterministico per la maggior parte delle query.
Per query complesse o con pochi parametri riconoscibili, serve un fallback LLM (OpenAI).

## Decision

### Flusso provider in `NlpLogisticsParseService`

```
execute(query, context)
    │
    ├─ context.ai === true  ──────────────────→ LlmProvider direttamente
    │                                            (toggle esplicito utente in UI)
    └─ HeuristicProvider
            │
            ├─ confidence alta/media ─────────→ done
            │
            └─ confidence bassa
                    │
                    ├─ NLP_AUTO_ESCALATE=true ─→ LlmProvider (trasparente all'utente)
                    │
                    └─ NLP_AUTO_ESCALATE=false → done (UI mostra badge confidence bassa)
```

### Regola di escalation

```php
private function shouldEscalateToLlm(array $parsed, array $context): bool
{
    if ($context['ai'] ?? false) return true;

    $autoEscalate = config('services.nlp.auto_escalate_on_low_confidence', false);
    return $autoEscalate && ($parsed['_confidence']['level'] ?? 'alta') === 'bassa';
}
```

### Config

```
# .env
NLP_PROVIDER=heuristic
NLP_AUTO_ESCALATE_ON_LOW_CONFIDENCE=false  # default: comportamento A
```

```php
// config/services.php
'nlp' => [
    'provider'                        => env('NLP_PROVIDER', 'heuristic'),
    'auto_escalate_on_low_confidence' => env('NLP_AUTO_ESCALATE_ON_LOW_CONFIDENCE', false),
],
```

## Rationale

- **Default = false** → comportamento A: l'utente controlla esplicitamente via toggle AI in UI.
  Nessun costo API nascosto, nessuna latenza inattesa.
- **Auto-escalate opt-in** → quando il team operativo è a proprio agio con LLM e vuole ridurre
  i casi di confidence bassa senza intervento manuale.
- Il parametro è pronto per essere esposto in un futuro pannello impostazioni di sistema
  (oggi gestito via `.env` su VPS).

## File impattati

| File | Modifica |
|------|---------|
| `app/Services/Nlp/NlpLogisticsParseService.php` | Logica `shouldEscalateToLlm()` |
| `app/Services/Nlp/NlpProviderFactory.php` | Support provider `llm` + override param |
| `app/Services/Nlp/Providers/LlmNlpProvider.php` | Nuovo provider OpenAI |
| `app/Http/Controllers/API_NlpLogisticsExecuteController.php` | Log `provider` effettivo |
| `config/services.php` | Nuova chiave `auto_escalate_on_low_confidence` |
| `.env.example` | `NLP_AUTO_ESCALATE_ON_LOW_CONFIDENCE=false` |

## Traceability

- YouTrack: GLE-NLP-08 (3-125)
- Obsidian: sessione 2026-05-02
