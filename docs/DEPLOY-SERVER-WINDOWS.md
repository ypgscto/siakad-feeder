# Deploy Server Windows — Skenario Saat Ini

| Aplikasi | Status di server | Skrip |
|----------|------------------|-------|
| **Siakad-API** | **Update** (sudah ada, `.env` jangan disentuh) | `siakad-api/deploy/update.ps1` |
| **Siakad-Feeder** | **Baru** (belum pernah ada) | `siakad-feeder/deploy/fresh-install.ps1` |

**Urutan:** update Siakad-API dulu → instalasi Siakad-Feeder.

---

## A. Dari PC development (sekali)

```powershell
cd C:\laragon\www\Siakad-Feeder
git add -A
git commit -m "deploy: skrip fresh-install feeder + update api"
git push origin main
```

Siapkan file update Siakad-API untuk disalin ke server (jika tidak pakai git di server):

- `app/Http/Controllers/Api/SiakadSyncController.php`
- `app/Services/SiakadReadService.php`
- `deploy/update.ps1`
- `deploy/remote-post-deploy.ps1`

---

## B. Server — Update Siakad-API (aplikasi lama)

### B.1 Salin kode terbaru

Timpa file di folder API di server (contoh `C:\webserver\www\siakad-api` atau `C:\laragon\www\siakad-api`) — **jangan** salin `.env`.

**Wajib** ada folder `deploy\` berisi `update.ps1` (dari PC development). Jika belum:

```powershell
cd C:\webserver\www\siakad-api
mkdir deploy
# Salin deploy\update.ps1 dari PC development ke folder deploy\
```

### B.2 Jalankan update

```powershell
cd C:\laragon\www\siakad-api
powershell -ExecutionPolicy Bypass -File deploy\update.ps1
```

Skrip ini:

- Memeriksa `.env` **sudah ada** (wajib untuk update)
- **Tidak** membuat / menimpa `.env`
- **Tidak** menjalankan `app:prepare-production` (hindari reset data lokal)
- Menjalankan: `composer install`, `migrate`, `config:cache`, `route:cache`

### B.3 Verifikasi

```powershell
curl.exe -s http://98.142.245.18/siakad-api/public/api/health
```

Harus: `{"ok":true,"service":"siakad-api","siakad_db":"ok"}`

Opsional — filter NIM (fitur baru):

```text
GET /api/mahasiswa-sync?prodi_id=...&tahun_id=...&nims=25222067
```

---

## C. Server — Instalasi baru Siakad-Feeder

### C.1 Clone / salin proyek

**Opsi A — Git** (pastikan kode terbaru sudah di-push ke GitHub):

```powershell
cd C:\webserver\www
git clone https://github.com/ypgscto/siakad-feeder.git siakad-feeder
cd siakad-feeder
```

**Opsi B — Zip dari PC development** jika GitHub belum ter-update: salin folder `Siakad-Feeder` ke `C:\webserver\www\siakad-feeder` (tanpa `vendor`, `node_modules` — akan di-install skrip).

### C.2 Fresh install (satu perintah)

```powershell
powershell -ExecutionPolicy Bypass -File deploy\fresh-install.ps1
```

Skrip ini:

- Buat `.env` dari `.env.example` **hanya jika belum ada**
- `php artisan key:generate` jika `APP_KEY` kosong
- `composer install`, `npm build`, `migrate`, cache
- `php artisan db:seed` (admin + pemetaan awal)

### C.3 Edit `.env` Feeder (setelah install)

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://98.142.245.18/siakad-feeder/public
APP_SUBDIRECTORY=/siakad-feeder/public

SIAKAD_API_BASE_URL=http://98.142.245.18/siakad-api/public
SIAKAD_API_TOKEN=<sama dengan token di siakad-api .env>
SIAKAD_KODE_ID=093146

FEEDER_WS_URL=http://103.167.35.204:8100/ws/live2.php
FEEDER_USERNAME=...
FEEDER_PASSWORD=...
FEEDER_PREFER_JSON=true
```

Lalu:

```powershell
php artisan config:clear
php artisan config:cache
```

### C.4 Web & uji

- Document root → `C:\webserver\www\siakad-feeder\public` (sesuaikan path server)
- Login → **Pengaturan Koneksi** → tes API & Feeder
- **Pemetaan Feeder**

```powershell
php artisan sifeeder:siakad-ping
php artisan sifeeder:feeder-ping
```

---

## D. Update Siakad-Feeder via GitHub

### D.1 Dari PC development — push ke GitHub

```powershell
cd C:\laragon\www\Siakad-Feeder
git add -A
git commit -m "deploy: perbaikan Apache subfolder + skrip production"
git push origin main
```

### D.2 Opsi A — Otomatis (GitHub Actions)

Workflow `.github/workflows/deploy.yml` jalan setiap push ke `main`.

**Secrets** di repo GitHub → Settings → Secrets and variables → Actions:

| Secret | Contoh nilai |
|--------|----------------|
| `DEPLOY_HOST` | `98.142.245.18` |
| `DEPLOY_USER` | user SSH Windows (mis. `Administrator`) |
| `DEPLOY_SSH_KEY` | private key SSH (OpenSSH) |
| `DEPLOY_PATH` | `C:\webserver\www\siakad-feeder` |
| `DEPLOY_PORT` | `22` (opsional) |

Di server harus ada **OpenSSH Server** dan folder aplikasi sudah `git clone` dari GitHub. Workflow menjalankan `git reset --hard origin/main` lalu `deploy\remote-post-deploy.ps1`.

Cek status deploy: tab **Actions** di https://github.com/ypgscto/siakad-feeder

### D.3 Opsi B — Manual di server (git pull)

Jika GitHub Actions belum dikonfigurasi, jalankan di server:

```powershell
cd C:\webserver\www\siakad-feeder
powershell -ExecutionPolicy Bypass -File deploy\git-pull-deploy.ps1
```

Atau satu baris:

```powershell
cd C:\webserver\www\siakad-feeder; git pull origin main; powershell -ExecutionPolicy Bypass -File deploy\remote-post-deploy.ps1
```

**Pertama kali** folder bukan dari git? Inisialisasi:

```powershell
cd C:\webserver\www\siakad-feeder
git init
git remote add origin https://github.com/ypgscto/siakad-feeder.git
git fetch origin main
git checkout -B main origin/main
# .env sudah ada — jangan di-commit; tetap di folder
powershell -ExecutionPolicy Bypass -File deploy\remote-post-deploy.ps1
```

`.env` Feeder **tidak** ditimpa deploy. Jangan jalankan `fresh-install.ps1` ulang.

### D.4 Verifikasi setelah update

```text
http://98.142.245.18/siakad-feeder/public/up
http://98.142.245.18/siakad-feeder/public/
```

Jika masih 500: `powershell -ExecutionPolicy Bypass -File deploy\fix-apache-production.ps1`

---

## E. Ringkasan — apa yang menyentuh `.env`

| Skrip | Siakad-API `.env` | Siakad-Feeder `.env` |
|-------|-------------------|----------------------|
| `siakad-api/deploy/update.ps1` | **Tidak disentuh** | — |
| `siakad-feeder/deploy/fresh-install.ps1` | — | Buat hanya jika belum ada |
| `siakad-feeder/deploy/git-pull-deploy.ps1` | — | **Tidak disentuh** |

---

## F. Troubleshooting

| Masalah | Solusi |
|---------|--------|
| API update: `.env wajib sudah ada` | Normal — jangan copy `.env.example` di update |
| Feeder 404 | Akses lewat `.../siakad-feeder/public/` atau arahkan virtual host ke folder `public` |
| **500 Server Error** (Apache) | Jalankan `deploy\fix-apache-production.ps1`. Penyebab umum: `npm run build` belum dijalankan (`public/build/manifest.json` hilang), `APP_KEY` kosong, migrate belum jalan. Cek `storage\logs\laravel.log` |
| **405 Method Not Allowed** | Tambah `APP_SUBDIRECTORY=/siakad-feeder/public` di `.env` (atau pakai kode terbaru — auto-detect dari `SCRIPT_NAME`); `php artisan config:clear` |
| Pengaturan URL kembali ke `.test` | Pakai kode terbaru (perbaikan form pengaturan) |
| Kirim mahasiswa lambat | Pastikan Siakad-API sudah di-update (filter `nims`) |

Lihat juga: [NEO-FEEDER-SERVER.md](./NEO-FEEDER-SERVER.md), [DEPLOY.md](./DEPLOY.md)
