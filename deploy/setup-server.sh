#!/usr/bin/env bash
# =============================================================================
# aquilala — NEW SERVER PROVISIONING (Ubuntu 24.04, x86_64 or ARM64)
# RUN AS ROOT on a fresh server:
#     sudo bash setup-server.sh
#
# Optional environment overrides:
#   SITE_DOMAIN=aquilala.me     domain that will point here
#   APP_DIR=/var/www/aquilala   where the app will live
#   DB_NAME / DB_USER / DB_PASS database name/user/password
#                                (DB_PASS must be letters/digits only)
#
# After this succeeds, run:  sudo bash deploy-app.sh <backup.tar.gz>
# =============================================================================
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive

log()  { printf '[setup] %s\n' "$*"; }
warn() { printf '[setup][WARN] %s\n' "$*"; }

if [ "$(id -u)" -ne 0 ]; then
  echo "Run as root:  sudo bash setup-server.sh"
  exit 1
fi

APP_DIR="${APP_DIR:-/var/www/aquilala}"
SITE_DOMAIN="${SITE_DOMAIN:-aquilala.me}"
CRED_FILE="/root/.aquilala-db-creds"

# Reuse credentials from a previous run (makes re-runs idempotent).
if [ -f "$CRED_FILE" ]; then
  # shellcheck disable=SC1090
  . "$CRED_FILE"
fi
DB_NAME="${DB_NAME:-aquilala_db}"
DB_USER="${DB_USER:-aquilala_user}"
DB_PASS="${DB_PASS:-$(openssl rand -hex 16)}"

# The deploy script writes these into .env with sed — keep them simple.
if ! printf '%s' "$DB_PASS" | grep -qE '^[A-Za-z0-9]+$' \
   || ! printf '%s' "$DB_NAME" | grep -qE '^[A-Za-z0-9_]+$' \
   || ! printf '%s' "$DB_USER" | grep -qE '^[A-Za-z0-9_]+$'; then
  echo "DB_NAME/DB_USER must be letters/digits/underscore, DB_PASS letters/digits only."
  echo "Example:  DB_PASS=\$(openssl rand -hex 16) sudo -E bash setup-server.sh"
  exit 1
fi

log "Installing packages (nginx, mysql, php8.3, node22, supervisor, certbot) ..."
apt-get update -y
apt-get upgrade -y
apt-get install -y \
  nginx mysql-server cron supervisor ca-certificates curl gnupg unzip git rsync \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-xml php8.3-mbstring php8.3-curl \
  php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl php8.3-sqlite3 php8.3-opcache \
  certbot python3-certbot-nginx tesseract-ocr

# ---------- Node.js 22 (Vite 7 requires Node >= 20.19 / 22.12) --------------
if ! command -v node >/dev/null 2>&1 || ! node -v | grep -qE '^v(2[2-9]|[3-9][0-9])'; then
  log "Installing Node.js 22 ..."
  curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
  apt-get install -y nodejs
fi
log "node: $(node -v)  npm: $(npm -v)"

# ---------- Composer ---------------------------------------------------------
if ! command -v composer >/dev/null 2>&1; then
  log "Installing Composer ..."
  curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
  php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
  rm -f /tmp/composer-setup.php
fi
log "php: $(php -r 'echo PHP_VERSION;')  composer: $(composer -V 2>/dev/null | head -1)"

# ---------- System basics ----------------------------------------------------
timedatectl set-timezone Asia/Manila || true

# ---------- Swap (critical on small VMs — e.g. Azure B-series ships 1 GiB) ---
if ! swapon --show=NAME --noheadings 2>/dev/null | grep -q . && [ ! -f /swapfile ]; then
  RAM_MB="$(awk '/MemTotal/ {print int($2/1024)}' /proc/meminfo)"
  if [ "$RAM_MB" -lt 3072 ]; then
    log "Low RAM (${RAM_MB} MB) — creating 4 GB swapfile (MySQL + npm build need it) ..."
    fallocate -l 4096M /swapfile
    chmod 600 /swapfile
    mkswap /swapfile >/dev/null
    swapon /swapfile
    grep -qE '^/swapfile\b' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
    printf 'vm.swappiness=10\n' > /etc/sysctl.d/99-aquilala-swap.conf
    sysctl --system >/dev/null 2>&1 || true
  fi
fi

# ---------- MySQL ------------------------------------------------------------
log "Configuring MySQL (db: $DB_NAME, user: $DB_USER) ..."
systemctl enable --now mysql >/dev/null 2>&1 || true
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

# ---------- PHP limits --------------------------------------------------------
log "Tuning PHP (uploads 64M, memory 512M) ..."
for SAPI in cli fpm; do
  cat > "/etc/php/8.3/$SAPI/conf.d/99-aquilala.ini" <<INI
memory_limit = 512M
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 120
date.timezone = Asia/Manila
INI
done
systemctl restart php8.3-fpm

# ---------- nginx site --------------------------------------------------------
log "Writing nginx site for $SITE_DOMAIN ..."
mkdir -p "$APP_DIR/public"
cat > /etc/nginx/sites-available/aquilala <<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name $SITE_DOMAIN www.$SITE_DOMAIN;
    root $APP_DIR/public;
    index index.php;

    client_max_body_size 64M;

    gzip on;
    gzip_types text/plain text/css application/json application/javascript image/svg+xml;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_read_timeout 120;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~* \.(jpg|jpeg|png|gif|webp|ico|svg|css|js|woff2?)\$ {
        expires 7d;
        access_log off;
    }
}
NGINX
ln -sf /etc/nginx/sites-available/aquilala /etc/nginx/sites-enabled/aquilala
rm -f /etc/nginx/sites-enabled/default
nginx -t

# ---------- Services ---------------------------------------------------------
log "Enabling services ..."
systemctl enable --now nginx >/dev/null 2>&1 || true
systemctl enable --now php8.3-fpm >/dev/null 2>&1 || true
systemctl enable --now mysql >/dev/null 2>&1 || true
systemctl enable --now supervisor >/dev/null 2>&1 || true
systemctl enable --now cron >/dev/null 2>&1 || true
systemctl reload nginx

# ---------- Firewall ---------------------------------------------------------
if command -v ufw >/dev/null 2>&1; then
  log "Configuring ufw (22, 80, 443) ..."
  ufw allow OpenSSH >/dev/null 2>&1 || true
  ufw allow 80/tcp >/dev/null 2>&1 || true
  ufw allow 443/tcp >/dev/null 2>&1 || true
  ufw --force enable >/dev/null
fi

# ---------- Persist DB credentials for deploy-app.sh -------------------------
umask 077
cat > "$CRED_FILE" <<CRED
DB_NAME=$DB_NAME
DB_USER=$DB_USER
DB_PASS=$DB_PASS
CRED
umask 022

log "----------------------------------------------------------------"
log "SERVER STACK READY."
log "  Site root : $APP_DIR/public"
log "  Domain    : $SITE_DOMAIN   (override with SITE_DOMAIN=...)"
log "  Database  : $DB_NAME  /  user: $DB_USER"
log "  DB creds  : $CRED_FILE  (deploy-app.sh reads this)"
log ""
log "NEXT:"
log "  1) scp backup + scripts here:"
log "       scp deploy\\deploy-app.sh  ubuntu@<NEW-IP>:/root/"
log "       scp aquilala-backup-*.tar.gz ubuntu@<NEW-IP>:/root/"
log "  2) sudo bash deploy-app.sh /root/aquilala-backup-<ts>.tar.gz"
log ""
log "REMINDER: in the cloud console, make sure the instance's"
log "Security List / firewall also allows ports 22, 80 and 443."
