# System Architecture

## Core Layers

### 1. Domain Layer
- Customers
- Pickups (Withdraw)
- Trips (Journey, JourneyStop)
- Materials (CerCode, CatalogItem, Recipe, RecipeNode)
- Warehouse movements (OrderItem, warehouse ops)

### 2. AI Interpretation Layer
- Logistics NLP Engine → interpreta intent logistici
- Analytics NLP Engine → interpreta query analitiche
- Output: solo JSON strutturato, mai SQL

### 3. Deterministic Query Builders
- `LogisticsCandidateQueryBuilder` — candidati per pickup
- `AnalyticsAggregationBuilder` — aggregazioni analytics

### 4. Scoring Layer (Logistics)
Weighted scoring basato su:
- Distance
- Priority
- Fill factor

### 5. Presentation Layer
- Backend restituisce dataset normalizzati + suggerimento visualizzazione.
- Frontend (Vue 3 + Inertia.js) gestisce il rendering dei chart.
- Intestazione applicativa responsive: `Components/UI/AppHeader.vue`, montata
  una sola volta in `Layouts/MainLayout.vue` per tutte le route; nessuna
  variante selezionata tramite nome della pagina. Account, notifiche, tema
  e apertura del drawer mantengono lo stesso contratto mobile/desktop.
- Presentazione tappe autista: `Driver/Journey/Components/JourneyStopsPanel.vue`
  con eventi verso la pagina che coordina le mutazioni API; helper di sola
  presentazione in `driverStopPresentation.js`.

## Separation of Concerns

- AI layer interpreta l'intent dell'utente.
- Domain layer applica la business logic.
- Data layer esegue le query ottimizzate.
- Frontend visualizza i risultati.

## Geo Strategy

Attuale: MySQL + calcolo Haversine.
Futuro: sostituire GeoProvider con implementazione PostGIS se necessario (abstraction layer già presente).

## Dispatch Module (GLE-53)

Struttura dedicata per il workflow di dispatch logistico:

**Frontend**
- `resources/js/Pages/LogisticDispatch/` — pagine operative
- `resources/js/Pages/LogisticDispatch/Partials/` — blocchi UI riutilizzabili

**Backend**
- `app/Http/Controllers/LogisticDispatchController.php` — Inertia views / read models
- `app/Http/Controllers/API_LogisticDispatchController.php` — mutation commands
- `app/Services/Dispatch/` — business rules e state machine
- Endpoint command-style sotto `/api/logistic/dispatch/*`

`Dashboard/Logistic.vue` rimane launcher/entry point soltanto.

## Authorization Architecture

Tre layer in cascata:
1. **Route middleware** — `auth`, `verified`, gate di area (`accessBackofficeArea`, `accessWarehouseArea`, `accessDriverArea`)
2. **Policy** — per risorsa (Order, Journey, Customer, ecc.) con trait `AuthorizesDomainRoles` e bypass `is_admin`
3. **Controller** — `authorize()` / `Gate::authorize()` espliciti su ogni endpoint API sensibile

I service non gestiscono authorization: assumono che sia già risolta a monte.

Dettagli completi in `.claude/rules/decisions/004-authorization-report.md`.

## Confine pianificazione/esecuzione (2026-10-31)

- `JourneyExecutionService`: comando unico di prima partenza e comandi
  successivi transazionali con revisione attesa.
- `PlanSnapshot`/`JourneyBaseline`: originale immutabile dell'aggregato;
  `JourneyOrderExecution`, materiali e consegne dedicati contengono l'operativo.
- `order_commitments`: unico impegno di un ordine, rilasciabile prima della
  fine del viaggio in caso di salto totale.
- `PlanningWriteBoundary` + `FrozenPlanningGuard`: impediscono modifiche
  al pianificato dopo la partenza, anche attraverso vecchi controller.
- `ExecutionProjection` e `DriverJourneyReadModel`: lettura coerente per
  autista/Logistica/magazzino e documenti, indipendente da riuso del sorgente.
- `DocumentRevision`: fingerprint di ordine e viaggio; ristampa senza
  transizioni business; job ancorato agli input richiesti.
- Ingressi nuovi: `/api/journeys/{id}/execution/*`. I consumer legacy non devono
  usare `orders.journey_id` come fonte dello storico delle esecuzioni.
- Dettagli e limiti: `docs/Piano operativo esecuzione viaggi.md`.

- Convoglio fisso nell'esecuzione: nessun cambio di motrice/rimorchio dopo
  la partenza, neppure tramite API o sgancio con trailer_id nullo. Cambi
  prima della partenza restano operazioni di pianificazione Logistica.
- Riordino da autista prima della partenza: OP-001 in `docs/open-points.md`,
  non abilitato in attesa del chiarimento con il cliente.
- `StopReopening`: riapertura come tappa corrente con fatti conservati e
  protezione delle esecuzioni già ricevute/impegnate altrove.

- Consultazione completa piano originale/storico riservata a Logistica e
  ruoli di controllo tramite `JourneyPolicy::viewExecutionHistory`, applicata
  sia all'API storico sia alle capability della UI condivisa. Non mostrare
  il blocco analitico o i confronti generali nell'interfaccia dell'autista;
  mantenere disponibili le correzioni/ripristini operativi puntuali.
