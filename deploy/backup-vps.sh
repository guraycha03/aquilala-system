#!/usr/bin/env bash
# =============================================================================
# aquilala — MIGRATION BACKUP
# RUN ON THE OLD (Hostinger) VPS, as root:
#     sudo bash backup-vps.sh [path-to-app-dir]
#
# If no path is given, the script searches for "artisan" automatically.
#
# Produces:  /root/aquilala-backup-<timestamp>.tar.gz
# Download:  scp root@<old-server-ip>:/root/aquilala-backup-*.tar.gz .
# =============================================================================
set -euo pipefail

log()  { printf '[backup] %s\n' "$*"; }
warn() { printf '[backup][WARN] %s\n' "$*"; }

if [ "$(id -u)" -ne 0 ]; then
  echo "Run as root:  sudo bash backup-vps.sh"
  exit 1
fi

# ---------- locate the Laravel app (the directory containing artisan) -------
APP_DIR="${1:-}"
if [ -z "$APP_DIR" ]; then
  log "Searching for artisan ..."
  FOUND="$(find /var/www /home /root /opt /srv -maxdepth 5 -name artisan -type f 2>/dev/null | head -1 || true)"
  if [ -n "$FOUND" ]; then APP_DIR="$(dirname "$FOUND")"; fi
fi
if [ -z "${APP_DIR:-}" ] || [ ! -f "$APP_DIR/artisan" ]; then
  echo "Could not locate the app. Run:  sudo bash backup-vps.sh /path/to/app"
  exit 1
fi
APP_DIR="$(cd "$APP_DIR" && pwd)"
ENV_FILE="$APP_DIR/.env"
if [ ! -f "$ENV_FILE" ]; then
  echo "Missing $ENV_FILE — aborting."
  exit 1
fi
log "App found: $APP_DIR"

# read a value from .env (tolerates quotes and CRLF)
env_get() {
  grep -E "^${1}=" "$ENV_FILE" | tail -1 \
    | sed -E "s/^[^=]+=//; s/\r$//; s/^\"//; s/\"$//; s/^'//; s/'$//" || true
}

DB_NAME="$(env_get DB_DATABASE)"
DB_USER="$(env_get DB_USERNAME)"
DB_PASS="$(env_get DB_PASSWORD)"
DB_HOST_RAW="$(env_get DB_HOST)"
DB_HOST="${DB_HOST_RAW:-127.0.0.1}"

TS="$(date +%Y%m%d-%H%M)"
STAGE="/root/.aquilala-backup-stage"
rm -rf "$STAGE"
mkdir -p "$STAGE/meta" "$STAGE/env"

# ---------- 1) database dump ------------------------------------------------
log "Dumping database '${DB_NAME:-?}' ..."
DUMP_OK=0
if [ -n "$DB_NAME" ]; then
  if [ -n "$DB_PASS" ] && mysqldump --single-transaction --routines --triggers --events \
       -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" >"$STAGE/db.sql" 2>/dev/null; then
    DUMP_OK=1
    log "Dump OK (using credentials from .env)."
  elif mysqldump --single-transaction --routines --triggers --events \
       "$DB_NAME" >"$STAGE/db.sql" 2>/dev/null; then
    DUMP_OK=1
    log "Dump OK (using OS root)."
  fi
fi
if [ "$DUMP_OK" -ne 1 ] || [ ! -s "$STAGE/db.sql" ]; then
  echo "Database dump FAILED."
  echo "Check DB_DATABASE / DB_USERNAME / DB_PASSWORD in $ENV_FILE,"
  echo "or dump manually:  mysqldump --single-transaction $DB_NAME > /root/db.sql"
  exit 1
fi
log "DB dump size: $(du -h "$STAGE/db.sql" | cut -f1)"

# ---------- 2) .env ---------------------------------------------------------
cp -p "$ENV_FILE" "$STAGE/env/.env"
log "Saved .env (contains API keys + APP_KEY — keep the backup private!)."

# ---------- 3) application files (excluding vendor/node_modules/logs) -------
log "Staging application files ..."
APP_STAGE="$STAGE/appdir"
mkdir -p "$APP_STAGE"
cp -a "$APP_DIR/." "$APP_STAGE/"
rm -rf "$APP_STAGE/vendor" "$APP_STAGE/node_modules"
rm -rf "$APP_STAGE/storage/logs/"* \
       "$APP_STAGE/storage/framework/cache/"* \
       "$APP_STAGE/storage/framework/sessions/"* \
       "$APP_STAGE/storage/framework/views/"*
tar -czf "$STAGE/app.tar.gz" -C "$APP_STAGE" .
rm -rf "$APP_STAGE"
log "App archive size: $(du -h "$STAGE/app.tar.gz" | cut -f1)"

# ---------- 4) server configs (reference for the new server) ----------------
log "Saving server configs ..."
for d in /etc/nginx /etc/apache2 /etc/lsws /etc/php /etc/supervisor /etc/letsencrypt /etc/cron.d; do
  if [ -d "$d" ]; then
    tar -czf "$STAGE/etc_$(basename "$d").tar.gz" "$d" 2>/dev/null || warn "could not archive $d"
  fi
done

# ---------- 5) environment metadata -----------------------------------------
snap() {
  OUT="$1"; shift
  if command -v "$1" >/dev/null 2>&1; then
    { echo "\$ $*"; "$@"; } > "$STAGE/meta/$OUT" 2>&1 || true
  else
    echo "$1: not installed" > "$STAGE/meta/$OUT"
  fi
}
{
  echo "date:  $(date -Is)"
  echo "host:  $(hostname)"
  echo "os:    $(. /etc/os-release 2>/dev/null && echo "$PRETTY_NAME" || uname -a)"
  echo "app:   $APP_DIR"
} > "$STAGE/meta/info.txt"
snap os.txt cat /etc/os-release
snap php.txt php -v
snap node.txt node -v
snap composer.txt composer -V
snap mysql.txt mysql --version
snap nginx.txt nginx -v
dpkg --get-selections > "$STAGE/meta/packages.txt" 2>/dev/null || true
crontab -l > "$STAGE/meta/crontab-root.txt" 2>/dev/null || true
crontab -u www-data -l > "$STAGE/meta/crontab-www-data.txt" 2>/dev/null || true

# ---------- 6) pack ---------------------------------------------------------
OUT="/root/aquilala-backup-$TS.tar.gz"
tar -czf "$OUT" -C "$STAGE" .
rm -rf "$STAGE"

log "----------------------------------------------------------------"
log "BACKUP CREATED: $OUT  ($(du -h "$OUT" | cut -f1))"
log ""
log "Download it to your PC now (from the repo root):"
log "  scp root@<old-server-ip>:$OUT ."
log ""
log "Contains: db.sql, env/.env, app.tar.gz, server configs, meta info."
log "Do NOT delete until the migration is verified. This is your rollback."
