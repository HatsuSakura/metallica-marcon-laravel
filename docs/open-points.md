# Punti aperti da chiarire con Metallica Marcon

Registro corrente delle domande al cliente. Le note recuperate in
`docs/recovery/legacy-obsidian/` rimangono fonti storiche, non questo registro.

## OP-001 — Riordino tappe da autista prima della partenza

- **Stato:** risolto dal PO il 2026-11-09; implementato localmente, non ancora pubblicato in QA.
- **Riferimenti:** GLE-120, GLE-122, confine pianificazione/esecuzione alla prima partenza.
- **Decisione:** il pianificato rimane modificabile dalla Logistica. L'autista non può riordinare prima dell'avvio effettivo.
- **UX:** «Riordina» è disponibile anche prima della partenza. Propone «Annulla» oppure «Avvia e riordina», spiegando che viene registrato l'inizio effettivo e che le correzioni al piano vanno richieste alla Logistica.
- **Effetto:** solo dopo un avvio riuscito si apre l'editor della sequenza operativa. Il piano originale è congelato dal comando di partenza esistente. Un avvio bloccato non cambia sequenza né crea baseline.
- **Nessun nuovo processo di approvazione:** non è prevista una proposta dell'autista da approvare prima della partenza.

Verifiche e scenari aggiuntivi: [note sede e avvio per riordino](qa/2026-11-09-driver-notes-reorder.md).
