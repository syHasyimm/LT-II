# Panduan Deploy Produksi

Panduan ini untuk satu server Linux (VPS) dengan **Ubuntu 24.04, Nginx, PHP-FPM 8.3+, MySQL 8, Supervisor, dan cron**. Skalanya cukup untuk ratusan gudep (PRD §9).

Contoh berkas konfigurasi ada di folder [`deploy/`](../deploy) dan [`.env.production.example`](../.env.production.example). Ganti `daftar.contoh-kwarcab.or.id` dan `/var/www/perkemahan-lt2` sesuai server Anda.

## 1. Kebutuhan server

| Komponen | Versi / catatan |
| --- | --- |
| PHP | 8.3 atau lebih baru, dengan ekstensi `gd`, `mbstring`, `xml`, `dom`, `curl`, `fileinfo`, `zlib`, `pdo_mysql`, `intl`, `bcmath`. Ekstensi `zip` **tidak** wajib. |
| php.ini | `upload_max_filesize = 8M`, `post_max_size = 12M`, `memory_limit = 512M` (render PDF) |
| MySQL | 8.x, database `utf8mb4`, plus `mysqldump` untuk backup |
| Node.js | 20+ (untuk `npm run build` saat deploy) |
| Lainnya | Composer 2, Git, Supervisor, sertifikat HTTPS (certbot / Let's Encrypt) |

Kapasitas disk: hitung kasar ±3 GB untuk 100 gudep (pas foto + PDF dokumen + ID Card + sertifikat), ditambah ruang backup.

## 2. Instalasi pertama

```bash
# Kode aplikasi
sudo mkdir -p /var/www/perkemahan-lt2 && sudo chown $USER:www-data /var/www/perkemahan-lt2
git clone <url-repo> /var/www/perkemahan-lt2
cd /var/www/perkemahan-lt2

# Database
sudo mysql -e "CREATE DATABASE perkemahan_lt2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'perkemahan_lt2'@'localhost' IDENTIFIED BY 'GANTI-KATA-SANDI-KUAT';
GRANT ALL PRIVILEGES ON perkemahan_lt2.* TO 'perkemahan_lt2'@'localhost';"

# Konfigurasi
cp .env.production.example .env
nano .env                       # isi APP_URL, DB_*, MAIL_*, GOOGLE_*, BACKUP_PATH
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan key:generate
npm ci && npm run build

# Skema, jenis dokumen, dan template bawaan (admin TIDAK dibuat lewat seeder di produksi)
php artisan migrate --force
php artisan db:seed --force

# Akun admin panitia (kata sandi diketik, tidak tersimpan di .env)
php artisan app:create-admin

# Izin folder
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwX storage bootstrap/cache

php artisan optimize
```

## 3. Web server, queue, dan jadwal

1. **Nginx**: salin [`deploy/nginx.conf`](../deploy/nginx.conf) ke `/etc/nginx/sites-available/perkemahan-lt2`, sesuaikan `server_name` dan socket PHP-FPM, aktifkan, lalu `sudo nginx -t && sudo systemctl reload nginx`. Pasang HTTPS dengan `sudo certbot --nginx -d daftar.contoh-kwarcab.or.id`.
2. **Queue worker** (email notifikasi + generate ID Card/Sertifikat): salin [`deploy/supervisor-queue.conf`](../deploy/supervisor-queue.conf) ke `/etc/supervisor/conf.d/`, lalu `sudo supervisorctl reread && sudo supervisorctl update`.
3. **Scheduler** (backup dan pembersihan): `sudo crontab -u www-data deploy/crontab`. Cek dengan `php artisan schedule:list`.

## 4. Login Google

Di Google Cloud Console → APIs & Services → Credentials, buat **OAuth Client ID** jenis *Web application*:

- Authorized JavaScript origins: `https://daftar.contoh-kwarcab.or.id`
- Authorized redirect URIs: `https://daftar.contoh-kwarcab.or.id/auth/google/callback`

Isi `GOOGLE_CLIENT_ID` dan `GOOGLE_CLIENT_SECRET` di `.env`, lalu `php artisan optimize`. Atur layar persetujuan OAuth ke **In production** agar semua akun Google bisa masuk, bukan hanya akun uji.

## 5. Backup dan pemulihan

Jadwal otomatis (lihat `routes/console.php`):

| Kapan | Isi | Disimpan |
| --- | --- | --- |
| Setiap malam 01.30 WIB | Dump database (`lt2-db-*.zip`) | 14 terakhir (`BACKUP_KEEP_DAILY`) |
| Minggu 02.30 WIB | Database + seluruh `storage/app/private` (`lt2-penuh-*.zip`) | 4 terakhir (`BACKUP_KEEP_FULL`) |

Backup manual kapan saja: `php artisan app:backup` (penuh) atau `php artisan app:backup --db-only`.

**Salin backup ke luar server** (wajib, karena disk server bisa rusak), misalnya dengan `rclone` ke Google Drive panitia setiap pagi:

```bash
# crontab pengguna yang punya akses rclone
0 5 * * * rclone copy /var/backups/perkemahan-lt2 gdrive:backup-perkemahan-lt2 --max-age 48h
```

**Pemulihan:**

```bash
php artisan down
unzip lt2-penuh-YYYYMMDD-HHMMSS.zip -d /tmp/pulih
mysql -u perkemahan_lt2 -p perkemahan_lt2 < /tmp/pulih/database.sql
rsync -a /tmp/pulih/private/ storage/app/private/
sudo chown -R www-data:www-data storage
php artisan optimize && php artisan up
```

Untuk pemulihan dari backup harian, lewati langkah `rsync` (dokumen diambil dari backup penuh terakhir). Uji pemulihan minimal sekali sebelum pendaftaran dibuka.

## 6. Memperbarui aplikasi

```bash
cd /var/www/perkemahan-lt2
bash deploy/deploy.sh
```

Skrip ini memasang mode pemeliharaan, menarik kode, memasang dependensi tanpa paket dev, build aset, migrasi, cache, lalu me-restart queue worker. Jalankan `php artisan app:backup --db-only` dulu bila pembaruan berisi migrasi.

## 7. Daftar periksa sebelum dibuka

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` memakai `https://`
- [ ] HTTPS aktif, `SESSION_SECURE_COOKIE=true`
- [ ] Login Google berhasil dengan akun Gmail biasa (bukan akun pengembang)
- [ ] Email notifikasi terkirim (kirim pendaftaran uji → email masuk)
- [ ] Admin sudah mengaktifkan **2FA** di Akun → Keamanan
- [ ] **Jadwal Pendaftaran** sudah diatur (buka/tutup, WIB)
- [ ] Template ID Card & Sertifikat dicek di editor: nama penandatangan, kota, dan tanggal di sertifikat sudah benar, **Pratinjau PDF** sesuai
- [ ] `php artisan schedule:list` menampilkan backup; `php artisan app:backup` berhasil; backup tersalin ke luar server
- [ ] `supervisorctl status` menunjukkan queue worker *RUNNING*
- [ ] `/up` mengembalikan 200 (bisa dipakai layanan pemantau uptime)
- [ ] Data uji UAT dihapus atau database dikosongkan sebelum dibuka untuk umum

## 8. Pemantauan dan masalah umum

| Gejala | Periksa |
| --- | --- |
| Email tidak terkirim, generate tidak jalan | `sudo supervisorctl status`, `storage/logs/queue.log`, tabel `failed_jobs` (`php artisan queue:failed`, `queue:retry all`) |
| Login Google gagal "redirect_uri_mismatch" | URI di Google Console harus persis sama dengan `GOOGLE_REDIRECT_URI` |
| Unggah gagal "413" | `client_max_body_size` Nginx dan `upload_max_filesize` PHP |
| PDF kosong / font salah | Folder `resources/fonts` ikut ter-deploy, `memory_limit` cukup |
| Halaman 500 | `storage/logs/laravel-*.log`; jangan nyalakan `APP_DEBUG` di produksi |
