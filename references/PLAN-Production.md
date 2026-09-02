# PLAN PRODUCTION - AturAja.com

## Ringkasan

| Item | Value |
|------|-------|
| **Stack** | Laragon + Nginx + PHP 8.2 + MySQL 8 |
| **Domain** | `aturaja.local` |
| **URL** | `http://aturaja.local` |
| **Akses** | Lokal saja (localhost) |

---

## Phase 1: Pre-Deployment Preparation

| # | Task | Detail |
|---|------|--------|
| 1.1 | Backup database | Export `aturaja` database via phpMyAdmin atau CLI |
| 1.2 | Backup `.env` | Simpan copy `.env` ke `.env.backup` |
| 1.3 | Pastikan data penting tersimpan | Cek tidak ada uncommitted changes di git |

---

## Phase 2: Konfigurasi `.env` Production

Edit file `.env` dengan settings berikut:

```ini
APP_NAME=AturAja
APP_ENV=production
APP_KEY=<sama, jangan ubah>
APP_DEBUG=false
APP_URL=http://aturaja.local

APP_LOCALE=id
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=id_ID

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3308
DB_DATABASE=aturaja
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=log
```

### Perubahan dari Development

| Setting | Development | Production |
|---------|-------------|------------|
| `APP_ENV` | `local` | `production` |
| `APP_DEBUG` | `true` | `false` |
| `APP_URL` | `http://localhost` | `http://aturaja.local` |
| `LOG_LEVEL` | `debug` | `warning` |

---

## Phase 3: Build Frontend Production

```bash
npm run build
```

File CSS/JS akan di-minify di `public/build/`.

---

## Phase 4: Laravel Optimization

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## Phase 5: Konfigurasi Nginx di Laragon

### Install Nginx

1. Buka Laragon → Menu → Nginx → Install
2. Pilih versi terbaru

### Buat File Konfigurasi

Lokasi: `C:\laragon\etc\nginx\aturaja.conf`

```nginx
server {
    listen 80;
    server_name aturaja.local;

    root D:/Workspaces/GitHub/aturaja/aturaja.com/public;
    index index.php;

    charset utf-8;
    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## Phase 6: Konfigurasi Hosts Windows

Edit `C:\Windows\System32\drivers\etc\hosts` (Run as Administrator):

```
127.0.0.1    aturaja.local
```

---

## Phase 7: Database Migration

```bash
php artisan migrate --force
```

---

## Phase 8: Queue Worker Setup

### Opsi 1: Script Batch Manual

Buat file `start-worker.bat`:

```batch
@echo off
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

### Opsi 2: Windows Task Scheduler

Buat scheduled task untuk auto-start queue worker.

---

## Phase 9: Scheduler Setup

### Buat File `scheduler.bat`

```batch
@echo off
php artisan schedule:run
```

### Konfigurasi Windows Task Scheduler

- **Trigger:** Setiap menit
- **Action:** Jalankan `scheduler.bat`

---

## Phase 10: Permissions Check

Pastikan folder `storage/` dan `bootstrap/cache/` bisa ditulis:

```bash
icacls "D:\Workspaces\GitHub\aturaja\aturaja.com\storage" /grant Everyone:F /T
icacls "D:\Workspaces\GitHub\aturaja\aturaja.com\bootstrap\cache" /grant Everyone:F /T
```

---

## Phase 11: Restart & Test

1. **Restart Laragon** (atau reload Nginx)
2. Buka browser → `http://aturaja.local`
3. Test login, transaksi, export, dll.

---

## Checklist Akhir

| # | Task | Status |
|---|------|--------|
| 1 | `.env` production sudah benar | ☐ |
| 2 | `npm run build` berhasil | ☐ |
| 3 | `php artisan config:cache` berhasil | ☐ |
| 4 | Nginx config sudah benar | ☐ |
| 5 | Hosts file sudah diupdate | ☐ |
| 6 | Database migrated | ☐ |
| 7 | Queue worker berjalan | ☐ |
| 8 | Scheduler berjalan | ☐ |
| 9 | Akses `http://aturaja.local` berhasil | ☐ |
| 10 | Semua fitur berfungsi | ☐ |

---

## Backup Strategy

### Script Backup Database

Buat file `backup.bat`:

```batch
@echo off
set TIMESTAMP=%date:~-4%%date:~-7,2%%date:~-10,2%
mysqldump -u root aturaja > D:\backups\aturaja_%TIMESTAMP%.sql
```

---

## Rollback Plan

Jika ada masalah setelah deploy:

1. Matikan Nginx di Laragon
2. Kembalikan `.env` ke versi development
3. Jalankan `php artisan config:clear`
4. Restart Laragon
