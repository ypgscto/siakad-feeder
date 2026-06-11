# Deploy Siakad-Feeder — Server Windows (Apache)

Hanya **2 skrip** yang perlu diingat:

| Situasi | Perintah |
|---------|----------|
| **Instalasi pertama** | `deploy\install.ps1` |
| **Update dari GitHub** | `deploy\update.ps1` |

Kedua skrip otomatis: sinkron GitHub, aktifkan SQLite PHP, `composer` + `npm build` + `migrate`, cache Laravel.

**.env dan database.sqlite tidak pernah ditimpa.**

---

## Instalasi pertama

```powershell
cd C:\webserver\www
git clone https://github.com/ypgscto/siakad-feeder.git siakad-feeder
cd siakad-feeder
powershell -ExecutionPolicy Bypass -File deploy\install.ps1
```

Edit `.env` production, lalu:

```powershell
php artisan config:cache
```

---

## Update (setiap push ke GitHub)

Di server:

```powershell
cd C:\webserver\www\siakad-feeder
powershell -ExecutionPolicy Bypass -File deploy\update.ps1
```

**Jangan** pakai `git pull` manual — `update.ps1` memakai `git reset --hard` + `git clean` sehingga konflik file lokal (salinan manual) teratasi.

---

## Folder sudah ada tapi deploy berantakan?

Jalankan **update** saja — skrip akan inisialisasi `.git` jika belum ada:

```powershell
cd C:\webserver\www\siakad-feeder
powershell -ExecutionPolicy Bypass -File deploy\update.ps1
```

`.env` yang sudah ada di-backup ke `.deploy-backup\` sebelum sync.

---

## Verifikasi

```text
http://98.142.245.18/siakad-feeder/public/up     → harus 200 OK
http://98.142.245.18/siakad-feeder/public/     → halaman login
```

Login default (setelah seed): `admin@gmail.com` / `123456`

---

## Skrip lama (masih jalan, mengarah ke skrip baru)

| Lama | Baru |
|------|------|
| `fresh-install.ps1` | `install.ps1` |
| `git-pull-deploy.ps1` | `update.ps1` |
| `remote-post-deploy.ps1` | dipanggil internal `update.ps1` |
| `enable-php-sqlite.ps1` | otomatis di `install` / `update` |
| `fix-apache-production.ps1` | sama dengan `update.ps1` |

Panduan lengkap: [docs/DEPLOY-WINDOWS.md](../docs/DEPLOY-WINDOWS.md)
