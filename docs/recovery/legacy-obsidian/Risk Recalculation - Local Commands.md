---
title: Risk Recalculation - Local Commands
date: 2026-03-13
project: GLE
status: historical
recovered_on: 2026-10-30
recovery_status: historical
---

> **Nota recuperata — storica, non procedura corrente.** Origine: vecchio vault Obsidian; recupero 2026-10-30. Per lo stato attuale consultare [sviluppi e riconciliazione](../../development-status.md). Non eseguire comandi di deploy, migrazione o modifica dati sulla sola base di questa nota.


## Scope
Comandi per eseguire il ricalcolo rischio in locale con naming standardizzato (`risk`).

## Prerequisiti
- Eseguire dalla root del progetto Laravel.
- DB locale configurato e raggiungibile.

## Comando disponibile
- Ricalcolo globale (tutti i customer/siti):
```bash
php artisan risk:global
```

## Esecuzione su singolo sito/customer (attuale via Tinker)
Al momento non esistono ancora command dedicati `risk:site` o `risk:customer`.
Usare Tinker:

- Singolo sito:
```bash
php artisan tinker --execute "app(\App\Services\CalculateRiskService::class)->recalculateSiteRisk(['siteId' => 123]);"
```

- Singolo customer:
```bash
php artisan tinker --execute "app(\App\Services\CalculateRiskService::class)->recalculateCustomerRisk(['customerId' => 45]);"
```

## Note
- Il comando schedulato in app è `risk:global` a mezzanotte (`00:00`).
- Se serve CLI dedicata per sito/customer, creare:
  - `php artisan risk:site {siteId}`
  - `php artisan risk:customer {customerId}`


## Context Snapshot (2026-03-13)

### Current implementation status
- Service in use: `app/Services/CalculateRiskService.php`
- Naming standardized to English (`risk` namespace for commands).
- Active methods:
  - `recalculateSiteRisk(array $data): Site`
  - `recalculateCustomerRisk(array $data): array`
  - `recalculateGlobalRisk(): array`
- Input validation enforced (`siteId` / `customerId` positive int).
- Safety guard present to avoid division by zero (`daysUntilNextWithdraw <= 0 => 1`).

### Active artisan commands
- `php artisan risk:global`
- `php artisan risk:customer {customerId}`
- `php artisan risk:site {siteId}`
- Scheduler: `risk:global` daily at `00:00`.

### Algorithm summary (operational)
- For each site, reads last 1-3 withdraws.
- Computes:
  - days since latest withdraw
  - gaps between recent withdraws
  - latest residue percentage
- Builds expected interval (`days_until_next_withdraw`) via weighted history.
- Computes risk:
  - `risk = (daysSinceLatestWithdraw / expectedInterval) + residueFactor`
  - capped at `1.25`
- Persists:
  - `sites.calculated_risk_factor`
  - `sites.days_until_next_withdraw`

### Technical opinion captured
- Strengths:
  - simple, explainable heuristic
  - combines cadence + residue signal
  - bounded output, easy to threshold in UI
- Weak points:
  - sensitive to data quality (same-day withdraws, missing/dirty residue)
  - ad-hoc branching by available history (1/2/3 withdraws)
  - hardcoded constants not calibrated on historical outcomes
  - sparse-history defaults may mis-rank sites

### Future improvements (not now)
1. Move constants to config (`config/risk.php`).
2. Add robust test matrix (0/1/2/3 withdraws, same-day events, boundary values).
3. Add explainability/audit fields (`risk_version`, `computed_at`, input snapshot).
4. Calibrate weights and cap against historical operational outcomes.

### Note
This context was intentionally stored without opening a new implementation stream.
