# PHP 8.4 runtime upgrade (Laravel 11 kept)

Prepared 2026-11-04 on `chore/php84-laravel11-latest`, applied on `main`
on top of the QA driver release (`3863996`).

## Why

- PHP 8.2 reaches end of life on 2026-12-31.
- PHP2Shell (CVE-2026-109618/109619) needs at least 8.4.27 on the 8.4
  branch.
- Laravel 11 supports PHP 8.2–8.4, so **8.4 is the ceiling** until the
  Laravel 12 upgrade. PHP 8.5 needs Laravel 12.
- PHP 8.4 has security support until the end of 2028.

## What changes

| Item | Before | After |
|---|---|---|
| Runtime image | `php:8.2-apache` (floating) | `php:8.4.27-apache` (pinned) |
| Local dev (`.mise.toml`) | PHP 8.2.32 | PHP 8.4.27 |
| `laravel/framework` | v11.21.0 | v11.57.1, the last 11.x |
| Other dependencies | — | minor and patch updates within the existing constraints (`-W`), including `nette/schema` 1.3.0 → 1.4.0, which allows PHP 8.4 |
| `config.platform.php` | none (resolved for the local PHP) | `8.2.0`: the lock stays installable on QA, see below |
| `deploy.sh` | `docker compose build` | `docker compose build --pull` |
| `expose_php` | On (`X-Powered-By: PHP/x.y.z`) | Off |
| Own code | `NlpProviderFactory::make(string $override = null)` | `?string`: the only PHP 8.4 deprecation in `app/` |

**Composer advisories:** 62 advisories in 16 packages before; 0 open
after, plus 5 accepted below.

## Lock resolved for PHP 8.2 (`config.platform.php`)

QA runs on SiteGround with resident **PHP 8.2.35** and deploys with
`composer install` from the lock; production runs the Docker image on 8.4.
Resolved on PHP 8.4 without a pin, the lock pulled `symfony/clock`,
`css-selector`, `event-dispatcher`, `string` and `translation` 8.1, which
require PHP ≥ 8.4.1 and would break the QA install. With `platform.php`
set to `8.2.0` they stay on 7.4, which runs on both.

Remove the pin (or raise it to 8.4) once QA moves to PHP 8.4.

## Accepted Laravel 11 advisories (`config.policy.advisories.ignore-id`)

Laravel 11 is out of security support. These five are fixed only in 12.x,
and Composer refuses every 11.x version unless they are ignored
explicitly.

| Advisory | Severity | Exposure in this app |
|---|---|---|
| PKSA-b41t-wbzk-9q51: TokenGuard authentication bypass | critical | **not exploitable**: only the `session` guard is configured; no `token`/`api` guard, no Sanctum |
| PKSA-3r5d-mb8f-1qw9 / PKSA-mdq4-51ck-6kdq: CRLF in the `email` rule | high | `email` is used on login and on `certified_email` (customers). **Mitigation to consider:** use `email:rfc,strict` or reject `\r\n` explicitly where the value reaches mail headers |
| PKSA-m5cs-t1y6-qpcs: temporary signed URL path confusion | medium | only `verification.verify` uses `signed` |
| PKSA-d5tc-s1qs-h781: XSS in the debug page | low | `APP_DEBUG=false` in production |

Remove the ignore list with the Laravel 12 upgrade.

## Verified (2026-11-04)

The verification ran against a throwaway MariaDB 10.11, the same version
as production, so the dev database was never touched.

- **First pass, on the branch** (before the QA driver release): 14 passed
  and 2 failed on both PHP 8.2 (old lock) and 8.4.27 (new lock); the 2
  failures predated the change. The production image was built on
  `php:8.4.27-apache` with redis, gd, pdo_mysql and OPcache; `/login`
  returned 200 without `X-Powered-By`.
- **Final pass, on `main`** with the driver release and the PHP 8.2 pin:
  - **82 passed (529 assertions) on PHP 8.4.27**, with
    `error_reporting=-1` and no deprecation or warning;
  - **82 passed on PHP 8.2.32**, the QA branch;
  - `composer check-platform-reqs` passes on 8.2 (lock) and 8.4;
  - `composer audit`: 0 open advisories, 5 ignored (below). Abandoned,
    dev only: `dragon-code/contracts`, `pretty-array`, `support`.

## Deploy notes

- This is a full release: `deploy.sh` runs `git pull`, so everything on
  `main` ships. Production is at `b0cf477`, so the driver
  release and its two migrations ship too: take a DB dump first.
- After the deploy, verify:
  - `docker exec metallicamarcon php -v` shows 8.4.27;
  - `/login` returns 200;
  - the worker and scheduler logs are clean;
  - `laravel.log` has no new errors;
  - `curl -sI` shows no `X-Powered-By`.
- **Rollback:**
  - before deploying, tag the current images
    (`docker tag metallicamarcon-metallicamarcon:latest
    metallicamarcon-metallicamarcon:pre-php84`, and the same for the
    worker and scheduler);
  - to roll back, check out the previous commit, retag the images and run
    `docker compose up -d --no-deps` on the three services;
  - this change adds no migration, but the release does
    (`2026_10_31_200000`, `2026_11_02_180000`): roll back the schema
    from the pre-deploy dump, not with `migrate:rollback` blindly.
- Not included:
  - the ADR-0024 compose layout (single `compose.yaml`);
  - Node 20 (EOL) in the asset stage;
  - the `composer:2` floating tag.
