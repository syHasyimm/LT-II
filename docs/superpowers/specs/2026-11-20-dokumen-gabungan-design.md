# Dokumen gabungan per gudep (KTA, Izin Orang Tua, Keterangan Sehat)

Tanggal: 2026-11-20 · Ukuran: perubahan kecil (desain singkat)

## Maksud dan alasan

Saat ini KTA, Surat Izin Orang Tua, dan Surat Keterangan Sehat diunggah **per anggota** lewat matriks anggota × dokumen, sehingga satu gudep bisa butuh 40-an kali unggah. Pembina ingin cukup **satu kotak unggah per jenis** berisi berkas gabungan seluruh anggota, supaya pengerjaan jauh lebih ringan.

## Tujuan / bukan tujuan / anti-tujuan

- **Tujuan:** halaman Foto & Dokumen berisi 4 kartu unggah tingkat gudep (KTA, Izin Orang Tua, Keterangan Sehat, Kesiapan Sekolah); pas foto tetap per anggota.
- **Bukan tujuan:** banyak berkas per kotak, penggabungan PDF otomatis di aplikasi, pemetaan halaman PDF ke anggota.
- **Anti-tujuan:** pembina masih harus mengunggah per orang; admin kehilangan kemampuan menolak berkas dan meminta unggah ulang.

## Pendekatan yang dipertimbangkan

1. **Satu kotak, banyak berkas** — termudah dari ponsel, batas 2 MB per file tetap; admin memeriksa kumpulan file. *Tidak dipilih.*
2. **Satu kotak, tepat satu berkas** — pembina menggabungkan sendiri menjadi satu PDF; batas ukuran dinaikkan. **Dipilih pengguna.**

Cara membangun pilihan 2: ubah ketiga jenis menjadi `scope = gudep` di `document_types`, memakai ulang jalur dokumen gudep yang sudah ada (unggah, unggah ulang, verifikasi, daftar periksa). Membuat jalur baru tidak memberi manfaat.

## Keputusan

1. **Migrasi** baru mengubah `kta`, `izin_ortu`, `kesehatan` menjadi `scope = gudep`, `required_for = null`, dengan label yang menjelaskan isi gabungan, mis. "KTA seluruh anggota (digabung 1 PDF)", "Surat Izin Orang Tua seluruh peserta (digabung 1 PDF)", "Surat Keterangan Sehat seluruh anggota (digabung 1 PDF)". KTA dan sehat mencakup pembina pendamping; izin hanya peserta.
2. **Halaman Foto & Dokumen pembina:** 4 kartu unggah di atas, masing-masing dengan petunjuk singkat isi berkas. Matriks anggota disederhanakan menjadi daftar **pas foto** saja.
3. **Batas unggah** naik dari 2 MB ke **10 MB** (`config/camp.php` `max_upload_kb`), berlaku untuk semua dokumen. Setelan PHP `upload_max_filesize`/`post_max_size` harus ≥ 10 MB (dicatat di README/panduan deploy).
4. **Admin:** verifikasi tetap per berkas; tolak → pembina unggah ulang berkas gabungan.
5. **Ikut disesuaikan:** `DocumentChecklist`/`RegistrationProgress` (daftar periksa, hitungan berkas, Beranda), panel verifikasi admin, ekspor Excel (kolom dokumen per anggota diganti status 4 dokumen gudep), `DemoDataSeeder`, `DocumentFactory`, tes, PRD (BR-05, FR-DOC-01, FR-DOC-02), `docs/PANDUAN-PEMBINA.md`, `docs/PANDUAN-ADMIN.md`.
6. **Data lama:** aplikasi masih tahap dev, jadi migrasi **menghapus** baris `documents` yang ber-`member_id` beserta file-nya di disk privat. `down()` hanya mengembalikan scope/label, tidak memulihkan data.

## Diserahkan ke pembangun

- Kode dan relasi `member_id` pada `documents` tetap ada (nullable) kecuali jelas tidak terpakai; boleh dibiarkan.
- Teks petunjuk per kartu dan susunan daftar pas foto mengikuti gaya halaman yang ada.
- Pembersihan komponen yang tak terpakai lagi (mis. `DocCell`) bila memang tidak dipakai di tempat lain.
