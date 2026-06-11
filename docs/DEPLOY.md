# Deploy Siakad-Feeder via GitHub Actions

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

Buat repo kosong di GitHub (mis. `ypgs-it/Siakad-Feeder`), lalu:

```bash
git branch -M main
git remote add origin git@github.com:ORG/Siakad-Feeder.git
git push -u origin main
```

## 2. Setup awal server

```bash
# Clone sekali (opsional) atau buat folder kosong untuk rsync
sudo mkdir -p /var/www/siakad-feeder
sudo chown -R deploy:deploy /var/www/siakad-feeder

# Setelah file pertama masuk (deploy manual atau rsync):
cd /var/www/siakad-feeder
bash deploy/server-setup.sh /var/www/siakad-feeder
nano .env   # APP_ENV=production, APP_DEBUG=false, APP_URL, API, Feeder
```

Contoh `.env` production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://siakad-feeder.kampus.ac.id

DB_CONNECTION=sqlite
DB_DATABASE=

CACHE_STORE=file
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

Nginx: lihat `deploy/nginx.conf.example`.

## 3. Kunci SSH untuk GitHub Actions

Di server (user `deploy`):

```bash
ssh-keygen -t ed25519 -C "github-actions-siakad-feeder" -f ~/.ssh/github_deploy -N ""
cat ~/.ssh/github_deploy.pub >> ~/.ssh/authorized_keys
```

Salin **private key** (`~/.ssh/github_deploy`) untuk GitHub Secret.

## 4. GitHub Secrets

Di repo GitHub: **Settings → Secrets and variables → Actions → New repository secret**

| Secret | Contoh | Wajib |
|--------|--------|-------|
| `DEPLOY_HOST` | `103.167.35.204` atau `siakad-feeder.kampus.ac.id` | Ya |
| `DEPLOY_USER` | `deploy` | Ya |
| `DEPLOY_SSH_KEY` | Isi private key (mulai `-----BEGIN...`) | Ya |
| `DEPLOY_PATH` | `/var/www/siakad-feeder` | Ya |
| `DEPLOY_PORT` | `22` | Opsional |

Opsional: buat **Environment** `production` di GitHub untuk approval manual sebelum deploy.

## 5. Deploy

- **Otomatis:** push ke branch `main`
- **Manual:** tab **Actions** → **Deploy to Server** → **Run workflow**

## 6. Setelah deploy

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
