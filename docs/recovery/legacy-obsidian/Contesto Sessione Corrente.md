---
progetto: Metallica Marcon
aggiornato: 2026-04-15
recovered_on: 2026-10-30
recovery_status: historical
---

> **Nota recuperata — storica, non procedura corrente.** Origine: vecchio vault Obsidian; recupero 2026-10-30. Per lo stato attuale consultare [sviluppi e riconciliazione](../../development-status.md). Non eseguire comandi di deploy, migrazione o modifica dati sulla sola base di questa nota.


# Contesto Sessione Corrente — Metallica Marcon

## Focus attuale
<!-- Cosa stai lavorando adesso — aggiorna ad ogni sessione -->

## Ultima sessione
**Data**: 2026-04-08
**Argomento**: Claude-izzazione progetto + setup JARVIS (hooks, skills, Fase 1-4)

### Cosa è stato fatto
- Migrazione struttura da `.codex/` a `.claude/rules/`
- Creazione `CLAUDE.md` con full project context
- Installazione hooks: `check-vue-runtime`, `remind-decisions`, `suggest-youtrack`
- Installazione skills: `/feature`, `/review`, `/arch`
- Setup MCP YouTrack in `.claude/settings.json`

### Decisioni prese
- D1–D11 già consolidate in `.claude/rules/decisions/001-core.md`
- Nessuna nuova decisione architetturale in questa sessione

### Blockers / TODO
- Nessun blocker attivo
- Testare `/feature` su una feature reale di Metallica Marcon

## Issues aperte (YouTrack)
<!-- Aggiorna con le GLE attive -->
- GLE-53: Dispatch module (in progress, branch `feature/logistic-dispatch-board`)

## Note per la prossima sessione
- Entrare subito nel branch `feature/logistic-dispatch-board` e verificare stato
- Usare `/feature` per la prossima feature da implementare
- Ricordare: D7 (no Vue runtime), UTF-8 (D11), log NLP (D3)

---

## Sessione 2026-04-15

**Argomento**: Riconfigurazione YouTrack MCP + fix post-deploy

### Cosa è stato fatto
- Diagnosi perdita config MCP YouTrack: `settings.local.json` non conteneva `mcpServers`
- Identificato che MCP_DOCKER (Docker Desktop) espone GitHub/Maps/Obsidian, non YouTrack
- Riconfigurato YouTrack MCP a livello user (`~/.claude.json`) via `claude mcp add --transport http -s user`
- Verificato funzionamento reale (`YouTrack MCP v2026.1`) nonostante `claude mcp list` mostri `✗ Failed` (falso negativo: probe usa GET, MCP richiede POST)
- Rotazione del token YouTrack registrata; identificatori delle credenziali omessi dalla copia versionata.

### Decisioni
- MCP YouTrack a scope **user** (`~/.claude.json`), non project — disponibile in tutti i progetti
- Transport: `http` (non `sse` — YouTrack non supporta SSE)
- Note aggiornate in `VPS/Hostinger VPS — Accessi e Infrastruttura.md`
