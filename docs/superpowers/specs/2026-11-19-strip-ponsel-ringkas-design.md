# Rancangan — Strip langkah pembina di ponsel: ringkas + bar progres

## Maksud
Strip langkah di ponsel (dibuat di T9.1) dinilai user terlalu ramai. Isinya 7 bulatan ikon dengan lencana nomor, centang, dan label, dan harus digeser. User ingin tampilan yang **lebih sederhana, tetapi rapi dan elegan**.

## Tujuan / bukan tujuan
- **Tujuan:**
  - Di ponsel, pembina sekilas tahu posisinya di alur dan langkah mana yang sudah selesai, lewat satu bilah tenang yang muat di layar 375 px tanpa digeser.
  - Daftar langkah lengkap tetap bisa dibuka.
- **Bukan tujuan:**
  - Mengubah timeline vertikal di layar lebar.
  - Mengubah alur 6 langkah, tombol Kembali/Lanjut, atau data dari server.
- **Anti-tujuan:** ikon, lencana, atau angka berlapis kembali muncul di bilah atas ponsel.

## Pendekatan
Yang dipertimbangkan:
- **A. Ringkas + bar progres (dipilih).**
- B. Titik bersambung.
- C. Tab teks saja.

Ketiganya ditampilkan sebagai mockup, dan user memilih A.

Untuk daftar lengkap ada dua pilihan: **panel dari bawah (dipilih)** atau bilah yang membuka ke bawah. Bilah yang membuka ke bawah tidak dipilih karena mendorong konten halaman.

## Rancangan
- **Bilah atas ponsel** (`StripTimeline` di `resources/js/components/pembina/step-timeline.tsx`, < md, sticky, latar marun gelap):
  - **Baris 1:** "Langkah {n}/6" (emas, kecil) diikuti judul langkah aktif (putih, tebal). Di kanan, tombol teks **"Semua langkah ▾"** (emas).
  - **Baris 2:** 6 segmen garis tipis setinggi ±4 px dalam grid 6 kolom. Segmen langkah `done` berwarna emas, langkah halaman aktif putih, sisanya samar.
  - **Di Ringkasan (Beranda):** "Ringkasan" diikuti "{x} dari 6 selesai", segmen tetap ditampilkan tanpa langkah aktif.
- **Panel "Semua langkah":**
  - Tombol membuka `Sheet` (`resources/js/components/ui/sheet.tsx`) dari bawah, bersudut atas membulat.
  - Isinya daftar timeline vertikal yang sama dengan desktop: Ringkasan + langkah 1–6, ikon, status, "Lanjutkan di sini", lalu nama dan tombol **Keluar**.
  - Panel tertutup setelah satu langkah dipilih.
  - Isi daftar dipakai bersama dengan `VerticalTimeline` agar tidak ada duplikasi.
- **Aksesibilitas:**
  - Bilah tetap `nav aria-label="Langkah pendaftaran"`.
  - Segmen progres bersifat dekoratif; posisinya dibacakan lewat teks "Langkah n dari 6".
  - Tombol panel punya `aria-expanded` dari Sheet, dan judul panel ditulis sr-only.

## Diserahkan ke pembuat
Jarak, ukuran huruf, dan animasi panel persis. Semuanya memakai token merah-emas yang ada.

## Verifikasi
- **Browser 375 px:**
  - Bilah muat tanpa digeser.
  - Teks dan segmen benar di Ringkasan, Putra, dan Kirim.
  - Panel terbuka, menavigasi, lalu tertutup.
  - Tidak ada geser horizontal.
- **Laptop:** timeline vertikal tidak berubah.
- **Pipeline:** `npm run types:check`, `npm run check`, `npm run build`, dan tes PHP yang ada tetap hijau.
