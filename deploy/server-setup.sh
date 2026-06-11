#!/usr/bin/env bash
# Setup awal di server Linux (jalankan sekali sebagai user deploy).
# Contoh: bash deploy/server-setup.sh /var/www/siakad-feeder
set -euo pipefail

DEPLOY_PATH="${1:-/var/www/siakad-feeder}"

echo "==> Membuat direktori $DEPLOY_PATH"
sudo mkdir -p "$DEPLOY_PATH"
sudo chown -R "$USER:$USER" "$DEPLOY_PATH"

cd "$DEPLOY_PATH"

if [[ ! -f .env ]]; then
  if [[ -f .env.example ]]; then
    cp .env.example .env
    echo "==> .env dibuat dari .env.example — edit sebelum production!"
  else
    echo "WARNING: .env.example tidak ada. Buat .env manual."
  fi
fi

mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache database
touch database/database.sqlite
chmod -R ug+rwx storage bootstrap/cache
chmod 664 database/database.sqlite 2>/dev/null || true

if [[ -f artisan ]]; then
  php artisan key:generate --force
  php artisan migrate --force
  php artisan storage:link 2>/dev/null || true
fi

echo ""
echo "Setup awal selesai."
echo "Langkah berikutnya:"
echo "  1. Edit $DEPLOY_PATH/.env (APP_URL, Siakad-API, Feeder, dll.)"
echo "  2. Atur web server document root ke: $DEPLOY_PATH/public"
echo "  3. Tambahkan GitHub Secrets untuk workflow deploy"
