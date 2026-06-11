# Deploy Siakad-Feeder (Windows + Laragon)

**Repository:** https://github.com/ypgscto/siakad-feeder

Lokal dan server memakai **Windows** + **Laragon**. Deploy otomatis lewat GitHub Actions: SSH ke server → `git pull` → skrip PowerShell.

## Ringkasan alur

1. Push ke branch `main` di GitHub
2. GitHub Actions SSH ke server Windows
3. `git fetch` + `git reset --hard origin/main`
4. `deploy/remote-post-deploy.ps1` — `composer install`, `npm run build`, `artisan migrate`, cache

File **tidak** di-sync lewat rsync; server harus sudah punya clone Git + `.env` sendiri.

---

## 1. Lokal (Windows + Laragon)

```powershell
cd C:\laragon\www\Siakad-Feeder
git status
git push origin main
```

Remote: `https://github.com/ypgscto/siakad-feeder.git`

Development:

```powershell
composer install
npm install
npm run build
copy .env.example .env
php artisan key:generate
php artisan migrate
```

URL lokal (Auto virtual hosts Laragon): `http://siakad-feeder.test`

---

## 2. Setup awal server Windows

### 2.1 Prasyarat

- **Laragon** (PHP 8.2+, Composer, Node.js — centang saat install)
- **Git for Windows**
- **OpenSSH Server** (Windows):  
  *Settings → System → Optional features → Add OpenSSH Server*  
  Lalu: `Start-Service sshd` dan buka port **22** di firewall.

### 2.2 Clone proyek

```powershell
cd C:\laragon\www
git clone https://github.com/ypgscto/siakad-feeder.git siakad-feeder
cd siakad-feeder
copy .env.example .env
notepad .env
```

### 2.3 Isi `.env` production (contoh)

```env
APP_NAME="Siakad-Feeder"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://siakad-feeder.test

DB_CONNECTION=sqlite
DB_DATABASE=

CACHE_STORE=file
SESSION_DRIVER=database

SIAKAD_API_BASE_URL=http://siakad-api.test
SIAKAD_API_TOKEN=token-sama-dengan-siakad-api
SIAKAD_KODE_ID=093146

FEEDER_WS_URL=http://103.167.35.204:8100/ws/live2.php
FEEDER_USERNAME=...
FEEDER_PASSWORD=...
FEEDER_ID_PERGURUAN_TINGGI=...
```

### 2.4 Setup pertama

```powershell
cd C:\laragon\www\siakad-feeder
php artisan key:generate
powershell -ExecutionPolicy Bypass -File deploy\server-setup.ps1
```

### 2.5 Laragon / web server

- Aktifkan **Auto virtual hosts** di Laragon
- Folder `C:\laragon\www\siakad-feeder` → biasanya `http://siakad-feeder.test`
- Document root harus mengarah ke folder **`public`** (Laragon otomatis untuk struktur Laravel)
- Jika pakai **IIS**: install URL Rewrite; `public\web.config` sudah disertakan

Tambah entri hosts jika perlu (Laragon sering otomatis):

```
127.0.0.1 siakad-feeder.test
```

Sesuaikan `APP_URL` dengan URL yang dipakai.

### 2.6 Kunci SSH untuk GitHub Actions

Di **server** (PowerShell, user yang dipakai SSH — mis. user Windows Anda):

```powershell
ssh-keygen -t ed25519 -C "github-actions-siakad-feeder" -f $env:USERPROFILE\.ssh\github_deploy -N '""'
Get-Content $env:USERPROFILE\.ssh\github_deploy.pub |
    Add-Content $env:USERPROFILE\.ssh\authorized_keys
Get-Content $env:USERPROFILE\.ssh\github_deploy
```

Salin **seluruh private key** (`-----BEGIN ... END-----`) ke GitHub Secret `DEPLOY_SSH_KEY`.

Repo **private**: di server, clone dengan credential atau deploy key:

```powershell
git config --global credential.helper manager
git pull   # login GitHub sekali
```

---

## 3. GitHub Secrets

Repo → **Settings → Secrets and variables → Actions**

| Secret | Contoh (Windows) | Wajib |
|--------|------------------|-------|
| `DEPLOY_HOST` | IP server atau `192.168.1.10` | Ya |
| `DEPLOY_USER` | `Administrator` atau nama user Windows | Ya |
| `DEPLOY_SSH_KEY` | Private key OpenSSH | Ya |
| `DEPLOY_PATH` | `C:/laragon/www/siakad-feeder` | Ya |
| `DEPLOY_PORT` | `22` | Opsional |

Gunakan **garis miring `/`** di `DEPLOY_PATH` agar kompatibel dengan OpenSSH.

---

## 4. Deploy

- **Otomatis:** `git push origin main`
- **Manual:** GitHub → **Actions** → **Deploy to Server** → **Run workflow**

### Deploy manual di server (tanpa Actions)

```powershell
cd C:\laragon\www\siakad-feeder
git pull origin main
powershell -ExecutionPolicy Bypass -File deploy\remote-post-deploy.ps1
```

---

## 5. Setelah deploy

- Buka URL di `APP_URL` → halaman login
- Superadmin: **Pengaturan Koneksi** atau periksa `.env`
- Database SQLite: `database\database.sqlite` (tidak dihapus saat `git pull`)

---

## Troubleshooting (Windows)

| Masalah | Solusi |
|---------|--------|
| `php` tidak dikenali via SSH | Set `SIFEEDER_PHP=C:\laragon\bin\php\php-8.2.x\php.exe` di Environment Variables user |
| `composer` / `npm` tidak dikenali | Pastikan Laragon terinstall; atau set `SIFEEDER_COMPOSER` / `SIFEEDER_NPM` |
| 500 / permission | Pastikan `storage` dan `bootstrap\cache` bisa ditulis user yang menjalankan PHP |
| CSS hilang | Jalankan `npm run build`; cek folder `public\build` |
| SSH ditolak | OpenSSH Server aktif; firewall port 22; user punya hak login |
| `git pull` gagal (repo private) | Credential Manager atau deploy key read-only |

---

## CI

Workflow `ci.yml` menjalankan `php artisan test` di GitHub (Linux runner) saat push/PR ke `main`.

---

## Linux (opsional)

Skrip bash lama tetap ada: `deploy/remote-post-deploy.sh`, `deploy/nginx.conf.example` — untuk server Linux jika suatu saat dipindah.
