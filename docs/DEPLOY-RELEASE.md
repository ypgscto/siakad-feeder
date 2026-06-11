# Deploy rilis — HP + tanggal daftar (Juni 2026)

Perubahan utama:
- Nomor HP dari Siakad/NIM (bukan `000000000000`)
- `tanggal_daftar` dari `TglKuliahMulai` Siakad (genap `20252` → bukan `2025-09-01`)
- Deploy skrip prioritaskan **PHP 8.2**
- Perintah: `sifeeder:preview-biodata`, `sifeeder:sync-settings-from-env`

---

## 1. Siakad-Feeder (GitHub)

Di **server production**:

```powershell
cd C:\webserver\www\siakad-feeder
git pull origin main
powershell -ExecutionPolicy Bypass -File deploy\update.ps1
```

Verifikasi:

```powershell
php artisan list sifeeder
php artisan sifeeder:preview-biodata 25222067
php artisan sifeeder:siakad-ping
```

Harus terlihat:
- `sifeeder:preview-biodata`
- `Tanggal daftar ke Feeder: 2026-02-...` (bukan `2025-09-01`)
- `handphone` bukan `000000000000`

Opsional — paksa PHP 8.2:

```powershell
$env:SIFEEDER_PHP = "C:\webserver\bin\php\php-8.2.xx-Win32-vs16-x64\php.exe"
powershell -ExecutionPolicy Bypass -File deploy\update.ps1
```

---

## 2. Siakad-API (wajib — HP + TglKuliahMulai)

Feeder membaca `/api/mahasiswa-sync`. API harus mengirim field:
- `handphone`
- `tgl_kuliah_mulai`

Update `app/Services/SiakadReadService.php` di server (git pull repo siakad-api jika ada), lalu:

```powershell
cd C:\webserver\www\siakad-api
powershell -ExecutionPolicy Bypass -File deploy\update.ps1
php artisan config:cache
```

Tes:

```powershell
curl.exe -s -H "Authorization: Bearer <TOKEN>" ^
  "http://98.142.245.18/siakad-api/public/api/mahasiswa-sync?nims=25222067"
```

JSON harus memuat `"handphone"` dan `"tgl_kuliah_mulai"`.

---

## 3. Setelah deploy — kirim mahasiswa

1. Login Siakad-Feeder → **Data Mahasiswa** → Tampilkan Data
2. Cek kolom **HP Siakad**, **HP ke Feeder**
3. Centang mahasiswa → **Kirim Biodata + Riwayat**

Jika biodata sudah ada (NIK terdaftar) → gunakan **Tambah Riwayat** saja.

---

## 4. Troubleshooting

| Gejala | Solusi |
|--------|--------|
| `preview-biodata` tidak ada | `git pull` belum dapat commit terbaru |
| HP Siakad = `-` | siakad-api belum di-update |
| Tanggal masuk < mulai semester | Feeder belum dapat `TanggalDaftarResolver` / API tanpa `tgl_kuliah_mulai` |
| Deploy pakai PHP 8.3 | Set `SIFEEDER_PHP` ke path php-8.2 |
