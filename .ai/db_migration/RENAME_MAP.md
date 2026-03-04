# Rename Map (Current Schema -> Canonical Schema)

## Legend
- `safe`: name-only change (no data transform).
- `semantic`: name change plus meaning/value alignment required.
- `typo`: spelling correction.

## areas
No column renames planned.

## area_site
No column renames planned.

## cargos
| old_column | new_column | class | notes |
|---|---|---|---|
| `casse` | `crate_count` | safe | Italian -> English |
| `spazi_casse` | `crate_slots` | safe | Italian -> English |
| `spazi_bancale` | `pallet_slots` | safe | Italian -> English |

## catalog_items
No column renames planned.

## cer_codes
No column renames planned.

## customers
| old_column | new_column | class | notes |
|---|---|---|---|
| `customer_occasionale` | `is_occasional_customer` | safe | boolean naming |
| `ragione_sociale` | `company_name` | semantic | agreed business term |
| `partita_iva` | `vat_number` | semantic | agreed business term |
| `codice_fiscale` | `tax_code` | semantic | Italy-specific fiscal code |
| `indirizzo_legale` | `legal_address` | safe | Italian -> English |
| `codice_sdi` | `sdi_code` | safe | keep SDI acronym |
| `email_commerciale` | `sales_email` | safe | Italian -> English |
| `email_amministrativa` | `administrative_email` | safe | Italian -> English |
| `pec` | `certified_email` | semantic | PEC meaning normalization |
| `job_type` | `business_type` | semantic | confirm business meaning |

## holders
No column renames planned.

## internal_contacts
No column renames planned.

## journeys
| old_column | new_column | class | notes |
|---|---|---|---|
| `dt_start` | `planned_start_at` | semantic | planned vs actual clarity |
| `dt_end` | `planned_end_at` | semantic | planned vs actual clarity |
| `real_dt_start` | `actual_start_at` | semantic | planned vs actual clarity |
| `real_dt_end` | `actual_end_at` | semantic | planned vs actual clarity |
| `warehouse_id_1` | `primary_warehouse_id` | semantic | role-based naming |
| `warehouse_download_dt_1` | `primary_warehouse_download_at` | semantic | role-based naming |
| `warehouse_id_2` | `secondary_warehouse_id` | semantic | role-based naming |
| `warehouse_download_dt_2` | `secondary_warehouse_download_at` | semantic | role-based naming |
| `cargo_for_vehicle_id` | `vehicle_cargo_id` | safe | simplify name |
| `cargo_for_trailer_id` | `trailer_cargo_id` | safe | simplify name |
| `logistic_id` | `logistics_user_id` | semantic | clearer FK owner role |
| `state` | `status` | semantic | global convention |

## journey_cargos
| old_column | new_column | class | notes |
|---|---|---|---|
| `truck_location` | `cargo_location` | semantic | domain term normalization |
| `is_grounding` | `is_grounded` | semantic | boolean meaning cleanup |
| `state` | `status` | semantic | global convention |

## journey_cargo_order_item
| old_column | new_column | class | notes |
|---|---|---|---|
| `warehouse_download_id` | `download_warehouse_id` | safe | role-first FK naming |

## journey_events
| old_column | new_column | class | notes |
|---|---|---|---|
| `state` | `status` | semantic | global convention |

## journey_stops
No column renames planned.

## journey_stop_actions
No column renames planned.

## journey_stop_orders
No column renames planned.

## orders
| old_column | new_column | class | notes |
|---|---|---|---|
| `logistic_id` | `logistics_user_id` | semantic | clearer FK owner role |
| `state` | `status` | semantic | global convention |
| `expected_withdraw_dt` | `expected_withdraw_at` | safe | `_at` datetime suffix |
| `real_withdraw_dt` | `actual_withdraw_at` | semantic | planned vs actual clarity |
| `has_ragno` | `has_crane` | semantic | Italian term normalization |
| `ragnista_id` | `crane_operator_user_id` | semantic | Italian term normalization |
| `machinery_time` | `machinery_time_minutes` | semantic | add explicit unit |

## order_counters
No column renames planned.

## order_holders
| old_column | new_column | class | notes |
|---|---|---|---|
| `holder_piene` | `filled_holders_count` | safe | Italian -> English |
| `holder_vuote` | `empty_holders_count` | safe | Italian -> English |
| `holder_totale` | `total_holders_count` | safe | Italian -> English |

## order_items
| old_column | new_column | class | notes |
|---|---|---|---|
| `adr` | `has_adr` | safe | boolean naming |
| `adr_onu_code` | `adr_un_code` | semantic | ONU -> UN normalization |
| `adr_lotto` | `adr_lot_code` | safe | Italian -> English |
| `warehouse_downaload_worker_id` | `warehouse_download_worker_id` | typo | spelling fix |
| `warehouse_downaload_dt` | `warehouse_download_at` | typo | spelling + `_at` |
| `is_ragnabile` | `is_crane_eligible` | semantic | Italian term normalization |
| `selection_time` | `selection_duration_minutes` | semantic | explicit unit |
| `machinery_time_fraction` | `machinery_time_share` | semantic | clarify business meaning |
| `adr_totale` | `is_adr_total` | semantic | boolean naming + meaning review |
| `adr_esenzione_totale` | `has_adr_total_exemption` | semantic | Italian -> English |
| `adr_esenzione_parziale` | `has_adr_partial_exemption` | semantic | Italian -> English |
| `state` | `status` | semantic | global convention |

## order_item_explosions
No column renames planned.

## order_item_groups
No column renames planned.

## order_item_images
No column renames planned.

## recipes
No column renames planned.

## recipe_nodes
No column renames planned.

## sites
| old_column | new_column | class | notes |
|---|---|---|---|
| `denominazione` | `name` | safe | Italian -> English |
| `tipologia` | `site_type` | safe | Italian -> English |
| `indirizzo` | `address` | safe | Italian -> English |
| `lat` | `latitude` | safe | canonical geospatial naming |
| `lng` | `longitude` | safe | canonical geospatial naming |
| `fattore_rischio_calcolato` | `calculated_risk_factor` | semantic | Italian -> English |
| `giorni_prossimo_ritiro` | `days_until_next_withdraw` | semantic | Italian -> English |
| `has_transpallet_el` | `has_electric_pallet_truck` | semantic | abbreviation expansion |
| `has_transpallet_ma` | `has_manual_pallet_truck` | semantic | abbreviation expansion |

## timetables
| old_column | new_column | class | notes |
|---|---|---|---|
| `hours_array` | `hours_json` | semantic | stored type is JSON text |

## trailers
No column renames planned.

## users
| old_column | new_column | class | notes |
|---|---|---|---|
| `is_ragnista` | `is_crane_operator` | semantic | Italian term normalization |

## user_warehouse
No column renames planned.

## vehicles
No column renames planned.

## warehouses
| old_column | new_column | class | notes |
|---|---|---|---|
| `denominazione` | `name` | safe | Italian -> English |
| `indirizzo` | `address` | safe | Italian -> English |
| `lat` | `latitude` | safe | canonical geospatial naming |
| `lng` | `longitude` | safe | canonical geospatial naming |
| `note` | `notes` | safe | plural consistency |

## withdraws
| old_column | new_column | class | notes |
|---|---|---|---|
| `withdraw_date` | `withdrawn_at` | semantic | datetime suffix + tense |
| `manual_insert` | `is_manual_entry` | safe | boolean naming |
| `user_id` | `created_by_user_id` | semantic | confirm role meaning |

## workers
No column renames planned.

## worker_warehouse
No column renames planned.

## Confirmed Decisions
- Apply `state -> status` in this phase for compatibility columns.
- Use `pec -> certified_email`.
- `machinery_time` and `selection_time` units are minutes.
- `withdraws.user_id` semantic role is `created_by_user_id`.
