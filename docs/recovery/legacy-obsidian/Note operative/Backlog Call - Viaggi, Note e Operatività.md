---
title: Backlog Call - Viaggi, Note e Operatività
date: 2026-03-11
project: GLE
status: historical
source: appunti riunione
recovered_on: 2026-10-30
recovery_status: historical
---

> **Nota recuperata — storica, non procedura corrente.** Origine: vecchio vault Obsidian; recupero 2026-10-30. Per lo stato attuale consultare [sviluppi e riconciliazione](../../../development-status.md). Non eseguire comandi di deploy, migrazione o modifica dati sulla sola base di questa nota.


# Contesto
Raccolta strutturata degli appunti emersi in call, da rifinire prima della creazione issue YouTrack.

# Decisioni emerse
- Introdurre campi note su più entità operative.
- Mostrare le note in dashboard viaggio lato Driver.
- Introdurre nuova dashboard gestione viaggi lato Logistica.
- Gestire ritiri speciali con opzione `data fissa` (nullable, default `null`).
- Migliorare tracciabilità legacy (`legacy_code`) su Order Index/Edit.
- Migliorare UX filtri mappa con invio esplicito (`Vai!`).

# Requisiti funzionali

## RF-01 Aggiunta campi note (issue madre)
- Introdurre gestione note su entità core.
- Questa RF è la madre delle seguenti figlie.

### RF-01.1 Note Cliente
- Aggiungere campo `note` in creazione/modifica Cliente.
- Persistenza e visualizzazione coerente nelle viste Cliente.

### RF-01.2 Note Sede
- Aggiungere campo `note` in creazione/modifica Sede.
- Persistenza e visualizzazione coerente nelle viste Sede.

### RF-01.3 Note Ordine
- Aggiungere campo `note` in creazione/modifica Ordine.
- Persistenza e visualizzazione coerente nelle viste Ordine.

### RF-01.4 Note Viaggio
- Aggiungere campo `note` in creazione/modifica Viaggio.
- Persistenza e visualizzazione coerente nelle viste Viaggio.

## RF-02 Visibilità note in dashboard viaggio Driver
- In dashboard viaggio (Driver) mostrare:
- A livello viaggio: note viaggio
- Nel dettaglio tappa: note cliente, note sede, dettaglio item ordine, note ordine

## RF-03 Dashboard gestione viaggi Logistica
- Creare dashboard dedicata alla Logistica per gestire tipologie operative:
- Scarico
- Doppio scarico
- Magazzino
- Altri casi operativi tracciati nel flowchart

## RF-04 Ordini speciali con data fissa
- Nella create/update Ordine aggiungere toggle `Data fissa`.
- Se toggle attivo, abilitare inserimento data+ora.
- Campo nullable con default `null`.
- Caso d’uso principale: ritiri speciali (es. Finanza).

## RF-05 Gestione Sedi cliente
- Supportare aggiunta e modifica sede per ogni cliente.
- Già gestita con issue GLE-48, GLE-49, GLE-50.

## RF-06 Inserimento manuale ritiro effettuato
- Aggiungere funzione per registrare manualmente un ritiro effettuato su una sede.
- Obiettivo: retrocompatibilità con processo legacy.

## RF-07 PDF tabella Ordine
- In Ordine Create/Update/Show aggiungere pulsante per generare PDF stampabile della tabella item.
- Il PDF deve includere CER code e gli altri attributi item.

## RF-08 PDF tabella Ordine anche da interfaccia viaggio
- In interfaccia viaggio, per ogni ordine, rendere scaricabile il PDF tabella item.
- RF figlia di RF-07.

## RF-09 Esposizione `legacy_code` Ordine
- Tipo classificato: Bug.
- Mostrare `legacy_code` in pagina Order Index.
- Mostrare `legacy_code` in pagina Edit Ordine.

## RF-10 Filtri mappa con invio esplicito
- Cambiare logica da reattiva immediata a invio manuale.
- Aggiungere pulsante `Vai!` che applica i filtri.

# Punti da chiarire
- Formato e limiti campo note (lunghezza, supporto multilinea, eventuale markdown).
- Permessi di visualizzazione/modifica note per ruolo.
- Layout definitivo dashboard Logistica e stati/azioni disponibili.
- Template PDF richiesto (branding, ordinamenti, raggruppamenti, firma).
- Tracciamento audit su inserimento manuale ritiro (chi/quando/perché).

# Candidate issue (bozza)
| Ref | Summary proposta | Tipo | Stato iniziale | Parent | YouTrack ID |
|---|---|---|---|---|---|
| RF-01 | Aggiunta campi note su entità core | Feature | Completato | - | GLE-51 |
| RF-01.1 | Aggiunta note su Cliente | Feature | Completato | RF-01 | GLE-59 |
| RF-01.2 | Aggiunta note su Sede | Feature | Completato | RF-01 | GLE-60 |
| RF-01.3 | Aggiunta note su Ordine | Feature | Completato | RF-01 | GLE-61 |
| RF-01.4 | Aggiunta note su Viaggio | Feature | Completato | RF-01 | GLE-62 |
| RF-02 | Visualizzazione note in dashboard Viaggio lato Driver | Feature | Completato | - | GLE-52 |
| RF-03 | Nuova dashboard gestione Viaggi per Logistica | Feature | Da fare | - | GLE-53 |
| RF-04 | Ordini speciali con data fissa (data+ora) | Feature | Da fare | - | GLE-54 |
| RF-05 | Supporto aggiunta/modifica Sedi da scheda Cliente | Feature | Completato (GLE-48, GLE-49, GLE-50) | - | GLE-48, GLE-49, GLE-50 |
| RF-06 | Inserimento manuale ritiro effettuato per retrocompatibilità | Feature | Completato | - | GLE-55 |
| RF-07 | Generazione PDF stampabile tabella item ordine | Feature | Da fare | - | GLE-56 |
| RF-08 | Download PDF tabella item da interfaccia Viaggio | Feature | Da fare | RF-07 | GLE-63 |
| RF-09 | Esposizione legacy_code in Order Index/Edit | Bug | Completato | - | GLE-57 |
| RF-10 | Filtri mappa con pulsante Vai (no reattivo immediato) | Feature | Completato | - | GLE-58 |
