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
