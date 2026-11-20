# Pendaftaran Perkemahan LT 2

Aplikasi web pendaftaran gugus depan (gudep) untuk Perkemahan Lomba Tingkat 2.
Pembina Pendamping login dengan Google, mengisi data gudep, **8 peserta Putra**, **8 peserta Putri**, dan pembina pendamping (tanpa batas jumlah), lalu mengunggah dokumen. Admin panitia memverifikasi pendaftaran.

- Kebutuhan lengkap: [`PRD-Pendaftaran-Perkemahan-LT2.md`](PRD-Pendaftaran-Perkemahan-LT2.md)
- Daftar task dan progres: [`docs/TASKS.md`](docs/TASKS.md)
- Referensi desain FigCraft (merah & emas): [`docs/design/`](docs/design/)
- Deploy produksi, backup, dan pemulihan: [`docs/DEPLOY.md`](docs/DEPLOY.md)
- Panduan pengguna: [pembina](docs/PANDUAN-PEMBINA.md) · [admin](docs/PANDUAN-ADMIN.md)
- Rencana uji coba bersama panitia: [`docs/UAT.md`](docs/UAT.md)

## Stack

Laravel 13 · React 19 + Inertia 3 · Tailwind CSS 4 + shadcn/ui · MySQL 8 · Fortify (login admin) · Socialite (login Google) · Pest

## Status

**Fase 0–7 selesai** (MVP, template, ID Card & Sertifikat, serta penyempurnaan). Fitur yang sudah bisa dipakai:

| Area    | Fitur                                                                                                                                                                                                                                                               |
| ------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Publik  | Beranda dengan jadwal, hitung mundur, statistik, dan cek nomor seri; halaman verifikasi `/verify/{serial}`                                                                                                                                                          |
| Pembina | Login Google, data gudep, peserta Pa/Pi dengan kuota 8/8, impor peserta dari Excel/CSV, repeater pembina, pas foto, matriks dokumen, unggah ulang dokumen ditolak, pratinjau dokumen, kirim pendaftaran dengan daftar periksa                                       |
| Admin   | Login email + kata sandi (2FA opsional dengan pengingat), dashboard, daftar gudep, ekspor Excel, panel verifikasi dokumen, verifikasi atau minta revisi, perpanjangan per gudep, jadwal buka/tutup, editor template visual, generate dan unduh ID Card & Sertifikat |
| Sistem  | Data terkunci setelah dikirim, read-only di luar jadwal, dokumen di disk privat, audit log, email notifikasi lewat queue, nomor seri `LT2-2026-ID/CERT-0001` yang tetap saat generate ulang                                                                         |

Fase 8 sudah menyiapkan backup terjadwal, kit deploy, panduan pengguna, dan rencana UAT. Yang tersisa adalah UAT bersama panitia dan deploy ke server (lihat `docs/TASKS.md`).

## Instalasi lokal (Laragon / Windows)

Kebutuhan: PHP 8.3+ dengan ekstensi `gd`, Composer, Node 20+, MySQL 8.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Buat database `perkemahan_lt2` (utf8mb4), lalu sesuaikan `.env`:

```dotenv
DB_DATABASE=perkemahan_lt2
DB_USERNAME=root
DB_PASSWORD=
```

Jalankan migrasi dan seeder:

```bash
php artisan migrate --seed
```

Seeder membuat akun admin dari `ADMIN_EMAIL` / `ADMIN_PASSWORD` di `.env`. Di lingkungan `local`, kata sandi bawaannya `password` bila `ADMIN_PASSWORD` kosong. Seeder juga membuat jadwal pendaftaran contoh (khusus lokal).

Data contoh opsional (6 gudep dengan berbagai status, lengkap dengan PDF dan pas foto contoh):

```bash
php artisan db:seed --class=DemoDataSeeder
```

### Menjalankan

```bash
composer dev
```

Perintah ini menjalankan server Laravel, Vite, queue worker, dan log sekaligus. Bisa juga dijalankan terpisah:

```bash
php artisan serve
npm run dev
php artisan queue:work
```

Buka <http://localhost:8000>.

- **Pembina:** `/login`. Di lingkungan `local` tersedia tombol **Masuk sebagai pembina demo** agar bisa dicoba tanpa kredensial Google.
- **Admin:** `/admin/login`.

## Login Google untuk Pembina

1. Buat OAuth Client ID (jenis _Web application_) di Google Cloud Console.
2. Tambahkan _Authorized redirect URI_: `https://domain-anda/auth/google/callback` (lokal: `http://localhost:8000/auth/google/callback`).
3. Isi `.env`:

```dotenv
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

Akun pembina dibuat otomatis saat login pertama. Email yang terdaftar sebagai admin tidak bisa login lewat Google.

## Akun admin

Untuk produksi, buat admin lewat perintah artisan agar kata sandi tidak tersimpan di `.env`:

```bash
php artisan app:create-admin
```

Registrasi akun publik dimatikan. Admin bisa mengaktifkan autentikasi dua langkah (aplikasi autentikator + kode cadangan) di menu **Akun → Keamanan**. Selama belum aktif, dashboard admin menampilkan pengingat.

## Konfigurasi aturan

`config/camp.php` berisi kuota (8 per regu), minimal pembina, batas unggah (dokumen 10 MB, pas foto 2 MB), format file, tahun acara, dan prefiks nomor seri.
Jadwal buka/tutup **tidak** ada di kode: admin mengaturnya di **Jadwal Pendaftaran** (tersimpan di tabel `settings`, zona waktu Asia/Jakarta).

Jenis dokumen wajib ada di tabel `document_types`. Semuanya diunggah **satu berkas per gudep**:

| Kode               | Dokumen                | Isi berkas                                         |
| ------------------ | ---------------------- | -------------------------------------------------- |
| `kta`              | Kartu Tanda Anggota    | KTA semua peserta dan pembina, digabung 1 PDF      |
| `izin_ortu`        | Surat Izin Orang Tua   | izin setiap peserta, digabung 1 PDF                |
| `kesehatan`        | Surat Keterangan Sehat | surat sehat semua peserta dan pembina, digabung 1 PDF |
| `kesiapan_sekolah` | Surat Kesiapan Sekolah | satu surat dari sekolah/instansi                   |

Menambah anggota setelah berkas gabungan disetujui mengembalikan berkas itu ke status *menunggu* agar diperiksa ulang.

## Template, ID Card, dan Sertifikat

- **Template bawaan** (dipasang `TemplateSeeder`): ID Card Peserta, ID Card Pembina (55 × 85 mm), dan Sertifikat (A4 lanskap). Gambar latarnya diekspor dari desain FigCraft (`resources/templates/`).
- **Editor template** di **Admin → Template**: unggah gambar latar, aktifkan atau nonaktifkan field, geser dan ubah ukuran di kanvas berskala mm, atur font/ukuran/warna, lalu **Pratinjau PDF**. Placeholder yang tersedia: `{nama}`, `{peran}`, `{peran_lengkap}`, `{gudep}`, `{sekolah}`, `{npsn}`, `{penanggung_jawab}`, `{alamat}`, `{kwartir}` (alias lama untuk `{alamat}`), `{nomor_seri}`, `{tahun}`.
- **Generate** di **Admin → ID Card / Sertifikat**: pilih satu orang, satu gudep, atau semua gudep terverifikasi. Proses berjalan di queue dengan indikator progres, jadi **queue worker harus berjalan**.
- **Unduh**: PDF satuan, PDF per gudep (satu kartu per halaman, atau lembar A4 berisi 9 kartu dengan tanda potong untuk ID Card), serta ZIP per gudep atau seluruhnya.
- **Nomor seri** diberikan saat pertama kali dibuat dan tidak berubah saat generate ulang; file lama ditimpa. QR pada kartu/sertifikat mengarah ke `/verify/{nomor-seri}`.
- PDF dibuat dengan dompdf memakai font TTF di `resources/fonts` (Playfair Display, Plus Jakarta Sans, lisensi OFL). ZIP dibuat dengan `zipstream-php`, sehingga ekstensi PHP `zip` tidak diperlukan.

## Ekspor dan impor Excel

- **Ekspor** (Admin → Gudep & Verifikasi → **Ekspor Excel**): file `.xlsx` berisi sheet _Gudep_ dan _Anggota_ (nama, gudep, peran, status tiap dokumen, nomor seri). Ekspor mengikuti filter status dan pencarian yang sedang aktif, dan tercatat di audit log.
- **Impor peserta** (Pembina → Anggota → **Impor banyak peserta dari Excel**): unduh template, isi kolom Regu, Nama lengkap, Tanggal lahir, dan Nomor KTA, lalu unggah `.xlsx` atau `.csv`. Baris yang tidak valid, ganda, atau melebihi kuota 8 per regu dilewati dan dilaporkan per nomor baris. Pas foto tetap diunggah satu per satu.
- Keduanya memakai `shuchkin/simplexlsx(gen)`, jadi tidak butuh ekstensi PHP `zip`.

## Email & queue

Email notifikasi perubahan status dan generate ID Card / Sertifikat berjalan lewat queue `database`. Jalankan `php artisan queue:work` (sudah termasuk di `composer dev`). Di lokal, `MAIL_MAILER=log` menulis email ke `storage/logs/laravel.log`.

## Pengujian

```bash
composer test          # Pint + Larastan + Pest
npm run check          # format & lint frontend
npm run types:check    # TypeScript
```

Tes Feature mencakup alur end-to-end dari login pembina sampai gudep terverifikasi, sertifikat dibuat, dan QR-nya terverifikasi di halaman publik (`tests/Feature/Domain/RegistrationFlowTest.php`).

## Keamanan dan privasi

- Pas foto dan dokumen disimpan di `storage/app/private` dengan nama acak, dan hanya disajikan lewat controller yang memeriksa otorisasi (pembina pemilik gudep atau admin).
- Tipe file diperiksa dari isi file, bukan hanya ekstensi.
- Pembina hanya bisa melihat dan mengubah gudepnya sendiri. Pembina yang membuka `/admin/*` mendapat 403.
- Perubahan status, peninjauan dokumen, jadwal, dan perpanjangan dicatat di `activity_logs`.
- Header keamanan (`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, HSTS di produksi) di semua respons.
- Backup otomatis lewat `php artisan app:backup`: database setiap malam, database + `storage/app/private` setiap Minggu. Salin folder backup ke luar server (lihat `docs/DEPLOY.md`). Data tidak dihapus otomatis (BR-13).
