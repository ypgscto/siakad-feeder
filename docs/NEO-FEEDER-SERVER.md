# Panduan Setting Server Neo Feeder (PDDIKTI)

Dokumen ini untuk admin server Neo Feeder di **`103.167.35.204:8100`**, mengatasi gejala yang muncul dari Siakad-Feeder:

| Gejala | Artinya |
|--------|---------|
| **Tes GetToken OK**, kirim data gagal | Nginx (port 8100) hidup, backend WS (port 3003) tidak stabil atau hang saat Insert |
| **502 Bad Gateway** | Nginx jalan, backend Feeder / database belum siap atau crash |
| **Connection refused** | Tidak ada proses yang listen di 8100/3003 (service mati) |
| **Proses ~9 menit lalu gagal** | Client menunggu timeout berulang; backend tidak pernah mengembalikan respons Insert |

> **Instalasi Anda:** folder `C:\NEO FEEDER` = **Neo Feeder versi Windows** (bukan Docker).  
> Perintah `docker`, `ss`, dan `grep` **tidak dipakai**. Ikuti **Bagian A** di bawah.

---

## A. Panduan Windows (`C:\NEO FEEDER`) — pakai ini

### A.1 Arsitektur

```
Siakad-Feeder
  → http://IP:8100/ws/live2.php     (Nginx — folder nginx\)
      → proxy ke 127.0.0.1:3003     (server-win.exe — backend Node.js)
          → PostgreSQL              (service Windows NEO_FEEDER_DB)
```

File penting:

| File / folder | Fungsi |
|---------------|--------|
| `C:\NEO FEEDER\runner-win.exe` | Launcher utama (shortcut desktop) |
| `C:\NEO FEEDER\app\server-win.exe` | Backend Web Service (port 3003) |
| `C:\NEO FEEDER\nginx\conf\nginx.conf` | Setting proxy port 8100 |
| Service `NEO_FEEDER_DB` | Database PostgreSQL Feeder |

### A.2 Checklist CMD / PowerShell (ganti perintah Linux)

Buka **CMD** atau **PowerShell sebagai Administrator**:

```cmd
cd /d "C:\NEO FEEDER"
```

**1) Cek port 8100 dan 3003 sedang listen**

```cmd
netstat -ano | findstr ":8100"
netstat -ano | findstr ":3003"
```

Harus ada baris `LISTENING`. Jika kosong → Nginx atau `server-win.exe` belum jalan.

**2) Cek proses Neo Feeder**

```cmd
tasklist | findstr /I "server-win runner-win nginx"
```

**3) Cek service database**

```cmd
sc query NEO_FEEDER_DB
```

Status harus `RUNNING`. Jika `STOPPED`:

```cmd
net start NEO_FEEDER_DB
```

Atau: `Win + R` → `services.msc` → cari **NEO_FEEDER_DB** → Start, Startup type: **Automatic**.

**4) Tes Web Service dari server sendiri**

```cmd
curl -s -o NUL -w "HTTP %%{http_code} waktu %%{time_total}s\n" ^
  -X POST http://127.0.0.1:8100/ws/live2.php ^
  -H "Content-Type: application/json" ^
  -d "{\"act\":\"GetToken\",\"username\":\"USER\",\"password\":\"PASS\"}"
```

(Ganti `USER` / `PASS` dengan akun Feeder WS.)

**5) Buka di browser (di server Feeder)**

- `http://localhost:8100` → UI Neo Feeder harus tampil
- `http://localhost:3003` → indikasi backend Node.js jalan

| Hasil browser | Artinya |
|---------------|---------|
| 8100 gagal, 3003 OK | Nginx tidak jalan — jalankan `runner-win.exe` |
| 8100 OK, 3003 gagal | **server-win.exe** tidak jalan → lihat A.3 |
| Keduanya OK | Lanjut tuning nginx.conf (Bagian 3) |

### A.3 Atasi 502 / Insert lambat (Windows)

**Langkah 1 — Start database dulu, baru aplikasi**

1. `services.msc` → **NEO_FEEDER_DB** → Start  
2. Tunggu 15 detik  
3. Klik kanan shortcut Neo Feeder di desktop → **Run as administrator**  
   Atau manual: klik kanan `C:\NEO FEEDER\app\server-win.exe` → **Run as administrator**  
4. Tunggu 30–60 detik, ulangi tes `curl` / kirim 1 mahasiswa

**Langkah 2 — Auto-start saat Windows boot**

1. `Win + R` → `shell:startup` → Enter  
2. Copy shortcut **Neo Feeder** dari desktop ke folder Startup  
3. `services.msc` → **NEO_FEEDER_DB** → Startup type: **Automatic**

**Langkah 3 — Firewall Windows**

Pastikan port **8100** dan **3003** diizinkan (biasanya otomatis saat install):

```cmd
netsh advfirewall firewall show rule name=all | findstr /I "8100 3003 Neo"
```

Jika perlu buka untuk jaringan kampus (ganti profil sesuai kebutuhan):

```cmd
netsh advfirewall firewall add rule name="Neo Feeder WS 8100" dir=in action=allow protocol=TCP localport=8100
netsh advfirewall firewall add rule name="Neo Feeder WS 3003" dir=in action=allow protocol=TCP localport=3003
```

**Langkah 4 — Performa Windows (opsional, disarankan LLDIKTI)**

1. `Win + R` → `sysdm.cpl` → tab **Advanced** → Performance **Settings**  
2. Tab **Advanced** → Processor scheduling: **Background services**  
3. Pastikan RAM cukup (minimal 4 GB kosong saat sinkron)

**Langkah 5 — Log error**

```cmd
type "C:\NEO FEEDER\nginx\logs\error.log"
```

Cari baris `upstream`, `502`, `timed out` — biasanya backend 3003 tidak merespons.

### A.4 Edit Nginx di Windows

File: **`C:\NEO FEEDER\nginx\conf\nginx.conf`**

Pada blok `location /ws/`, pastikan ada (timeout dalam **detik**):

```nginx
proxy_connect_timeout 60s;
proxy_send_timeout    300s;
proxy_read_timeout    300s;
send_timeout          300s;
client_max_body_size  20M;
proxy_pass http://127.0.0.1:3003;
```

Setelah simpan: tutup Neo Feeder sepenuhnya (Task Manager → akhiri `nginx`, `server-win`, `runner-win`), lalu jalankan ulang **Run as administrator**.

### A.4b XML hang, JSON OK (gejala umum Windows)

Jika **GetToken cepat** tetapi request **XML** (Content-Type `application/xml`) hang/timeout:

- Penyebab: backend `server-win.exe` atau Nginx tidak memproses body XML dengan benar.
- **Siakad-Feeder** memakai **JSON** secara default (`FEEDER_PREFER_JSON=true`) — sama seperti aplikasi SiFeeder lama.
- Opsional perbaikan server: restart `server-win.exe` sebagai Administrator, update Neo Feeder ke versi terbaru dari PDDIKTI.

### A.5 Restart bersih (Windows)

```cmd
net stop NEO_FEEDER_DB
taskkill /F /IM nginx.exe 2>nul
taskkill /F /IM server-win.exe 2>nul
timeout /t 5
net start NEO_FEEDER_DB
timeout /t 15
cd /d "C:\NEO FEEDER"
start "" "C:\NEO FEEDER\runner-win.exe"
```

Tunggu 1 menit sebelum tes kirim data dari Siakad-Feeder.

---

## B. Arsitektur Neo Feeder Docker (Linux) — jika pakai Docker

```
Client → :8100 (Nginx container) → :3003 (backend WS) → PostgreSQL :54333
```

**GetToken** dan **InsertBiodataMahasiswa** melewati jalur yang sama.

---

## C. Checklist Linux / Docker

```bash
cd /path/to/neofeeder
docker compose ps
docker compose logs --tail=100 app-pddikti
ss -tlnp | grep -E '8100|3003|54333'
curl -s -o /dev/null -w "%{http_code} %{time_total}s\n" \
  -X POST http://127.0.0.1:8100/ws/live2.php \
  -H "Content-Type: application/json" \
  -d '{"act":"GetToken","username":"USER","password":"PASS"}'
```

---

## 3. Setting Nginx (`nginx.conf`)

**Windows:** `C:\NEO FEEDER\nginx\conf\nginx.conf`  
**Docker/Linux:** `nginx/nginx.conf` di folder instalasi

File ini di-mount ke container (lihat `docker-compose.yml`). Untuk endpoint `/ws/` yang dipakai Siakad-Feeder:

```nginx
events {
    worker_connections 4096;
    multi_accept on;
}

http {
    keepalive_timeout 65;
    client_max_body_size 20M;   # naikkan dari default 8M jika payload XML besar

    server {
        listen 8100;
        server_name localhost;

        access_log /var/log/nginx/access.log;
        error_log  /var/log/nginx/error.log warn;

        location /ws/ {
            proxy_pass http://127.0.0.1:3003;

            proxy_http_version 1.1;
            proxy_set_header Connection "";
            proxy_set_header Host $http_host;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;

            # Timeout dalam DETIK (bukan milidetik)
            proxy_connect_timeout 60s;
            proxy_send_timeout    300s;
            proxy_read_timeout    300s;
            send_timeout          300s;

            proxy_buffering on;
            proxy_buffer_size 1m;
            proxy_buffers 16 1m;
            proxy_busy_buffers_size 2m;
        }
    }
}
```

**Catatan penting**

- Di Nginx, `proxy_read_timeout 60000` tanpa satuan = **60.000 detik** (~16 jam), bukan 60 detik. Gunakan `300s` atau `600s` agar jelas.
- **502** sering karena `proxy_pass` ke `127.0.0.1:3003` tetapi **backend belum listen** (baru restart / crash).
- Setelah edit: `docker compose exec app-pddikti nginx -t` lalu `docker compose restart app-pddikti`.

---

## 4. Pastikan backend port 3003 selalu jalan

Bug umum instalasi Docker (DIKTI): service WS di port **3003** tidak aktif karena file executable hilang / permission salah.

```bash
docker compose exec app-pddikti sh -c "ss -tlnp | grep 3003"
```

Jika kosong:

```bash
docker compose down
docker compose up -d
docker compose logs -f app-pddikti
```

Tunggu 1–2 menit setelah `up`, ulangi cek port 3003. Jika masih mati, reinstall/update image Neo Feeder dari paket resmi DIKTI atau ikuti panduan [docker-feeder-pddikti](https://github.com/pizaini/docker-feeder-pddikti).

**`docker-compose.yml` — praktik yang disarankan:**

```yaml
services:
  app-pddikti:
    restart: unless-stopped
    depends_on:
      - db-pddikti
    environment:
      TZ: Asia/Jakarta
    # Batasi akses eksternal: hanya IP kampus yang boleh ke 8100
    ports:
      - "8100:8100"

  db-pddikti:
    image: postgres:12
    restart: unless-stopped
```

---

## 5. Setting PostgreSQL (penyebab Insert lambat)

Insert biodata menulis ke DB Feeder lokal. Jika DB lambat atau lock, WS terlihat “hang”.

```bash
docker compose exec db-pddikti psql -U postgres -p 54333 -c "SELECT 1;"
```

**Tuning minimal** (sesuaikan RAM server, contoh server 4 GB):

Di `docker-compose.yml` tambahkan untuk service `db-pddikti`:

```yaml
command: >
  postgres
  -p 54333
  -c shared_buffers=256MB
  -c work_mem=16MB
  -c maintenance_work_mem=128MB
  -c max_connections=100
  -c checkpoint_completion_target=0.9
```

Setelah ubah:

```bash
docker compose down
docker compose up -d
```

**Maintenance berkala**

```bash
# Vacuum DB Feeder (jadwalkan malam hari, saat tidak ada sinkron)
docker compose exec db-pddikti vacuumdb -U postgres -p 54333 --all --analyze
```

---

## 6. Resource server (CPU / RAM / disk)

Neo Feeder + PostgreSQL di satu VPS kecil mudah kehabisan resource → backend restart → **502** / **connection refused**.

| Resource | Minimum disarankan | Tanda kekurangan |
|----------|-------------------|------------------|
| RAM | 4 GB (8 GB lebih aman) | OOM kill container, restart sendiri |
| CPU | 2 vCPU | Load tinggi, request menumpuk |
| Disk | 20 GB+ kosong | PostgreSQL lambat, write gagal |

```bash
free -h
df -h
docker stats --no-stream
dmesg | tail -20   # cek OOM killer
```

Jika RAM penuh: naikkan RAM VPS atau kurangi service lain di server yang sama.

---

## 7. Firewall & jaringan

Pastikan dari server **Siakad-Feeder** (Laragon) ke Feeder:

```bash
# Dijalankan dari PC/server Siakad-Feeder
curl -v --connect-timeout 10 http://103.167.35.204:8100/ws/live2.php
```

Di server Feeder (contoh `ufw`):

```bash
# Hanya izinkan IP kampus (ganti dengan IP publik kampus)
ufw allow from IP_KAMPUS to any port 8100 proto tcp
ufw deny 8100/tcp
```

Hindari expose port **54333** (PostgreSQL) ke internet.

---

## 8. Prosedur restart yang benar

Urutan yang aman setelah maintenance:

```bash
cd /path/to/neofeeder
docker compose down
docker compose up -d db-pddikti
sleep 15
docker compose up -d app-pddikti
sleep 30

# Verifikasi
curl -s -X POST http://127.0.0.1:8100/ws/live2.php \
  -H "Content-Type: application/json" \
  -d '{"act":"GetToken","username":"...","password":"..."}'
```

**Jangan** langsung tes kirim data dari Siakad-Feeder dalam 30 detik pertama setelah restart — tunggu backend 3003 stabil (gejala 502 pertama percobaan lalu OK adalah normal).

---

## 9. Monitoring sederhana (opsional)

Cron setiap 5 menit di server Feeder:

```bash
#!/bin/bash
URL="http://127.0.0.1:8100/ws/live2.php"
CODE=$(curl -s -o /dev/null -w "%{http_code}" --connect-timeout 5 -X POST "$URL" \
  -H "Content-Type: application/json" \
  -d '{"act":"GetToken","username":"USER","password":"PASS"}')
if [ "$CODE" != "200" ]; then
  echo "$(date) Feeder WS unhealthy HTTP $CODE" >> /var/log/feeder-health.log
  cd /path/to/neofeeder && docker compose restart app-pddikti
fi
```

Ganti `USER`/`PASS` dengan akun WS. Restart otomatis hanya bantu jika masalahnya service mati — tidak mengatasi DB corrupt atau bug aplikasi.

---

## 10. Mapping gejala → tindakan

| Gejala | Kemungkinan penyebab | Tindakan server |
|--------|---------------------|-----------------|
| 502 Bad Gateway | Backend 3003 belum jalan | Cek log container, restart `app-pddikti`, tunggu 1–2 menit |
| Connection refused | Container down / firewall | `docker compose ps`, buka port 8100, cek `ss -tlnp` |
| GetToken OK, Insert timeout | DB lambat / backend hang | Tuning PostgreSQL, cek disk & RAM, vacuum DB |
| Kadang OK kadang gagal | Resource ketat / restart otomatis | Naikkan RAM, `restart: unless-stopped`, monitor `docker stats` |
| Error setelah restart | Urutan start DB → app | Start `db-pddikti` dulu, tunggu, baru `app-pddikti` |

---

## 11. Setting di sisi Siakad-Feeder (pelengkap)

Agar tidak menunggu terlalu lama saat server bermasalah:

| Setting | Nilai disarankan | Keterangan |
|---------|------------------|------------|
| Timeout Neo Feeder (baca) | 60–90 detik | Pengaturan Koneksi |
| `FEEDER_WRITE_TIMEOUT` | 45 detik | Timeout khusus Insert/Update (`.env`) |
| `FEEDER_WRITE_RETRY_ATTEMPTS` | 2 | Maksimal retry kirim data |

Ini **tidak** mengganti perbaikan server, tetapi gagal lebih cepat dan pesan error lebih jelas.

---

## 12. Referensi

- [pizaini/docker-feeder-pddikti](https://github.com/pizaini/docker-feeder-pddikti) — struktur Docker & `nginx.conf` resmi komunitas
- [Atasi 502/504 Neo Feeder Docker](https://www.drimtekno.xyz/2022/03/atasi-error-nginx-502-504-pada-neo.html)
- Panduan setup Nginx reverse proxy: [book.najamudinridha.com](https://book.najamudinridha.com/books/setup-neo-feeder-vps-linux-ubuntu/page/08-pointing-sub-domain-dan-setup-nginx-proxy-virtualhost-untuk-neo-feeder-pddikti)

---

**Ringkasan:** Masalah “lambat 9 menit lalu gagal” hampir selalu kombinasi **backend WS (3003) tidak merespons Insert** dan **client menunggu timeout berulang**. Perbaikan utama di server: pastikan container & port 3003 stabil, tuning Nginx `/ws/`, PostgreSQL cukup resource, restart terurut, dan monitor RAM/disk.
