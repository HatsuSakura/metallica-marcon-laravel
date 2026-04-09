# Audit Layer (GLE-26)

## Architettura

Due layer distinti:

### 1. Audit di dominio
- Fondazione tecnica: package `owen-it/laravel-auditing` (`^14.0`)
- Obiettivo: tracciare chi ha modificato quale record e quali campi
- Applicato via trait condiviso `App\Models\Concerns\HasDomainAudit`
- Ogni model ha whitelist `auditInclude` esplicita

### 2. Audit di processo
- Layer custom applicativo (implementazione successiva)
- Obiettivo: tracciare eventi business che non coincidono con semplici diff di campo
- Esempi: documenti generati, documenti invalidati, journey started, ordine associato/disassociato da journey

## Stato attuale (GLE-95)

- `owen-it/laravel-auditing` installato
- `config/audit.php` configurato con guard `web`
- Migration `audits` aggiunta
- Trait `HasDomainAudit` creato
- Model MVP cablati con whitelist
- Prossimi step: `php artisan migrate`, validazione runtime record audit, poi GLE-98

## Scope MVP — Model auditati

**Anagrafiche**: Customer, Site, Withdraw, InternalContact, Timetable, User, Worker, Warehouse, Area

**Ordini**: Order, OrderItem, OrderHolder

**Journey / Logistica**: Journey, JourneyStop

**Mezzi / contenitori**: Vehicle, Trailer, Cargo, Holder

**Cataloghi / ricette**: CerCode, CatalogItem, Recipe, RecipeNode

## Convenzione generale

Per model tecnici/configurativi:
- Escludere sempre: `id`, `created_at`, `updated_at`, `deleted_at`
- Auditare i campi che rappresentano il contenuto reale del modello
- Demandare la leggibilità del diff al layer di presentazione audit

## Eventi di processo pianificati

**Order**: `order.documents_generation_started`, `order.documents_generated`, `order.documents_generation_failed`, `order.documents_invalidated`, `order.attached_to_journey`, `order.detached_from_journey`, `order.state_changed`

**Journey**: `journey.created`, `journey.updated`, `journey.started`, `journey.start_blocked`, `journey.executed`, `journey.closed`, `journey.documents_generation_started`, `journey.orders_attached`, `journey.orders_detached`

**JourneyStop**: `journey_stop.created`, `journey_stop.updated`, `journey_stop.deleted`, `journey_stop.reordered`, `journey_stop.completed`, `journey_stop.skipped`

**OrderItem**: `order_item.created`, `order_item.updated`, `order_item.deleted`

## Decisioni esplicite

- `journey_id` su Order va gestito in doppio: diff tecnico sul campo + evento leggibile di associazione/disassociazione
- I campi di processo warehouse e stati tecnici su OrderItem verranno aggiunti in fase successiva (audit di processo end-to-end)
- Le relazioni pivot (User↔Warehouse, Worker↔Warehouse, Area↔Site) restano fuori dal layer base MVP

## Scomposizione YouTrack (GLE-26)

- GLE-26-A: audit base infrastrutturale (`OwenIt\Auditing`)
- GLE-26-B: audit domain model core (logistici/ordini)
- GLE-26-C: audit anagrafiche e configurazioni operative
- GLE-26-D: audit di processo (documenti ordine + lifecycle journey)
- GLE-26-E: UI di consultazione audit (fase successiva)
