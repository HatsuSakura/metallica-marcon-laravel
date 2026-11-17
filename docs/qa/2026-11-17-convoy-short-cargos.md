# Cassoni del convoglio: ammessi due corti — 2026-11-17

Correzione di requisito confermata dal PO: non è obbligatorio accoppiare un
lungo e un corto; è vietato soltanto accoppiare due lunghi.

| Motrice | Rimorchio | Ammesso |
| --- | --- | --- |
| Corto | Corto | Sì |
| Corto | Lungo | Sì |
| Lungo | Corto | Sì |
| Lungo | Lungo | No |

Aggiornati VehicleConfiguration backend e journeyVehicleConfiguration frontend,
condivisi dai flussi di creazione e modifica viaggio. Rimangono motrice e cassone
obbligatori, cassone obbligatorio sul rimorchio se presente, incompatibilità dei
cassoni speciali e divieto di rimorchio per sponda/furgone.

Verifiche isolate su main: 9 test PHP, 83 asserzioni (configurazione, confine
pianificazione, salvataggi e regressione elenco ordini); test frontend sulle quattro coppie; build Vite riuscita (warning
chunk già presente), git diff --check passato. Nessuna migrazione.

Stato: hotfix preparata su main per il rilascio; deploy e collaudo QA da eseguire.
