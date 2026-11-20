# Rencana UAT (User Acceptance Test)

Uji coba bersama panitia dan **3–5 gudep nyata** sebelum pendaftaran dibuka (PRD Fase 8). Tujuannya memastikan alur bisa dipakai pembina dari ponsel tanpa pendampingan, dan panitia bisa memverifikasi serta mencetak hasilnya.

## Persiapan

| Hal | Keterangan |
| --- | --- |
| Lingkungan | Server staging dengan HTTPS dan login Google aktif (bisa server produksi sebelum dibuka, lalu dikosongkan) |
| Peserta uji | 1–2 admin panitia, 3–5 pembina dari gudep berbeda (campuran ponsel Android/iPhone, minimal satu laptop) |
| Data | Data asli atau data contoh yang realistis: 16 peserta, 1–2 pembina, dokumen hasil foto ponsel |
| Jadwal | Atur **Jadwal Pendaftaran** terbuka selama UAT |
| Waktu | ±60 menit untuk pembina, ±45 menit untuk admin |
| Bahan | `docs/PANDUAN-PEMBINA.md` dan `docs/PANDUAN-ADMIN.md` (bagikan **setelah** skenario P1–P3 dicoba tanpa panduan, untuk menguji kejelasan tampilan) |

Setelah UAT, kosongkan data uji. Perintah penghapus database **diblokir** saat `APP_ENV=production`, jadi lakukan UAT dengan `APP_ENV=staging`, kosongkan data, lalu ubah ke `production` sebelum dibuka:

```bash
php artisan app:backup                      # simpan dulu hasil UAT bila perlu
php artisan migrate:fresh --seed --force    # database kosong + jenis dokumen + template bawaan
rm -rf storage/app/private/photos storage/app/private/documents storage/app/private/generated
php artisan app:create-admin
# ubah APP_ENV=production di .env, lalu:
php artisan optimize
```

Template yang sudah disesuaikan saat UAT ikut terhapus oleh `migrate:fresh`. Catat perubahannya, atau sesuaikan template setelah data dikosongkan.

## Skenario pembina (dari ponsel)

| ID | Skenario | Hasil yang diharapkan |
| --- | --- | --- |
| P1 | Buka alamat pendaftaran, masuk dengan Google | Masuk ke Beranda Pembina, hitung mundur jadwal tampil |
| P2 | Isi data gudep | Tersimpan, langkah 1 bertanda selesai |
| P3 | Tambah 8 peserta Putra satu per satu, termasuk pas foto dari kamera | Indikator 8/8, peserta ke-9 tidak bisa ditambah |
| P4 | Impor 8 peserta Putri dari template Excel (sengaja buat 1 baris salah) | 7 masuk, 1 baris dilaporkan dengan alasannya; perbaiki lalu impor ulang |
| P5 | Lengkapi data diri dan tambah 1 pembina lain | Pemilik akun tidak bisa dihapus, pembina lain bisa |
| P6 | Unggah empat dokumen gudep (KTA, izin, dan sehat masing-masing satu PDF gabungan, plus Kesiapan Sekolah) | Keempat kartu berstatus diunggah; file > 10 MB atau format lain ditolak dengan pesan jelas |
| P7 | Coba **Kirim** saat masih ada yang kurang | Daftar kekurangan tampil dengan tautan perbaikan |
| P8 | Kirim pendaftaran lengkap | Status *Dikirim*, data terkunci, email diterima |
| P9 | Setelah admin minta revisi: buka catatan, unggah ulang dokumen yang ditolak, kirim lagi | Status kembali *Dikirim*, email diterima |
| P10 | Setelah terverifikasi: buka Beranda | Status *Terverifikasi*, data hanya bisa dilihat |

## Skenario admin (dari laptop)

| ID | Skenario | Hasil yang diharapkan |
| --- | --- | --- |
| A1 | Masuk di `/admin/login`, aktifkan 2FA, keluar, masuk lagi dengan kode | Diminta kode autentikator, lalu masuk dashboard |
| A2 | Atur jadwal buka/tutup | Banner jadwal di beranda publik dan pembina berubah |
| A3 | Buka gudep berstatus *Dikirim*, periksa dokumen | Pratinjau PDF/foto tampil di panel |
| A4 | Tolak satu dokumen dengan catatan, lalu **Minta revisi** | Status gudep *Perlu revisi*, pembina menerima email |
| A5 | Setelah pembina kirim ulang: setujui semua dokumen, **Verifikasi gudep** | Status *Terverifikasi* |
| A6 | Beri perpanjangan untuk satu gudep setelah jadwal ditutup | Hanya gudep itu yang masih bisa mengubah data |
| A7 | Ekspor Excel (semua dan hasil filter *Terverifikasi*) | File terbuka di Excel/Google Sheets, kolom status dokumen benar |
| A8 | Ubah teks penandatangan di template sertifikat, pratinjau PDF | Pratinjau sesuai |
| A9 | Generate ID Card untuk satu gudep, unduh lembar A4 3×3, cetak skala 100% | Kartu berukuran 55 × 85 mm, tanda potong pas |
| A10 | Generate sertifikat semua gudep terverifikasi, unduh ZIP | Satu PDF per anggota, nomor seri berurutan |
| A11 | Pindai QR pada kartu hasil cetak dengan ponsel | Halaman verifikasi menyatakan sah |
| A12 | Generate ulang setelah ubah template | Nomor seri tetap sama |

## Hal yang diamati

- Apakah pembina bisa menyelesaikan P1–P8 **tanpa bertanya**? Catat di langkah mana mereka ragu.
- Waktu yang dibutuhkan untuk mendaftarkan satu gudep lengkap.
- Ukuran dan kejelasan hasil foto dokumen dari ponsel.
- Apakah email notifikasi sampai (cek folder spam).
- Kualitas cetak ID Card dan sertifikat (warna, ketajaman foto, QR terbaca).

## Formulir temuan

| No | Skenario | Perangkat / peramban | Yang terjadi | Yang diharapkan | Tingkat (Kritis / Sedang / Kecil) | Tangkapan layar |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | | | | | | |
| 2 | | | | | | |
| 3 | | | | | | |

**Kriteria lolos:** semua skenario P1–P10 dan A1–A12 berhasil, tidak ada temuan *Kritis* yang terbuka, dan minimal 3 dari 5 pembina menyelesaikan pendaftaran tanpa bantuan.
