
> **Nota recuperata — storica, non procedura corrente.** Origine: vecchio vault Obsidian; recupero 2026-10-30. Per lo stato attuale consultare [sviluppi e riconciliazione](../../development-status.md). Non eseguire comandi di deploy, migrazione o modifica dati sulla sola base di questa nota.

# Metallica Marcon — Manuale di Installazione

> Deploy in produzione su VPS condivisa.
> URL produzione: **https://gestionalelogistica.metallicamarcon.it**
> Accesso: DNS A diretto → VPS + Let's Encrypt TLS
> Aggiornato: 2026-04-15

---

## Architettura

```
Internet
  │
  ▼
DNS A: gestionalelogistica.metallicamarcon.it → 76.13.137.60
  │  (provider: abdns — record A aggiornato il 2026-04-15)
  ▼
VPS porta 443
  │
  ▼
Nginx  (Docker, su vps-net)
  │  proxy_pass http://metallicamarcon:80
  ▼
┌──────────────── metallicamarcon-internal ──────────────────┐
│                                                            │
│  metallicamarcon:80      metallicamarcon-worker            │
│  (PHP 8.2 + Apache)      (queue worker)                    │
│                                                            │
│  metallicamarcon-db      metallicamarcon-valkey            │
│  (MariaDB 11)            (Valkey 7.2)                      │
└────────────────────────────────────────────────────────────┘
```

---

## Prerequisiti VPS

- [x] Rete Docker condivisa: `docker network create vps-net`
- [x] Nginx centralizzato attivo su `vps-net`
- [x] DNS A aggiornato dal provider (35.214.194.1 → 76.13.137.60) — completato 2026-04-15
- [x] Certificato Let's Encrypt emesso — completato 2026-04-15

---

## Installazione — prima volta

### 1. Clone del repository

```bash
cd /var/www
git clone git@github-<alias>:HatsuSakura/metallica-marcon-laravel.git metallicamarcon
cd metallicamarcon
```

### 2. Sostituire docker-compose.yml con la versione di produzione

```bash
cp docker-compose.prod.yml docker-compose.yml
```

### 3. Creare il file .env

```bash
nano .env
```

Valori minimi:

```dotenv
APP_NAME="Metallica Marcon Gestionale"
APP_ENV=production
# Credenziali: APP_KEY — valorizzare fuori dal repository; esempio originale conservato nello snapshot privato.
APP_DEBUG=false
APP_URL=https://gestionalelogistica.metallicamarcon.it

DB_CONNECTION=mysql
DB_HOST=metallicamarcon-db
DB_PORT=3306
DB_DATABASE=metallicamarcon
DB_USERNAME=metallicamarcon_user
# Credenziali: DB_PASSWORD — valorizzare fuori dal repository; esempio originale conservato nello snapshot privato.
# Credenziali: DB_ROOT_PASSWORD — valorizzare fuori dal repository; esempio originale conservato nello snapshot privato.

QUEUE_CONNECTION=redis
REDIS_HOST=metallicamarcon-valkey
REDIS_PORT=6379
# Credenziali: REDIS_PASSWORD — valorizzare fuori dal repository; esempio originale conservato nello snapshot privato.

SESSION_DRIVER=redis
CACHE_STORE=redis

# Credenziali: SEED_ADMIN_PASSWORD — valorizzare fuori dal repository; esempio originale conservato nello snapshot privato.
```

> **Attenzione password con `$`:** sia Docker Compose che phpdotenv (il parser di Laravel) interpretano `$` come inizio di variabile. Se la password contiene `$`, racchiuderla sempre in **virgolette singole** nel `.env`:
# Credenziali: SEED_ADMIN_PASSWORD — valorizzare fuori dal repository; esempio originale conservato nello snapshot privato.
> Non usare virgolette doppie — non proteggono il `$`.
> Per generare password sicure senza `$`: `openssl rand -base64 32 | tr -d '=+/$'`

### 4. Virtual host Nginx — blocco HTTP (prima del certificato)

```bash
nano /var/www/nginx/conf.d/gestionalelogistica.conf
```

Contenuto minimo per abilitare la challenge ACME:

```nginx
server {
    listen 80;
    server_name gestionalelogistica.metallicamarcon.it;
    location /.well-known/acme-challenge/ { root /var/www/certbot; }
    location / { return 301 https://$host$request_uri; }
}
```

```bash
docker exec nginx nginx -t && docker exec nginx nginx -s reload
```

### 5. Build e avvio

```bash
docker compose build
docker compose up -d
```

### 6. Generare APP_KEY e riscrivere nel .env

```bash
docker exec metallicamarcon php artisan key:generate --show
# Credenziali: APP_KEY — valorizzare fuori dal repository; esempio originale conservato nello snapshot privato.
nano .env

docker compose up -d --force-recreate metallicamarcon
```

### 7. Certificato TLS con Let's Encrypt

> Eseguire solo dopo che il DNS ha propagato.
> Verifica: `nslookup gestionalelogistica.metallicamarcon.it` → deve restituire `76.13.137.60`

```bash
certbot certonly --webroot \
  --webroot-path /var/www/certbot \
  -d gestionalelogistica.metallicamarcon.it
```

### 8. Aggiornare vhost Nginx con blocco HTTPS

```bash
cat > /var/www/nginx/conf.d/gestionalelogistica.conf << 'EOF'
server {
    listen 80;
    server_name gestionalelogistica.metallicamarcon.it;
    location /.well-known/acme-challenge/ { root /var/www/certbot; }
    location / { return 301 https://$host$request_uri; }
}

server {
    listen 443 ssl;
    server_name gestionalelogistica.metallicamarcon.it;

    ssl_certificate     /etc/letsencrypt/live/gestionalelogistica.metallicamarcon.it/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/gestionalelogistica.metallicamarcon.it/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;

    location / {
        proxy_pass         http://metallicamarcon:80;
        proxy_set_header   Host              $host;
        proxy_set_header   X-Real-IP         $remote_addr;
        proxy_set_header   X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header   X-Forwarded-Proto $scheme;
    }
}
EOF

docker exec nginx nginx -t && docker exec nginx nginx -s reload
```

### 9. Inizializzazione DB e seed

```bash
docker exec metallicamarcon php artisan migrate --force
docker exec metallicamarcon php artisan storage:link
docker exec metallicamarcon php artisan db:seed --force
docker exec metallicamarcon chown -R www-data:www-data storage bootstrap/cache
```

> **Seeder prod/dev:** il `DatabaseSeeder` rileva automaticamente `APP_ENV`.
> In produzione crea solo l'utente admin (`<email-amministratore>`), leggendo la password da `SEED_ADMIN_PASSWORD` nel `.env`.
> In sviluppo crea gli utenti di test completi.
> Il seed è un'operazione **one-shot** — non va ripetuto nei deploy successivi.

### 10. Cache di produzione

```bash
docker exec metallicamarcon php artisan config:cache
docker exec metallicamarcon php artisan route:cache
docker exec metallicamarcon php artisan view:cache
```

---

## Deploy successivi

```bash
cd /var/www/metallicamarcon
bash deploy.sh
```

`deploy.sh` esegue in sequenza: pull → build → up → migrate → storage:link → queue:restart → cache → permessi.

---

## Verifica post-installazione

```bash
# Tutti i container attivi
docker compose ps

# Migration applicate
docker exec metallicamarcon php artisan migrate:status

# Valkey raggiungibile
docker exec metallicamarcon-valkey valkey-cli -a "${REDIS_PASSWORD}" ping
# atteso: PONG

# Nginx: configurazione ok
docker exec nginx nginx -t
```

Checklist funzionale:
- [ ] https://gestionalelogistica.metallicamarcon.it raggiungibile via browser
- [ ] Login con account amministratore
- [ ] Upload avatar funzionante (verifica storage:link)
- [ ] `docker logs metallicamarcon-worker --tail=20` — nessun errore, worker attivo

---

## Log utili

| Componente | Comando |
|---|---|
| Laravel app | `docker logs metallicamarcon --tail=50 -f` |
| Queue worker | `docker logs metallicamarcon-worker --tail=50 -f` |
| MariaDB | `docker logs metallicamarcon-db --tail=20` |
| Valkey | `docker logs metallicamarcon-valkey --tail=20` |
| Nginx (globale) | `docker logs nginx --tail=50 -f` |
| Laravel (file) | `docker exec metallicamarcon tail -f storage/logs/laravel.log` |
