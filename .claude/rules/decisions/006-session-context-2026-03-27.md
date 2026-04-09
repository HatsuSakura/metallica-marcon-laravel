# Session Context 2026-03-27

## Completed
- Route / middleware hardening completed.
- Policy hardening and explicit gate registration completed.
- Authorization report generation completed.
- Repeatable authorization matrix export implemented.
- `UserAccountController@update` hardened with `self_or_admin` controller check.

## Canonical sources
- `.ai/decisions/authorization-matrix.json`
- `scripts/export-authorization-matrix.ps1`
- `.ai/decisions/generated/authorization-gates.csv`
- `.ai/decisions/generated/authorization-resources.csv`
- `.ai/decisions/generated/authorization-routes.csv`
- `.ai/decisions/generated/authorization-matrix.md`

## Current rules of note
- `UserAccountController@update`: only target user or admin.
- `OrderItemExplosionController@store/@update/@destroy/@applyRecipe`: tracked as `stale_route`, intentionally pending.
- `WithdrawController@attachFile`: tracked as `stale_route`, intentionally pending.

## Operational note
Regenerate the matrix with:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/export-authorization-matrix.ps1
```

The JSON file is the source of truth. CSV/Markdown files are generated artifacts.
