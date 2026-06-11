# Panduan Deploy — Server Windows + Apache

**Repo:** https://github.com/ypgscto/siakad-feeder

---

## Ringkasan

| # | Di mana | Apa yang dilakukan |
|---|---------|-------------------|
| 1 | **PC development** | `git push origin main` |
| 2 | **Server** | `deploy\update.ps1` |

Instalasi pertama di server: `deploy\install.ps1` (sekali saja).

---

## A. Instalasi pertama (server)

### Prasyarat

- Windows + Apache (bukan IIS)
- PHP, Composer, Node.js/npm (mis. dari `C:\webserver\bin\`)
- Git

### Langkah

```powershell
cd C:\webserver\www
git clone https://github.com/ypgscto/siakad-feeder.git siakad-feeder
cd siakad-feeder
powershell -ExecutionPolicy Bypass -File deploy\install.ps1
```

Skrip otomatis:

1. Sinkron kode GitHub
2. Aktifkan ekstensi PHP SQLite
3. Buat `.env` + `APP_KEY`
4. `composer install`, `npm run build`, `migrate`, `seed`
5. Cache config/route/view

### Edit `.env` (setelah install)

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://98.142.245.18/siakad-feeder/public
APP_SUBDIRECTORY=/siakad-feeder/public

SIAKAD_API_BASE_URL=http://98.142.245.18/siakad-api/public
SIAKAD_API_TOKEN=<sama dengan siakad-api>
SIAKAD_KODE_ID=093146

FEEDER_WS_URL=http://103.167.35.204:8100/ws/live2.php
FEEDER_USERNAME=...
FEEDER_PASSWORD=...
FEEDER_PREFER_JSON=true

DB_CONNECTION=sqlite
```

```powershell
php artisan config:cache
```

### Uji

| URL | Hasil |
|-----|-------|
| `http://98.142.245.18/siakad-feeder/public/up` | HTTP 200 |
| `http://98.142.245.18/siakad-feeder/public/` | Halaman login |

Login: `admin@gmail.com` / `123456`

---

## B. Update dari GitHub (rutin)

### Di PC development

```powershell
cd C:\laragon\www\Siakad-Feeder
git add -A
git commit -m "pesan perubahan"
git push origin main
```

### Di server

```powershell
cd C:\webserver\www\siakad-feeder
powershell -ExecutionPolicy Bypass -File deploy\update.ps1
```

**Jangan pakai `git pull` manual.** `update.ps1` memakai `git reset --hard` + `git clean` sehingga file salinan manual tidak bikin konflik lagi.

`.env` dan `database/database.sqlite` **tidak dihapus** (di-backup ke `.deploy-backup/`).

---

## C. Folder sudah berantakan (kasus Anda)

Jika pernah salin file manual dan `git pull` gagal:

```powershell
cd C:\webserver\www\siakad-feeder
powershell -ExecutionPolicy Bypass -File deploy\update.ps1
```

Satu perintah — tidak perlu hapus folder atau `git pull` terpisah.

---

## D. GitHub Actions (opsional, otomatis)

Setiap push ke `main`, workflow deploy via SSH.

**Secrets** di GitHub → Settings → Secrets → Actions:

| Secret | Nilai |
|--------|-------|
| `DEPLOY_HOST` | `98.142.245.18` |
| `DEPLOY_USER` | user SSH Windows |
| `DEPLOY_SSH_KEY` | private key OpenSSH |
| `DEPLOY_PATH` | `C:\webserver\www\siakad-feeder` |
| `DEPLOY_PORT` | `22` (opsional) |

---

## E. Troubleshooting

| Gejala | Solusi |
|--------|--------|
| `git pull` konflik / overwrite | Pakai `deploy\update.ps1`, bukan `git pull` |
| `could not find driver` (sqlite) | Otomatis di install/update; manual: `deploy\enable-php-sqlite.ps1` |
| HTTP 500 | `deploy\diagnose.ps1` lalu `deploy\update.ps1`. Umum: migrate belum jalan, `manifest.json` hilang, `SESSION_DRIVER=database` tanpa tabel sessions |
| HTTP 404 | Akses lewat `.../siakad-feeder/public/` |
| `.env` hilang | Restore dari `.deploy-backup\env-*.bak` |

---

## F. Siakad-API (update terpisah)

Update API **sebelum** atau bersamaan dengan Feeder:

```powershell
cd C:\webserver\www\siakad-api
powershell -ExecutionPolicy Bypass -File deploy\update.ps1
```

`.env` API tidak disentuh.

---

Lihat juga: [deploy/README.md](../deploy/README.md)
