# Tasks — Metallica Marcon

## Milestones
- [x] M0 — Struttura Claude + agentic team setup
- [x] M1 — Authorization matrix + guard hardening (GLE-55/56/63)
- [x] M2 — Logistic dispatch module (GLE-53)
- [x] M3 — Order journey document workflow
- [ ] M4 — Deploy produzione su VPS (gestionalelogistica.metallicamarcon.it)
- [ ] M5 — Queue con Valkey in produzione
- [ ] M6 — Hardening: rate limits, monitoring, alerting

## Active

### M4 — Deploy produzione VPS ✓ COMPLETATO 2026-04-15
- [x] Creare Dockerfile (multi-stage: Composer → Node → PHP/Apache)
- [x] Creare docker-compose.prod.yml (app + worker + MariaDB + Valkey)
- [x] Creare deploy.sh
- [x] Aggiornare CLAUDE.md con riferimenti deploy
- [x] DNS record A aggiornato dal provider (35.214.194.1 → 76.13.137.60)
- [x] Certificato Let's Encrypt emesso
- [x] Virtual host Nginx configurato (HTTP + HTTPS + acme-challenge)
- [x] Primo deploy completato — app raggiungibile su https://gestionalelogistica.metallicamarcon.it
- [x] Storage bind mount aggiunto (avatars, documenti, media persistono tra deploy)
- [x] Seeder prod/dev separati (prod: solo admin da SEED_ADMIN_PASSWORD, no Faker)
- [x] Fallback avatar spostato in public/images (era gitignored in storage)
- [x] YouTrack MCP configurato in ~/.claude.json (scope user, transport http) — 2026-04-15

### Fix post-deploy (2026-04-15)
- [x] APP_KEY mancante → generata e inserita in .env con --force-recreate
- [x] storage:link aggiunto a deploy.sh
- [x] Validazione avatar condizionale (solo se file presente)
- [x] user_code salvato in DB + watch reattivo in Edit.vue
- [x] Avatar size constrainta in edit page (w-32 h-32 object-cover)
- [x] migrate:fresh --seed --force eseguito → utente admin con id=1

## Backlog
- Refactor controller ordini (sospeso su richiesta cliente — vedi nota Obsidian)
- Dialog refinement NLP (Phase 2)
- Migrazione PostGIS (futuro)
- Automazione n8n monitoraggio scadenza certificati TLS su VPS condivisa (vedi `.claude/rules/decisions/008-tls-cert-monitoring-vps.md`) — tool pronti in `/opt/dide/ssl/bin/` sulla VPS, manca il wiring del workflow n8n

## Fix produzione (2026-09-15)
- [x] Certificato TLS scaduto su `gestionalelogistica.metallicamarcon.it` — causa: nginx containerizzato non veniva ricaricato dopo il rinnovo certbot (nessun deploy-hook). Reload manuale eseguito, sito verificato OK (nuovo cert valido fino 11/11/2026).
- [x] Deploy-hook mancante installato su VPS (`/etc/letsencrypt/renewal-hooks/deploy/reload-docker-nginx.sh`) — root cause risolta per i rinnovi futuri.
- [x] Tool general-purpose creati e testati: `check-tls-cert-endpoint.py` (monitor esterno, verifica il certificato realmente servito) e `renew-letsencrypt-cert.py` (rinnovo on-demand + reload + verifica) — sorgente in `developer-platform/Scripts/`, deployati su VPS in `/opt/dide/ssl/bin/`.
- [ ] Wiring n8n (workflow periodico di check + eventuale auto-remediation on-demand) — vedi ADR 008
