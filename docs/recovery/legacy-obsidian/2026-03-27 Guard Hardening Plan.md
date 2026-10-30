
> **Nota recuperata — storica, non procedura corrente.** Origine: vecchio vault Obsidian; recupero 2026-10-30. Per lo stato attuale consultare [sviluppi e riconciliazione](../../development-status.md). Non eseguire comandi di deploy, migrazione o modifica dati sulla sola base di questa nota.

# Guard Hardening Plan

Data: 2026-03-27
Status: In esecuzione

## Obiettivo
Rendere effettiva la protezione di tutte le chiamate backend: route middleware, policy di ruolo/ownership, authorize espliciti sugli endpoint.

## Blocchi
1. Route middleware hardening
2. Policy ruoli / least privilege
3. Authorize espliciti nei controller API

## Stato attuale
- Blocco 1 eseguito: chiusi i buchi principali su `journey`, `api`, `warehouse-manager`, `journeyCargo` e gruppi area con gate (`accessBackofficeArea`, `accessWarehouseArea`, `accessDriverArea`).
- Blocco 2 eseguito: introdotto nucleo condiviso ruoli/ownership per policy; strette le policy permissive; aggiunte policy esplicite per `CatalogItem` e `Recipe`.
- Blocco 3 eseguito: aggiunti `Gate::authorize(...)` / `$this->authorize(...)` sugli endpoint API e su controller web sensibili (catalogo, ricette, magazzino, utenti, ordini, viaggi, customer risk).

## Note importanti
- Corretto bug strutturale: alcuni controller avevano `__consruct` invece di `__construct`, quindi `authorizeResource()` non veniva mai attivato.
- Verifiche eseguite: `php -l` sui file modificati e `route:list -v` per confermare i middleware risultanti.

## Residui da monitorare
- Affinare eventualmente la separazione tra aree backoffice/logistica e aree warehouse se il processo richiede una segmentazione ancora più stretta.
- Valutare in un secondo step i controller legacy meno usati che non sono ancora stati portati a uno schema uniforme `viewAny/view/create/update/delete`.
