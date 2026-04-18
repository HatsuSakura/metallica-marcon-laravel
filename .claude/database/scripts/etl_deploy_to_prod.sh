#!/usr/bin/env bash
# =============================================================
# etl_deploy_to_prod.sh
#
# Esegue i 3 passi finali della pipeline ETL:
#   1. Dump di marconinertia_etl (esclude legacy_customers_stage)
#   2. Upload sul VPS via SCP
#   3. Drop + recreate + restore del DB di produzione
#
# Prerequisiti:
#   - ETL steps 01/02/03 già eseguiti su marconinertia_etl
#   - Bonifica post-migrazione già applicata
#   - Chiave SSH ~/.ssh/claude_vps presente e configurata
#   - Container locale: metallica-marcon-laravel-mysql-1
#   - Container VPS: metallicamarcon-db
# =============================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Credenziali da file gitignored
CONF_FILE="${SCRIPT_DIR}/etl_deploy_to_prod.conf"
if [[ ! -f "$CONF_FILE" ]]; then
  echo "ERRORE: file di config mancante: $CONF_FILE"
  echo "Crea il file con: VPS_ROOT_PASS=\"<password>\""
  exit 1
fi
source "$CONF_FILE"

# ------------------------------------
# Configurazione
# ------------------------------------
LOCAL_CONTAINER="metallica-marcon-laravel-mysql-1"
LOCAL_DB="marconinertia_etl"
LOCAL_ROOT_PASS="root"

VPS_HOST="76.13.137.60"
VPS_SSH_KEY="$HOME/.ssh/claude_vps"
VPS_SSH_USER="root"
VPS_CONTAINER="metallicamarcon-db"
VPS_DB="metallicamarcon"
VPS_DB_USER="metallicamarcon"
# VPS_ROOT_PASS caricato da etl_deploy_to_prod.conf

DUMP_FILE="/tmp/dump_marconinertia_etl_$(date +%Y%m%d_%H%M%S).sql"
VPS_DUMP_PATH="/tmp/dump_marconinertia_etl.sql"

# ------------------------------------
# Helper
# ------------------------------------
log() { echo "[$(date '+%H:%M:%S')] $*"; }

ssh_vps() {
  ssh -i "$VPS_SSH_KEY" -o StrictHostKeyChecking=no "${VPS_SSH_USER}@${VPS_HOST}" "$@"
}

# ------------------------------------
# STEP 1 — Dump locale
# ------------------------------------
log "STEP 1/3 — Dump di ${LOCAL_DB} (esclusa legacy_customers_stage)..."

docker exec "$LOCAL_CONTAINER" mysqldump \
  -uroot -p"$LOCAL_ROOT_PASS" \
  --ignore-table="${LOCAL_DB}.legacy_customers_stage" \
  --single-transaction \
  --routines \
  --triggers \
  "$LOCAL_DB" > "$DUMP_FILE"

DUMP_SIZE=$(du -sh "$DUMP_FILE" | cut -f1)
log "  Dump completato: $DUMP_FILE ($DUMP_SIZE)"

# ------------------------------------
# STEP 2 — Upload sul VPS
# ------------------------------------
log "STEP 2/3 — Upload sul VPS (${VPS_HOST})..."

scp -i "$VPS_SSH_KEY" -o StrictHostKeyChecking=no \
  "$DUMP_FILE" "${VPS_SSH_USER}@${VPS_HOST}:${VPS_DUMP_PATH}"

log "  Upload completato."

# ------------------------------------
# STEP 3 — Drop + recreate + restore su VPS
# ------------------------------------
log "STEP 3/3 — Drop, recreate e restore di ${VPS_DB} sul VPS..."

# Drop e recreate DB
ssh_vps "docker exec -i ${VPS_CONTAINER} mariadb -uroot -p'${VPS_ROOT_PASS}'" << SQL
DROP DATABASE IF EXISTS \`${VPS_DB}\`;
CREATE DATABASE \`${VPS_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON \`${VPS_DB}\`.* TO '${VPS_DB_USER}'@'%';
FLUSH PRIVILEGES;
SQL

log "  Database ricreato."

# Restore dump
ssh_vps "docker exec -i ${VPS_CONTAINER} mariadb -uroot -p'${VPS_ROOT_PASS}' ${VPS_DB}" < "$DUMP_FILE"

log "  Restore completato."

# Rimuovi eventuale sentinel user 9999 (ghost da run fallito)
ssh_vps "docker exec ${VPS_CONTAINER} mariadb -uroot -p'${VPS_ROOT_PASS}' ${VPS_DB} -e 'DELETE FROM users WHERE id = 9999;'" 2>/dev/null || true

# Verifica conteggi post-restore
log ""
log "=== Verifica post-restore ==="
ssh_vps "docker exec ${VPS_CONTAINER} mariadb -uroot -p'${VPS_ROOT_PASS}' ${VPS_DB}" << SQL
SELECT 'customers'           AS entity, COUNT(*) AS total FROM customers
UNION ALL SELECT 'sites',              COUNT(*) FROM sites
UNION ALL SELECT 'withdraws',          COUNT(*) FROM withdraws
UNION ALL SELECT 'timetables',         COUNT(*) FROM timetables
UNION ALL SELECT 'internal_contacts',  COUNT(*) FROM internal_contacts
UNION ALL SELECT 'users',              COUNT(*) FROM users;
SQL

# Pulizia
ssh_vps "rm -f ${VPS_DUMP_PATH}" 2>/dev/null || true
rm -f "$DUMP_FILE"

log ""
log "=== Deploy ETL completato ==="
