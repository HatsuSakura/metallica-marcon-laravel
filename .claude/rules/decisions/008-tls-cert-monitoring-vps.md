# ADR 2026-09-15 — TLS Certificate Monitoring & Remediation on the Shared VPS

## Status
Accepted (immediate fix applied) — n8n wiring still open, see "Next steps"

## Context
`https://gestionalelogistica.metallicamarcon.it/` served an expired TLS
certificate to browsers (browser warning screen), even though Let's
Encrypt/certbot on the VPS reported the certificate as valid for another
57 days.

## Root cause
`certbot.timer` (host-level, standard Let's Encrypt automation) had already
renewed the certificate on 2026-08-13 — the check-and-renew mechanism
itself was never broken. The reverse proxy (`nginx`) runs as a Docker
container that bind-mounts `/etc/letsencrypt` **read-only**. A TLS-serving
process reads the certificate into memory at startup/reload and does not
notice a rotated file on disk by itself. No deploy-hook existed under
`/etc/letsencrypt/renewal-hooks/deploy/` to tell the container to reload,
so nginx kept serving the pre-renewal (now expired) certificate until a
manual `docker exec nginx nginx -s reload` was run.

This is a **Docker/deploy-hook integration gap**, not a missing
check-and-renew automation. Building a periodic "does certbot need to
renew" job would have been redundant with `certbot.timer`.

## Decision
1. **Structural fix** — installed
   `/etc/letsencrypt/renewal-hooks/deploy/reload-docker-nginx.sh` on the
   VPS (root, `docker exec nginx nginx -s reload`). Certbot runs every
   deploy-hook in that directory after any successful renewal, for any
   domain on this host — this closes the gap for
   `gestionalelogistica.metallicamarcon.it` and for any future cert added
   to this same nginx container.
2. **Independent monitoring tool** — `check-tls-cert-endpoint.py`: opens a
   real TLS handshake against the public endpoint (bypassing verification
   so it can inspect an already-expired cert instead of just failing to
   connect) plus an optional HTTP GET. This is the layer that actually
   caught today's class of bug: certbot's own bookkeeping (`certbot
   certificates`) was already "correct" and would not have flagged
   anything — only checking what the browser actually receives does.
   General-purpose (stdlib + `openssl` CLI only, any host:port), not tied
   to certbot/Docker/this project.
3. **On-demand remediation tool** — `renew-letsencrypt-cert.py`: wraps
   `certbot renew --cert-name <name>`, runs a configurable reload command,
   then re-verifies the live endpoint. Not part of a periodic loop
   (certbot's own timer already renews on schedule) — it's the "fix it
   now" action a human or an n8n auto-remediation workflow can trigger
   when the monitoring tool reports a problem.

Both scripts live source-of-truth in
`developer-platform/Scripts/` (workstation) and are deployed to
`/opt/dide/ssl/bin/` on the VPS (host-level, root-owned — the existing
convention on this host for cross-cutting ops tooling, see
`/opt/dide/backup/bin/`). Not scoped under `metallica-marcon`'s own repo
because they're host-wide, general-purpose tools, not application code.

## Immediate fix applied (2026-09-15)
- `docker exec nginx nginx -s reload` run manually — site verified serving
  the already-renewed certificate (valid until 2026-11-11).
- Deploy-hook installed and syntax/run-tested (idempotent reload).
- Both Python tools deployed to `/opt/dide/ssl/bin/` on the VPS and smoke
  tested from there (checker: OK/57d/HTTP 200 on the real site, and
  correctly flags a known-expired public test cert as `EXPIRED`; renewer:
  clean `--dry-run` against the real certbot config).

## Next steps (not done yet)
- Wire `check-tls-cert-endpoint.py` into an n8n workflow on `dide_n8n`
  (periodic check across all Creactive-hosted domains, alert on
  warning/critical/expired) as "the other automations already running"
  the PO referenced.
- Decide whether `renew-letsencrypt-cert.py` is invoked by n8n directly as
  an auto-remediation step, or only surfaced as a suggested manual action.
- `dide_n8n`'s container has no `python3`, only `openssl`, and its
  Docker-socket access is read-write but it has no SSH path back to this
  same host (its mounted SSH key targets an unrelated external site) — the
  n8n workflow will need either `docker exec`-style invocation via the
  socket, or a new SSH credential scoped to this host, to actually call
  these scripts. Open design question for the automation task.
- Consider whether these tools should eventually move into the DIDE git
  repo (`/var/www/dide` on the VPS, source at
  `~/projects/creactive/DIDE`) now that they're VPS-wide infra rather than
  a metallica-marcon concern — today they were deployed directly (not via
  DIDE's git-tracked deploy flow) to keep today's fix minimal.

## Traceability
- Full picture also recorded in `developer-platform/Runbooks/RUN-019-tls-certificate-monitoring-and-renewal.md`
  and `developer-platform/Inventory/creactive-vps-shared-hosting.md`.
