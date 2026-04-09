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

### M4 — Deploy produzione VPS
- [ ] Creare Dockerfile (multi-stage: Composer → Node → PHP/Apache)
- [ ] Creare docker-compose.prod.yml (app + worker + MariaDB + Valkey)
- [ ] Creare deploy.sh
- [ ] Aggiornare CLAUDE.md con riferimenti deploy
- [ ] Attendere cambio record A DNS dal provider (35.214.194.1 → 76.13.137.60)
- [ ] Emettere certificato Let's Encrypt dopo propagazione DNS
- [ ] Configurare virtual host Nginx su VPS
- [ ] Primo deploy e verifica

## Backlog
- Refactor controller ordini (sospeso su richiesta cliente — vedi nota Obsidian)
- Dialog refinement NLP (Phase 2)
- Migrazione PostGIS (futuro)
