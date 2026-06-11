# Deploy Siakad-Feeder via GitHub Actions

**Repository:** https://github.com/ypgscto/siakad-feeder

Panduan push ke GitHub dan deploy otomatis ke server (VPS/Linux) dengan SSH + rsync.

## Ringkasan alur

1. Push ke branch `main` di GitHub
2. GitHub Actions membangun asset (`npm run build`) dan `composer install --no-dev`
3. File di-sync ke server (rsync), **tanpa** menimpa `.env`, `storage/`, atau `database.sqlite`
4. SSH menjalankan `deploy/remote-post-deploy.sh` (migrate, cache)

## Prasyarat server

- PHP **8.2+** (`pdo_sqlite`, `mbstring`, `curl`, `xml`, `zip`)
- Nginx atau Apache dengan document root → `.../public`
- User SSH untuk deploy (mis. `deploy`)
- Opsional: Composer/Node **tidak wajib** di server (build dilakukan di GitHub)

## 1. Repository GitHub

Di komputer lokal (folder proyek):

```bash
cd /path/to/Siakad-Feeder
git init
git add .
git commit -m "Initial commit Siakad-Feeder"
```

Remote sudah diarahkan ke `https://github.com/ypgscto/siakad-feeder.git`. Push:

```bash
git push -u origin main
```

## 2. Setup awal server (Ubuntu/Debian)

Jalankan di server sebagai user yang punya akses SSH (mis. `deploy`).

### 2.1 Paket & PHP

```bash
sudo apt update
sudo apt install -y nginx php8.2-fpm php8.2-cli php8.2-sqlite3 php8.2-mbstring \
  php8.2-xml php8.2-curl php8.2-zip git unzip
php -v   # harus 8.2+
```

### 2.2 Folder aplikasi

```bash
sudo mkdir -p /var/www/siakad-feeder
sudo chown -R $USER:$USER /var/www/siakad-feeder
```

**Opsi A — tunggu deploy GitHub pertama** (rsync mengisi folder), lalu:

```bash
cd /var/www/siakad-feeder
cp .env.example .env
nano .env
php artisan key:generate
bash deploy/server-setup.sh /var/www/siakad-feeder
```

**Opsi B — clone manual dulu** (agar `.env` siap sebelum Actions):

```bash
cd /var/www
git clone https://github.com/ypgscto/siakad-feeder.git siakad-feeder
cd siakad-feeder
cp .env.example .env
nano .env
composer install --no-dev --optimize-autoloader
npm ci && npm run build
bash deploy/server-setup.sh /var/www/siakad-feeder
```

### 2.3 Isi `.env` production (contoh)

```env
APP_NAME="Siakad-Feeder"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://feeder.stikesgunungsari.ac.id

DB_CONNECTION=sqlite
DB_DATABASE=

CACHE_STORE=file
SESSION_DRIVER=database

SIAKAD_API_BASE_URL=http://IP-ATAU-HOST-SIAKAD-API
SIAKAD_API_TOKEN=token-sama-dengan-siakad-api
SIAKAD_KODE_ID=093146

FEEDER_WS_URL=http://103.167.35.204:8100/ws/live2.php
FEEDER_USERNAME=...
FEEDER_PASSWORD=...
FEEDER_ID_PERGURUAN_TINGGI=...
```

### 2.4 Nginx

```bash
sudo cp /var/www/siakad-feeder/deploy/nginx.conf.example /etc/nginx/sites-available/siakad-feeder
sudo nano /etc/nginx/sites-available/siakad-feeder
# Ubah server_name dan pastikan root = /var/www/siakad-feeder/public

sudo ln -sf /etc/nginx/sites-available/siakad-feeder /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

### 2.5 Kunci SSH untuk GitHub Actions

Di server (user `deploy`):

```bash
ssh-keygen -t ed25519 -C "github-actions-siakad-feeder" -f ~/.ssh/github_deploy -N ""
cat ~/.ssh/github_deploy.pub >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/github_deploy ~/.ssh/authorized_keys
cat ~/.ssh/github_deploy
```

Salin **seluruh isi private key** ke GitHub Secret `DEPLOY_SSH_KEY`.

### 2.6 Hak akses (setelah deploy pertama)

```bash
cd /var/www/siakad-feeder
sudo chown -R www-data:$USER storage bootstrap/cache database
chmod -R ug+rwx storage bootstrap/cache
chmod 664 database/database.sqlite
```

## 3. GitHub Secrets

Di repo GitHub: **Settings → Secrets and variables → Actions → New repository secret**

| Secret | Contoh | Wajib |
|--------|--------|-------|
| `DEPLOY_HOST` | `103.167.35.204` atau `siakad-feeder.kampus.ac.id` | Ya |
| `DEPLOY_USER` | `deploy` | Ya |
| `DEPLOY_SSH_KEY` | Isi private key (mulai `-----BEGIN...`) | Ya |
| `DEPLOY_PATH` | `/var/www/siakad-feeder` | Ya |
| `DEPLOY_PORT` | `22` | Opsional |

Opsional: buat **Environment** `production` di GitHub untuk approval manual sebelum deploy.

## 4. Deploy

- **Otomatis:** push ke branch `main`
- **Manual:** tab **Actions** → **Deploy to Server** → **Run workflow**

## 5. Setelah deploy

- Cek `https://APP_URL/login`
- Superadmin: atur koneksi di **Pengaturan Koneksi** atau pastikan `.env` benar
- Database SQLite ada di `database/database.sqlite` (tidak di-overwrite deploy)

## Troubleshooting

| Masalah | Solusi |
|---------|--------|
| Permission denied `storage` | `chmod -R ug+rwx storage bootstrap/cache` |
| 500 setelah deploy | `php artisan config:clear` di server; cek `storage/logs/laravel.log` |
| Asset kosong | Pastikan workflow `npm run build` sukses; folder `public/build` ikut ter-sync |
| `.env` hilang | Deploy **tidak** mengirim `.env` — buat manual di server |

## CI

Workflow `ci.yml` menjalankan `php artisan test` pada PR/push ke `main`.
