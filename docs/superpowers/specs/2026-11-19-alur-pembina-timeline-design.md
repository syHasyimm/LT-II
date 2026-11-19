# Rancangan — Alur per langkah dan timeline vertikal di area Pembina

## Context
Pembina sekarang berpindah lewat 4 menu: Beranda, Anggota (tab Putra/Putri/Pembina), Dokumen, dan Kirim. Menu ini ada di bilah atas pada layar lebar dan di bawah pada ponsel.

User ingin area pembina dibagi **per alur** agar aplikasi lebih mudah dipakai: Data Gudep → Peserta Putra → Peserta Putri → Pembina → Foto & Dokumen → Kirim Pendaftaran. Menu link diganti **navigasi vertikal di kanan konten bergaya timeline dengan ikon**.

Tujuannya agar pembina tahu harus mengisi apa dan kelanjutannya ke mana, tanpa harus menebak menu.

## Keputusan user
- **Ponsel:** timeline menjadi strip langkah ringkas berikon di atas konten. Strip ini bisa digeser horizontal dan langkah aktif ditandai. Menu bawah dihapus.
- **Urutan:** bebas tetapi dipandu. Semua langkah di timeline bisa dibuka kapan saja, dan tombol **Lanjut** mengarah ke langkah berikutnya. Langkah yang belum lengkap ditandai. Hanya Data Gudep yang wajib diisi lebih dulu (perilaku saat ini).
- **Pendekatan: A, timeline di layout.** Halaman yang ada dipakai ulang. Tab di halaman Anggota dipecah menjadi tiga langkah, dan setiap langkah mendapat footer Kembali/Lanjut.

## Approaches
- **A. Timeline di layout + halaman lama (dipilih).** Perubahan paling kecil. Form, impor Excel, dan tes yang ada tetap dipakai.
- B. Route baru per langkah (`/pembina/peserta-putra`, …). Hasil bagi pembina sama dengan A, tetapi perlu redirect, dan tautan serta tes ikut diubah. Tidak dipilih.
- C. Satu halaman wizard di sisi klien. Semua halaman harus dibangun ulang, dan posisi langkah hilang saat halaman dimuat ulang. Tidak dipilih.

## Tujuan / bukan tujuan
- **Tujuan:**
  - Pembina selalu melihat posisinya di alur, status setiap langkah (selesai / sedang dibuka / belum), dan bisa lanjut dengan satu ketukan.
  - Tetap nyaman di ponsel 375 px.
- **Bukan tujuan:**
  - Mengubah aturan bisnis: kuota, validasi, penguncian setelah kirim, dan jadwal tetap sama.
  - Mengubah area admin.
- **Anti-tujuan:**
  - Pembina tertahan di satu langkah karena data langkah lain belum ada.
  - Ruang form di ponsel menyempit.

## Rancangan

### 1. Data langkah (server)
- `app/Services/RegistrationProgress.php` → `steps()`: langkah `peserta` dipecah menjadi `peserta_pa` dan `peserta_pi`. Hasilnya 6 langkah: gudep, peserta_pa, peserta_pi, pembina, berkas, kirim.
  - Setiap langkah membawa `title`, `subtitle`, `value` (mis. "5/8"), `state` (done / current / todo), dan `href`.
  - `href` langkah Putra/Putri: `pembina.members.index?tab=pa|pi`.
- `app/Http/Middleware/HandleInertiaRequests.php`: dua shared prop baru, khusus pengguna pembina, dihitung lazy dengan closure.
  - `pembinaSteps`: hasil `RegistrationProgress` untuk gudep pengguna.
  - `pembinaStep`: kunci langkah halaman saat ini, ditentukan dari nama route.
    - `gudep.edit` → gudep
    - `members.index` → diambil dari `tab`
    - `members.create/{regu}` → pa/pi
    - `members.edit/{member}` → `group` anggota, atau pembina
    - `documents.index` → berkas
    - `submission.show` → kirim
    - `dashboard` → ringkasan
- Dashboard pembina (`resources/js/pages/pembina/dashboard.tsx`) tetap menampilkan daftar langkah sebagai ringkasan, sekarang 6 langkah.

### 2. Timeline (frontend)
- Komponen baru `resources/js/components/pembina/step-timeline.tsx`.
- **Desktop (md+):** panel vertikal di kanan konten, `sticky top`.
  - Item teratas: **Ringkasan** (ikon Home, tautan ke Beranda).
  - Langkah 1–6 dengan ikon: Data Gudep (`Flag`), Peserta Putra (`User`), Peserta Putri (`UserRound`), Pembina Pendamping (`Users`), Foto & Dokumen (`Camera`), Kirim (`Send`).
  - Setiap item: bulatan ikon pada garis vertikal, judul, nilai kecil (mis. "8/8", "12/51").
  - Gaya status: **done** = bulatan emas dengan centang dan garis emas; **halaman aktif** = bulatan marun dengan cincin dan teks marun tebal; **todo** = outline; **current** (langkah belum lengkap pertama) diberi titik penanda "lanjutkan di sini".
  - Bagian bawah panel: nama pembina dan tombol **Keluar**.
- **Ponsel (<md):** strip horizontal sticky di atas, bisa digeser, hanya ikon bernomor dengan label pendek. Langkah aktif otomatis digulir ke tengah. Menu bawah dihapus, sehingga `pb-28` di `main` dan offset notifikasi ponsel ikut disesuaikan.
- Aksesibilitas: `nav aria-label="Langkah pendaftaran"`, `aria-current="step"` pada halaman aktif, dan status dibacakan lewat teks sr-only ("selesai", "belum lengkap").

### 3. Layout dan halaman
- `resources/js/layouts/pembina-layout.tsx`:
  - Bilah atas desktop hanya berisi brand, tautan menunya dihapus.
  - Kontainer menjadi grid `md:grid-cols-[minmax(0,1fr)_280px]` dengan lebar ±`max-w-5xl`, berisi konten lalu timeline.
  - Bottom nav dihapus. `pembinaNav` diganti data dari `pembinaSteps`.
- Komponen baru `resources/js/components/pembina/step-nav.tsx`: footer **← Kembali** / **Lanjut: {langkah berikutnya} →**, dihitung dari `pembinaSteps` dan `pembinaStep`.
  - Dipasang di halaman Anggota (per tab) dan Dokumen.
  - Halaman Kirim hanya punya Kembali.
  - Data Gudep tetap memakai tombol "Simpan & lanjut" yang sudah ada.
- `resources/js/pages/pembina/members/index.tsx`:
  - Baris tab Putra/Putri/Pembina dihapus.
  - Judul header mengikuti langkah: "Peserta Putra", "Peserta Putri", "Pembina Pendamping".
  - Isi tetap memakai `PesertaList` / `PembinaList` / `ImportPeserta`.
- Tipe di `resources/js/types` untuk `pembinaSteps` / `pembinaStep` sebagai shared props.

### 4. Pilihan saya (silakan dikoreksi)
- Beranda menjadi item teratas timeline ("Ringkasan"), di luar penomoran 1–6.
- Tombol Keluar pindah ke bawah panel timeline (desktop). Di ponsel tetap di header Beranda seperti sekarang.
- Halaman tambah/ubah anggota menandai langkah sesuai regu anggotanya.
- Dokumen spesifikasi disalin ke `docs/superpowers/specs/2026-11-19-alur-pembina-timeline-design.md` dan di-commit saat implementasi dimulai.

## Diserahkan ke pembuat
Jarak dan ukuran persis, animasi transisi, dan teks label pendek di strip ponsel. Semuanya mengikuti token merah-emas yang sudah ada.

## Verifikasi
- **Tes:**
  - Perbarui `tests/Feature/Domain/RegistrationProgressTest.php` (6 langkah, Pa/Pi terpisah).
  - Tes baru untuk shared prop `pembinaSteps` / `pembinaStep` per route, termasuk edit anggota Pa → `peserta_pa` dan pembina → `pembina`.
  - Pastikan pengguna admin tidak menerima prop tersebut.
- **Pipeline:** `composer test`, `npm run check`, `npm run types:check`, `npm run build`.
- **Browser (dev-login pembina demo):**
  - 1440 px: timeline di kanan, status benar, Lanjut berpindah langkah.
  - 375 px: strip di atas, tidak ada geser horizontal halaman, notifikasi tidak tertutup.
  - Audit aksesibilitas ulang.
- **Commit:** satu commit per bagian, dan TASKS.md ditambah task baru.
