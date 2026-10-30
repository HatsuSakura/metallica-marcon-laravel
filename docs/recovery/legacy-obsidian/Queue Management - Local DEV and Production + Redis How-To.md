---
title: Queue Management - Local DEV and Production + Redis How-To
date: 2026-03-14
project: GLE
status: historical
recovered_on: 2026-10-30
recovery_status: historical
---

> **Nota recuperata — storica, non procedura corrente.** Origine: vecchio vault Obsidian; recupero 2026-10-30. Per lo stato attuale consultare [sviluppi e riconciliazione](../../development-status.md). Non eseguire comandi di deploy, migrazione o modifica dati sulla sola base di questa nota.


# Obiettivo
Definire una procedura standard per:
- gestione code Laravel in Local DEV e Production
- installazione/configurazione Redis per queue affidabili

# 1) Queue in Local DEV

## A. Modalità semplice (sync)
Use case: sviluppo rapido, debugging immediato.

`.env`
```env
QUEUE_CONNECTION=sync
```

Comportamento:
- i Job vengono eseguiti subito nella request HTTP
- non serve worker (`queue:work`)

Pro:
- setup zero
- debugging facile

Contro:
- nessun comportamento realmente asincrono
- tempi request più lunghi

## B. Modalità realistica (database)
Use case: QA locale / test reali del flusso asincrono.

`.env`
```env
QUEUE_CONNECTION=database
```

Setup iniziale (una sola volta):
```bash
php artisan queue:table
php artisan queue:failed-table
php artisan migrate
```

Avvio worker:
```bash
php artisan queue:work --tries=3 --timeout=120 --sleep=1
```

Verifica:
- inserisci un job (es. generazione documenti ordine)
- controlla che venga consumato da worker
- in caso errore, verifica tabella `failed_jobs`

# 2) Queue in Production

## Scelta driver consigliata
- consigliato: `redis`
- alternativa: `database`

## Requisito fondamentale
In produzione deve esserci almeno un processo worker sempre attivo.

## Comando worker tipico
```bash
php artisan queue:work redis --queue=default --tries=3 --timeout=120 --sleep=1 --max-time=3600
```

## Gestione processi (Supervisor - Linux)
File esempio `/etc/supervisor/conf.d/laravel-worker.conf`
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/app/artisan queue:work redis --queue=default --sleep=1 --tries=3 --timeout=120 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/app/storage/logs/worker.log
stopwaitsecs=3600
```

Comandi:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
sudo supervisorctl status
```

## Deploy checklist queue-safe
1. `php artisan migrate --force`
2. `php artisan optimize:clear`
3. restart worker (Supervisor restart)
4. smoke test job asincrono

# 3) Redis install/configuration how-to

## A. Redis su Linux (Ubuntu)
```bash
sudo apt update
sudo apt install -y redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
redis-cli ping
```
Output atteso: `PONG`

Configurazione base (`/etc/redis/redis.conf`):
- `supervised systemd`
- `maxmemory <valore>` (es. `512mb`)
- `maxmemory-policy allkeys-lru` (opzionale)
- `requirepass <password-forte>` se necessario

Poi:
```bash
sudo systemctl restart redis-server
```

## B. Redis con Docker (dev/qa)
```bash
docker run -d --name redis-mm -p 6379:6379 redis:7-alpine
redis-cli -h 127.0.0.1 -p 6379 ping
```

## C. Laravel config Redis queue
`.env`
```env
QUEUE_CONNECTION=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
# Credenziali: REDIS_PASSWORD — valorizzare fuori dal repository; esempio originale conservato nello snapshot privato.
REDIS_PORT=6379
```

Pacchetti PHP richiesti:
- estensione `php-redis` (consigliata)
- alternativa: `predis/predis`

Controllo config:
```bash
php artisan config:clear
php artisan tinker
```
In tinker:
```php
config('queue.default');
```

# 4) Monitoring e failure handling

Comandi utili:
```bash
php artisan queue:failed
php artisan queue:retry all
php artisan queue:flush
```

Note:
- `queue:flush` cancella tutti i failed jobs
- usare con cautela

# 5) Standard operativo consigliato (Metallica Marcon)

Local DEV:
- default `sync` per sviluppo rapido
- usare `database` quando testi flussi async

QA:
- `database` o `redis` + worker attivo

Production:
- `redis` + Supervisor (o Horizon)
- almeno 2 worker
- log + monitoraggio `failed_jobs`
