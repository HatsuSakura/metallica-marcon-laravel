# Migrazione note Obsidian — Metallica Marcon

Data: 2026-10-30. Stato: 20/20 note recuperate come evidenza storica;
sintesi corrente separata in [development-status.md](../development-status.md).

## Conservazione

Il vault completo (129 file, 108 Markdown, 3.475.178 byte) è conservato in
`~/RECOVERY/2026-10-30-obsidian-vault/vault.tar.gz` con `FILES.json`,
`SHA256SUMS` e registro privato. Tutti i membri sono stati riletti dal tar
e confrontati SHA-256 con l'origine, ricontrollata dopo la copia.
Originali, nomi e timestamp restano disponibili; nessuna cancellazione.
La copia raw include allegati e `.obsidian` e non va indicizzata né committata.

Lo snapshot è sul disco workstation, l'origine resta sul vecchio disco:
due supporti fisici, ma backup cifrato offsite e automazione 3-2-1 non sono
ancora configurati. Non considerare completata la protezione disaster recovery.

## Classificazione e destinazioni

Tutte le note restano nel perimetro di questo progetto: nessuna promozione
automatica di dettagli cliente in `shared` o `domains`. `legacy-obsidian/`
è un archivio storico versionabile, non una seconda cartella di procedure attive.
I futuri aggiornamenti vanno nelle pagine correnti, non riscrivendo la storia.
Il manifest registra hash originali e hash delle copie sanificate.

| Nota originale | Evidenza recuperata | Classificazione | Sanificazione |
|---|---|---|---|
| 2026-03-27 Authorization Matrix Workflow.md | [copia storica](legacy-obsidian/2026-03-27%20Authorization%20Matrix%20Workflow.md) | autorizzazioni | 0 interventi |
| 2026-03-27 Authorization Report.md | [copia storica](legacy-obsidian/2026-03-27%20Authorization%20Report.md) | autorizzazioni | 0 interventi |
| 2026-03-27 Guard Hardening Plan.md | [copia storica](legacy-obsidian/2026-03-27%20Guard%20Hardening%20Plan.md) | autorizzazioni | 0 interventi |
| Contesto Sessione Corrente.md | [copia storica](legacy-obsidian/Contesto%20Sessione%20Corrente.md) | sessione storica; MCP sostituito da RUN-021 | 1 interventi |
| DB Runbook - First Production Bootstrap.md | [copia storica](legacy-obsidian/DB%20Runbook%20-%20First%20Production%20Bootstrap.md) | database; PowerShell storico | 0 interventi |
| DB Runbook - QA Alignment Post Refactor.md | [copia storica](legacy-obsidian/DB%20Runbook%20-%20QA%20Alignment%20Post%20Refactor.md) | database; PowerShell storico | 0 interventi |
| Incoming Journey.md | [copia storica](legacy-obsidian/Incoming%20Journey.md) | dispatch; requisiti e avanzamento storico | 0 interventi |
| Installazione.md | [copia storica](legacy-obsidian/Installazione.md) | deploy storico; da confrontare con script Ubuntu | 8 interventi |
| NLP Evolution.md | [copia storica](legacy-obsidian/NLP%20Evolution.md) | NLP; piano storico | 0 interventi |
| Note operative/Backlog Call - Viaggi, Note e Operatività.md | [copia storica](legacy-obsidian/Note%20operative/Backlog%20Call%20-%20Viaggi%2C%20Note%20e%20Operativit%C3%A0.md) | requisiti; stati storici | 0 interventi |
| Note operative/GLE - Development Plan from YouTrack MCP.md | [copia storica](legacy-obsidian/Note%20operative/GLE%20-%20Development%20Plan%20from%20YouTrack%20MCP.md) | piano e conteggi storici | 0 interventi |
| Note operative/GLE - Issue Status & Effort Analysis - 2026-03-13.md | [copia storica](legacy-obsidian/Note%20operative/GLE%20-%20Issue%20Status%20%26%20Effort%20Analysis%20-%202026-03-13.md) | stime e conteggi storici; mojibake conservato | 0 interventi |
| Note operative/GLE-53 - Process Mapping (As-Is) - 2026-03-17.md | [copia storica](legacy-obsidian/Note%20operative/GLE-53%20-%20Process%20Mapping%20%28As-Is%29%20-%202026-03-17.md) | dispatch; requisiti storici | 0 interventi |
| Note operative/GLE-53 - Target State Design (To-Be) - 2026-03-17.md | [copia storica](legacy-obsidian/Note%20operative/GLE-53%20-%20Target%20State%20Design%20%28To-Be%29%20-%202026-03-17.md) | dispatch; requisiti storici | 0 interventi |
| Note operative/GLE-56-63 - Minimal Tech Spec (Docs Generation and READY Gate).md | [copia storica](legacy-obsidian/Note%20operative/GLE-56-63%20-%20Minimal%20Tech%20Spec%20%28Docs%20Generation%20and%20READY%20Gate%29.md) | documenti ordine; specifica storica | 0 interventi |
| Note operative/Incoming Journey - Functional Design Draft - 2026-03-18.md | [copia storica](legacy-obsidian/Note%20operative/Incoming%20Journey%20-%20Functional%20Design%20Draft%20-%202026-03-18.md) | dispatch; requisiti e avanzamento storico | 0 interventi |
| Note operative/Refactor controller ordini.md | [copia storica](legacy-obsidian/Note%20operative/Refactor%20controller%20ordini.md) | refactor sospeso dal cliente | 0 interventi |
| Queue Management - Local DEV and Production + Redis How-To.md | [copia storica](legacy-obsidian/Queue%20Management%20-%20Local%20DEV%20and%20Production%20%2B%20Redis%20How-To.md) | queue; istruzioni storiche | 1 interventi |
| Risk Recalculation - Local Commands.md | [copia storica](legacy-obsidian/Risk%20Recalculation%20-%20Local%20Commands.md) | risk; contiene sezioni CLI superate | 0 interventi |
| youtrack tags.md | [copia storica](legacy-obsidian/youtrack%20tags.md) | convenzioni tracker storiche | 0 interventi |

## Collegamenti e compatibilità

- Nessun duplicato byte-identico trovato nei Markdown preesistenti in `.ai/`,
  `.claude/` e `docs/`. Sovrapposizioni semantiche conservate come storia e
  riconciliate nella pagina corrente, senza sostituire decisioni più recenti.
- Un wikilink Obsidian interno è stato convertito in un link Markdown relativo.
  La cartella originale Metallica Marcon non contiene allegati propri.
- I riferimenti storici a `.ai/db_migration/` vanno confrontati con
  `.claude/database/`: molti script sono stati trasferiti, alcuni nomi QA/prod
  sono cambiati. Non applicare sostituzioni meccaniche o lanciare il vecchio ETL.
- `~/.claude.json`, MCP_DOCKER, branch storici e comandi PowerShell restano
  testimonianze dell'ambiente precedente. Il Codex attuale usa
  `.codex/config.toml` e RUN-021 di Developer Platform.
- La nota Risk contiene due stati successivi: il sorgente corrente definisce
  anche `risk:site` e `risk:customer` in `routes/console.php`.
- I caratteri mojibake dell'analisi effort sono conservati nel documento storico;
  i titoli correnti arrivano dallo snapshot MCP, senza reinterpretare il raw.
- Rimossi dalla copia versionabile identificatori token, email personale e
  assegnazioni credenziali anche dimostrative. Gli originali restano nel tar
  privato: nessuna informazione viene distrutta.

## Verifica dello stato

Snapshot completo di 115 issue acquisito via YouTrack MCP: 41 non risolte,
74 risolte. L'esito e le discrepanze sono in `../development-status.md`.
Non sono stati modificati ticket, codice applicativo, database o deployment.
Il completamento nel tracker non prova la presenza in produzione; la presenza
di codice non chiude automaticamente un ticket.
