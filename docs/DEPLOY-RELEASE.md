# Deploy server production — Siakad-API + Siakad-Feeder

| Aplikasi | GitHub |
|----------|--------|
| **Siakad-API** | https://github.com/ypgscto/siakad-api |
| **Siakad-Feeder** | https://github.com/ypgscto/siakad-feeder |

Path server: `C:\webserver\www\siakad-api` dan `C:\webserver\www\siakad-feeder`

---

## Deploy penuh (urutan wajib)

Jalankan di **server production** sebagai Administrator atau user yang punya akses git:

```powershell
# 1. Siakad-API dulu (Feeder baca data dari sini)
cd C:\webserver\www\siakad-api
powershell -ExecutionPolicy Bypass -File deploy\update.ps1

# 2. Siakad-Feeder
cd C:\webserver\www\siakad-feeder
powershell -ExecutionPolicy Bypass -File deploy\update.ps1
```

Opsional — paksa PHP 8.2 (sama dengan Apache):

```powershell
$env:SIAKAD_API_PHP = "C:\webserver\bin\php\php-8.2.xx-Win32-vs16-x64\php.exe"
$env:SIFEEDER_PHP = $env:SIAKAD_API_PHP
```

---

## Verifikasi setelah deploy

### Siakad-API

```powershell
curl.exe -s http://98.142.245.18/siakad-api/public/api/health
curl.exe -s -H "Authorization: Bearer <SIAKAD_API_TOKEN>" ^
  "http://98.142.245.18/siakad-api/public/api/mahasiswa-sync?nims=25222067"
```

JSON mahasiswa harus memuat:
- `"handphone": "25222067"` (atau nilai dari Siakad)
- `"tgl_kuliah_mulai": "2026-02-..."` (dari tabel `tahun`)

### Siakad-Feeder

```powershell
cd C:\webserver\www\siakad-feeder
php artisan sifeeder:siakad-ping
php artisan sifeeder:preview-biodata 25222067
php artisan list sifeeder
```

Preview harus menampilkan:
- `Tanggal daftar ke Feeder: 2026-02-...` (bukan `2025-09-01` untuk semester genap `20252`)
- `handphone` bukan `000000000000`

---

## Instalasi pertama (server baru)

```powershell
cd C:\webserver\www

git clone https://github.com/ypgscto/siakad-api.git siakad-api
cd siakad-api
copy .env.example .env
notepad .env
powershell -ExecutionPolicy Bypass -File deploy\install.ps1

cd C:\webserver\www
git clone https://github.com/ypgscto/siakad-feeder.git siakad-feeder
cd siakad-feeder
copy .env.example .env
notepad .env
powershell -ExecutionPolicy Bypass -File deploy\install.ps1
```

---

## Kirim mahasiswa ke Neo Feeder

1. Login Feeder → **Data Mahasiswa** → Tampilkan Data
2. Cek kolom **HP Siakad**, **HP ke Feeder**
3. Centang mahasiswa → **Kirim Biodata + Riwayat**

Jika biodata sudah ada di Feeder (NIK terdaftar) → **Tambah Riwayat** saja.

---

## Troubleshooting

| Gejala | Solusi |
|--------|--------|
| `preview-biodata` tidak ada | `git pull` Feeder belum dapat commit terbaru |
| HP Siakad = `-` | `deploy\update.ps1` di siakad-api |
| Tanggal masuk < mulai semester | API tanpa `tgl_kuliah_mulai` atau Feeder belum update |
| Deploy pakai PHP 8.3 | Set `SIAKAD_API_PHP` / `SIFEEDER_PHP` |
| `git fetch` gagal | Cek internet / hak akses GitHub di server |

---

## Perubahan rilis (Juni 2026)

- HP dari Siakad/NIM (bukan `000000000000`)
- `tanggal_daftar` dari `TglKuliahMulai` Siakad
- Deploy skrip + GitHub untuk **kedua** repo
- Perintah: `sifeeder:preview-biodata`, `sifeeder:sync-settings-from-env`

Panduan detail:
- API: [siakad-api/docs/DEPLOY-WINDOWS.md](https://github.com/ypgscto/siakad-api/blob/main/docs/DEPLOY-WINDOWS.md)
- Feeder: [docs/DEPLOY-WINDOWS.md](DEPLOY-WINDOWS.md)
