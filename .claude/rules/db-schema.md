# Database Schema Reference

Generato da `SHOW FULL COLUMNS` sul DB locale `marconinertia` — aggiornare ad ogni migration rilevante.

Legenda chiavi: `PK` primary, `FK` foreign, `UNI` unique, `IDX` index, `MUL` multi-key/FK.

---

## Anagrafiche

### customers
| Campo | Tipo | Null | Note |
|-------|------|------|------|
| id | bigint unsigned | no | PK |
| company_name | varchar(191) | sì | IDX fulltext |
| vat_number | varchar(191) | sì | |
| tax_code | varchar(191) | sì | |
| legal_address | varchar(191) | sì | |
| sdi_code | varchar(191) | sì | |
| business_type | enum(generico,industriale,commerciale,agricola) | sì | |
| seller_id | bigint unsigned | no | FK users |
| is_occasional_customer | tinyint(1) | sì | |
| sales_email | varchar(191) | sì | |
| administrative_email | varchar(191) | sì | |
| certified_email | varchar(191) | sì | |
| notes | text | sì | |
| created_at / updated_at / deleted_at | timestamp | sì | soft delete |

### sites
| Campo | Tipo | Null | Note |
|-------|------|------|------|
| id | bigint unsigned | no | PK |
| customer_id | bigint unsigned | no | FK customers |
| name | varchar(191) | sì | |
| site_type | varchar(191) | sì | |
| is_main | tinyint(1) | sì | sede principale del cliente |
| address | varchar(191) | sì | |
| latitude | double | sì | |
| longitude | double | sì | |
| calculated_risk_factor | double | sì | 0.0–1.0+ (calcolato da ritiri storici) |
| days_until_next_withdraw | bigint | sì | giorni stimati al prossimo ritiro |
| has_muletto | tinyint(1) | no | |
| has_electric_pallet_truck | tinyint(1) | sì | |
| has_manual_pallet_truck | tinyint(1) | sì | |
| other_machines | text | no | |
| has_adr_consultant | tinyint(1) | no | |
| notes | text | sì | |
| created_at / updated_at / deleted_at | timestamp | sì | soft delete |

### internal_contacts
| Campo | Tipo | Null | Note |
|-------|------|------|------|
| id | bigint unsigned | no | PK |
| site_id | bigint unsigned | no | FK sites |
| name | varchar(191) | no | |
| surname | varchar(191) | sì | |
| phone | varchar(191) | sì | |
| mobile | varchar(191) | sì | |
| email | varchar(191) | sì | |
| role | varchar(191) | sì | |
| created_at / updated_at | timestamp | sì | |

### timetables
| Campo | Tipo | Null | Note |
|-------|------|------|------|
| id | bigint unsigned | no | PK |
| site_id | bigint unsigned | no | FK sites |
| hours_json | longtext | sì | orari di apertura in JSON |
| created_at / updated_at | timestamp | sì | |

### withdraws
| Campo | Tipo | Null | Note |
|-------|------|------|------|
| id | bigint unsigned | no | PK |
| customer_id | bigint unsigned | no | FK customers |
| site_id | bigint unsigned | no | FK sites |
| vehicle_id | bigint unsigned | sì | FK vehicles |
| driver_id | bigint unsigned | sì | FK users |
| created_by_user_id | bigint unsigned | sì | |
| withdrawn_at | datetime | sì | data effettiva ritiro |
| residue_percentage | float | sì | percentuale residuo |
| is_manual_entry | tinyint(1) | sì | |
| created_at / updated_at / deleted_at | timestamp | sì | soft delete |

---

## Ordini

### orders
| Campo | Tipo | Null | Note |
|-------|------|------|------|
| id | bigint unsigned | no | PK |
| legacy_code | varchar(191) | sì | codice dal sistema legacy |
| customer_id | bigint unsigned | no | FK customers |
| site_id | bigint unsigned | no | FK sites |
| journey_id | bigint unsigned | sì | FK journeys |
| logistics_user_id | bigint unsigned | sì | FK users |
| crane_operator_user_id | bigint unsigned | sì | FK users |
| status | enum(creato,pronto,pianificato,eseguito,scaricato,chiuso) | sì | |
| documents_status | enum(not_generated,generating,generated,failed) | no | |
| documents_generated_at | timestamp | sì | |
| documents_version | int unsigned | no | |
| documents_error | text | sì | |
| cargo_location | enum(vehicle,trailer,fulfill) | sì | |
| is_urgent | tinyint(1) | no | |
| requested_at | timestamp | sì | data richiesta ordine |
| expected_withdraw_at | timestamp | sì | data ritiro pianificata |
| fixed_withdraw_at | timestamp | sì | data ritiro fissa |
| actual_withdraw_at | timestamp | sì | data ritiro effettiva |
| has_crane | tinyint(1) | sì | |
| machinery_time_minutes | int | sì | |
| notes | text | sì | |
| created_at / updated_at / deleted_at | timestamp | sì | soft delete |

### order_items
| Campo | Tipo | Null | Note |
|-------|------|------|------|
| id | bigint unsigned | no | PK |
| order_id | bigint unsigned | no | FK orders |
| cer_code_id | bigint unsigned | no | FK cer_codes |
| holder_id | bigint unsigned | sì | FK holders (null se sfuso) |
| order_item_group_id | bigint unsigned | sì | FK order_item_groups |
| updated_by_user_id | bigint unsigned | sì | FK users |
| warehouse_manager_id | bigint unsigned | sì | FK users |
| warehouse_id | bigint unsigned | sì | FK warehouses |
| **is_bulk** | tinyint(1) | no | **true = materiale sfuso** |
| holder_quantity | int | sì | numero holder (null se sfuso) |
| custom_l_cm / custom_w_cm / custom_h_cm | decimal(8,2) | sì | dimensioni custom |
| description | text | sì | |
| weight_declared | double | sì | peso dichiarato (kg) |
| weight_gross | double | sì | peso lordo (kg) |
| weight_tare | double | sì | tara (kg) |
| weight_net | double | sì | peso netto (kg) |
| adr / has_adr | tinyint(1) | sì | materiale ADR |
| adr_un_code | varchar(191) | sì | |
| adr_hp | varchar(191) | sì | |
| adr_lotto / adr_lot_code | varchar(191) | sì | |
| adr_volume | double | sì | |
| has_adr_total_exemption | tinyint(1) | sì | |
| has_adr_partial_exemption | tinyint(1) | sì | |
| status | enum(creato,caricato,scaricato,lavorazione,classificato,chiuso,trasbordo) | sì | |
| is_holder_dirty / total_dirty_holders | tinyint(1) / int | no/sì | |
| is_holder_broken / total_broken_holders | tinyint(1) / int | no/sì | |
| is_warehouse_added | tinyint(1) | no | aggiunto in magazzino |
| is_not_found | tinyint(1) | no | non trovato in magazzino |
| has_non_conformity | tinyint(1) | no | |
| has_exploded_children | tinyint(1) | no | ha figli da esplosione ricetta |
| has_selection | tinyint(1) | no | |
| selection_duration_minutes | double | sì | |
| is_crane_eligible | tinyint(1) | sì | |
| is_transshipment | tinyint(1) | no | |
| recognized_price / recognized_weight | double | sì | |
| machinery_time_fraction / machinery_time_share | int | sì | |
| is_machinery_time_manual | tinyint(1) | no | |
| warehouse_download_at / warehouse_weighing_dt / warehouse_selection_dt | timestamp | sì | |
| warehouse_notes / warehouse_non_conformity | text | sì | |
| created_at / updated_at / deleted_at | timestamp | sì | soft delete |

### order_holders
| Campo | Tipo | Null | Note |
|-------|------|------|------|
| id | bigint unsigned | no | PK |
| order_id | bigint unsigned | no | FK orders |
| holder_id | bigint unsigned | no | FK holders |
| filled_holders_count | int | sì | |
| empty_holders_count | int | sì | |
| total_holders_count | int | sì | |
| created_at / updated_at / deleted_at | timestamp | sì | soft delete |

---

## CER e Materiali

### cer_codes
| Campo | Tipo | Null | Note |
|-------|------|------|------|
| id | bigint unsigned | no | PK |
| code | varchar(191) | no | es. "160214" |
| description | varchar(191) | no | |
| is_dangerous | tinyint(1) | no | codice pericoloso (asterisco) |
| created_at / updated_at / deleted_at | timestamp | sì | soft delete |

### catalog_items
| Campo | Tipo | Null | Note |
|-------|------|------|------|
| id | bigint unsigned | no | PK |
| name | varchar(191) | no | UNI |
| type | enum(material,component) | no | |
| code | varchar(191) | sì | |
| parent_catalog_item_id | bigint unsigned | sì | FK self (albero) |
| is_active | tinyint(1) | no | |
| created_at / updated_at / deleted_at | timestamp | sì | soft delete |

### recipes
| Campo | Tipo | Null | Note |
|-------|------|------|------|
| id | bigint unsigned | no | PK |
| name | varchar(191) | no | UNI |
| catalog_item_id | bigint unsigned | sì | FK catalog_items UNI |
| version | int unsigned | no | |
| is_active | tinyint(1) | no | |
| created_at / updated_at / deleted_at | timestamp | sì | soft delete |

### recipe_nodes
| Campo | Tipo | Null | Note |
|-------|------|------|------|
| id | bigint unsigned | no | PK |
| recipe_id | bigint unsigned | no | FK recipes |
| parent_node_id | bigint unsigned | sì | FK self |
| catalog_item_id | bigint unsigned | no | FK catalog_items |
| is_override | tinyint(1) | no | |
| sort | int unsigned | no | |
| suggested_ratio | decimal(8,3) | sì | |
| created_at / updated_at | timestamp | sì | |

### order_item_explosions
| Campo | Tipo | Null | Note |
|-------|------|------|------|
| id | bigint unsigned | no | PK |
| order_item_id | bigint unsigned | no | FK order_items |
| parent_explosion_id | bigint unsigned | sì | FK self |
| catalog_item_id | bigint unsigned | no | FK catalog_items |
| recipe_id | bigint unsigned | sì | FK recipes |
| recipe_version | int unsigned | sì | |
| explosion_source | enum(ad_hoc,recipe) | sì | |
| weight_net | decimal(10,3) | sì | kg |
| notes | text | sì | |
| sort | int unsigned | no | |
| created_at / updated_at | timestamp | sì | |

---

## Logistica e Journey

### journeys
| Campo | Tipo | Null | Note |
|-------|------|------|------|
| id | bigint unsigned | no | PK |
| vehicle_id | bigint unsigned | no | FK vehicles |
| trailer_id | bigint unsigned | sì | FK trailers |
| driver_id | bigint unsigned | no | FK users |
| logistics_user_id | bigint unsigned | sì | FK users |
| vehicle_cargo_id | bigint unsigned | sì | FK cargos |
| trailer_cargo_id | bigint unsigned | sì | FK cargos |
| primary_warehouse_id | bigint unsigned | sì | FK warehouses |
| secondary_warehouse_id | bigint unsigned | sì | FK warehouses |
| status | enum(creato,attivo,eseguito,chiuso) | sì | |
| dispatch_status | enum(pending,in_progress,on_hold,managed) | no | |
| is_double_load | tinyint(1) | no | |
| is_temporary_storage | tinyint(1) | no | |
| planned_start_at / planned_end_at | timestamp | sì | |
| actual_start_at / actual_end_at | timestamp | sì | |
| primary_warehouse_download_at | timestamp | sì | |
| secondary_warehouse_download_at | timestamp | sì | |
| dispatch_started_at / dispatch_managed_at / dispatch_updated_at | timestamp | sì | |
| plan_version | int unsigned | no | |
| notes | text | sì | |
| created_at / updated_at / deleted_at | timestamp | sì | soft delete |

### journey_stops
| Campo | Tipo | Null | Note |
|-------|------|------|------|
| id | bigint unsigned | no | PK |
| journey_id | bigint unsigned | no | FK journeys |
| kind | enum(customer,technical) | sì | |
| customer_id | bigint unsigned | sì | FK customers |
| technical_action_id | bigint unsigned | sì | FK journey_stop_actions |
| customer_visit_index | int unsigned | sì | indice visita cliente nel journey |
| planned_sequence / sequence | int unsigned | no | ordine pianificato / effettivo |
| status | enum(planned,in_progress,done,skipped,cancelled) | sì | |
| location_lat / location_lng | decimal(10,7) | sì | |
| address_text | varchar(191) | sì | |
| started_at / completed_at | timestamp | sì | |
| description / notes / reason_text / driver_notes | text/varchar | sì | |
| reason_code | varchar(64) | sì | |
| created_at / updated_at | timestamp | sì | |

### journey_stop_orders
| Campo | Tipo | Null | Note |
|-------|------|------|------|
| id | bigint unsigned | no | PK |
| journey_id | bigint unsigned | no | FK journeys |
| journey_stop_id | bigint unsigned | no | FK journey_stops |
| order_id | bigint unsigned | no | FK orders |
| created_at / updated_at | timestamp | sì | |

### journey_stop_actions
| Campo | Tipo | Null | Note |
|-------|------|------|------|
| id | bigint unsigned | no | PK |
| code | varchar(191) | no | UNI |
| label | varchar(191) | no | |
| requires_location | tinyint(1) | no | |
| is_active | tinyint(1) | no | IDX |
| created_at / updated_at | timestamp | sì | |

### journey_cargos
| Campo | Tipo | Null | Note |
|-------|------|------|------|
| id | bigint unsigned | no | PK |
| journey_id | bigint unsigned | no | FK journeys |
| cargo_id | bigint unsigned | no | FK cargos |
| warehouse_id | bigint unsigned | sì | FK warehouses |
| cargo_location | enum(vehicle,trailer,fulfill) | sì | |
| operation_mode | enum(unload,drop_only) | no | |
| is_grounded | tinyint(1) | sì | |
| download_sequence | tinyint unsigned | no | |
| status | enum(creato,attivo,eseguito,chiuso) | sì | |
| created_at / updated_at / deleted_at | timestamp | sì | soft delete |

---

## Mezzi e Contenitori

### vehicles
| Campo | Tipo | Null | Note |
|-------|------|------|------|
| id | bigint unsigned | no | PK |
| name / description / plate / type | varchar | sì | |
| driver_id | bigint unsigned | sì | FK users (driver default) |
| trailer_id | bigint unsigned | sì | FK trailers |
| has_trailer | tinyint(1) | no | |
| load_capacity | double | no | kg |
| created_at / updated_at | timestamp | sì | |

### trailers
| Campo | Tipo | Null | Note |
|-------|------|------|------|
| id | bigint unsigned | no | PK |
| name / description / plate | varchar | sì | |
| is_front_cargo | tinyint | sì | |
| load_capacity | double | no | kg |
| created_at / updated_at | timestamp | sì | |

### cargos
| Campo | Tipo | Null | Note |
|-------|------|------|------|
| id | bigint unsigned | no | PK |
| name / description | varchar | sì | |
| is_cargo | tinyint(1) | no | |
| is_long | tinyint(1) | no | |
| total_count | int | no | |
| length | double | sì | |
| crate_count (casse) | int | sì | numero cassoni |
| crate_slots (spazi_casse) | int | sì | spazi cassoni |
| pallet_slots (spazi_bancale) | int | sì | spazi bancale |
| created_at / updated_at | timestamp | sì | |

### holders
| Campo | Tipo | Null | Note |
|-------|------|------|------|
| id | bigint unsigned | no | PK |
| name | varchar(191) | sì | |
| description | varchar(191) | sì | |
| is_custom | tinyint(1) | no | IDX |
| volume | double | sì | litri |
| equivalent_holder_id | bigint unsigned | sì | FK self |
| equivalent_units | int unsigned | sì | |
| created_at / updated_at | timestamp | sì | |

---

## Magazzino e Aree

### warehouses
| Campo | Tipo | Null | Note |
|-------|------|------|------|
| id | bigint unsigned | no | PK |
| name / address | varchar | sì | |
| latitude / longitude | double | sì | |
| notes | text | sì | |
| created_at / updated_at | timestamp | sì | |

### areas
| Campo | Tipo | Null | Note |
|-------|------|------|------|
| id | bigint unsigned | no | PK |
| name | varchar(191) | no | |
| polygon | text | no | coordinate poligono area |
| created_at / updated_at | timestamp | sì | |

---

## Utenti e Operatori

### users
| Campo | Tipo | Null | Note |
|-------|------|------|------|
| id | bigint unsigned | no | PK |
| name / surname | varchar(191) | no | |
| user_code | varchar(191) | sì | codice operativo |
| email | varchar(191) | sì | UNI (null per worker-user) |
| email_verified_at | timestamp | sì | |
| password | varchar(191) | sì | |
| avatar | varchar(191) | sì | path relativo storage |
| role | enum(manager,logistic,driver,warehouse_chief,warehouse_manager,warehouse_worker,customer,developer) | sì | |
| is_admin | tinyint(1) | no | bypass tutte le policy |
| customer_id | bigint unsigned | sì | FK customers (per ruolo customer) |
| can_login | tinyint(1) | no | |
| is_crane_operator | tinyint(1) | sì | |
| remember_token | varchar(100) | sì | |
| created_at / updated_at | timestamp | sì | |

### workers
| Campo | Tipo | Null | Note |
|-------|------|------|------|
| id | bigint unsigned | no | PK |
| name / surname | varchar(191) | no | |
| created_at / updated_at | timestamp | sì | |

---

## NLP

### nlp_query_logs
| Campo | Tipo | Null | Note |
|-------|------|------|------|
| id | bigint unsigned | no | PK |
| user_id | bigint unsigned | sì | FK users (null se system) |
| intent | varchar(32) | no | logistics \| analytics |
| operation | varchar(32) | no | parse \| execute |
| raw_text | text | no | query originale |
| parsed_json | longtext | sì | JSON strutturato prodotto |
| provider | varchar(64) | sì | heuristic \| openai |
| model | varchar(64) | sì | es. gpt-4o |
| success | tinyint(1) | no | IDX |
| error_code | varchar(64) | sì | |
| latency_ms | int unsigned | sì | |
| token_usage | longtext | sì | JSON {prompt, completion, total} |
| created_at / updated_at | timestamp | sì | |

---

## Pivot / Join Tables

| Tabella | Entità collegate |
|---------|-----------------|
| area_site | areas ↔ sites |
| user_warehouse | users ↔ warehouses |
| worker_warehouse | workers ↔ warehouses |
| journey_cargo_order_item | journey_cargos ↔ order_items |

---

## Note operative

**Sfuso vs Holder**: `order_items.is_bulk = true` identifica materiale sfuso. Se `false`, il materiale è in holder (`holder_id` + `holder_quantity` valorizzati).

**CER**: il codice testuale è in `cer_codes.code` (es. `"160214"`). `order_items` usa FK `cer_code_id`.

**Rischio sito**: `sites.calculated_risk_factor` — valore float, calcolato da ritiri storici. Soglia corrente per "pericoloso" in NLP: `>= 0.75` (vedi `LogisticsCandidateQueryBuilder::HAZARDOUS_RISK_THRESHOLD`).

**Status ordine — mapping NLP→DB**:
| NLP | DB enum |
|-----|---------|
| requested | creato, pronto |
| planned | pianificato |
| executed | eseguito, scaricato |
| closed | chiuso |

**Ultimo ritiro**: calcolato tramite `withdraws.withdrawn_at` per `site_id`.
