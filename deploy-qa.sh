#!/usr/bin/env bash
# =============================================================
# deploy-qa.sh — Metallica Marcon | Deploy QA (SiteGround, native PHP)
#
# QA runs on a managed SiteGround account with native PHP (no Docker, no
# git on the host). Production is different: see deploy.sh (Docker).
#
# What it does:
#   1. exports the committed HEAD (git archive) into a temporary build
#      directory: the working tree and the local .env are never shipped;
#   2. builds the frontend there with the QA VITE_* values read from the
#      remote .env (values are never printed);
#   3. on the server: snapshot of code + vendor + database, maintenance
#      mode, rsync of an allowlist, composer install (pinned Composer),
#      migrate, caches, queue:restart, back online;
#   4. smoke checks and a release marker.
#
# Safety model:
#   - SYNC_PATHS is an allowlist: .env, storage/, vendor/ and everything
#     not listed are never touched;
#   - --delete prunes files removed from git inside the synced paths, but
#     old hashed assets (public/build/assets) and host files are
#     protected; the real run lists every deletion and asks first;
#   - on failure after maintenance ON the site stays in maintenance, and
#     the rollback commands for the snapshot are printed.
#
# Usage:
#   ./deploy-qa.sh --dry-run    # build + preview the transfer, no remote change
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
# Verified 2026-11-04 (QA deploys of 8c5bba8 and a1f2829). Relative to $HOME.
REMOTE_DIR="www/testgestionalelogistica.metallicamarcon.it/public_html"
QA_URL="https://testgestionalelogistica.metallicamarcon.it"
SSH_KEY="$HOME/secrets/ssh/creactive/metallica-marcon/qa-siteground"
LAST_DEPLOY_FILE="$LOCAL_DIR/.last-qa-deploy"

# Composer on the host is 2.4.3 (2022): use a pinned, checksum-verified
# phar instead, downloaded once into the account.
COMPOSER_VERSION="2.11.1"
COMPOSER_SHA256="6f1b891e1d4927d62740276c540fb490e47689e19e102c49e8da849794882f8c"

BACKUP_ROOT="deploy-backups/metallica-qa"   # relative to the remote $HOME
BACKUP_KEEP=5

SSH_OPTS=(-i "$SSH_KEY" -p "$REMOTE_PORT" -o IdentitiesOnly=yes -o ConnectTimeout=15)
REMOTE="$REMOTE_USER@$REMOTE_HOST"

# Application code only. composer.lock is included so the remote
# `composer install` reproduces exactly what was tested.
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

RSYNC_FILTERS=(
  --exclude ".env*"
  --exclude "/bootstrap/cache/"
  --exclude "/public/storage"
  --exclude "/public/hot"
  # Never delete: old hashed assets (cached browsers still request them)
  # and files created on the host.
  --filter "P /public/build/assets/**"
  --filter "P /public/php_errorlog"
  --filter "P /public/note.txt"
  --filter "P /database/*.sqlite"
)

DRY_RUN=0
case "${1:-}" in
  --dry-run) DRY_RUN=1 ;;
  "") ;;
  *) echo "Usage: $0 [--dry-run]" >&2; exit 1 ;;
esac

step() { echo "==> $*"; }
die() { echo "!! $*" >&2; exit 1; }
rssh() { ssh "${SSH_OPTS[@]}" "$REMOTE" "$@"; }

for cmd in git rsync ssh npm php curl sha256sum; do
  command -v "$cmd" >/dev/null || die "missing command: $cmd"
done

# ============================
# 1. COMMIT TO DEPLOY
# ============================

cd "$LOCAL_DIR"
git fetch -q origin
HEAD_COMMIT="$(git rev-parse HEAD)"
SHORT="${HEAD_COMMIT:0:7}"
if [[ -z "$(git branch -r --contains "$HEAD_COMMIT" 2>/dev/null)" ]]; then
  die "HEAD $SHORT is not pushed to origin: push it first, QA must be traceable."
fi
if [[ -n "$(git status --porcelain)" ]]; then
  echo "Note: uncommitted changes are ignored; deploying committed $SHORT only."
fi
step "Deploying $SHORT ($(git log -1 --format=%s "$HEAD_COMMIT"))"

# ============================
# 2. SSH + REMOTE PREREQUISITES
# ============================

step "Verifying SSH connectivity..."
rssh -o BatchMode=yes 'echo ok' >/dev/null 2>&1 \
  || die "SSH connection failed. Check the key, host, and any account-side lockout."
REMOTE_PHP="$(rssh 'php -r "echo PHP_VERSION;"')"
step "Connection OK. Remote PHP $REMOTE_PHP."

# ============================
# 3. CLEAN BUILD FROM HEAD
# ============================

BUILD_DIR="$(mktemp -d "${TMPDIR:-/tmp}/mm-qa-build.XXXXXX")"
cleanup() { rm -rf "$BUILD_DIR"; }
trap cleanup EXIT

step "Exporting $SHORT into a clean build directory..."
git archive "$HEAD_COMMIT" | tar -x -C "$BUILD_DIR"

# QA VITE_* values, read from the remote .env and kept in memory only.
declare -a VITE_ENV=()
while IFS= read -r line; do
  [[ "$line" =~ ^VITE_[A-Z0-9_]+= ]] || continue
  key="${line%%=*}"
  value="${line#*=}"
  value="${value%\"}"; value="${value#\"}"
  VITE_ENV+=("$key=$value")
done < <(rssh "grep -E '^VITE_[A-Z0-9_]+=' '$REMOTE_DIR/.env'" | tr -d '\r')
for needed in $(grep -rhoE 'import\.meta\.env\.VITE_[A-Z0-9_]+' "$BUILD_DIR/resources/js" | sed 's/.*\.//' | sort -u); do
  printf '%s\n' "${VITE_ENV[@]}" | grep -q "^${needed}=" || die "remote .env has no $needed"
done
step "QA frontend variables: $(printf '%s\n' "${VITE_ENV[@]}" | cut -d= -f1 | tr '\n' ' ')"

# Same pinned Composer locally: versions compute the lock content-hash
# differently (2.10 reports a 2.11 lock as outdated).
LOCAL_PHAR="${XDG_CACHE_HOME:-$HOME/.cache}/metallica-marcon/composer-$COMPOSER_VERSION.phar"
if [[ ! -f "$LOCAL_PHAR" ]] || ! echo "$COMPOSER_SHA256  $LOCAL_PHAR" | sha256sum -c --quiet - 2>/dev/null; then
  mkdir -p "$(dirname "$LOCAL_PHAR")"
  curl -fsSL -o "$LOCAL_PHAR.tmp" "https://getcomposer.org/download/$COMPOSER_VERSION/composer.phar"
  echo "$COMPOSER_SHA256  $LOCAL_PHAR.tmp" | sha256sum -c --quiet - || die "composer checksum mismatch"
  mv "$LOCAL_PHAR.tmp" "$LOCAL_PHAR"
fi

step "Installing build dependencies (vendor for the Ziggy alias, node_modules)..."
(cd "$BUILD_DIR" && php "$LOCAL_PHAR" install --no-dev --no-scripts --no-interaction --quiet)
(cd "$BUILD_DIR" && npm ci --no-audit --no-fund --loglevel=error)

step "Building frontend assets with the QA values..."
# No .env exists in BUILD_DIR, so only the QA values reach Vite.
(cd "$BUILD_DIR" && env "${VITE_ENV[@]}" npm run build >/dev/null)
[[ -f "$BUILD_DIR/public/build/manifest.json" ]] || die "build produced no manifest"
MANIFEST_SHA="$(sha256sum "$BUILD_DIR/public/build/manifest.json" | cut -d' ' -f1)"
step "Build complete (manifest ${MANIFEST_SHA:0:12})."

# ============================
# 4. PREVIEW THE TRANSFER
# ============================

cd "$BUILD_DIR"
# --checksum: the export has commit mtimes, so compare content, not dates.
RSYNC_BASE=(-az --checksum --delete "${RSYNC_FILTERS[@]}" -e "ssh ${SSH_OPTS[*]}")
PREVIEW="$(rsync -n --itemize-changes "${RSYNC_BASE[@]}" "${SYNC_PATHS[@]}" "$REMOTE:$REMOTE_DIR/")"
UPLOADS="$(grep -E '^[<>c]f' <<< "$PREVIEW" | cut -c13- || true)"
DELETIONS="$(grep -E '^\*deleting' <<< "$PREVIEW" | sed 's/^\*deleting *//' || true)"
step "Transfer preview: $(grep -c . <<< "$UPLOADS" || true) file(s) to upload or update."
if [[ -n "$DELETIONS" ]]; then
  echo "    Files that will be DELETED on QA (not in git):"
  sed 's/^/      /' <<< "$DELETIONS"
fi

if [[ "$DRY_RUN" -eq 1 ]]; then
  if [[ -n "$UPLOADS" ]]; then
    echo "    Files that will be uploaded:"
    sed 's/^/      /' <<< "$UPLOADS" | head -60
  fi
  step "Dry run finished: no remote change."
  exit 0
fi

if [[ -n "$DELETIONS" ]]; then
  read -r -p "Delete the files listed above on QA? [y/N] " confirm
  [[ "$confirm" == "y" || "$confirm" == "Y" ]] || die "Deploy aborted, nothing changed."
fi

# ============================
# 5. SNAPSHOT + MAINTENANCE ON
# ============================

TS="$(date -u +%Y%m%dT%H%M%SZ)"
BACKUP_DIR="$BACKUP_ROOT/${TS}-before-${SHORT}"

step "Snapshot of code, vendor and database on the host..."
rssh bash -s -- "$REMOTE_DIR" "$BACKUP_DIR" "$BACKUP_ROOT" "$BACKUP_KEEP" <<'REMOTE_SNAPSHOT'
set -Eeuo pipefail
app="$1"; backup="$HOME/$2"; root="$HOME/$3"; keep="$4"
umask 077
mkdir -p "$backup"
cd "$HOME/$app"
tar czf "$backup/code.tar.gz" --exclude 'storage/*' --exclude 'bootstrap/cache/*' \
  app bootstrap config database resources routes public artisan composer.json composer.lock vendor
# DB credentials from .env into a private option file, never on the command line.
cnf="$backup/.my.cnf"
php -r '
  require "vendor/autoload.php";
  $e = Dotenv\Dotenv::createArrayBacked(getcwd())->load();
  $q = fn($v) => "\"" . addcslashes((string) $v, "\\\"") . "\"";
  echo "[client]\nhost=", $q($e["DB_HOST"] ?? "localhost"), "\nport=", $q($e["DB_PORT"] ?? "3306"),
       "\nuser=", $q($e["DB_USERNAME"]), "\npassword=", $q($e["DB_PASSWORD"] ?? ""), "\n";
  file_put_contents("php://stderr", $e["DB_DATABASE"]);
' > "$cnf" 2> "$backup/.dbname"
trap 'rm -f "$cnf" "$backup/.dbname"' EXIT
mysqldump --defaults-extra-file="$cnf" --single-transaction --quick --routines --triggers \
  --no-tablespaces "$(cat "$backup/.dbname")" | gzip > "$backup/database.sql.gz"
gzip -t "$backup/code.tar.gz" "$backup/database.sql.gz"
(cd "$backup" && sha256sum code.tar.gz database.sql.gz > SHA256SUMS)
# Keep the newest snapshots only.
ls -1d "$root"/*-before-* 2>/dev/null | sort | head -n -"$keep" | xargs -r rm -rf
echo "    snapshot: ~/${backup#"$HOME"/} ($(du -sh "$backup" | cut -f1))"
REMOTE_SNAPSHOT

rollback_help() {
  cat >&2 <<EOF

!! DEPLOY FAILED. QA is left in MAINTENANCE MODE on purpose.
!! Snapshot: ~/$BACKUP_DIR (code.tar.gz, database.sql.gz, SHA256SUMS)
!! To roll back (on the host, in ~/$REMOTE_DIR):
!!   tar xzf ~/$BACKUP_DIR/code.tar.gz
!!   only if a migration ran: restore ~/$BACKUP_DIR/database.sql.gz with mysql
!!   php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache
!!   php artisan queue:restart && php artisan up
EOF
}

step "Maintenance mode ON..."
rssh "cd '$REMOTE_DIR' && php artisan down --retry=60"
trap 'rollback_help' ERR

# ============================
# 6. SYNC + LARAVEL BOOTSTRAP
# ============================

step "Syncing application code..."
rsync "${RSYNC_BASE[@]}" "${SYNC_PATHS[@]}" "$REMOTE:$REMOTE_DIR/"

step "Composer install, migrations, caches..."
rssh bash -s -- "$REMOTE_DIR" "$COMPOSER_VERSION" "$COMPOSER_SHA256" <<'REMOTE_BOOTSTRAP'
set -Eeuo pipefail
app="$1"; version="$2"; sha="$3"
phar="$HOME/.local/share/composer/composer-$version.phar"
if [[ ! -f "$phar" ]] || ! echo "$sha  $phar" | sha256sum -c --quiet - 2>/dev/null; then
  mkdir -p "$(dirname "$phar")"
  curl -fsSL -o "$phar.tmp" "https://getcomposer.org/download/$version/composer.phar"
  echo "$sha  $phar.tmp" | sha256sum -c --quiet - || { rm -f "$phar.tmp"; echo "composer checksum mismatch" >&2; exit 1; }
  mv "$phar.tmp" "$phar"
fi
cd "$HOME/$app"
php "$phar" install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
REMOTE_BOOTSTRAP

# ============================
# 7. RELEASE MARKER + ONLINE
# ============================

DEPLOYED_AT="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
rssh "cd '$REMOTE_DIR' && mkdir -p storage/app/deployments && cat > storage/app/deployments/qa-release.json && php artisan up" <<EOF
{
    "source_commit": "$HEAD_COMMIT",
    "deployed_at": "$DEPLOYED_AT",
    "environment": "QA",
    "php": "$REMOTE_PHP",
    "manifest_sha256": "$MANIFEST_SHA",
    "snapshot": "~/$BACKUP_DIR",
    "deployed_with": "deploy-qa.sh",
    "manual_acceptance": "pending"
}
EOF
trap - ERR

# ============================
# 8. SMOKE CHECKS
# ============================

step "Smoke checks..."
FAILED=0
check() {
  local path="$1" want="$2" got
  got="$(curl -s -o /dev/null -w '%{http_code}' "$QA_URL$path")"
  printf '    %-40s %s (expected %s)\n' "$path" "$got" "$want"
  [[ "$got" == "$want" ]] || FAILED=1
}
check /login 200
check /dashboard-driver 302
while read -r asset; do
  check "/build/$asset" 200
done < <(php -r '$m=json_decode(file_get_contents("public/build/manifest.json"),true); foreach($m as $e) if(!empty($e["isEntry"])) echo $e["file"],"\n";' | sort -u)
rssh "cd '$REMOTE_DIR' && php artisan about --only=environment | grep -E 'Laravel Version|PHP Version|Debug Mode|Maintenance Mode'"

echo "$HEAD_COMMIT" > "$LAST_DEPLOY_FILE"
step "Commit $SHORT recorded in .last-qa-deploy."
if [[ "$FAILED" -ne 0 ]]; then
  die "QA deploy done, but a smoke check failed: see the lines above. Snapshot: ~/$BACKUP_DIR"
fi
echo ""
step "QA DEPLOY COMPLETE ($SHORT). Manual acceptance is still pending."
