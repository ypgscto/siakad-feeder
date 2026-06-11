# Deploy Fresh — Windows Server (Siakad-Feeder + Siakad-API)

Panduan ini untuk **server Windows baru** (Laragon / IIS / Apache) dengan aplikasi **belum pernah terpasang**.

**Urutan wajib:** pasang **Siakad-API dulu** → lalu **Siakad-Feeder** → pastikan **Neo Feeder** sudah jalan.

---

## Ringkasan komponen

| Aplikasi | Folder contoh | Fungsi |
|----------|---------------|--------|
| **siakad-api** | `C:\laragon\www\siakad-api` | Baca `siakad_db` (MySQL), REST API untuk Feeder |
| **siakad-feeder** | `C:\laragon\www\siakad-feeder` | UI jembatan SIAKAD → Neo Feeder |
| **Neo Feeder** | `C:\NEO FEEDER` | WS PDDIKTI (biasanya server terpisah) |

---

## 0. Sebelum deploy — dari PC development

### Siakad-Feeder (GitHub)

Pastikan perubahan terbaru sudah di **commit & push** ke `main`:

```powershell
cd C:\laragon\www\Siakad-Feeder
git status
git add -A
git commit -m "fix: Feeder JSON, pengaturan koneksi, filter NIM kirim mahasiswa"
git push origin main
```

Repo: https://github.com/ypgscto/siakad-feeder

### Siakad-API — **perlu update**

**Ya**, Siakad-API harus di-update jika server masih versi lama. Perubahan untuk Siakad-Feeder terbaru:

| File | Perubahan |
|------|-----------|
| `app/Http/Controllers/Api/SiakadSyncController.php` | Parameter query `nims` / `nim` pada `mahasiswaSync` |
| `app/Services/SiakadReadService.php` | Filter SQL `Login IN (...)` saat kirim mahasiswa terpilih |

Tanpa update ini, kirim mahasiswa tercentang tetap mengambil **semua** mahasiswa filter prodi/tahun (lambat).

**Cara update siakad-api di server:** salin 2 file di atas dari development, atau deploy ulang folder proyek (lihat bagian 3).

---

## 1. Prasyarat server Windows

- Windows Server 2019/2022 (atau Windows 10/11 + Laragon)
- **Laragon Full** (PHP **8.2+**, Composer, Node.js **20+**, MySQL/MariaDB)
- **Git for Windows**
- Akses ke **MySQL Siakad** (`siakad_db`) — host/port/user dari Sisfo produksi
- Port firewall: **80/443** (web), **22** (SSH deploy opsional)
- Neo Feeder dapat dijangkau dari server (mis. `http://103.167.35.204:8100`)

```powershell
# Cek (PowerShell)
php -v
composer -V
node -v
npm -v
git --version
```

---

## 2. Deploy Siakad-API (pertama)

### 2.1 Salin / clone proyek

```powershell
cd C:\laragon\www
# Opsi A: salin dari USB/zip development
# Opsi B: git clone <url-repo-siakad-api> siakad-api
cd siakad-api
```

Pastikan 2 file **nims** (lihat tabel bagian 0) sudah versi terbaru.

### 2.2 Environment

```powershell
copy .env.example .env
notepad .env
```

Contoh **production** (sesuaikan IP/domain):

```env
APP_NAME=SiakadAPI
APP_ENV=production
APP_DEBUG=false
APP_KEY=                                    # diisi key:generate
APP_URL=http://98.142.245.18/siakad-api/public
APP_SUBDIRECTORY=/siakad-api/public

LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3308
DB_DATABASE=siakad_api
DB_USERNAME=root
DB_PASSWORD=

SIAKAD_API_TOKEN=siakad-api-shared-token-2026
SIAKAD_KODE_ID=093146

SIAKAD_DB_HOST=127.0.0.1
SIAKAD_DB_PORT=3306
SIAKAD_DB_DATABASE=siakad_db
SIAKAD_DB_USERNAME=...
SIAKAD_DB_PASSWORD=...
```

Token `SIAKAD_API_TOKEN` **harus sama** nanti di Siakad-Feeder.

### 2.3 Setup pertama

```powershell
cd C:\laragon\www\siakad-api
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan app:prepare-production --force
php artisan config:cache
php artisan route:cache
```

Atau pakai skrip:

```powershell
powershell -ExecutionPolicy Bypass -File deploy\remote-post-deploy.ps1
```

### 2.4 Web server (subfolder `/siakad-api/public`)

Pastikan file ada di repo dan ter-upload:

- `web.config` (root proyek)
- `.htaccess` (root proyek)
- `public\web.config`

Laragon: folder `www\siakad-api` → alias Apache; document root ke **`public`** atau URL dengan `/public`.

### 2.5 Verifikasi API

```powershell
curl.exe -s http://98.142.245.18/siakad-api/public/api/health
```

Harus: `{"ok":true,"service":"siakad-api","siakad_db":"ok"}`

```powershell
curl.exe -s -H "Authorization: Bearer siakad-api-shared-token-2026" ^
  "http://98.142.245.18/siakad-api/public/api/mahasiswa-sync?prodi_id=...&tahun_id=..."
```

Opsional filter NIM (fitur baru):

```powershell
curl.exe -s -H "Authorization: Bearer TOKEN" ^
  "http://98.142.245.18/siakad-api/public/api/mahasiswa-sync?prodi_id=X&tahun_id=Y&nims=25222067"
```

---

## 3. Deploy Siakad-Feeder (kedua)

### 3.1 Clone dari GitHub

```powershell
cd C:\laragon\www
git clone https://github.com/ypgscto/siakad-feeder.git siakad-feeder
cd siakad-feeder
```

### 3.2 Environment

```powershell
copy .env.example .env
notepad .env
```

Contoh **production**:

```env
APP_NAME="Siakad-Feeder"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://98.142.245.18/siakad-feeder/public

DB_CONNECTION=sqlite
DB_DATABASE=
CACHE_STORE=file
SESSION_DRIVER=database

SIAKAD_API_BASE_URL=http://98.142.245.18/siakad-api/public
SIAKAD_API_TOKEN=siakad-api-shared-token-2026
SIAKAD_API_TIMEOUT=120
SIAKAD_KODE_ID=093146

FEEDER_WS_URL=http://103.167.35.204:8100/ws/live2.php
FEEDER_USERNAME=...
FEEDER_PASSWORD=...
FEEDER_ID_PERGURUAN_TINGGI=...
FEEDER_DEFAULT_ID_WILAYAH=070000
FEEDER_PREFER_JSON=true
FEEDER_WRITE_TIMEOUT=45

SIFEEDER_ALLOW_LOCAL_LOGIN_FALLBACK=true
SIFEEDER_SIAKAD_EMAIL_DOMAIN=stikesgunungsari.ac.id
```

> **Penting:** `SIAKAD_API_BASE_URL` tanpa `/api` di akhir. Path API ditambahkan otomatis oleh aplikasi.

### 3.3 Setup pertama (sekali)

```powershell
cd C:\laragon\www\siakad-feeder
php artisan key:generate
powershell -ExecutionPolicy Bypass -File deploy\server-setup.ps1
php artisan db:seed --force
```

`db:seed` membuat user admin bootstrap + pemetaan Feeder awal (sesuaikan `SIFEEDER_SEED_*` di `.env` jika perlu).

### 3.4 Web server

- Document root: `C:\laragon\www\siakad-feeder\public`
- IIS: `public\web.config` sudah disertakan
- Sesuaikan `APP_URL` dengan URL yang dipakai browser

### 3.5 Verifikasi Feeder app

```powershell
cd C:\laragon\www\siakad-feeder
php artisan sifeeder:siakad-ping
php artisan sifeeder:feeder-ping
```

Browser: login → **Pengaturan Koneksi** → **Tes Siakad-API** & **Tes Neo Feeder**.

Setelah simpan URL production di Pengaturan, pastikan nilai **tidak kembali** ke `siakad-api.test` (butuh versi kode terbaru dengan perbaikan form).

---

## 4. Deploy ulang (server sudah pernah setup)

### Siakad-Feeder (otomatis GitHub Actions)

Push ke `main` → workflow `Deploy to Server` (SSH + `git pull` + `remote-post-deploy.ps1`).

Secrets: `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_SSH_KEY`, `DEPLOY_PATH` = `C:/laragon/www/siakad-feeder`

### Siakad-Feeder (manual)

```powershell
cd C:\laragon\www\siakad-feeder
powershell -ExecutionPolicy Bypass -File deploy\git-pull-deploy.ps1
```

### Siakad-API (manual)

Salin file terbaru (minimal 2 file nims) lalu:

```powershell
cd C:\laragon\www\siakad-api
powershell -ExecutionPolicy Bypass -File deploy\remote-post-deploy.ps1
```

---

## 5. Checklist setelah go-live

- [ ] `api/health` → `siakad_db: ok`
- [ ] Login Siakad-Feeder (SSO atau admin seed)
- [ ] Pengaturan Koneksi: URL production tersimpan
- [ ] Tes Siakad-API & Tes Neo Feeder OK
- [ ] **Pemetaan Feeder** — prodi, agama, jenis daftar
- [ ] Kirim **1 mahasiswa** tercentang (uji JSON Feeder)
- [ ] Neo Feeder: `runner-win.exe` + `NEO_FEEDER_DB` Running (lihat `docs/NEO-FEEDER-SERVER.md`)

---

## 6. Troubleshooting singkat

| Masalah | Solusi |
|---------|--------|
| API 404 HTML Apache | `APP_SUBDIRECTORY`, upload `web.config` / `.htaccess` |
| Feeder: GetToken OK, kirim gagal | `FEEDER_PREFER_JSON=true`; cek Neo Feeder Windows |
| URL pengaturan kembali ke `.test` | Deploy kode terbaru (`SettingsFieldName` + `Arr::get`) |
| Email sudah digunakan (Feeder) | Biodata sudah ada → kirim **riwayat** saja; atau perbaiki email unik |
| `php` tidak dikenali via SSH | Set `SIFEEDER_PHP` ke path Laragon PHP |

---

## 7. Dokumen terkait

- [DEPLOY.md](./DEPLOY.md) — GitHub Actions & SSH
- [NEO-FEEDER-SERVER.md](./NEO-FEEDER-SERVER.md) — tuning server Neo Feeder Windows
- `siakad-api/docs/DEPLOY-PRODUCTION.md` — detail API
