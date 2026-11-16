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

## OP-002 — Reinvio verifica dopo cambio email

- **Stato:** requisito raccolto dal PO durante il collaudo del 2026-11-12; implementato localmente il 2026-11-13 come GLE-127, in attesa di rilascio QA e ricollaudo.
- **Requisito:** dopo la modifica dell'indirizzo email di un utente deve essere possibile reinviare la mail di verifica al nuovo indirizzo, anche se il precedente risultava già verificato.
- **Evidenza QA:** `UserController::update` aggiorna l'indirizzo senza invalidare `email_verified_at`; `API_UserResetAndResendController::resendVerification` non invia se l'utente risulta già verificato. Comportamento confermato leggendo il codice sul server di collaudo il 2026-11-12.
- **Accettazione:** cambiare l'indirizzo di un utente già verificato e richiedere il reinvio deve produrre la mail di verifica per il nuovo indirizzo. Il reset password resta un'operazione distinta.
- **Perimetro della sessione:** solo appunto e diagnosi in lettura; nessuna modifica applicativa, invio email o modifica tracker.

Aggiornamento OP-002 del 2026-11-13: il PO ha confermato anche l’invio automatico al cambio effettivo dell’indirizzo. Evidenze e limiti nella [nota post-UAT](qa/2026-11-13-driver-uat-followup.md).
