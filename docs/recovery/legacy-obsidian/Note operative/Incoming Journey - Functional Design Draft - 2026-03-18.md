
> **Nota recuperata — storica, non procedura corrente.** Origine: vecchio vault Obsidian; recupero 2026-10-30. Per lo stato attuale consultare [sviluppi e riconciliazione](../../../development-status.md). Non eseguire comandi di deploy, migrazione o modifica dati sulla sola base di questa nota.

in ch# Incoming Journey - Functional Design Draft

Date: 2026-03-18
Status: Draft v0.1 (to refine with full process walkthrough)
Scope: Post-"Journey Start" to unload completion, replacing Excel-driven operational phase with integrated digital flow.

## Problem framing
Planning assumptions made before departure often diverge from reality:
- more/fewer holders than expected
- different real item dimensions/weights
- stop sequence changes in the field
- other execution-time surprises

Current risk: operational unload instructions become inaccurate for warehouse workers.

## Core functional objective
After pickup and before unload, model the real cargo composition with enough precision to guide warehouse execution (where each quantity is physically located and where it must be unloaded).

## Key design points (initial)
1. Introduce explicit per-item allocation rows
- Model allocation as first-class records (not only flags), so one `OrderItem` can be split across front truck/trailer with quantities.
- Suggested fields: `journey_cargo_id`, `order_item_id`, `allocated_holder_qty`, `allocated_weight`, `source` (`planned|actual`), `notes`.

2. Add reconciliation step between planned vs actual pickup
- Logistics/driver confirms real quantities after field reality is known.
- System records deltas from planned values and keeps traceability.

3. Keep clear domain separation
- `JourneyCargo`: physical container context (front truck/trailer and related unload metadata).
- Allocation rows: quantitative truth (how much of each `OrderItem` is in each cargo).

4. Decouple concepts currently mixed under "double load"
- Physical placement (truck/trailer split) must be independent from unload strategy (single unload, double unload, temporary storage, overnight hold).

5. Enforce hard validation invariants
- Sum of allocations per `OrderItem` <= actual loaded quantity.
- Optional strict close rule: journey cannot close with unresolved allocation mismatch.
- Prevent impossible combinations at plan save time.

6. Integrate this directly into the dispatch workflow UI
- One office workflow should cover: dispatch strategy + real cargo allocation + unload destination plan.
- Avoid parallel legacy screens and Excel side-processes.

7. Optimize warehouse-facing operational output
- Warehouse view must show: exact quantity, physical location (truck/trailer), unload sequence/destination, and exception flags.
- Goal: reduce search time and mistakes during unload.

## Explicit product direction (confirmed)
- Backward compatibility with legacy planning flow is NOT required.
- We can design a clean target model and process, then implement directly.

## Next workshop step
Collect full "coming-home" journey process from operations and refine:
- actors and responsibilities
- states and handoffs
- exceptions and recovery paths
- mandatory data capture at each step
- acceptance criteria for "solid and functional" go-live

---

## Processo reale (raccolta operativa 2026-03-18)

### Contesto operativo
Quando il driver rientra verso i magazzini chiama la Logistica. L'operatore decide le modalita operative in base a:
- ETA del convoglio
- carico dei 3 magazzini
- coda operativa
- eventi locali (es. conferimenti privati)

Il convoglio include sempre motrice e spesso rimorchio.

### Variabilita reale del carico
Il piano iniziale puo divergere dalla realta:
- numero contenitori diverso dal previsto
- materiali extra/non dichiarati
- ripartizione fisica reale diversa da quella pianificata
- eventuale split dello stesso materiale tra motrice e rimorchio

La logistica, su chiamata, censisce eccezioni e registra la ripartizione reale indicata dal driver.

### Decisioni post-ripartizione
Dopo la ripartizione reale degli `OrderItem` tra motrice/rimorchio, la logistica definisce:
- ordine di scarico (`1°` / `2°`)
- modalita per ciascun contenitore di carico: `solo appoggio` oppure `svuotamento`
- tipo svuotamento: `scarico semplice` o `doppio scarico`

Note operative:
- Il doppio scarico puo valere anche con sola motrice (un solo contenitore fisico, due magazzini di scarico).
- `Solo appoggio` implica messa a terra/parcheggio e gestione entro 24h.

### Destinazione pianificata vs effettiva
Per ogni `OrderItem` convivono due valori:
- `Destinazione Pianificata` (da pianificazione viaggio)
- `Scarico Effettivo` (dove il materiale viene realmente scaricato)

Se diversi, nasce bisogno di `trasbordo` (journey dedicato a spostare materiale tra magazzini).

### Vista warehouse
Il magazzino opera per `OrderItem` ma riconcilia sempre per `Order/Cliente`:
- pesatura, selezione, classificazione, stoccaggio
- tracciabilita completa origine->quantita->cliente (audit/valutazione qualità cliente)

## Direzione architetturale proposta (process-first)

### JourneyCargo: tenere o rimuovere?
Risposta: mantenere il concetto, ma ridefinirlo.

- Non usarlo piu come contenitore di tutta la verita operativa.
- Usarlo come entita "contenitore fisico di viaggio" (motrice/rimorchio, ordine di scarico, stato operativo).
- Introdurre una entita di allocazione quantitativa per rappresentare split reali dello stesso item.

Motivo: serve modellare in modo nativo casi come "5 casse stesso item: 3 motrice, 2 rimorchio" senza workaround.

### Unita funzionali consigliate
1. `JourneyCargo` (contenitore fisico)
- identifica motrice/rimorchio, sequenza scarico, modalita appoggio/svuotamento, SLA appoggio.

2. `JourneyCargoAllocation` (verita quantitativa)
- associa `OrderItem` al contenitore con quantita/peso effettivi.
- permette split su piu contenitori.

3. `UnloadInstruction` (istruzione di scarico)
- per allocation o per sotto-quantita: magazzino effettivo + ordine scarico.
- supporta scarico semplice e doppio scarico.

4. `TransshipmentNeed` (fabbisogno trasbordo)
- nasce quando scarico effettivo != destinazione pianificata.
- alimenta la pianificazione journey di trasbordo.

## Stati operativi suggeriti (alto livello)
- `in_rientro`
- `in_valutazione_logistica`
- `piano_confermato`
- `solo_appoggio` (con `due_by` <= 24h)
- `in_svuotamento`
- `svuotato`
- `trasbordo_richiesto` (flag/evento derivato)
- `chiuso`

## Implicazioni UX
Un'unica dashboard logistica deve coprire:
- viaggi in rientro / da pianificare
- ripartizione reale item tra motrice/rimorchio
- istruzioni di scarico (semplice/doppio, ordine, warehouse)
- messa a terra e relativa coda/sla
- coda fabbisogni trasbordo + creazione journey trasbordo

## Domande aperte da confermare con business
1. Lo split va modellato in "numero contenitori", "peso", o entrambi?
2. Il trasbordo va creato automaticamente o solo proposto alla logistica?
3. SLA solo appoggio: hard stop a 24h o eccezioni autorizzabili?
4. Chi puo modificare la ripartizione reale dopo conferma (solo logistica? anche warehouse)?
5. In audit, quale evento blocca la storicizzazione finale del carico reale?

---

## Decisioni confermate (workshop 2026-03-18)

1. Split operativo
- Lo split e governato per contenitori.
- Il peso viene ripartito in modo stimato per contenitore:
  - esempio: 3 casse, 600kg => 200kg/cassa stimati
  - se split 1 truck + 2 trailer => 200kg truck, 400kg trailer

2. Trasbordo
- `effettivo != pianificato` genera proposta, non automazione diretta.
- Gli item vengono flaggati come `proposti_per_trasbordo` e accumulati in una coda dedicata.
- Solo operatore Logistica puo creare/organizzare journey di trasbordo.
- Warehouse non puo creare journey di trasbordo.

3. Permessi post-conferma logistica
- Warehouse non modifica allocazioni.
- Warehouse esegue e puo registrare eccezioni.

4. Solo appoggio (SLA 24h)
- Niente blocco hard.
- Solo alert/escalation.

---

## Caso errore umano in scarico (nuovo requisito)

Scenario:
- Item previsto per warehouse A viene scaricato per errore in warehouse B.
- Non era pianificato come doppio scarico/trasbordo.

Regola operativa:
- L'errore deve essere registrabile senza bloccare il processo.
- L'item puo:
  - essere proposto a trasbordo verso A, oppure
  - restare stoccato in B se l'overlap operativo lo consente.

## Decisione audit/freeze (proposta allineata al processo)

Direzione preferita: freeze finale a chiusura journey.

Per mantenere governance forte durante l'operativita quotidiana:
- introdurre un "freeze operativo" (soft) alla conferma logistica: il piano e baseline.
- consentire dopo baseline solo modifiche classificate come:
  - `eccezione_warehouse`
  - `correzione_scarico`
  - `deviazione_destinazione`
- tutte append-only su event log, con autore, timestamp, motivo.
- freeze audit definitivo a `journey chiuso`.

Risultato:
- massima aderenza al real world (errore umano gestibile)
- tracciabilita forte per analisi qualità e responsabilita
- nessun blocco hard in operativita

---

## Governance ruoli warehouse (confermata)

Ruoli warehouse:
- `warehouse_chief`
- `warehouse_manager`
- `warehouse_worker`

Permessi su entita operative alto livello (`Order`, `Journey`, `JourneyCargo`):
- consentiti solo a `warehouse_chief` e `warehouse_manager`
- `warehouse_worker` non puo operare a questo livello

Permessi su `OrderItem` (fase operativa di campo):
- `warehouse_chief` e `warehouse_manager`: consentito
- `warehouse_worker`: consentito per pesatura e classificazione

Nota design:
- mantenere separato il perimetro "governo flusso" (journey/cargo/decisioni) dal perimetro "esecuzione item" (peso/classifica/eccezioni operative).

---

## Matrice operativa (evento -> ruolo -> azione)

| Fase / Evento                                     | Driver                                              | Logistica                                                 | Warehouse Chief/Manager              | Warehouse Worker             | Output/Note                                      |
| ------------------------------------------------- | --------------------------------------------------- | --------------------------------------------------------- | ------------------------------------ | ---------------------------- | ------------------------------------------------ |
| Journey in rientro (pre-chiamata)                 | Solo consultazione stato viaggio                    | Monitora coda e carico magazzini                          | Solo consultazione                   | Solo consultazione           | Stato operativo: `in_rientro`                    |
| Telefonata driver -> logistica                    | Comunica carico reale, eccezioni, split contenitori | Registra dichiarazioni driver                             | Nessuna azione                       | Nessuna azione               | Apertura fase `in_valutazione_logistica`         |
| Censimento eccezioni carico                       | Fornisce dettagli                                   | Aggiorna variazioni item/quantita rispetto al pianificato | Nessuna azione                       | Nessuna azione               | Eventi append-only di variazione                 |
| Ripartizione reale item (truck/trailer)           | Nessuna modifica diretta (oggi)                     | Definisce allocazioni reali per contenitori               | Nessuna azione                       | Nessuna azione               | Crea/aggiorna `JourneyCargoAllocation`           |
| Definizione ordine scarico (1°/2°)                | Nessuna azione                                      | Decide sequenza in base a vincoli reali                   | Puo consultare                       | Nessuna azione               | Regola su motrice/rimorchio e trailer scarrabile |
| Scelta modalita (`solo appoggio` / `svuotamento`) | Nessuna azione                                      | Decide e conferma piano                                   | Puo consultare                       | Nessuna azione               | Baseline operativa (`soft freeze`)               |
| Piano dispatch confermato                         | Visualizza istruzioni                               | Conferma piano e lock logico                              | Visualizza istruzioni                | Visualizza istruzioni        | Da qui solo eccezioni/correzioni append-only     |
| Esecuzione scarico warehouse                      | Nessuna azione                                      | Supervisiona                                              | Esegue, puo registrare eccezioni     | Esegue task operativi item   | Stato: `in_svuotamento`                          |
| Pesatura e classificazione item                   | Nessuna azione                                      | Nessuna azione                                            | Consentito                           | Consentito                   | Ambito `OrderItem`                               |
| Errore di scarico (A previsto, B effettivo)       | Nessuna azione                                      | Valuta esito (trasbordo o permanenza in B)                | Registra eccezione operativa         | Registra evidenza operativa  | Evento `deviazione_destinazione`                 |
| Proposta trasbordo (effettivo != pianificato)     | Nessuna azione                                      | Crea/conferma proposta                                    | Puo consultare coda proposte         | Puo consultare coda proposte | Flag `proposti_per_trasbordo`                    |
| Creazione journey di trasbordo                    | Nessuna azione                                      | Consentito (solo Logistica)                               | Non consentito                       | Non consentito               | Nuovo journey specializzato                      |
| Solo appoggio oltre soglia 24h                    | Nessuna azione                                      | Riceve alert/escalation                                   | Riceve alert/escalation              | Nessuna azione               | No blocco hard                                   |
| Chiusura journey                                  | Nessuna azione                                      | Conferma chiusura processo                                | Puo completare step operativi finali | Nessuna azione               | `freeze audit definitivo`                        |

### Regole trasversali
1. Dopo conferma logistica non si riscrive il piano: solo eventi di eccezione/correzione append-only.
2. `Chief/Manager` governano `Order/Journey/JourneyCargo`.
3. `Worker` opera su `OrderItem` per pesatura/classificazione, non su governance journey/cargo.
4. Trasbordi pianificabili solo da Logistica.

---

## Principio UI/UX confermato: componenti riusabili e role-aware

I 3 step:
- Journey in rientro (pre-chiamata)
- Telefonata driver -> logistica
- Censimento eccezioni carico

devono essere implementati con componenti riusabili e non hardcoded sul solo utente Logistica.

### Obiettivo
- Oggi: operativita principalmente Logistica.
- Domani: possibilita di delegare al Driver il censimento carico su `Order/OrderItem` e la disposizione su `Journey/JourneyCargo`.

### Regola tecnica di progettazione
- Un unico set di componenti "operativi" (es. `CargoReturnContext`, `LoadCensusEditor`, `CargoAllocationEditor`).
- Abilitazioni per ruolo/capability (non per pagina duplicata):
  - `can_read_return_context`
  - `can_edit_load_census`
  - `can_edit_cargo_allocation`
  - `can_confirm_dispatch_plan`
- Le pagine/dashboard diverse (Logistica oggi, Driver domani) compongono gli stessi componenti con capability differenti.

### Vincolo
- Evitare fork di UI/logic tra dashboard Logistica e dashboard Driver.
- Stessa semantica dati/eventi, diverso perimetro di permessi.

### Estensione esplicita: ripartizione reale item (truck/trailer)
Anche la fase di `Ripartizione reale item (truck/trailer)` deve usare componenti riusabili role-aware.

- Componente target: `CargoAllocationEditor` (riusabile).
- Stessa semantica dati/eventi per tutte le dashboard.
- Permessi per capability:
  - Oggi: edit principalmente Logistica.
  - Domani: edit Driver se abilitato.
  - Warehouse: default consultazione + registrazione eccezioni operative (no modifica allocazione baseline, salvo policy future).

Regola: niente implementazioni duplicate tra dashboard; un solo flusso, permessi diversi.

---

## API strategy ridotta (human-readable, v1)

Principio:
- pochi endpoint aggregati
- payload espliciti
- semantica orientata al processo
- no frammentazione prematura

### Endpoint v1
1. `GET /api/logistic/dispatch/{journey}/workspace`
- Carica un unico workspace con:
  - contesto journey
  - censimento carico reale
  - allocazioni truck/trailer
  - istruzioni scarico
  - eccezioni/eventi recenti

2. `PUT /api/logistic/dispatch/{journey}/workspace`
- Salva bozza operativa in un unico payload:
  - census
  - allocations
  - unload_instructions
- Obiettivo: supportare UI a componenti riusabili senza moltiplicare chiamate.

3. `POST /api/logistic/dispatch/{journey}/confirm`
- Conferma baseline operativa (soft freeze).
- Dopo conferma: solo eccezioni/correzioni append-only.

4. `POST /api/logistic/dispatch/{journey}/events`
- Registra eventi operativi append-only:
  - eccezione warehouse
  - deviazione destinazione
  - proposta trasbordo

### Endpoint extra (solo quando necessari)
5. `POST /api/logistic/transshipments/{id}/approve`
- Solo logistica, approvazione proposta trasbordo.

6. `POST /api/logistic/dispatch/{journey}/close`
- Chiusura journey e freeze audit definitivo.

## Regole di leggibilita codice/API
- Naming esplicito e consistente con il linguaggio operativo.
- Request DTO semplici e documentati.
- Validation in classi dedicate con messaggi leggibili.
- Service layer lineare (metodi piccoli, responsabilita chiare).
- Event payload chiari, append-only, facilmente ispezionabili.

---

## Mini-contratti API (request/response JSON)

### 1) GET `/api/logistic/dispatch/{journey}/workspace`
Response `200`:
```json
{
  "journey": {
    "id": 123,
    "status": "logistics_review",
    "driver": { "id": 8, "name": "Mario Rossi" },
    "eta_at": "2026-03-18T15:30:00Z"
  },
  "census": {
    "items": [
      {
        "order_item_id": 9001,
        "order_id": 501,
        "customer_name": "Cliente Alfa",
        "planned_containers": 4,
        "actual_containers": 5,
        "total_weight_kg": 600.0,
        "notes": "2 casse extra"
      }
    ]
  },
  "cargos": [
    {
      "id": 11,
      "cargo_location": "vehicle",
      "planned_unload_sequence": 1,
      "operation_mode": "unload"
    },
    {
      "id": 12,
      "cargo_location": "trailer",
      "planned_unload_sequence": 2,
      "operation_mode": "drop_only",
      "drop_due_at": "2026-03-19T12:00:00Z"
    }
  ],
  "allocations": [
    {
      "id": 7001,
      "journey_cargo_id": 11,
      "order_item_id": 9001,
      "allocated_containers": 3,
      "estimated_weight_kg": 360.0,
      "source": "actual"
    },
    {
      "id": 7002,
      "journey_cargo_id": 12,
      "order_item_id": 9001,
      "allocated_containers": 2,
      "estimated_weight_kg": 240.0,
      "source": "actual"
    }
  ],
  "unload_instructions": [
    {
      "id": 8001,
      "allocation_id": 7001,
      "target_warehouse_id": 1,
      "instruction_type": "simple",
      "unload_sequence": 1,
      "planned_target_warehouse_id": 1,
      "proposed_for_transshipment": false
    }
  ],
  "events": [
    {
      "id": 10001,
      "event": "load_census_updated",
      "at": "2026-03-18T14:55:00Z",
      "by_user": "logistic.user"
    }
  ]
}
```

### 2) PUT `/api/logistic/dispatch/{journey}/workspace`
Request:
```json
{
  "census": {
    "items": [
      {
        "order_item_id": 9001,
        "actual_containers": 5,
        "total_weight_kg": 600.0,
        "notes": "2 casse extra"
      }
    ]
  },
  "allocations": [
    {
      "journey_cargo_id": 11,
      "order_item_id": 9001,
      "allocated_containers": 3,
      "source": "actual"
    },
    {
      "journey_cargo_id": 12,
      "order_item_id": 9001,
      "allocated_containers": 2,
      "source": "actual"
    }
  ],
  "unload_instructions": [
    {
      "order_item_id": 9001,
      "journey_cargo_id": 11,
      "target_warehouse_id": 1,
      "instruction_type": "simple",
      "unload_sequence": 1,
      "planned_target_warehouse_id": 1
    }
  ],
  "cargo_settings": [
    {
      "journey_cargo_id": 11,
      "planned_unload_sequence": 1,
      "operation_mode": "unload",
      "drop_due_at": null
    },
    {
      "journey_cargo_id": 12,
      "planned_unload_sequence": 2,
      "operation_mode": "drop_only",
      "drop_due_at": "2026-03-19T12:00:00Z"
    }
  ]
}
```
Response `200`:
```json
{
  "message": "Workspace salvato",
  "journey_id": 123,
  "totals": {
    "items": 14,
    "allocations": 20,
    "instructions": 20
  }
}
```

Validation minime:
- `allocated_containers > 0`
- somma allocazioni per `order_item_id` <= `actual_containers`
- se `operation_mode=drop_only` allora `drop_due_at` obbligatorio

### 3) POST `/api/logistic/dispatch/{journey}/confirm`
Request:
```json
{
  "notes": "Piano confermato con chiamata driver 15:05"
}
```
Response `200`:
```json
{
  "message": "Baseline confermata",
  "journey": {
    "id": 123,
    "status": "dispatch_planned",
    "baseline_confirmed_at": "2026-03-18T15:06:00Z"
  }
}
```

Validation minime:
- almeno 1 allocation presente
- almeno 1 instruction presente
- journey in stato `logistics_review`

### 4) POST `/api/logistic/dispatch/{journey}/events`
Request (eccezione warehouse):
```json
{
  "event": "warehouse_exception_logged",
  "payload": {
    "order_item_id": 9001,
    "from_warehouse_id": 1,
    "to_warehouse_id": 2,
    "reason": "congestione magazzino A",
    "action": "stored_in_B"
  }
}
```
Request (proposta trasbordo):
```json
{
  "event": "transshipment_proposed",
  "payload": {
    "order_item_id": 9001,
    "from_warehouse_id": 2,
    "to_warehouse_id": 1,
    "containers": 2,
    "estimated_weight_kg": 240.0,
    "reason": "destinazione effettiva diversa da pianificata"
  }
}
```
Response `201`:
```json
{
  "message": "Evento registrato",
  "event_id": 10025
}
```

### 5) POST `/api/logistic/transshipments/{id}/approve` (extra)
Request:
```json
{
  "notes": "Approvato per prossimo giro utile"
}
```
Response `200`:
```json
{
  "message": "Trasbordo approvato",
  "transshipment": {
    "id": 555,
    "status": "approved",
    "approved_by_user_id": 3
  }
}
```

### 6) POST `/api/logistic/dispatch/{journey}/close` (extra)
Request:
```json
{
  "notes": "Scarico completato e audit congelato"
}
```
Response `200`:
```json
{
  "message": "Journey chiuso",
  "journey": {
    "id": 123,
    "status": "closed",
    "audit_frozen_at": "2026-03-18T18:40:00Z"
  }
}
```

### Error format unico (consigliato)
```json
{
  "message": "Validation failed",
  "errors": {
    "allocations.0.allocated_containers": ["Il valore deve essere maggiore di zero"]
  }
}
```

---

## Analisi `order_items` (Keep / Move / Deprecate)

Fonte: verifica schema reale DB + modello `OrderItem` + usage applicativo.

### KEEP (baseline commerciale + operativita warehouse)
- Identita e baseline:
  - `order_id`, `cer_code_id`, `order_item_group_id`
  - `holder_id`, `holder_quantity`, `is_bulk`
  - `description`
  - `weight_declared`
  - `custom_l_cm`, `custom_w_cm`, `custom_h_cm`
- Pesi e lavorazioni:
  - `weight_gross`, `weight_tare`, `weight_net`
  - `warehouse_download_worker_id`, `warehouse_download_at`
  - `warehouse_weighing_worker_id`, `warehouse_weighing_dt`
  - `warehouse_selection_worker_id`, `warehouse_selection_dt`
  - `has_selection`, `selection_duration_minutes`
- Qualita/non conformita:
  - `has_non_conformity`, `warehouse_non_conformity`
  - `is_holder_dirty`, `total_dirty_holders`
  - `is_holder_broken`, `total_broken_holders`
  - `is_not_found`
- Altri campi operativi ancora validi:
  - `warehouse_notes`
  - `is_warehouse_added`
  - `is_crane_eligible`, `is_machinery_time_manual`
  - `status`

### KEEP con riallineamento semantico
- `warehouse_id`
  - TO-BE: trattarlo come `planned_target_warehouse_id` (destinazione pianificata).
  - Lo scarico effettivo passa nelle nuove `unload_instructions`.

### MOVE (uscita da `order_items` verso nuovi modelli)
- Split reale truck/trailer (oggi su pivot `journey_cargo_order_item`) -> `journey_cargo_allocations`
- Scarico effettivo (`download_warehouse_id` pivot) -> `journey_cargo_unload_instructions`
- Fabbisogno trasbordo (`is_transshipment`) -> `transshipment_needs`

### DEPRECATE (debito tecnico / duplicati naming)
- `journey_cargo_id` nel model: non presente nel DB reale, residuo legacy da rimuovere dal model.
- Coppie duplicate ADR:
  - `adr` vs `has_adr`
  - `adr_lotto` vs `adr_lot_code`
  - `adr_totale` vs `is_adr_total`
  - `adr_esenzione_totale` vs `has_adr_total_exemption`
  - `adr_esenzione_parziale` vs `has_adr_partial_exemption`
- Coppia duplicate machinery:
  - `machinery_time_fraction` vs `machinery_time_share`

Nota: i campi duplicati ADR/machinery sono ancora usati in vari punti UI/controller; deprecazione graduale, non drop immediato.

---

## Roadmap implementativa (1-2-3)

### 1) PR-01 - Pulizia semantica `order_items` (senza rompere flusso)
Obiettivo:
- ridurre ambiguita sul modello corrente prima di introdurre nuovo dominio.

Scope:
- Rimuovere dal model campi stale non presenti in DB (`journey_cargo_id` da `$fillable`).
- Introdurre naming canonico nel codice per campi duplicati (preferenza):
  - `has_adr`, `adr_un_code`, `adr_lot_code`, `is_adr_total`, `has_adr_total_exemption`, `has_adr_partial_exemption`
  - `machinery_time_share` (o `machinery_time_fraction`: scegliere uno standard unico)
- Mantenere compatibilita interna temporanea con mapper/accessor se serve.

Deliverable:
- Refactor model/request validation/frontend bindings su naming canonico.
- Test regressione create/edit order item.

Done when:
- Nessun riferimento applicativo ai campi stale.
- Naming duplicato non usato come sorgente primaria.

### 2) PR-02 - Nuovo dominio dati TO-BE
Obiettivo:
- introdurre i nuovi modelli processo rientro/scarico.

Schema:
- `journey_cargo_allocations`
- `journey_cargo_unload_instructions`
- `transshipment_needs`
- (eventuale) `journey_load_census_items` se si decide persistenza dedicata del census.

Regole core:
- Split per contenitori.
- Peso stimato derivato da proporzione contenitori su peso totale item.
- `effettivo != pianificato` -> proposta trasbordo (non automatismo esecutivo).

Deliverable:
- Migration + model + relation Eloquent.
- Service di dominio per calcolo split/pesi + validazioni invarianti.

Done when:
- Dati TO-BE persistiti e interrogabili end-to-end.
- Vincoli di coerenza applicati lato service/validator.

### 3) PR-03 - Switch applicativo (API + componenti riusabili)
Obiettivo:
- usare il nuovo dominio in Dashboard Logistica (e predisporre riuso Driver futuro).

API v1 ridotte:
- `GET /api/logistic/dispatch/{journey}/workspace`
- `PUT /api/logistic/dispatch/{journey}/workspace`
- `POST /api/logistic/dispatch/{journey}/confirm`
- `POST /api/logistic/dispatch/{journey}/events`
- Extra: approve transshipment / close journey

UI:
- Componenti role-aware riusabili:
  - `CargoReturnContext`
  - `LoadCensusEditor`
  - `CargoAllocationEditor`
- Nessun fork dashboard-specific della business logic.

Governance:
- Baseline confermata = soft freeze.
- Dopo baseline: solo eventi append-only di eccezione/correzione.
- Freeze audit definitivo a chiusura journey.

Done when:
- Flusso completo operativo su dashboard logistica senza Excel.
- Coda proposte trasbordo gestita e approvabile solo da logistica.
- Warehouse limita modifiche al perimetro consentito (chief/manager vs worker).

---

## Decisione operativa
Procedere in ordine 1 -> 2 -> 3.
No backward compatibility con vecchia UX di pianificazione.

---

## Implementation progress (draft) - 2026-03-18

### PR-01 (in progress) - semantic cleanup
Implemented:
- Removed stale `journey_cargo_id` from `OrderItem::$fillable`.
- Added normalization bridges in `OrderItem` for duplicated fields:
  - `adr <-> has_adr`
  - `adr_lotto <-> adr_lot_code`
  - `adr_totale <-> is_adr_total`
  - `adr_esenzione_totale <-> has_adr_total_exemption`
  - `adr_esenzione_parziale <-> has_adr_partial_exemption`
  - `machinery_time_fraction <-> machinery_time_share`
- Updated main order validators to canonical ADR keys in:
  - `OrderController`
  - `DriverOrderController`
  - `WorkerOrderController`
  - `WorkerJourneyCargo`

### PR-02 (started) - new TO-BE data model
Added migration:
- `2026_03_18_140000_create_journey_dispatch_workspace_tables.php`

Created tables:
- `journey_load_census_items`
- `journey_cargo_allocations`
- `journey_cargo_unload_instructions`
- `transshipment_needs`

Added models:
- `JourneyLoadCensusItem`
- `JourneyCargoAllocation`
- `JourneyCargoUnloadInstruction`
- `TransshipmentNeed`

Added relations on existing models:
- `Journey` -> `loadCensusItems`, `cargoAllocations`, `transshipmentNeeds`
- `JourneyCargo` -> `allocations`
- `OrderItem` -> `loadCensusItems`, `cargoAllocations`

### PR-03 (started) - reduced workspace API
Added controller:
- `API_LogisticDispatchWorkspaceController`

Added routes:
- `GET /api/logistic/dispatch/{journey}/workspace`
- `PUT /api/logistic/dispatch/{journey}/workspace`
- `POST /api/logistic/dispatch/{journey}/confirm`
- `POST /api/logistic/dispatch/{journey}/events`
- `POST /api/logistic/transshipments/{transshipment}/approve`
- `POST /api/logistic/dispatch/{journey}/close`

Note:
- Current implementation is functional scaffolding aligned with the reduced API strategy.
- Next step is hardening business validations and permissions matrix endpoint-by-endpoint.

## Implementation Update - Final Hardening (2026-03-18)

### API hardening completed
- Controller hardened: `API_LogisticDispatchWorkspaceController`
- Strong business validations added:
  - rejects orphan unload instructions (instruction without allocation)
  - payload duplicate protection (`census`, `allocations`, `unload_instructions`)
  - `double` instruction requires `unload_sequence`
  - transshipment proposal requires reason when flagged
  - transshipment event payload strict checks:
    - required keys
    - positive containers
    - different source/destination warehouse
    - warehouse IDs existence
    - `order_item` must belong to journey
    - proposed containers cannot exceed available item containers
  - confirm hard checks:
    - at least one ACTUAL allocation
    - each allocation must have at least one unload instruction
    - per-item allocation sum must exactly match census actual containers
- Journey state guards:
  - no save/confirm/event/approve on journey already closed (`dispatch_status = managed`)
  - close allowed only from `in_progress` / `on_hold`

### Role policy endpoint-by-endpoint
- New policy methods in `JourneyPolicy` and applied per endpoint via `authorize()`:
  - `dispatchWorkspaceView`
  - `dispatchWorkspaceSave`
  - `dispatchWorkspaceConfirm`
  - `dispatchWorkspaceAppendWarehouseEvent`
  - `dispatchWorkspaceAppendTransshipmentProposal`
  - `dispatchWorkspaceAppendLogisticEvent`
  - `dispatchTransshipmentApprove`
  - `dispatchWorkspaceClose`
- Role matrix now enforced by policy and no longer by ad-hoc inline checks in controller.
- Explicit policy registration added in `AppServiceProvider` for `Journey::class`.

### Feature API tests
- Added: `tests/Feature/LogisticDispatchWorkspaceApiTest.php`
- Includes scenario coverage for:
  - save + confirm happy path
  - split > census rejected
  - non-logistic forbidden on save/confirm/close/approve
  - warehouse worker can log warehouse exception but cannot propose transshipment
  - transshipment proposal rejects alien order item
  - confirm rejects incomplete split
  - close rejects wrong dispatch status
- Current local DB is schema-incomplete (many pending migrations), so tests are currently auto-skipped when required dispatch tables are missing.
- Test guard added: `ensureDispatchSchemaOrSkip()` to avoid false negatives on legacy/local bootstrap.

## Progress Update - UI Integration + Contract Cleanup + Demo Seeder (2026-03-18)

### 1) Frontend integration on dispatch detail
- Added reusable component: `DispatchWorkspacePanel.vue`
- Integrated into logistic dispatch detail page (`LogisticDispatch/Show.vue`), under dispatch plan section.
- New UI capabilities:
  - load workspace snapshot (`GET /api/logistic/dispatch/{journey}/workspace`)
  - edit census per item (actual containers + total weight)
  - split allocations per cargo (truck/trailer) by item
  - set unload instructions per allocation (target warehouse, sequence, type)
  - save workspace (`PUT /api/logistic/dispatch/{journey}/workspace`)
  - confirm baseline (`POST /api/logistic/dispatch/{journey}/confirm`)
  - close journey audit (`POST /api/logistic/dispatch/{journey}/close`)
  - append events (warehouse exception / transshipment proposal)
  - approve proposed transshipment

### 2) Frontend API contract cleanup
- Added centralized dispatch API client: `resources/js/Pages/LogisticDispatch/api/dispatchApi.js`
- Standardized frontend integration around named methods and unified error extraction (`extractApiMessage`), reducing ad-hoc axios handling.
- Refactored existing components to use shared API client:
  - `DispatchPlanCard.vue`
  - `LogisticDispatch/Show.vue`

### 3) Realistic fixture/seed for local demo & e2e bootstrap
- Added `DispatchWorkspaceDemoSeeder`.
- Seeder creates a complete incoming-journey scenario:
  - logistic + driver users
  - customer + site
  - truck + trailer + cargos
  - active journey with primary/secondary warehouse
  - order + orderItems
  - journey_cargos (vehicle/trailer)
  - load census
  - actual allocations + unload instructions
  - one proposed transshipment need
- Seeder designed to be idempotent enough for local demos (guard on `legacy_code = DISPATCH_DEMO_001`).

## UI Unification Proposal - JourneyCargo + Piano Scarico + Workspace (2026-03-19)

### Problem statement
Current flow overlaps across three areas:
- old `/journeyCargo/{journey}/create`
- `Piano scarico`
- `Workspace carico reale`

All three touch similar concepts (cargo definition, warehouse sequence/order, item allocation, unload operations), creating duplicated mental model and operator confusion.

### Option 1 (selected now): Single Workspace in 3 guided steps
1. **Step A - Setup Convoglio / Cargo**
   - define JourneyCargo entities (truck/trailer)
   - set cargo attributes and unload order baseline
   - set primary/secondary warehouse context
2. **Step B - Census & Split by Item**
   - per `order_item`: real containers + total weight
   - split allocation across cargos (example: 5 boxes -> 3 truck, 2 trailer)
3. **Step C - Unload Instructions & Exceptions**
   - target warehouse per allocation
   - instruction type (simple/double/drop_only)
   - unload sequence (1/2)
   - optional transshipment proposal

Why selected:
- fastest path to end-to-end process test
- preserves business clarity with minimal UX risk
- cleanly absorbs old `/journeyCargo/{journey}/create` functionality

### Option 2 (planned next): Unified panel + Kanban interaction
- one integrated interface (expert mode)
- cargo columns (truck/trailer) with kanban-style item location
- explicit split action for container quantities
- inline unload instruction editing on allocation cards

Goal:
- improve speed and ergonomics after process validation with Option 1

### Option 3 (alternative): Full matrix expert page
- stacked sections always visible
- high information density, no step wizard
- suitable for advanced operators, higher cognitive load for occasional use

### Decision
- **Implement Option 1 now** for short-term process validation.
- **Refactor toward Option 2** immediately after test cycle, adding kanban location UX and dedicated split function.

### Delivery strategy
Phase 1 (now):
- complete Option 1 in `/logistic/dispatch/{journey}`
- retire dependency on old `/journeyCargo/{journey}/create`

Phase 2 (next):
- refactor UI to Option 2 style
- introduce kanban item placement + quantity split interaction
- keep backend contracts stable where possible

## Option 1 Implementation - Delivered on UI (2026-03-19)

### What has been implemented
- `/logistic/dispatch/{journey}` now uses a unified **Option 1 workspace** on the left side of the first row.
- The old separation between `Piano scarico` and `Workspace carico reale` has been functionally merged into a guided 3-step flow:
  1. Setup convoglio (plan + JourneyCargo configuration)
  2. Censimento e split per item
  3. Istruzioni di scarico, eccezioni, trasbordi, conferma baseline, chiusura audit

### Backend support added
- New API endpoint to configure JourneyCargo directly from dispatch page:
  - `PUT /api/logistic/dispatch/{journey}/cargos`
  - route name: `api.logistic-dispatch.cargos.save`
- Endpoint is role-protected and integrated with existing dispatch workspace controller.

### Frontend support added
- `dispatchApi.js` extended with `saveJourneyCargos(...)`.
- `DispatchWorkspacePanel.vue` rewritten to Option 1 UX with integrated stepper and actions.
- `Show.vue` updated to host the integrated workspace component (left) + operational actions card (right).

### Validation
- Logistic dispatch API feature tests still green after integration (`8 passed`).
- Frontend build command could not be executed in this environment due local PowerShell execution policy restrictions on `npm.ps1`.
