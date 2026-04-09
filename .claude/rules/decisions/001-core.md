# Core Architectural Decisions (D1–D11)

## D1: AI outputs JSON only
Reason: safety e deterministic execution.
Il layer NLP non genera mai SQL — produce solo JSON strutturato validato backend.

## D2: Separate NLP endpoints
- `POST /api/nlp/logistics`
- `POST /api/nlp/analytics`
Reason: profili di rischio e query type diversi richiedono endpoint separati.

## D3: Log all NLP queries
Reason: audit, debugging, future training.
Ogni interazione con il layer NLP viene persistita.

## D4: MySQL retained
Reason: sufficiente per lo scale attuale.
Migration path verso PostGIS preservato via abstraction layer (GeoProvider).

## D5: Frontend renders charts
Backend restituisce dataset normalizzati + suggerimento di visualizzazione.
Reason: evitare coupling backend con specifiche librerie chart.

## D6: Single-shot first
Dialog refinement pianificato ma non incluso nell'MVP.
Reason: ridurre la complessità iniziale, validare il single-shot prima.

## D7: No runtime-compiled Vue components
Usare solo SFC `.vue` standard importati dai parent.
Non usare `template: '...'` in oggetti JS (produce problemi di rendering solo in produzione).

## D8: Dispatch as dedicated domain module (GLE-53)
- `Dashboard/Logistic.vue` rimane entry-point soltanto.
- Nuovo workflow in `Pages/LogisticDispatch/*`.
- State transitions esposte come command endpoint espliciti.
- Legacy non-production journey cargo/warehouse process rimosso, non preservato.
Reference: `002-logistic-dispatch-strategy.md`

## D9: Logistic Dispatch board isolated from GLE-55-56-63
- Branch: `feature/logistic-dispatch-board` da `main`.
- Dispatch flow come modulo dedicato (`Pages/LogisticDispatch/*`) con endpoint sotto `/api/logistic/dispatch/*`.
Reference: `003-logistic-dispatch-scaffold.md`

## D10: Status naming convention for order document lifecycle
- Enum usage rinominato: `OrderDocumentsState` → `OrderDocumentsStatus`.
- Campo rinominato: `documents_state` → `documents_status`.
- Migration di compatibilità aggiunta: `2026_03_17_180000_rename_order_documents_state_to_status`.

## D11: Source file encoding standard
Tutti i file sorgente (`.vue`, `.js`, `.ts`, `.php`, `.md`, config) devono essere salvati in UTF-8.
Reason: prevenire mojibake e stringhe UI corrotte (es. `Quantità` → `Quantit<EF><BF><BD>`).
Non usare tool/comandi che scrivono in ANSI/Windows-1252 di default.
