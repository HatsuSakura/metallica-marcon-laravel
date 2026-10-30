
> **Nota recuperata — storica, non procedura corrente.** Origine: vecchio vault Obsidian; recupero 2026-10-30. Per lo stato attuale consultare [sviluppi e riconciliazione](../../development-status.md). Non eseguire comandi di deploy, migrazione o modifica dati sulla sola base di questa nota.

# Authorization Report

Data: 2026-03-27
Ambito: route applicative, endpoint API, controller authorization, policy/resource matrix, service layer

## Primitive di protezione
- `auth`: sessione autenticata obbligatoria
- `verified`: email verificata obbligatoria
- `accessBackofficeArea`: `developer`, `manager`, `logistic`, `is_admin = true`
- `accessWarehouseArea`: `developer`, `manager`, `logistic`, `warehouse_chief`, `warehouse_manager`, `warehouse_worker`, `is_admin = true`
- `accessDriverArea`: `developer`, `manager`, `logistic`, `driver`, `is_admin = true`
- `useLogisticsNlp`: `developer`, `manager`, `logistic`, `is_admin = true`
- `authorizeDriver(Request, Journey)`: solo driver assegnato

## Resource matrix sintetica
- `Cargo`, `Customer`, `Holder`, `Site`, `Trailer`, `Vehicle`, `Withdraw`: backoffice full CRUD (`developer`, `manager`, `logistic`, `admin`)
- `User`: backoffice full CRUD (`developer`, `manager`, `logistic`, `admin`), self-delete bloccato
- `CatalogItem`, `Recipe`: warehouse read-only; control roles full CRUD
- `Order`: control roles full CRUD; warehouse read + `warehouseManage`; driver create e accesso solo ai propri ordini assegnati
- `Journey`: control roles full CRUD; warehouse read + ability warehouse/dispatch specifiche; driver accesso solo ai propri viaggi assegnati
- `Notification`: solo proprietario

## API e backend calls principali
- Dispatch/logistic APIs: policy `JourneyPolicy` con ability dedicate (`dispatchWorkspaceView`, `dispatchWorkspaceSave`, `dispatchWorkspaceConfirm`, `dispatchWorkspaceClose`, `dispatchTransshipmentApprove`, append event abilities)
- Driver runtime APIs: ownership custom `authorizeDriver()` o policy `update` su `Journey`
- Order/document APIs: policy `OrderPolicy` (`view`, `update`)
- Warehouse APIs: ability `warehouseManage` su `Order` / `Journey`
- Customer/site risk APIs: policy `update` su `Customer` / `Site`
- User admin APIs: ability `manageCredentials` su `User`
- Catalog/recipe APIs: `CatalogItemPolicy` / `RecipePolicy`
- NLP API: gate `useLogisticsNlp`

## Service layer
- Nessun gate/policy nel service layer
- Boundary autorizzativo: `route middleware -> controller authorize -> policy/gate`
- I service eseguono solo logica business

## Note
- Corretto bug strutturale su controller con `__consruct` che impediva `authorizeResource()`
- Residuo da verificare: alcune route legacy del modulo explosion ancora presenti in `route:list` e da riallineare con i controller reali
