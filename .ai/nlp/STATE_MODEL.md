# NLP State Model

## Purpose
- Keep map-guided journey creation deterministic and explainable.
- Separate NLP draft state from user manual overrides.
- Enable refinement on `journey/create` without losing control.

## Core State Objects

### 1) `NlpQueryState`
Represents the latest structured NLP interpretation.

Fields:
- `id` (string|null): optional persisted draft id
- `intent` (`logistics`)
- `schema` (`logistics_canonical`)
- `raw_text` (string)
- `parsed_query` (object `LogisticsQuery`)
- `status` (`idle|parsing|parsed|invalid|error`)
- `warnings` (array)
- `error` (object|null)
- `source` (`map|journey_create`)
- `updated_at` (ISO datetime)

### 2) `CandidateSelectionState`
Represents deterministic candidate results produced from `parsed_query`.

Fields:
- `site_ids` (array<int>)
- `order_ids` (array<int>) nullable in map phase
- `total_candidates` (int)
- `applied` (bool)  
- `applied_at` (ISO datetime|null)
- `strategy` (`deterministic_filter_v1`)

### 3) `JourneyDraftState`
Represents the working draft in `journey/create`.

Fields:
- `selected_order_ids_truck` (array<int>)
- `selected_order_ids_trailer` (array<int>)
- `selected_order_ids_fulfill` (array<int>)
- `manual_overrides` (object)
- `origin_mode` (`manual|guided_map`)
- `nlp_linked_query_id` (string|null)
- `dirty` (bool)

### 4) `UiPanelState`
Represents UI-only behavior.

Fields:
- `is_nlp_panel_open` (bool)
- `is_info_panel_open` (bool)
- `is_loading` (bool)
- `last_action` (string|null)

## Ownership by Page

Map page owns:
- `NlpQueryState` (source = `map`)
- `CandidateSelectionState` for sites
- `UiPanelState`

Journey/create page owns:
- `NlpQueryState` (copied/linked from map)
- `CandidateSelectionState` for orders
- `JourneyDraftState`
- `UiPanelState`

## Allowed Transitions

### Map flow
1. `idle` -> `parsing` on Parse click
2. `parsing` -> `parsed` on valid structured query
3. `parsed` -> `applied=true` when user applies candidates
4. `parsed` -> `parsing` on refinement
5. any -> `error` on provider/validation failure

### Handoff map -> journey/create
1. User clicks "Go to journey definition"
2. Carry:
   - `raw_text`
   - `parsed_query`
   - candidate IDs (or a persisted draft id)
3. `journey/create` initializes `origin_mode=guided_map`

### Journey/create refinement
1. Current `NlpQueryState` loaded in panel
2. User refines text
3. New parse updates `parsed_query` and recalculates candidates
4. If user already changed selections manually:
   - mark `dirty=true`
   - require explicit "Apply new NLP set" action

## Deterministic Merge Rules

When applying NLP results in `journey/create`:
- Default behavior: replace candidate set, keep manual vehicle/date fields unchanged.
- If manual overrides exist on order selection:
  - show conflict summary
  - user chooses:
    - `replace_selection`
    - `merge_add_only`
    - `cancel`

## Persistence Strategy

Phase 1:
- Keep state in memory on page and pass via query payload for quick iteration.

Phase 2:
- Persist in `nlp_query_logs` + optional `nlp_conversation_states`.
- Pass only `nlp_query_state_id` between pages.

## API Contract Touchpoints

Map NLP parse:
- input: `{ query, context }`
- output: `{ ok, parsed, warnings }`

Map apply:
- local deterministic filtering of current loaded sites for MVP (`planning_sites` and site-side of `hybrid`).
- server-side deterministic execute in next phase.

Journey/create:
- accepts initial NLP payload or state id.
- allows re-parse/re-apply from side panel.
