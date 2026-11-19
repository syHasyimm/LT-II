# Daftar Task — Aplikasi Pendaftaran Perkemahan LT 2

Sumber kebutuhan: [`PRD-Pendaftaran-Perkemahan-LT2.md`](../PRD-Pendaftaran-Perkemahan-LT2.md)
Sumber desain: FigCraft `Perkemahan-LT2-Prototype.figcraft` (referensi PNG di [`docs/design/`](design/))

**Aturan kerja**
- Satu task = satu commit, pesan commit diawali kode task, misalnya `T2.3: ...`.
- Task dicentang setelah tes, lint, dan build hijau.
- Fase 0–7 selesai. Fase 8: bahan UAT dan kit rilis siap; UAT bersama panitia dan deploy ke server menunggu panitia/server.

Legenda: `[ ]` belum · `[x]` selesai

---

## Fase 0 · Fondasi

- [x] **T0.1 Pasang starter kit & repo**
  Laravel 13 + React (Inertia) + Tailwind 4 + shadcn/ui dari starter kit resmi, `git init`, `.env` dengan MySQL `perkemahan_lt2` (Laragon).
  *Selesai jika:* `php artisan migrate` dan `npm run build` berhasil.
- [x] **T0.2 Konfigurasi & enum domain**
  `config/camp.php` (PRD §7), enum `UserRole`, `MemberRole`, `TeamGroup`, `GudepStatus`, `DocumentStatus`, `TemplateType`, zona waktu `Asia/Jakarta`, locale `id`.
- [x] **T0.3 Referensi desain**
  Ekspor setiap frame FigCraft ke `docs/design/*.png`.
- [x] **T0.4 Design system di kode**
  Token warna merah-emas dan font (Playfair Display + Plus Jakarta Sans) di Tailwind `@theme`, tema shadcn, komponen dasar (Button primary/gold/outline, StatusBadge, QuotaIndicator), layout `public`, `pembina` (mobile-first + bottom nav), `admin` (sidebar).
- [x] **T0.5 Tooling kualitas**
  Pint, Larastan, Pest. Script `composer test` (Pint + Larastan + Pest) dan `npm run check` + `npm run types:check` berjalan hijau.

## Fase 1 · Auth & role

- [x] **T1.1 Login Google untuk Pembina** — FR-AUTH-01
  Kolom `google_id`, `avatar`, `role` di `users`. Socialite Google. Login pertama membuat user `role=pembina`. Dev-login `/dev/login-pembina` hanya untuk `APP_ENV=local`.
  *Tes:* Socialite di-mock, login kedua masuk ke akun yang sama.
- [x] **T1.2 Login Admin terpisah** — FR-AUTH-02
  `/admin/login` (email + password lewat Fortify), registrasi publik dimatikan, command `app:create-admin`, seeder admin. Akun Google tidak bisa menjadi admin.
- [x] **T1.3 Pembatasan peran** — FR-AUTH-03
  Middleware `EnsureRole`. Pembina ke `/admin/*` mendapat 403, admin ke area pembina dialihkan ke dashboard admin.
- [x] **T1.4 Beranda publik & halaman masuk** — desain P1/P2, M1
  `welcome.tsx` dengan jadwal, hitung mundur, dan statistik dari data asli. Halaman masuk Pembina sesuai M1.
  *Catatan:* tabel `settings`, model `Setting`, dan layanan `RegistrationWindow` dimajukan dari T4.1 karena dibutuhkan beranda. Statistik gudep/peserta disambungkan pada T2.1. Bagian "Verifikasi sertifikat" di beranda ditambahkan pada T6.5 (selesai).

## Fase 2 · Gudep & anggota

- [x] **T2.1 Model gudep & anggota** — BR-01, FR-AUTH-04
  Migrasi `gudeps`, `members`, model, factory, `GudepPolicy`.
  *Tes:* akses lintas gudep ditolak.
- [x] **T2.2 Form data gudep** — FR-GDP-01
  Nama, nomor gudep, asal sekolah/lembaga, kwartir/wilayah. Tersimpan sebagai draft.
- [x] **T2.3 Peserta Putra & Putri** — FR-GDP-02, BR-02 (desain M3, M3b)
  Tab Pa/Pi, indikator `x/8`, form peserta dalam sheet.
  *Tes:* peserta ke-9 per regu ditolak.
- [x] **T2.4 Repeater pembina pendamping** — FR-GDP-03, BR-03 (desain M3c)
  Tambah tanpa batas, hapus per baris, pemilik akun otomatis menjadi pembina pertama dan tidak bisa dihapus.
- [x] **T2.5 Data anggota & pas foto** — FR-GDP-04, BR-06
  Nama, jenis kelamin, tanggal lahir, KTA, pas foto (disk privat). Pembina juga punya nomor HP dan keterangan mendampingi Pa/Pi/keduanya.
- [x] **T2.6 Penguncian data** — FR-GDP-05, BR-08
  Form read-only saat status `submitted`/`verified`, bisa diedit saat `draft`/`revision`.
- [x] **T2.7 Beranda Pembina** — desain M2
  Banner jadwal, kartu status, progres langkah 1–5 yang terhubung ke tiap halaman.

## Fase 3 · Dokumen

- [x] **T3.1 Unggah dokumen privat** — FR-DOC-01, FR-DOC-04, BR-04, BR-05
  `document_types` + seeder (KTA, Izin Orang Tua hanya untuk peserta, Kesehatan, Kesiapan Sekolah per gudep), tabel `documents`, `PrivateFileService` (cek MIME asli, maksimal 2 MB, nama file acak, disajikan lewat controller berotorisasi).
- [x] **T3.2 Matriks kelengkapan** — FR-DOC-02 (desain M4)
  Tabel anggota × dokumen dengan status belum / diunggah / disetujui / ditolak.
- [x] **T3.3 Unggah ulang dokumen ditolak** — FR-DOC-03
  File lama diarsipkan, status kembali `pending`.
- [x] **T3.4 Pratinjau dokumen** — FR-DOC-05
  PDF dan gambar tampil di dialog tanpa diunduh.

## Fase 4 · Kirim & verifikasi (MVP)

- [x] **T4.1 Jadwal pendaftaran** — FR-PRD-01, FR-PRD-02, BR-12
  Tabel `settings`, halaman admin jadwal buka/tutup, middleware `EnsureRegistrationOpen`, read-only di luar jadwal, pesan "pendaftaran belum dibuka".
- [x] **T4.2 Banner, perpanjangan, tenggat revisi** — FR-PRD-03, FR-PRD-04, FR-PRD-05
- [x] **T4.3 Kirim pendaftaran** — FR-VER-01, BR-07 (desain M5, M6)
  Cek kelengkapan, tampilkan daftar kekurangan, status `submitted`, data terkunci.
- [x] **T4.4 Dashboard & daftar gudep admin** — FR-VER-02, FR-DSH-01 (desain D1)
  Ringkasan per status, filter, pencarian, paginasi.
- [x] **T4.5 Panel verifikasi** — FR-VER-03, FR-VER-04 (desain D2)
  Setuju/tolak per dokumen dengan catatan, status `verified` hanya jika semua dokumen disetujui, tolak pendaftaran membuat status `revision` beserta catatan.
- [x] **T4.6 Audit log & notifikasi email** — FR-VER-05, FR-VER-06
  `activity_logs`, email perubahan status lewat queue `database`.
- [x] **T4.7 Uji alur MVP & README**
  Uji end-to-end dari pembina daftar sampai admin verifikasi. README berisi cara instalasi, konfigurasi Google OAuth, dan membuat admin.

---

## Fase 5 · Template ID Card & Sertifikat

- [x] T5.1 Model `templates` (jenis, sasaran, ukuran mm, field JSON, aktif) — FR-TPL-01..03
- [x] T5.2 Unggah background dan daftar field — FR-TPL-05
- [x] T5.3 Editor visual dan pratinjau ukuran asli 55 × 85 mm (desain D3) — FR-TPL-04

## Fase 6 · Generate ID Card & Sertifikat

- [x] T6.1 `TemplateRenderer` ke PDF (dompdf) dan `QrCodeService`
- [x] T6.2 Nomor seri `LT2-2026-ID/CERT-0001`, aman dari duplikasi, dipertahankan saat generate ulang — BR-14, FR-GEN-03
- [x] T6.3 Queue job dan indikator progres; pilih penerima orang/gudep/semua — FR-GEN-01, FR-GEN-02
- [x] T6.4 Unduh PDF satuan dan ZIP; mode cetak 1 kartu atau A4 3 × 3 — FR-GEN-04, FR-GEN-06
- [x] T6.5 Halaman verifikasi publik `/verify/{serial}` (desain V1–V3) — FR-GEN-05

## Fase 7 · Penyempurnaan

- [x] **T7.1 Ekspor Excel daftar gudep dan anggota** — FR-DSH-02
  - Tombol **Ekspor Excel** di Admin → Gudep, mengikuti filter status dan pencarian yang aktif.
  - Workbook berisi sheet *Gudep* (status, jumlah anggota, rekap dokumen, tanggal kirim/verifikasi) dan *Anggota* (nama, peran, regu, gudep, status tiap dokumen, nomor seri ID Card/Sertifikat).
  - Dibuat dengan `shuchkin/simplexlsxgen` (tanpa ekstensi `zip`). Isi dari pengguna ditulis sebagai teks, jadi tidak bisa menjadi rumus. Setiap ekspor tercatat di audit log.
- [x] **T7.2 Import peserta dari Excel/CSV** — FR-GDP-06
  - Tombol **Impor banyak peserta dari Excel** di halaman Anggota. Dialog berisi unduh template `.xlsx` (sheet Peserta + Petunjuk), pilih file `.xlsx`/`.csv`, lalu laporan hasil.
  - Kolom dikenali dari judulnya, urutannya bebas. Tanggal diterima dari sel tanggal Excel, `15/03/2013`, `15-03-2013`, atau `2013-03-15`. CSV dengan titik koma atau koma, termasuk BOM dari Excel.
  - Validasinya sama dengan form anggota. Baris yang tidak valid, ganda (nama + tanggal lahir sama), atau melebihi kuota 8/8 dilewati dan dilaporkan per nomor baris.
  - Hanya bisa saat data boleh diubah dan jadwal buka. File tidak disimpan di server.
- [x] **T7.3 2FA admin** — FR-AUTH-05
  - 2FA TOTP + kode cadangan dari Fortify, diaktifkan admin di **Akun → Keamanan** (konfirmasi kata sandi dulu). Login admin lalu meminta kode sebelum masuk dashboard.
  - Halaman Akun (profil, kata sandi, 2FA, passkey) dan dialog setup 2FA memakai tema merah-emas panel admin.
  - Dashboard menampilkan pengingat **Aktifkan 2FA** selama admin belum mengaktifkannya (2FA tetap opsional sesuai PRD).
  - Tes: login → tantangan 2FA → dashboard, kode cadangan sekali pakai, kode salah ditolak.
- [x] **T7.4 Perapian UI mobile dan aksesibilitas**
  - Audit semua halaman publik, pembina (375 px), dan admin: label form, nama tombol, teks alt, kontras teks, ukuran area sentuh, dan geser horizontal.
  - Teks emas kecil digelapkan (`gold-700` / `gold-800`) agar kontras ≥ 4.5:1 (WCAG AA).
  - Tautan **Lewati ke konten utama**, landmark `main` di semua layout, cincin fokus keyboard emas, dan dukungan `prefers-reduced-motion`.
  - Label untuk pencarian gudep, pilihan penerima generate, kontrol editor template, dan thumbnail template. Satu `h1` per halaman.
  - Notifikasi di ponsel tidak lagi menutupi navigasi bawah pembina. Tautan aksi di halaman Kirim punya area sentuh 44 px.
  - Tidak ada geser horizontal di 375 px pada halaman yang diperiksa.

## Fase 8 · Pengujian & rilis

- [ ] **T8.1 UAT bersama panitia (3–5 gudep nyata dari ponsel)**: *bahan siap, pelaksanaan oleh panitia*
  - [x] Rencana UAT [`docs/UAT.md`](UAT.md): 10 skenario pembina, 12 skenario admin, hal yang diamati, formulir temuan, kriteria lolos, cara mengosongkan data uji.
  - [x] Tes otomatis alur utama: login Google sampai sertifikat terverifikasi publik, ditambah impor, ekspor, 2FA, dan backup.
  - [ ] Pelaksanaan UAT dan perbaikan temuan.
- [ ] **T8.2 Deploy produksi, backup terjadwal, panduan pengguna**: *kit siap, deploy menunggu server*
  - [x] Backup terjadwal `php artisan app:backup` (harian: database; mingguan: database + dokumen privat), retensi bisa diatur, tanpa ekstensi `zip`. Dicoba dengan `mysqldump` sungguhan.
  - [x] Header keamanan HTTP, dukungan reverse proxy (`TRUSTED_PROXIES`), health check `/up`, login demo hanya di lokal (dites).
  - [x] Kit deploy: [`docs/DEPLOY.md`](DEPLOY.md), `.env.production.example`, `deploy/nginx.conf`, `deploy/supervisor-queue.conf`, `deploy/crontab`, `deploy/deploy.sh`.
  - [x] Panduan pengguna: [`PANDUAN-PEMBINA.md`](PANDUAN-PEMBINA.md) dan [`PANDUAN-ADMIN.md`](PANDUAN-ADMIN.md).
  - [ ] Deploy ke server produksi, isi kredensial Google OAuth dan SMTP, uji pemulihan backup.

## Fase 9 · Penyempurnaan pascarilis

- [x] **T9.1 Alur pembina per langkah + timeline vertikal**: spec [`superpowers/specs/2026-11-19-alur-pembina-timeline-design.md`](superpowers/specs/2026-11-19-alur-pembina-timeline-design.md)
  - Area pembina menjadi 6 langkah: Data gudep → Peserta Putra → Peserta Putri → Pembina → Foto & dokumen → Kirim.
  - Timeline vertikal berikon di kanan konten (layar lebar) dan strip langkah di atas konten (ponsel). Menu atas dan menu bawah dihapus.
  - Status setiap langkah: selesai, sedang dibuka, atau "lanjutkan di sini". Semua langkah tetap bisa dibuka kapan saja.
  - Footer **Kembali / Lanjut** di halaman langkah. Tab di halaman Anggota diganti langkah Putra/Putri/Pembina.
  - Server membagikan `pembinaSteps` dan `pembinaStep` (khusus pembina). Ada tes untuk setiap halaman, edit anggota, pembina tanpa gudep, dan admin.

- [x] **T9.2 Bilah langkah ponsel ringkas**: spec [`superpowers/specs/2026-11-19-strip-ponsel-ringkas-design.md`](superpowers/specs/2026-11-19-strip-ponsel-ringkas-design.md)
  - Strip ikon di ponsel diganti bilah "Langkah n/6 · Judul" dengan 6 garis progres (emas = selesai, putih = sedang dibuka); di Beranda: "Ringkasan · x dari 6 selesai".
  - Tombol **Semua langkah** membuka panel bawah berisi timeline lengkap (sama dengan desktop) dan tombol Keluar; panel tertutup setelah memilih langkah.
  - Timeline desktop tidak berubah (daftar dipakai bersama lewat `TimelineList`).
