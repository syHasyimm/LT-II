# Panduan Admin Panitia

Panduan singkat mengelola pendaftaran Perkemahan Lomba Tingkat 2 dari panel admin.

## Masuk dan keamanan akun

- Alamat: `/admin/login` (email + kata sandi). Akun admin dibuat oleh pengelola server dengan `php artisan app:create-admin`.
- **Aktifkan autentikasi dua langkah (2FA):** menu **Akun → Keamanan → Aktifkan 2FA**, pindai QR dengan aplikasi autentikator (Google Authenticator, Microsoft Authenticator, dsb.), masukkan kodenya, lalu **simpan kode cadangan** di tempat aman. Selama 2FA belum aktif, dashboard menampilkan pengingat.
- Akun admin bisa melihat data dan dokumen semua peserta. Jangan berbagi akun; buat akun terpisah untuk setiap petugas.

## Sebelum pendaftaran dibuka

1. **Jadwal Pendaftaran**: atur tanggal dan jam buka/tutup (WIB). Di luar jadwal, pembina hanya bisa melihat data.
2. **Template** ID Card Peserta, ID Card Pembina, dan Sertifikat: buka editor, ganti teks contoh (kota dan tanggal penandatanganan, nama Ketua Panitia dan Ketua Kwarcab), lalu **Pratinjau PDF**.
3. Uji alur lengkap sekali dengan akun Google uji (lihat `docs/UAT.md`).

## Memeriksa pendaftaran

1. **Dashboard** menampilkan jumlah gudep per status, dokumen yang menunggu, dan daftar gudep. Gudep berstatus **Dikirim** tampil paling atas.
2. Buka gudep → periksa data anggota dan setiap dokumen (pratinjau langsung di panel).
3. Untuk setiap dokumen pilih **Setujui** atau **Tolak** (wajib dengan catatan, misalnya "Foto buram, unggah ulang").
4. Keputusan gudep:
   - **Verifikasi**: bila semua dokumen disetujui. Status menjadi *Terverifikasi* dan data terkunci permanen.
   - **Minta revisi**: tulis catatan untuk pembina. Bila pendaftaran sudah ditutup, isi **tenggat revisi** agar pembina masih bisa memperbaiki.
5. Pembina menerima email otomatis pada setiap perubahan status.

**Perpanjangan khusus:** di halaman gudep, atur perpanjangan waktu bila satu gudep butuh tambahan waktu setelah jadwal ditutup.

Semua keputusan tercatat di riwayat aktivitas gudep (audit log).

## Ekspor Excel

Menu **Gudep & Verifikasi → Ekspor Excel**. File berisi:

- sheet **Gudep**: status, jumlah peserta Pa/Pi dan pembina, rekap dokumen, tanggal kirim/verifikasi;
- sheet **Anggota**: nama, peran, regu, gudep, status tiap dokumen, nomor seri ID Card/Sertifikat.

Ekspor mengikuti filter dan pencarian yang sedang aktif (misalnya hanya gudep *Terverifikasi*). File berisi data pribadi peserta: simpan dan bagikan seperlunya.

## ID Card dan Sertifikat

1. Menu **ID Card** atau **Sertifikat** → pilih penerima: satu orang, satu gudep, atau semua gudep terverifikasi → **Generate**. Prosesnya berjalan di latar belakang dengan indikator progres.
2. Unduh:
   - PDF satuan per anggota;
   - PDF per gudep (ID Card bisa dalam **lembar A4 berisi 9 kartu** dengan tanda potong);
   - ZIP per gudep atau semua.
3. Nomor seri (`LT2-2026-ID-0001`, `LT2-2026-CERT-0001`) tetap sama saat generate ulang, misalnya setelah memperbaiki template.
4. QR di kartu/sertifikat membuka halaman verifikasi publik `/verify/{nomor-seri}`, yang hanya menampilkan nama, gudep, peran, dan tanggal terbit.

**Tips cetak ID Card:** cetak PDF lembar A4 dengan skala **100% / ukuran sebenarnya** (matikan "fit to page"), lalu potong mengikuti tanda potong. Ukuran kartu 55 × 85 mm.

## Backup

Server membuat backup otomatis (database setiap malam, database + dokumen setiap Minggu). Pengelola server sebaiknya menyalin backup ke luar server secara rutin. Detail dan cara pemulihan ada di `docs/DEPLOY.md`.
