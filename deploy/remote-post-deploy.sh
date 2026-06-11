#!/usr/bin/env bash
# Dijalankan di server setelah rsync (GitHub Actions).
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$APP_DIR"

if [[ ! -f .env ]]; then
  echo "ERROR: .env tidak ditemukan di $APP_DIR"
  echo "Buat .env dari .env.example lalu jalankan: php artisan key:generate"
  exit 1
fi

mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache database
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

if [[ ! -f database/database.sqlite ]]; then
  touch database/database.sqlite
  chmod 664 database/database.sqlite
fi

php artisan migrate --force
php artisan storage:link 2>/dev/null || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Deploy selesai: $(date -u +"%Y-%m-%dT%H:%M:%SZ")"
