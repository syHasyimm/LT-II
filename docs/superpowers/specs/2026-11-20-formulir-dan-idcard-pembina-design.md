# Formulir Pendaftaran (admin) dan ID Card untuk pembina

Tanggal: 2026-11-20 · Ukuran: perubahan kecil–sedang (desain singkat)

## Maksud

Setelah admin memverifikasi gudep:
- **Admin** bisa mengunduh **Formulir Pendaftaran** gudep itu (PDF) lewat tombol di samping tombol "Verifikasi gudep".
- **Pembina** bisa mengunduh **ID Card** seluruh peserta dan pembina gudepnya (PDF) dari akunnya.

## Tujuan / bukan tujuan / anti-tujuan

- **Tujuan:** formulir berkop Kwartir Ranting Kepenuhan berisi data gudep, peserta Putra, peserta Putri, dan pembina; ID Card otomatis tersedia untuk pembina tanpa langkah tambahan admin.
- **Bukan tujuan:** formulir untuk pembina; Sertifikat untuk pembina (tetap khusus admin); pas foto atau kolom tanda tangan di formulir; menyimpan file PDF.
- **Anti-tujuan:** tombol verifikasi menjadi lambat karena membuat PDF; pembina bisa mengunduh ID Card sebelum gudep terverifikasi atau milik gudep lain.

## Pendekatan

- **A. Dibuat saat diunduh (dipilih).** Formulir dirender dompdf dari Blade setiap kali diunduh. ID Card: saat verifikasi, job latar belakang yang ada (`GenerateFileJob`) memberi nomor seri; unduhan pembina merender PDF dari `generated_files` dengan template aktif, memakai ulang logika cetak per gudep admin (dipindah ke satu service). Selalu memakai template terbaru; tiap unduhan butuh beberapa detik.
- **B. Dibuat sekali lalu disimpan.** Unduhan instan, tetapi basi bila template diperbaiki dan perlu kode simpan/timpa. Tidak dipilih.
- Membuat ID Card langsung di request verifikasi ditolak: lambat untuk 17+ anggota berfoto.

## Keputusan

1. **ID Card otomatis saat verifikasi.** `ApproveGudep` men-dispatch batch `GenerateFileJob` untuk semua anggota gudep (jenis ID Card) setelah status menjadi terverifikasi, hanya bila template ID Card aktif tersedia untuk peran anggota. Tanpa template, verifikasi tetap berhasil; admin bisa membuat kartu nanti lewat menu ID Card seperti biasa.
2. **Unduhan pembina.** Halaman pembina berstatus Terverifikasi menampilkan tombol **Unduh ID Card**; PDF berupa lembar **A4 berisi 9 kartu dengan garis potong** (mode `sheet` yang sudah ada). Bila belum ada kartu yang dibuat, tampil "ID Card sedang disiapkan panitia" tanpa tombol. Rute pembina hanya untuk gudep miliknya yang berstatus terverifikasi.
3. **Formulir (admin).** Tombol **Unduh formulir** muncul di samping "Verifikasi gudep" di panel verifikasi saat status gudep terverifikasi. PDF A4 portrait:
   - Kop: logo tunas kelapa (kiri), teks "GERAKAN PRAMUKA / KWARTIR RANTING KEPENUHAN / Jalan Syech Abdul Wahab Rokan-Kota Tengah No. Tlp.- Kodepos: 28559 / Email : kwartirranting03kepenuhan@gmail.com", logo lambang ungu (kanan), garis ganda di bawah.
   - Judul "Formulir Pendaftaran Perkemahan LT 2".
   - Identitas gudep: nama, nomor gudep, NPSN, instansi, penanggung jawab, alamat, pemilik akun.
   - Tabel peserta Putra dan Putri: No, Nama, Tanggal lahir, No. KTA.
   - Tabel pembina pendamping: No, Nama, L/P, Tanggal lahir, No. KTA, No. HP, Mendampingi.
   - Tanggal dikirim dan diverifikasi.
4. **Logo kop.** Disimpan di `public/images/kop-kiri.png` dan `public/images/kop-kanan.png`, sementara dipotong dari screenshot pengguna (resolusi rendah); pengguna akan menimpanya dengan file asli.
5. **PRD:** BR-10 dan FR-GEN-04 diubah: ID Card gudep terverifikasi juga dapat diunduh pembina gudep itu; Sertifikat tetap khusus admin. Panduan pembina dan admin diperbarui.

## Diserahkan ke pembangun

- Nama dan letak service bersama untuk cetak ID Card per gudep.
- Gaya tabel formulir (mengikuti warna marun/emas aplikasi secukupnya, tetap rapi saat dicetak hitam-putih).
- Teks kop disimpan di `config/camp.php` agar mudah diubah tanpa menyentuh template.
