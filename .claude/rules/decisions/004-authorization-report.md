# Authorization Report

Data: 2026-03-27
Ambito: route applicative, endpoint API, controller authorization, policy/resource matrix, service layer

## Scope
- Inclusi: route applicative di business, API `api/*`, aree `driver/*`, `warehouse-manager/*`, `worker/*`, resource backoffice
- Esclusi dal dettaglio: `_debugbar/*`, `_ignition/*`, `up`, closure Laravel standard di auth/reset/verifica email

## 1. Primitive di protezione

### Middleware base
- `auth`: sessione autenticata obbligatoria
- `verified`: email verificata obbligatoria

### Gate di area
- `accessBackofficeArea`
  - livello: ruoli specifici + admin
  - ruoli: `developer`, `manager`, `logistic`, `is_admin = true`
- `accessWarehouseArea`
  - livello: ruoli specifici + admin
  - ruoli: `developer`, `manager`, `logistic`, `warehouse_chief`, `warehouse_manager`, `warehouse_worker`, `is_admin = true`
- `accessDriverArea`
  - livello: ruoli specifici + admin
  - ruoli: `developer`, `manager`, `logistic`, `driver`, `is_admin = true`
- `useLogisticsNlp`
  - livello: ruoli specifici + admin
  - ruoli: `developer`, `manager`, `logistic`, `is_admin = true`

### Ownership custom
- `authorizeDriver(Request, Journey)`
  - livello: ownership stretta
  - regola: solo il driver assegnato a quel viaggio puo eseguire le stop actions driver API

### Policy registration
- policy registrate esplicitamente in `AppServiceProvider`
  - `Cargo`, `CatalogItem`, `Customer`, `Holder`, `Journey`, `Order`, `Recipe`, `Site`, `Trailer`, `User`, `Vehicle`, `Withdraw`, `DatabaseNotification`

### Note su admin bypass
- Le domain policy usano il trait `AuthorizesDomainRoles` con bypass `is_admin`
- Anche i gate di area (`access*`, `useLogisticsNlp`) ora includono `is_admin`

## 2. Resource matrix

Legenda:
- `R`: sola lettura
- `C`: create
- `U`: update/edit
- `D`: delete
- `WM`: warehouse operations specifiche
- `DRV-own`: solo risorse assegnate al driver

| Resource | Read | Create | Update/Edit | Delete | Note |
| --- | --- | --- | --- | --- | --- |
| `Cargo` | `developer`, `manager`, `logistic`, `admin` | stessi | stessi | stessi | policy piena backoffice |
| `Customer` | `developer`, `manager`, `logistic`, `admin` | stessi | stessi | stessi | `authorizeResource` attivo |
| `Holder` | `developer`, `manager`, `logistic`, `admin` | stessi | stessi | stessi | policy piena backoffice |
| `Site` | `developer`, `manager`, `logistic`, `admin` | stessi | stessi | stessi | policy piena backoffice |
| `Trailer` | `developer`, `manager`, `logistic`, `admin` | stessi | stessi | stessi | `authorizeResource` attivo |
| `Vehicle` | `developer`, `manager`, `logistic`, `admin` | stessi | stessi | stessi | `authorizeResource` attivo |
| `Withdraw` | `developer`, `manager`, `logistic`, `admin` | stessi | stessi | stessi | backoffice only |
| `User` | `developer`, `manager`, `logistic`, `admin` | stessi | stessi | stessi, tranne self-delete | `manageCredentials` = stessi ruoli |
| `CatalogItem` | `developer`, `manager`, `logistic`, `warehouse_*`, `admin` | `developer`, `manager`, `logistic`, `admin` | stessi | stessi | warehouse read-only |
| `Recipe` | `developer`, `manager`, `logistic`, `warehouse_*`, `admin` | `developer`, `manager`, `logistic`, `admin` | stessi | stessi | warehouse read-only |
| `Order` | `developer`, `manager`, `logistic`, `warehouse_*`, `admin`, `driver` solo assegnato | `developer`, `manager`, `logistic`, `driver`, `admin` | `developer`, `manager`, `logistic`, `admin`, `driver` solo assegnato | `developer`, `manager`, `logistic`, `admin` | `warehouseManage` per operazioni di magazzino |
| `Journey` | `developer`, `manager`, `logistic`, `warehouse_*`, `admin`, `driver` solo assegnato | `developer`, `manager`, `logistic`, `admin` | `developer`, `manager`, `logistic`, `admin`, `driver` solo assegnato | `developer`, `manager`, `logistic`, `admin` | ability extra per dispatch e warehouse ops |
| `Notification` | proprietario notifica | n/a | proprietario notifica | n/a | solo `update`/seen |

## 3. Route groups

| Group / Prefix | Middleware | Livello |
| --- | --- | --- |
| `journey/*` | `auth`, `verified`, `can:accessBackofficeArea` | backoffice only |
| `journeyCargo/*` | `auth`, `verified`, `can:accessWarehouseArea` | warehouse/backoffice |
| `customer/*`, `site/*`, `order/*`, `vehicle/*`, `trailer/*`, `cargo/*`, `user/*`, `withdraw/*` | `auth`, `verified`, `can:accessBackofficeArea` | backoffice only |
| `driver/*` | `auth`, `verified`, `can:accessDriverArea` | driver area |
| `worker/*` | `auth`, `verified`, `can:accessWarehouseArea` | warehouse area |
| `warehouse-manager/*` | `auth`, `verified`, `can:accessWarehouseArea` | warehouse area |
| `map/*` | `auth`, `verified`, `can:accessBackofficeArea` | backoffice only |
| `catalog-items/*`, `recipes/*`, recipe node routes | `auth`, `verified`, `can:accessWarehouseArea` | warehouse/backoffice, con policy piu strette per write |
| `api/*` | `auth`, `verified` | seconda barriera: gate/policy nel controller |

## 4. API / backend endpoint matrix

### Dispatch / logistic
| Route | Controller | Protezione | Livello |
| --- | --- | --- | --- |
| `GET api/logistic/dispatch/{journey}/workspace` | `API_LogisticDispatchWorkspaceController@workspace` | `dispatchWorkspaceView` su `Journey` | control + warehouse + driver assegnato + admin |
| `PUT api/logistic/dispatch/{journey}/workspace` | `...@saveWorkspace` | `dispatchWorkspaceSave` | `developer`, `manager`, `logistic`, `admin` |
| `PUT api/logistic/dispatch/{journey}/cargos` | `...@upsertCargos` | `dispatchWorkspaceSave` | `developer`, `manager`, `logistic`, `admin` |
| `POST api/logistic/dispatch/{journey}/confirm` | `...@confirm` | `dispatchWorkspaceConfirm` | `developer`, `manager`, `logistic`, `admin` |
| `POST api/logistic/dispatch/{journey}/close` | `...@close` | `dispatchWorkspaceClose` | `developer`, `manager`, `logistic`, `admin` |
| `POST api/logistic/dispatch/{journey}/events` | `...@appendEvent` | ability dinamica per tipo evento | warehouse event: control + warehouse; proposal: control + warehouse; logistic event: control |
| `POST api/logistic/transshipments/{transshipment}/approve` | `...@approveTransshipment` | `dispatchTransshipmentApprove` | control + admin |
| `POST api/logistic/transshipments/{transshipment}/cancel` | `...@cancelTransshipment` | `dispatchTransshipmentApprove` | control + admin |
| `PUT api/logistic/dispatch/{journey}/plan` | `API_LogisticDispatchController@updatePlan` | `dispatchWorkspaceSave` | control + admin |
| `POST api/logistic/dispatch/{journey}/hold` | `API_LogisticDispatchController@hold` | `dispatchWorkspaceSave` | control + admin |
| `POST api/logistic/dispatch/{journey}/resume` | `API_LogisticDispatchController@resume` | `dispatchWorkspaceSave` | control + admin |

### Driver journey runtime
| Route | Controller | Protezione | Livello |
| --- | --- | --- | --- |
| `POST api/driver/journeys/{journey}/start` | `API_DriverJourneyStopsController@startJourney` | `authorizeDriver()` | solo driver assegnato |
| `PUT api/driver/journeys/{journey}/stops/reorder` | `...@reorder` | `authorizeDriver()` | solo driver assegnato |
| `PUT api/driver/journeys/{journey}/stops/{stop}/complete` | `...@complete` | `authorizeDriver()` | solo driver assegnato |
| `PUT api/driver/journeys/{journey}/stops/{stop}/skip` | `...@skip` | `authorizeDriver()` | solo driver assegnato |
| `POST api/driver/journeys/{journey}/stops/technical` | `...@createTechnical` | `authorizeDriver()` | solo driver assegnato |
| `PUT api/journey/updateState/{journey}` | `API_DriverJourneyUpdateController@updateState` | `Gate::authorize('update', $journey)` | control + driver assegnato + admin |

### Orders / documents
| Route | Controller | Protezione | Livello |
| --- | --- | --- | --- |
| `PUT api/order/updateState/{order}` | `API_DriverOrderUpdateController@updateState` | `Gate::authorize('update', $order)` | control + driver assegnato + admin |
| `POST api/orders/{order}/generate-documents` | `API_OrderDocumentsController@generate` | `update` su `Order` | control + driver assegnato + admin |
| `GET api/orders/{order}/document-status` | `...@status` | `view` su `Order` | control + warehouse + driver assegnato + admin |
| `GET api/orders/{order}/documents` | `...@list` | `view` su `Order` | control + warehouse + driver assegnato + admin |
| `GET api/orders/{order}/documents/{document}/download` | `...@download` | `view` su `Order` | control + warehouse + driver assegnato + admin |
| `GET api/journeys/{journey}/documents-status` | `JourneyController@documentsStatus` | `view` su `Journey` | control + warehouse + driver assegnato + admin |
| `POST api/journeys/{journey}/generate-documents` | `JourneyController@generateDocuments` | `update` su `Journey` | control + driver assegnato + admin |

### Warehouse operations
| Route | Controller | Protezione | Livello |
| --- | --- | --- | --- |
| `PUT api/warehouse-orders/{order}` | `API_WarehouseOrdersController@update` | `warehouseManage` su `Order` | control + warehouse + admin |
| `POST api/warehouse-order-items/save-items` | `API_WarehouseOrderItemsController@saveItems` | `warehouseManage` su `Order` e su ogni `OrderItem->order` | control + warehouse + admin |
| `PUT api/warehouse-order-items/{orderItem}` | `...@update` | `warehouseManage` su `OrderItem->order` | control + warehouse + admin |
| `POST api/warehouse-order-items/move-journey-cargo/{orderItem}` | `...@moveJourneyCargo` | `warehouseManage` su `OrderItem->order` | control + warehouse + admin |
| `PATCH api/warehouse-order-items/not-found/{orderItem}` | `...@flagNotFound` | `warehouseManage` su `OrderItem->order` | control + warehouse + admin |
| `PUT api/warehouse-journey-cargos/{journeyCargo}` | `API_WarehouseJourneyCargosController@update` | `warehouseManage` su `Journey` | control + warehouse + admin |
| `GET/PUT/DELETE warehouse-manager/*` | `WarehouseManager*Controller` | area gate + `warehouseManage`/`viewAny` su `Order` | control + warehouse + admin |
| `worker/journeyCargo/*` | `WorkerJourneyCargo` | area gate; controller legacy | warehouse area |

### Customer / site / risk
| Route | Controller | Protezione | Livello |
| --- | --- | --- | --- |
| `POST api/customer/{customer}/recalculate-risk` | `CustomerController@recalculateRisk` | `update` su `Customer` | control + admin |
| `PUT api/site/updateBooleans/{site}` | `API_SiteBooleanUpdateController@update` | `update` su `Site` | control + admin |
| `POST api/site/{site}/recalculate-risk` | `...@recalculateRisk` | `update` su `Site` | control + admin |
| `POST api/timetable/{site}` | `API_SiteTimetableController@store` | `update` su `Site` | control + admin |

### User admin
| Route | Controller | Protezione | Livello |
| --- | --- | --- | --- |
| `POST api/user/resend-verification/{user}` | `API_UserResetAndResendController@resendVerification` | `manageCredentials` su `User` | control + admin |
| `POST api/user/send-password-reset/{user}` | `...@sendPasswordResetEmail` | `manageCredentials` su `User` | control + admin |

### Catalog / recipes / explosion editor
| Route | Controller | Protezione | Livello |
| --- | --- | --- | --- |
| `GET api/catalog-items` | `CatalogItemController@search` | `viewAny` su `CatalogItem` | control + warehouse + admin |
| `POST api/catalog-items` | `CatalogItemController@store` | `create` su `CatalogItem` | control + admin |
| `GET api/recipes/default-tree` | `API_RecipeController@defaultTree` | `viewAny` su `CatalogItem` | control + warehouse + admin |
| `GET api/recipes/{recipe}/tree` | `API_RecipeController@recipeTree` | `view` su `Recipe` | control + warehouse + admin |
| `GET/PUT resource catalog-items/*` | `CatalogItemController` | policy `CatalogItemPolicy` | warehouse read-only, control full write |
| `GET/POST/PUT/DELETE resource recipes/*` | `RecipeController` | policy `RecipePolicy` | warehouse read-only, control full write |
| `PUT recipes/{recipe}/nodes/sync` | `RecipeNodeController@sync` | `update` su `Recipe` | control + admin |
| `GET order-items/{orderItem}/explosions` | `OrderItemExplosionController@show` | `warehouseManage` su `OrderItem->order` | control + warehouse + admin |
| `PUT order-items/{orderItem}/explosions/sync` | `OrderItemExplosionController@sync` | `warehouseManage` su `OrderItem->order` | control + warehouse + admin |

## 5. Controller-level notes

### Resource controllers con `authorizeResource()`
- `CustomerController`
- `CargoController`
- `HolderController`
- `SiteController`
- `TrailerController`
- `VehicleController`

### Controller con authorize espliciti manuali
- `OrderController`
- `JourneyController`
- `UserController`
- `CatalogItemController`
- `RecipeController`
- `RecipeNodeController`
- `OrderItemExplosionController`
- `WarehouseManagerOrderController`
- `WarehouseManagerOrderItemController`
- tutti gli `API_*Controller` principali di business

## 6. Service layer

### Stato attuale
- Non risultano gate/policy nel service layer applicativo
- I service osservati (`Dispatch`, `OrderDocumentGenerationService`, `CalculateRiskService`, `NlpLogisticsParseService`, `OrderItemUpdater`, `OrderItemExplosionSync`, `RecipeTreeService`) eseguono logica business, non authorization

### Conclusione
- Il boundary autorizzativo e: `route middleware -> controller authorize -> policy/gate`
- I service assumono che l’authorization sia gia stata risolta a monte

## 7. Residui / caveat

- Esistono controller legacy non ancora totalmente uniformati al pattern resource + policy su ogni singolo metodo, ma il perimetro principale di business e le API sensibili ora sono protetti
- Alcune route legacy del modulo explosion (`store`, `update`, `destroy`, `applyRecipe`) risultano ancora in `route:list`; vanno verificate separatamente se devono essere mantenute o rimosse, per allineare routing e controller reali
