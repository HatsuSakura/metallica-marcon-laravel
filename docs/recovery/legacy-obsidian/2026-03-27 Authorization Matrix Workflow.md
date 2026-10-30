
> **Nota recuperata — storica, non procedura corrente.** Origine: vecchio vault Obsidian; recupero 2026-10-30. Per lo stato attuale consultare [sviluppi e riconciliazione](../../development-status.md). Non eseguire comandi di deploy, migrazione o modifica dati sulla sola base di questa nota.

# Authorization Matrix Workflow

Data: 2026-03-27

## Source of truth
- Canonical matrix: `.ai/decisions/authorization-matrix.json`
- Export script: `scripts/export-authorization-matrix.ps1`
- Generated outputs: `.ai/decisions/generated/authorization-gates.csv`, `.ai/decisions/generated/authorization-resources.csv`, `.ai/decisions/generated/authorization-routes.csv`, `.ai/decisions/generated/authorization-matrix.md`

## Execution
```powershell
powershell -ExecutionPolicy Bypass -File scripts/export-authorization-matrix.ps1
```

## Extraction model
- `authorization-matrix.json` is the only maintained semantic source.
- CSV/Markdown outputs are generated artifacts and can be regenerated at any time.
- The process is idempotent: update JSON and/or route/controller protections, then rerun the script.

## Route table shape
- `method`
- `uri`
- `name`
- `action`
- `middleware`
- `area_gate`
- `resource`
- `ability`
- `authorization_type`
- `authorization_name`
- `authorization_level`
- `roles`
- `notes`

## Resource table shape
- `resource`
- `read_roles`
- `create_roles`
- `update_roles`
- `delete_roles`
- `read_level`
- `write_level`
- `notes`

## Special authorization states
- `policy`, `policy_ability`, `gate`, `controller_check`, `controller_scope`: effective protection exists.
- `middleware_authorize`: authorization derived from route middleware `Authorize:*`.
- `policy_inferred`: authorization inferred from resource controller action and resource matrix.
- `authorization_gap`: route is protected only by coarse middleware and lacks explicit controller policy/gate.
- `stale_route`: route exists but mapped controller method is missing.
- `public_endpoint`: intentionally callable without auth middleware.

## Findings to keep visible
- `UserAccountController@update` is currently marked as `authorization_gap`.
- `OrderItemExplosionController@store`, `@update`, `@destroy`, `@applyRecipe` are currently marked as `stale_route`.
- `WithdrawController@attachFile` is currently marked as `stale_route`.

## Current status
- Full route coverage in generated CSV: 198/198 routes classified.
- Generated markdown report: `.ai/decisions/generated/authorization-matrix.md`

## Update 2026-03-27 15:40
- `UserAccountController@update` is no longer an authorization gap.
- Current rule: `self_or_admin`.
- Implementation: controller-side explicit check allowing profile update only if the authenticated user matches the route-bound user, or if the authenticated user is admin.

## Pending legacy routes
The following items stay tracked as pending and are not to be removed yet because they belong to future warehouse-worker refactoring:
- `OrderItemExplosionController@store`
- `OrderItemExplosionController@update`
- `OrderItemExplosionController@destroy`
- `OrderItemExplosionController@applyRecipe`
- `WithdrawController@attachFile`

These routes remain classified as `stale_route` in the generated matrix until their worker-domain implementation is taken in hand.

## Session closure snapshot
- Hardening completed on routes, policies, gates and controller authorization boundaries.
- Canonical source remains `.ai/decisions/authorization-matrix.json`.
- Export script remains `scripts/export-authorization-matrix.ps1`.
- `UserAccountController@update` is hardened with `self_or_admin`.
- Warehouse legacy routes remain intentionally pending and tracked as `stale_route`.
