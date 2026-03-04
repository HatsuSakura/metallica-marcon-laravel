# DB Naming Conventions (Canonical Target)

## Goal
Define a single canonical naming style for domain schema before legacy-data migration.

## Rules
- Language: English only.
- Case/style: `snake_case`.
- Primary key: `id`.
- Foreign key: `<related_entity>_id`.
- Datetime/timestamp suffix: `_at` (example: `planned_start_at`).
- Date-only suffix: `_date` only when no time component is needed.
- Boolean prefix: `is_` or `has_`.
- Count/quantity suffix: `_count` or `_quantity` (explicit units when useful).
- Latitude/longitude: `latitude`, `longitude`.
- Status/state fields: prefer `status` over `state` (with explicit enum map).
- Acronyms:
  - Keep domain acronyms when they are standards (`cer`, `adr`, `sdi`).
  - Expand unclear acronyms to canonical terms where possible.

## Explicit Decisions (already agreed)
- `ragione_sociale` -> `company_name`
- `partita_iva` -> `vat_number`
