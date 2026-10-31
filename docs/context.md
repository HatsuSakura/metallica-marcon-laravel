# Contesto corrente — Metallica Marcon

Aggiornato: 2026-10-31. Sintesi di sessione; non sostituisce il tracker,
le decisioni di dominio o le prove di rilascio.

## Convenzioni concordate

- Usare la skill globale `developer-platform` per organizzazione della
  conoscenza, recupero note, secret, configurazione MCP e pubblicazione KB.
- “Salva nel contesto” richiede il salvataggio del contesto utile nel progetto
  e l'aggiornamento della sintesi KB quando aggiunge conoscenza durevole.
- Le richieste “su YouTrack” o relative a YouTrack usano il collegamento MCP;
  nessun fallback REST/browser silenzioso. Il progetto corrente usa la key GLE.

## Risultati verificati

- Il normale avvio di Codex carica YouTrack attraverso `.codex/config.toml` e
  l'helper condiviso in Developer Platform. Verificato su Codex 0.166.0 con
  23 strumenti MCP disponibili. Nessun override di shell o hub Docker.
- Il vault originale completo è preservato privatamente con checksum e
  verifica di rilettura di tutti i 129 file; il vecchio disco è intatto.
- Le 20 note Markdown del progetto sono recuperate come evidenza storica,
  sanificate e indicizzate in [MIGRATION.md](recovery/MIGRATION.md).
- YouTrack letto via MCP: 115 issue, 41 aperte e 74 chiuse. La
  [riconciliazione](development-status.md) distingue stato tracker, codice,
  verifica applicativa e deployment.

## Punti aperti

- GLE-53 resta aperta: distinguere dispatch as-is consegnato dai gap
  appoggi/trasbordi GLE-90/92/93.
- GLE-73–82 sono aperte benché esistano endpoint execute, builder e altri
  componenti: verificare criteri, test e rilascio prima di cambiare stati.
- GLE-26 e GLE-95–99 sono aperte; audit infrastrutturale presente nei sorgenti,
  validazione operativa e copertura end-to-end non dimostrate qui.
- Il refactor controller ordini resta sospeso dal cliente secondo le note;
  non riavviarlo automaticamente.
- Le checkbox milestone e alcuni inventari di deploy sono storicamente
  incoerenti: esiste documentazione del deploy PROD del 15 aprile e del fix
  TLS del 15 settembre, ma questa sessione non ha verificato il runtime PROD.
- Il token YouTrack è ancora in deposito transitorio esterno al repository;
  Bitwarden non è stato configurato come fonte per questa connessione.
- Snapshot locale più vecchio disco non completano una strategia cifrata
  offsite 3-2-1. Servono destinazione, automazione e prova di ripristino.

## Prossima ripresa

Pubblicazione del 2026-10-30: evidenze di progetto, skill Developer Platform e
KB sanificata sono su GitHub. Commit KB pubblicato:
`d30e7f32af97c9195770ad7a1016e672038b3f77`.
Il primo tentativo LAN del 2026-10-30 è fallito per timeout SSH. Il 2026-10-31
la sincronizzazione è stata completata fuori casa via `jarvis-remote`:
34 documenti, 144 vettori; fingerprint degli embedding aggiornati e campione
vettori verificato da QMD Doctor. Il comando di ripresa remoto utilizzato è:

```bash
cd /home/matteo/knowledge
KNOWLEDGE_PUBLISH_SSH_TARGET=jarvis-remote ./scripts/publish.sh --sync-only d30e7f32af97c9195770ad7a1016e672038b3f77
```

Il backup cifrato offsite è rimandato esplicitamente dal proprietario:
non avviarlo come parte di questa sincronizzazione.

Partire da questa pagina e da `development-status.md`; le note in
`recovery/legacy-obsidian/` sono storia, non istruzioni correnti.
La KB centrale conserva sintesi `proposed` con fonte e commit del progetto.
Eventuali chiusure ticket, deployment e cancellazioni delle fonti recuperate
richiedono un task specifico; non sono effetti del salvataggio del contesto.
