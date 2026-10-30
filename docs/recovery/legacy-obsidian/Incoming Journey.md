
> **Nota recuperata — storica, non procedura corrente.** Origine: vecchio vault Obsidian; recupero 2026-10-30. Per lo stato attuale consultare [sviluppi e riconciliazione](../../development-status.md). Non eseguire comandi di deploy, migrazione o modifica dati sulla sola base di questa nota.


## Requisito UI integrata (fusione logica JourneyCargo + Dispatch Workspace)

- La vecchia UI `/journeyCargo/{journey}/create` aveva un vantaggio chiave: dettagliare Motrice e Rimorchio separatamente.
- Per ciascun cargo (Motrice/Rimorchio) deve essere selezionabile il magazzino effettivo di scarico.
- Il magazzino effettivo di scarico dei cargo puo non coincidere col magazzino di destinazione pianificato degli item.
- Se i due cargo scaricano nello stesso magazzino, non serve distinguere ordine di scarico; se scaricano in magazzini diversi, serve ordine/sequenza.
- Per ciascun cargo/magazzino va definito il tipo operazione: scarico oppure appoggio.
- Per ogni item va verificata la coerenza tra destinazione pianificata e magazzino effettivo di scarico.
- Se non coincide, l'interfaccia deve permettere una decisione esplicita:
  - doppio scarico (solo se il secondo magazzino coincide con quello pianificato item), oppure
  - scarico con messa a terra e possibile trasbordo successivo.
- Queste decisioni devono convivere in un'unica interfaccia operativa, senza frammentazione tra step separati.

Nota operativa: prima di rifinire il requisito, verificare e ripristinare il funzionamento della vecchia pagina `/journeyCargo/{journey}/create` (caso test: `/journeyCargo/37/create`), con focus su caricamento item nelle liste draggable.

## Documentazione tecnica finale - Dispatch rientro (v1)

### Livello 1 - Human readable

Obiettivo
- Unificare le decisioni operative di rientro in un flusso coerente: censimento reale, configurazione convoglio, gestione mismatch item, conferma baseline.

Scelte chiave implementate
- Step 1: Censimento e split reale per item.
- Step 2 (unificato): Operativita convoglio + istruzioni + eccezioni/trasbordi + gestione mismatch.
- Il magazzino del cargo (`journey_cargos.warehouse_id`) rappresenta semanticamente il magazzino effettivo di scarico del cargo.
- Per ogni cargo e selezionabile modalita operativa:
  - `unload` (scarico)
  - `drop_only` (appoggio)

Regola mismatch
- Un item e in mismatch quando:
  - ha allocazione `actual` su un cargo,
  - il magazzino pianificato item (`order_items.warehouse_id`) e diverso dal magazzino effettivo del cargo (`journey_cargos.warehouse_id`).
- In caso di mismatch, decisione obbligatoria:
  - `double_unload` con magazzino secondario obbligatorio e diverso dall'effettivo cargo,
  - `grounding` (messa a terra).

Conferma baseline (hard rules)
- Non confermabile se:
  - mancano allocazioni reali,
  - mancano istruzioni di scarico per allocazione,
  - split non coerente con censimento,
  - esiste mismatch senza decisione,
  - `double_unload` senza secondario valido.

Nota naming
- Non e stato fatto rename DB da `warehouse_id` a `effective_warehouse_id` per minimizzare rischio e impatto.
- Nel dominio UI/API, `warehouse_id` del cargo va interpretato come effettivo.

### Livello 2 - AI / Markdown readable

Data model
- `journey_cargos`
  - aggiunta colonna: `operation_mode` enum(`unload`,`drop_only`) default `unload`
- nuova tabella `journey_cargo_mismatch_decisions`
  - `id`
  - `journey_id` FK -> journeys (cascade)
  - `journey_cargo_id` FK -> journey_cargos (cascade)
  - `order_item_id` FK -> order_items (cascade)
  - `decision` enum(`double_unload`,`grounding`)
  - `secondary_warehouse_id` nullable FK -> warehouses
  - `created_by_user_id` nullable FK -> users
  - `updated_by_user_id` nullable FK -> users
  - unique(`journey_cargo_id`,`order_item_id`)

Workspace API contract (aggiornato)
- `GET /api/logistic-dispatch/{journey}/workspace`
  - include: `cargos` (con `operation_mode`), `mismatch_decisions`
- `PUT /api/logistic-dispatch/{journey}/cargos/save`
  - payload cargo supporta `operation_mode`
  - `is_grounded` sincronizzato da `operation_mode == drop_only`
- `PUT /api/logistic-dispatch/{journey}/workspace/save`
  - supporta `mismatch_decisions[]`:
    - `journey_cargo_id`
    - `order_item_id`
    - `decision` (`double_unload|grounding`)
    - `secondary_warehouse_id` (obbligatorio per `double_unload`)
- `POST /api/logistic-dispatch/{journey}/confirm`
  - valida anche copertura e coerenza di tutte le mismatch decisions richieste.

Business validation internals
- calcolo mismatch su allocazioni `actual`:
  - join logico tra `journey_cargo_allocations(source=actual)` + `journey_cargos.warehouse_id` + `order_items.warehouse_id`
- pair key mismatch: `{journey_cargo_id}:{order_item_id}`
- per ogni pair mismatch, deve esistere decisione in `journey_cargo_mismatch_decisions`.

UI note implementative (DispatchWorkspacePanel)
- Step ridotti a 2:
  - `1. Censimento e split`
  - `2. Operativita convoglio`
- Nel blocco item/cargo dello step 2, se mismatch:
  - select decisione
  - select secondo magazzino se `double_unload`
- payload save workspace include `mismatch_decisions` generato da righe mismatch.

### Update regola Primario/Secondario (2026-03-20)

Decisione
- `primary_warehouse_id` e `secondary_warehouse_id` non sono piu input manuali UI.

Determinazione automatica
- Derivazione da `journey_cargos`:
  - usa `warehouse_id` (magazzino effettivo) e `download_sequence`.
- Se i magazzini effettivi sono uguali:
  - `primary = quel magazzino`, `secondary = null`.
- Se diversi:
  - `primary = magazzino con sequence 1`
  - `secondary = magazzino con sequence 2`

Implementazione
- UI: rimossi selector manuali primario/secondario in step convoglio.
- Backend (`upsertCargos`): dopo salvataggio cargo aggiorna automaticamente `journeys.primary_warehouse_id` e `journeys.secondary_warehouse_id`.
