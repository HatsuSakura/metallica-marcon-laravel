#!/bin/bash
# =============================================================
# deploy.sh — Metallica Marcon
# Script di deploy per PROD.
# Eseguire dalla directory /var/www/metallicamarcon sul VPS.
#
# Uso: bash deploy.sh
# =============================================================

set -e  # interrompe lo script al primo errore

CONTAINER=metallicamarcon

echo "==> [1/7] Pull codice aggiornato"
git config core.fileMode false
git pull
cp docker-compose.prod.yml docker-compose.yml

echo "==> [2/7] Build immagini (app + worker)"
docker compose build metallicamarcon metallicamarcon-worker

echo "==> [3/7] Riavvio container app (DB e Valkey restano attivi)"
docker compose up -d --no-deps metallicamarcon metallicamarcon-worker

echo "==> [4/8] Migration"
docker exec $CONTAINER php artisan migrate --force

echo "==> [5/8] Storage symlink (idempotente)"
docker exec $CONTAINER php artisan storage:link

echo "==> [6/8] Segnala ai worker di ricaricare il codice"
docker exec $CONTAINER php artisan queue:restart

echo "==> [7/8] Rigenera cache"
docker exec $CONTAINER php artisan config:cache
docker exec $CONTAINER php artisan route:cache
docker exec $CONTAINER php artisan view:cache

echo "==> [8/8] Permessi storage"
docker exec $CONTAINER chown -R www-data:www-data storage bootstrap/cache

echo ""
echo "Deploy completato."
