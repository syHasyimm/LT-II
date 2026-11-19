# Strip Langkah Ponsel Ringkas — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ganti strip ikon di ponsel dengan bilah ringkas "Langkah n/6 · Judul" + 6 segmen progres, dan tombol "Semua langkah" yang membuka panel bawah berisi timeline vertikal.

**Architecture:** Hanya `resources/js/components/pembina/step-timeline.tsx` yang berubah.
- `StripTimeline` ditulis ulang.
- Isi daftar timeline (`TimelineList`) diekstrak dari `VerticalTimeline` agar dipakai bersama oleh kartu desktop dan panel `Sheet` di ponsel.
- Data tetap dari shared props `pembinaSteps` / `pembinaStep`.

**Tech Stack:** React 19 + TypeScript, Inertia 3, Tailwind 4 (token merah-emas), shadcn `Sheet`.

**Spec:** `docs/superpowers/specs/2026-11-19-strip-ponsel-ringkas-design.md`

## Global Constraints

- Hanya tampilan di bawah `md` yang berubah. Timeline vertikal desktop harus terlihat sama seperti sebelumnya.
- Tidak boleh ada ikon, lencana nomor, atau centang di bilah atas ponsel.
- **Teks bilah:**
  - langkah aktif: `Langkah {n}/6` (emas, kecil) + judul langkah aktif (putih, tebal);
  - di Ringkasan: `Ringkasan` + `{x} dari 6 selesai`.
- **Tombol kanan:** `Semua langkah` + ikon `ChevronDown` (emas).
- **Segmen:** grid 6 kolom, tinggi 4 px, `rounded-full`. Warna: `done` = `bg-gold-500`, langkah halaman aktif = `bg-white`, lainnya = `bg-white/15`. Aktif mengalahkan `done`.
- **Panel:** `Sheet side="bottom"`, sudut atas `rounded-t-3xl`, latar ivory, `SheetTitle` sr-only "Langkah pendaftaran". Isinya `TimelineList` + baris nama/Keluar. Panel tertutup setelah tautan di dalamnya diklik.
- **Aksesibilitas:** `nav aria-label="Langkah pendaftaran"` tetap ada; segmen `aria-hidden`.
- Tanpa ketergantungan baru.

## Review Focus

1. **Pembina tanpa gudep** (`pembinaStep` = `gudep`, semua `todo` kecuali gudep `current`): bilah menampilkan "Langkah 1/6 · Data gudep" tanpa error. Cek di browser (Task 1 Step 3).
2. **`pembinaSteps` kosong/null** (misalnya halaman error yang dibuka pembina): bilah tidak crash, menampilkan "Ringkasan · 0 dari 6 selesai" atau tidak render. Ditangani oleh `?? []` + guard (Task 1 Step 1).
3. **Judul panjang** ("Pembina pendamping", "Kirim pendaftaran") di 375 px: judul terpotong rapi (`truncate`) dan tidak mendorong tombol "Semua langkah" keluar layar. Cek di browser.
4. **Panel tertutup setelah navigasi** dan tidak tetap terbuka saat kembali dengan tombol Back browser. Cek di browser.
5. **Desktop tidak berubah** setelah ekstraksi `TimelineList`: urutan, ikon, status, dan Keluar sama. Cek di browser 1440 px.

---

### Task 1: Bilah ringkas + panel "Semua langkah"

**Files:**
- Modify: `resources/js/components/pembina/step-timeline.tsx`

**Interfaces:**
- Consumes: `useTimelineItems()` (sudah ada di file yang sama), `Sheet`, `SheetContent`, `SheetTitle`, `SheetTrigger` dari `@/components/ui/sheet`.
- Produces:
  - `function TimelineList({ onNavigate }: { onNavigate?: () => void })`: `ol` timeline + baris nama/Keluar. Dipakai `VerticalTimeline` (tanpa `onNavigate`) dan panel ponsel (dengan `onNavigate={() => setOpen(false)}`).
  - Ekspor `StepTimeline({ variant })` dan `stepIcon` tidak berubah.

Ruling yang sudah diputuskan di plan: proyek tidak punya kerangka tes frontend, jadi "tes" untuk task ini adalah `tsc`, `vp check`, `build`, dan pemeriksaan browser dengan kriteria yang tertulis di Step 3.

- [ ] **Step 1: Implementasi.**
  - Ekstrak `TimelineList` dari isi `VerticalTimeline` (nav + daftar + footer nama/Keluar). `VerticalTimeline` menjadi kartu pembungkus + judul "Langkah pendaftaran" + `TimelineList`. Setiap `Link` di daftar memanggil `onNavigate` saat diklik.
  - Tulis ulang `StripTimeline` sesuai Global Constraints:
    - `n` = indeks langkah aktif + 1;
    - `x` = jumlah langkah `done`;
    - panel memakai `useState` untuk `open`.
  - Hapus `shortLabel` dan cabang `size="sm"` / `dark` di `StepBullet` yang tidak terpakai lagi.
- [ ] **Step 2: Cek statis.** Run: `npm run types:check && npx vp check --fix && npm run build`. Expected: tanpa error, `✓ built`.
- [ ] **Step 3: Browser.** Dev-login pembina demo, viewport 375×812, lalu cek:
  - `/pembina/anggota?tab=pa` → "Langkah 2/6", judul "Peserta Putra", segmen 1 emas dan segmen 2 putih;
  - `/pembina` → "Ringkasan · {x} dari 6 selesai";
  - `/pembina/kirim` → "Langkah 6/6";
  - ketuk "Semua langkah" → panel bawah tampil dengan 7 item dan Keluar → klik "Peserta Putri" → URL `?tab=pi` dan panel tertutup;
  - `document.documentElement.scrollWidth === innerWidth`;
  - viewport desktop: timeline vertikal tampil seperti sebelumnya.
- [ ] **Step 4: Commit.** `git commit -m "Pembina ponsel: bilah langkah ringkas dengan bar progres dan panel Semua langkah"`

### Task 2: Pipeline + dokumentasi

**Files:**
- Modify: `docs/TASKS.md` (T9.2 baru, dicentang), `docs/PANDUAN-PEMBINA.md` (kalimat strip ponsel)
- Add: spec + plan ini

- [ ] **Step 1: Perbarui dokumentasi.**
  - `PANDUAN-PEMBINA.md`: "Di ponsel, … deretan ikon di bagian atas yang bisa digeser" → "Di ponsel, bilah di bagian atas menampilkan langkah yang sedang dibuka dan garis progres; ketuk **Semua langkah** untuk melihat dan membuka langkah lain."
  - `TASKS.md`: tambah `T9.2 Bilah langkah ponsel ringkas`.
- [ ] **Step 2: Pipeline penuh.** Run: `composer test && npm run check && npm run types:check`. Expected: semua lolos (213 tes).
- [ ] **Step 3: Commit.** `git commit -m "T9.2: dokumentasi bilah langkah ponsel ringkas"`
