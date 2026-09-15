#!/usr/bin/env bash
# =============================================================
# deploy-qa.sh — Metallica Marcon | Deploy QA (SiteGround, Linux-native)
#
# Linux replacement for the legacy deploy.ps1 (WinSCP/PuTTY/Pageant)
# workflow. Builds frontend assets locally, then rsyncs an explicit
# allowlist of application directories over SSH, and runs the Laravel
# bootstrap commands remotely.
#
# The QA host is a managed SiteGround account with no git support at the
# current tier, so the build must happen locally and be uploaded as a
# diff — rsync's own delta-transfer algorithm handles this natively,
# which is why this script does not need deploy.ps1's manual git-diff
# file-list logic.
#
# Safety model: SYNC_PATHS is an allowlist, not a blocklist. --delete
# only ever prunes extraneous files inside directories that are actually
# part of the transfer, so it can structurally never touch .env,
# storage/, vendor/, or anything else not explicitly listed below.
#
# Usage:
#   ./deploy-qa.sh --dry-run    # preview only, no remote changes
#   ./deploy-qa.sh              # real deploy
# =============================================================

set -Eeuo pipefail

# ============================
# CONFIGURATION
# ============================

LOCAL_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
REMOTE_USER="u1872-gi4pyk5u8xvn"
REMOTE_HOST="gnldm1096.siteground.biz"
REMOTE_PORT="18765"
# TODO: verify this path once SSH access is confirmed working end to end —
# inherited from deploy.ps1, not yet independently re-verified on this
# account after the 2026-07 SiteGround reorganization.
REMOTE_DIR="/home/u1872-gi4pyk5u8xvn/www/testgestionalelogistica.metallicamarcon.it/public_html"
SSH_KEY="$HOME/secrets/ssh/creactive/metallica-marcon/qa-siteground"
LAST_DEPLOY_FILE="$LOCAL_DIR/.last-qa-deploy"

SSH_OPTS=(-i "$SSH_KEY" -p "$REMOTE_PORT" -o IdentitiesOnly=yes -o ConnectTimeout=15)

# Application code only — never vendor/, storage/, .env, tests/, or any
# packaging/tooling files. composer.lock is included so the remote
# `composer install` reproduces exactly what was built locally.
SYNC_PATHS=(
  app
  bootstrap
  config
  database
  resources
  routes
  public
  artisan
  composer.json
  composer.lock
)

DRY_RUN=0
if [[ "${1:-}" == "--dry-run" ]]; then
  DRY_RUN=1
fi

step() { echo "==> $*"; }

# ============================
# 1. VERIFY CLEAN WORKING TREE
# ============================

cd "$LOCAL_DIR"

if [[ -n "$(git status --porcelain)" ]]; then
  echo ""
  echo "!! WARNING: uncommitted changes present:"
  git status --short
  echo ""
  if [[ "$DRY_RUN" -eq 1 ]]; then
    echo "Dry run: continuing without confirmation (nothing will be modified)."
  else
    read -r -p "Continue anyway? Only the working tree as-is will be deployed [y/N] " confirm
    if [[ "$confirm" != "y" && "$confirm" != "Y" ]]; then
      echo "Deploy aborted."
      exit 1
    fi
  fi
fi

HEAD_COMMIT="$(git rev-parse HEAD)"

# ============================
# 2. VERIFY SSH CONNECTIVITY
# ============================

step "Verifying SSH connectivity..."
if ! ssh "${SSH_OPTS[@]}" -o BatchMode=yes "$REMOTE_USER@$REMOTE_HOST" 'echo ok' >/dev/null 2>&1; then
  echo "!! ERROR: SSH connection failed. Check the key, host, and any account-side lockout." >&2
  exit 1
fi
step "Connection OK."

# ============================
# 3. BUILD FRONTEND ASSETS
# ============================

step "Running npm run build..."
npm run build
step "Frontend build complete."

# ============================
# 4. CLEAN LOCAL CACHE ARTIFACTS
# ============================

rm -rf storage/debugbar storage/framework/cache/data storage/framework/sessions storage/framework/views 2>/dev/null || true
find bootstrap/cache -type f -name '*.php' -delete 2>/dev/null || true
step "Local cache cleaned."

# ============================
# 5. RSYNC — SYNC ALLOWLISTED PATHS
# ============================

RSYNC_EXCLUDES=(
  --exclude ".env*"
  --exclude "storage/logs/"
  --exclude "storage/debugbar/"
  --exclude "storage/framework/cache/"
  --exclude "storage/framework/sessions/"
  --exclude "storage/framework/views/"
  --exclude "bootstrap/cache/"
  --exclude "public/storage"
  --exclude "public/hot"
)

RSYNC_FLAGS=(-az --delete --human-readable --info=progress2)
if [[ "$DRY_RUN" -eq 1 ]]; then
  RSYNC_FLAGS+=(--dry-run)
  step "DRY RUN — nothing will actually be modified on the server."
fi

SYNC_SOURCES=()
for p in "${SYNC_PATHS[@]}"; do
  SYNC_SOURCES+=("$LOCAL_DIR/$p")
done

step "Syncing application code..."
rsync "${RSYNC_FLAGS[@]}" "${RSYNC_EXCLUDES[@]}" \
  -e "ssh ${SSH_OPTS[*]}" \
  "${SYNC_SOURCES[@]}" \
  "$REMOTE_USER@$REMOTE_HOST:$REMOTE_DIR/"

step "Sync complete."

if [[ "$DRY_RUN" -eq 1 ]]; then
  step "Dry run finished — no remote commands executed."
  exit 0
fi

# ============================
# 6. REMOTE LARAVEL BOOTSTRAP
# ============================

step "Running remote Laravel commands..."
ssh "${SSH_OPTS[@]}" "$REMOTE_USER@$REMOTE_HOST" bash -s <<EOF
set -e
cd "$REMOTE_DIR"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
EOF

# ============================
# 7. RECORD DEPLOYED COMMIT
# ============================

echo "$HEAD_COMMIT" > "$LAST_DEPLOY_FILE"
step "Commit ${HEAD_COMMIT:0:8} recorded in .last-qa-deploy."

echo ""
step "QA DEPLOY COMPLETE."
