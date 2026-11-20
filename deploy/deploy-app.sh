#!/usr/bin/env bash
# =============================================================================
# aquilala — RESTORE & DEPLOY on the NEW server
# Run AFTER setup-server.sh:
#     sudo bash deploy-app.sh /root/aquilala-backup-<timestamp>.tar.gz
#
# Optional environment overrides:
#   APP_DIR=/var/www/aquilala     app location
#   SITE_DOMAIN=aquilala.me       domain (used in the final smoke test)
#   FORCE_DB=1                    overwrite an already-populated database
#   GIT_REPO_URL=... GIT_REPO_BRANCH=main
#                                 deploy code from git instead of the backup
# =============================================================================
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive

log()  { printf '[deploy] %s\n' "$*"; }
warn() { printf '[deploy][WARN] %s\n' "$*"; }

if [ "$(id -u)" -ne 0 ]; then
  echo "Run as root:  sudo bash deploy-app.sh <backup.tar.gz>"
  exit 1
fi

BACKUP_TAR="${1:-}"
if [ -z "$BACKUP_TAR" ] || [ ! -f "$BACKUP_TAR" ]; then
  echo "Usage: sudo bash deploy-app.sh /root/aquilala-backup-<timestamp>.tar.gz"
  exit 1
fi

APP_DIR="${APP_DIR:-/var/www/aquilala}"
SITE_DOMAIN="${SITE_DOMAIN:-aquilala.me}"
CRED_FILE="/root/.aquilala-db-creds"

# ---------- prerequisites ----------------------------------------------------
if [ ! -f "$CRED_FILE" ]; then
  echo "Missing $CRED_FILE — run setup-server.sh first."
  exit 1
fi
# shellcheck disable=SC1090
. "$CRED_FILE"
if ! printf '%s' "$DB_PASS" | grep -qE '^[A-Za-z0-9]+$' \
   || ! printf '%s' "$DB_NAME" | grep -qE '^[A-Za-z0-9_]+$' \
   || ! printf '%s' "$DB_USER" | grep -qE '^[A-Za-z0-9_]+$'; then
  echo "Invalid credentials in $CRED_FILE (must be letters/digits/underscore)."
  exit 1
fi

# ---------- unpack backup ----------------------------------------------------
STAGE="/tmp/aquilala-restore"
rm -rf "$STAGE"
mkdir -p "$STAGE"
log "Unpacking $BACKUP_TAR ..."
tar -xzf "$BACKUP_TAR" -C "$STAGE"
for need in env/.env app.tar.gz; do
  if [ ! -e "$STAGE/$need" ]; then
    echo "Backup is incomplete (missing $need). Re-run backup-vps.sh on the old server."
    exit 1
  fi
done

# ---------- preserve user uploads before replacing the application ----------
# A git deploy replaces APP_DIR wholesale, so without this the product photos,
# avatars, imports and store documents uploaded through the UI disappear on
# every deploy. The live copy wins (it may hold uploads made after an older
# backup); the backup is only the first-deploy fallback.
PRESERVE_STAGE="/tmp/aquilala-storage-app-preserve"
rm -rf "$PRESERVE_STAGE"
mkdir -p "$PRESERVE_STAGE"

if [ -d "$APP_DIR/storage/app" ]; then
  log "Preserving uploads from $APP_DIR/storage/app ..."
  cp -a "$APP_DIR/storage/app/." "$PRESERVE_STAGE/"
elif [ -f "$STAGE/app.tar.gz" ]; then
  log "No live app directory — recovering uploads from backup ..."
  BACKUP_APP_STAGE="/tmp/aquilala-storage-app-from-backup"
  rm -rf "$BACKUP_APP_STAGE"
  mkdir -p "$BACKUP_APP_STAGE"
  tar -xzf "$STAGE/app.tar.gz" -C "$BACKUP_APP_STAGE" ./storage/app/ || true
  if [ -d "$BACKUP_APP_STAGE/storage/app" ]; then
    cp -a "$BACKUP_APP_STAGE/storage/app/." "$PRESERVE_STAGE/"
  fi
fi
log "Preserved $(find "$PRESERVE_STAGE" -type f | wc -l) upload file(s)."

# ---------- place the application -------------------------------------------
if [ -n "${GIT_REPO_URL:-}" ]; then
  log "Cloning $GIT_REPO_URL (${GIT_REPO_BRANCH:-main}) -> $APP_DIR"
  rm -rf "$APP_DIR"
  git clone --depth 1 --branch "${GIT_REPO_BRANCH:-main}" "$GIT_REPO_URL" "$APP_DIR"
else
  log "Restoring application from backup -> $APP_DIR"
  mkdir -p "$STAGE/appdir"
  tar -xzf "$STAGE/app.tar.gz" -C "$STAGE/appdir"
  rm -rf "$APP_DIR"
  mkdir -p "$APP_DIR"
  cp -a "$STAGE/appdir/." "$APP_DIR/"
fi

# ---------- put uploads back -------------------------------------------------
if [ -n "$(find "$PRESERVE_STAGE" -mindepth 1 -print -quit)" ]; then
  mkdir -p "$APP_DIR/storage/app"
  cp -a "$PRESERVE_STAGE/." "$APP_DIR/storage/app/"
  log "Uploads restored into $APP_DIR/storage/app."
else
  warn "No uploads were preserved — check the backup contents."
fi

# ---------- .env -------------------------------------------------------------
install -m 600 "$STAGE/env/.env" "$APP_DIR/.env"
log "Restored .env (API keys + APP_KEY preserved)."

env_get() {
  grep -E "^${1}=" "$APP_DIR/.env" | tail -1 \
    | sed -E "s/^[^=]+=//; s/\r$//; s/^\"//; s/\"$//; s/^'//; s/'$//" || true
}
set_env_var() {
  local key="$1" val="$2"
  if grep -qE "^${key}=" "$APP_DIR/.env"; then
    sed -i -E "s|^${key}=.*$|${key}=${val}|" "$APP_DIR/.env"
  else
    printf '%s=%s\n' "$key" "$val" >> "$APP_DIR/.env"
  fi
}

# Point .env at THIS server's database (credentials from setup-server.sh).
set_env_var DB_HOST "127.0.0.1"
set_env_var DB_DATABASE "$DB_NAME"
set_env_var DB_USERNAME "$DB_USER"
set_env_var DB_PASSWORD "$DB_PASS"
log ".env database settings updated for the new server."

# ---------- make sure DB + user exist (idempotent) ---------------------------
mysql -uroot <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
CREATE USER IF NOT EXISTS '$DB_USER'@'127.0.0.1' IDENTIFIED BY '$DB_PASS';
ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
ALTER USER '$DB_USER'@'127.0.0.1' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

# ---------- restore the database --------------------------------------------
if [ -f "$STAGE/db.sql" ]; then
  TABLES="$(mysql -uroot -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME';")"
  if [ "${TABLES:-0}" -gt 0 ] && [ "${FORCE_DB:-0}" != "1" ]; then
    warn "Database '$DB_NAME' already has $TABLES tables — dump NOT restored."
    warn "  First run? This is fine (empty DB restored earlier)."
    warn "  To OVERWRITE:  sudo FORCE_DB=1 bash deploy-app.sh $BACKUP_TAR"
  else
    log "Restoring database dump ..."
    mysql -uroot "$DB_NAME" < "$STAGE/db.sql"
    log "Database restored ($(mysql -uroot -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME';") tables)."
  fi
else
  warn "No db.sql in backup — migrations will create an empty schema."
fi

# ---------- PHP dependencies -------------------------------------------------
cd "$APP_DIR"
log "Installing PHP dependencies (composer) ..."
composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# ---------- Frontend build ---------------------------------------------------
if [ -f package-lock.json ]; then
  log "Installing npm dependencies (npm ci) ..."
  npm ci --no-audit --no-fund
else
  log "Installing npm dependencies (npm install) ..."
  npm install --no-audit --no-fund
fi
log "Building frontend (vite) ..."
npm run build

# ---------- ownership + Laravel housekeeping (as www-data) -------------------
chown -R www-data:www-data "$APP_DIR"
run_as_app() { runuser -u www-data -- "$@"; }

# If public/storage is a real directory (e.g. copied from Windows), move it aside.
if [ -e "$APP_DIR/public/storage" ] && [ ! -L "$APP_DIR/public/storage" ]; then
  warn "public/storage is a real directory — moving it aside."
  mv "$APP_DIR/public/storage" "$APP_DIR/storage/restored-public-storage"
fi

run_as_app php "$APP_DIR/artisan" storage:link --force
run_as_app php "$APP_DIR/artisan" migrate --force
run_as_app php "$APP_DIR/artisan" config:cache
run_as_app php "$APP_DIR/artisan" view:cache
# NOTE: route:cache is deliberately NOT run — this app uses closure routes,
# which Laravel cannot cache. That's fine (routes are still fast).
run_as_app php "$APP_DIR/artisan" config:clear >/dev/null 2>&1 || true
run_as_app php "$APP_DIR/artisan" config:cache

# ---------- queue worker (QUEUE_CONNECTION=database) -------------------------
log "Configuring queue worker (supervisor) ..."
mkdir -p /var/log/aquilala
chown www-data:www-data /var/log/aquilala
cat > /etc/supervisor/conf.d/aquilala-queue.conf <<SUP
[program:aquilala-queue]
command=php $APP_DIR/artisan queue:work --sleep=3 --tries=3 --timeout=90
directory=$APP_DIR
user=www-data
numprocs=1
autostart=true
autorestart=true
startsecs=5
stopsignal=TERM
stopasgroup=true
killasgroup=true
stdout_logfile=/var/log/aquilala/queue.log
stderr_logfile=/var/log/aquilala/queue-error.log
stdout_logfile_maxbytes=10485760
stderr_logfile_maxbytes=10485760
SUP
supervisorctl reread >/dev/null
supervisorctl update >/dev/null
sleep 2
supervisorctl status aquilala-queue || true

# ---------- scheduler cron (currently no scheduled tasks, but future-proof) --
if ! crontab -u www-data -l 2>/dev/null | grep -Fq 'artisan schedule:run'; then
  (crontab -u www-data -l 2>/dev/null || true; echo "* * * * * php $APP_DIR/artisan schedule:run >> /dev/null 2>&1") \
    | crontab -u www-data -
fi

# ---------- keep old server configs for reference ----------------------------
mkdir -p /root/old-server-configs
for f in "$STAGE"/etc_*.tar.gz; do
  [ -f "$f" ] || continue
  tar -xzf "$f" -C /root/old-server-configs || true
done

# ---------- smoke test --------------------------------------------------------
log "----------------------------------------------------------------"
log "DEPLOY COMPLETE. Quick check:"
CODE="$(curl -s -o /dev/null -w '%{http_code}' -H "Host: $SITE_DOMAIN" http://127.0.0.1/ || echo 000)"
log "  Local HTTP status: $CODE  (200 or 302 = OK, 500 = check logs)"
log ""
log "NEXT STEPS:"
log "  1) Test before DNS switch:"
log "       curl -H 'Host: $SITE_DOMAIN' http://<NEW-IP>/"
log "     (or add 'IP  $SITE_DOMAIN' to your PC's hosts file and open https://...)"
log "  2) Point the DNS A record of $SITE_DOMAIN to <NEW-IP>."
log "  3) After DNS propagates:"
log "       certbot --nginx -d $SITE_DOMAIN -d www.$SITE_DOMAIN"
log "  4) Verify: login, images, Excel export, emails, queue jobs."
log "     Logs: tail -f $APP_DIR/storage/logs/laravel.log"
log "            supervisorctl status aquilala-queue"
log "            tail -f /var/log/aquilala/queue-error.log"
