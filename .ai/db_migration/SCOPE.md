# Scope For Column Standardization (Phase 1-3)

## In Scope (Domain tables)
- `areas`
- `area_site`
- `cargos`
- `catalog_items`
- `cer_codes`
- `customers`
- `holders`
- `internal_contacts`
- `journeys`
- `journey_cargos`
- `journey_cargo_order_item`
- `journey_events`
- `journey_stops`
- `journey_stop_actions`
- `journey_stop_orders`
- `orders`
- `order_counters`
- `order_holders`
- `order_items`
- `order_item_explosions`
- `order_item_groups`
- `order_item_images`
- `recipes`
- `recipe_nodes`
- `sites`
- `timetables`
- `trailers`
- `users`
- `user_warehouse`
- `vehicles`
- `warehouses`
- `withdraws`
- `workers`
- `worker_warehouse`

## Out Of Scope (Framework/system/support tables)
- `cache`
- `cache_locks`
- `failed_jobs`
- `jobs`
- `job_batches`
- `migrations`
- `notifications`
- `password_reset_tokens`
- `sessions`
- `versions`

## Notes
- This scope matches the current request (bullets 1-3 only).
- Data migration from old DB dump will target the canonical names defined here.
