# Alur Pembina per Langkah + Timeline Vertikal — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ganti navigasi menu area pembina dengan alur 6 langkah (Gudep → Putra → Putri → Pembina → Foto & Dokumen → Kirim) yang ditampilkan sebagai timeline vertikal berikon di kanan konten (strip horizontal di ponsel), dengan footer Kembali/Lanjut.

**Architecture:** `RegistrationProgress::steps()` menghasilkan 6 langkah. `HandleInertiaRequests` membagikan `pembinaSteps` dan `pembinaStep` (kunci langkah halaman aktif) khusus untuk pembina. `PembinaLayout` merender `StepTimeline`, dan halaman memakai `StepNav`. Halaman yang ada dipakai ulang; tab di Anggota dihapus dan diganti query `?tab=`.

**Tech Stack:** Laravel 13, Inertia 3, React 19 + TypeScript, Tailwind 4 (token merah-emas di `resources/css/app.css`), lucide-react, Pest.

**Spec:** `docs/superpowers/specs/2026-11-19-alur-pembina-timeline-design.md`

## Global Constraints

- Kunci langkah (urutan tetap): `gudep`, `peserta_pa`, `peserta_pi`, `pembina`, `berkas`, `kirim`. Halaman Beranda memakai kunci `ringkasan` (bukan langkah bernomor).
- Judul langkah: "Data gudep", "Peserta Putra", "Peserta Putri", "Pembina pendamping", "Foto & dokumen", "Kirim pendaftaran".
- Ikon lucide: Ringkasan `Home`, gudep `Flag`, peserta_pa `User`, peserta_pi `UserRound`, pembina `Users`, berkas `Camera`, kirim `Send`.
- `state` setiap langkah: `done` | `current` (langkah belum selesai pertama) | `todo`. Semua langkah selalu bisa diklik (tanpa kunci urutan).
- Aturan bisnis (kuota, validasi, kunci setelah kirim, jadwal) tidak berubah. Area admin tidak disentuh.
- Teks UI dalam bahasa Indonesia. Warna hanya dari token yang ada (maroon-*, gold-*, ivory, sand, line, ink*).
- Breakpoint: timeline vertikal di `md` ke atas; strip horizontal di bawah `md`. Menu bawah dihapus.
- Aksesibilitas: `nav aria-label="Langkah pendaftaran"`, `aria-current="step"` pada langkah halaman aktif, status dibacakan dengan teks `sr-only` ("selesai" / "belum lengkap").

## Review Focus

1. **Pembina tanpa gudep:** prop tetap terkirim. Langkah `gudep` bernilai `current`, langkah lain `todo`, dan semua tautan tetap valid (halaman lain mengalihkan ke Data Gudep seperti sekarang). Tes di Task 1.
2. **Edit anggota:** membuka `/pembina/anggota/{id}` milik peserta Putri harus menandai `peserta_pi`, dan milik pembina menandai `pembina`, bukan `ringkasan`. Tes di Task 1.
3. **Query `tab` tidak valid** (`?tab=xyz`) harus jatuh ke `peserta_pa`, sama dengan perilaku `MemberController::index`. Tes di Task 1.
4. **Admin atau tamu tidak menerima `pembinaSteps`** (tanpa query tambahan, tanpa bocor data). Tes di Task 1.
5. **Ponsel 375 px:** strip tidak membuat halaman bisa digeser horizontal, dan notifikasi tidak tertutup. Dicek di browser pada Task 4.

---

### Task 1: Data langkah dan shared props

**Files:**
- Modify: `app/Services/RegistrationProgress.php` (`steps()`, ±baris 224–296)
- Modify: `app/Http/Middleware/HandleInertiaRequests.php` (`share()`)
- Create: `app/Services/PembinaStep.php`
- Modify: `tests/Feature/Domain/RegistrationProgressTest.php`
- Create: `tests/Feature/Domain/PembinaStepsTest.php`

**Interfaces:**
- Produces:
  - `RegistrationProgress::steps(): list<array{key: string, title: string, subtitle: string, value: string, href: string, state: 'done'|'current'|'todo'}>` dengan 6 langkah sesuai Global Constraints.
  - Langkah `peserta_pa`: subtitle `"Regu Putra · {n}/{max}"`, value `"{n}/{max}"`, done saat n == max, href `route('pembina.members.index', ['tab' => 'pa'])`. `peserta_pi` sama untuk `pi`.
  - `PembinaStep::fromRequest(Request $request): string` mengembalikan salah satu kunci langkah atau `'ringkasan'`.
  - Shared props: `pembinaSteps` (array di atas atau `null`) dan `pembinaStep` (string atau `null`). Keduanya `null` bila pengguna bukan pembina. Keduanya closure (lazy).

- [ ] **Step 1: Perbarui `RegistrationProgressTest`**: `has('steps', 6)`. Setelah gudep diisi: `steps.1.key` = `'peserta_pa'`, `steps.1.state` = `'current'`, `steps.1.value` = `'0/8'`, `steps.2.key` = `'peserta_pi'`, `steps.2.value` = `'0/8'`.
- [ ] **Step 2: Tulis `PembinaStepsTest`**:
  - `menandai langkah sesuai halaman`: dataset route → kunci yang diharapkan, dengan assert `->where('pembinaStep', ...)` pada respons Inertia.
    - `pembina.dashboard` → `ringkasan`
    - `pembina.gudep.edit` → `gudep`
    - `members.index?tab=pi` → `peserta_pi`
    - `members.index?tab=xyz` → `peserta_pa`
    - `members.create pa` → `peserta_pa`
    - `documents.index` → `berkas`
    - `submission.show` → `kirim`
  - `menandai langkah regu saat mengedit anggota`: anggota grup Pi → `peserta_pi`; anggota pembina → `pembina`.
  - `membagikan enam langkah untuk pembina tanpa gudep`: `has('pembinaSteps', 6)`, `pembinaSteps.0.state` = `current`, `pembinaSteps.1.state` = `todo`.
  - `tidak membagikan langkah ke admin`: `GET admin.dashboard` → `where('pembinaSteps', null)->where('pembinaStep', null)`.
- [ ] **Step 3: Jalankan tes, pastikan gagal.** Run: `php artisan test --compact tests/Feature/Domain/RegistrationProgressTest.php tests/Feature/Domain/PembinaStepsTest.php`. Expected: FAIL (`steps` masih 5, prop belum ada).
- [ ] **Step 4: Implementasi.**
  - Pecah langkah `peserta` di `steps()`.
  - Buat `PembinaStep::fromRequest`. Pemetaan dari `$request->route()?->getName()`:
    - `pembina.members.index` → tab (`pa|pi|pembina`, selain itu `pa`)
    - `pembina.members.create` → parameter `regu`
    - `pembina.members.edit` → `$member->isPembina() ? 'pembina' : 'peserta_'.$member->group->value`
    - `pembina.gudep.edit` → `gudep`
    - `pembina.documents.index` → `berkas`
    - `pembina.submission.show` → `kirim`
    - lainnya → `ringkasan`
  - Tambah dua prop di `share()`, memakai `RegistrationProgress::for($user->gudep)->steps()`.
- [ ] **Step 5: Jalankan tes, pastikan lolos.** Perintah sama dengan Step 3, ditambah `tests/Feature/Domain/SubmissionTest.php`. Expected: PASS.
- [ ] **Step 6: Commit.** `git add app tests && git commit -m "Alur pembina: 6 langkah (Pa/Pi terpisah) dan shared prop pembinaSteps/pembinaStep"`

### Task 2: Komponen timeline dan navigasi langkah

**Files:**
- Create: `resources/js/types/pembina.ts`
- Modify: `resources/js/types/global.d.ts` (shared props) dan `resources/js/types/index.ts` (re-export, bila pola itu dipakai)
- Create: `resources/js/components/pembina/step-timeline.tsx`
- Create: `resources/js/components/pembina/step-nav.tsx`

**Interfaces:**
- Consumes: shared props dari Task 1.
- Produces:
  - `type StepKey = 'gudep' | 'peserta_pa' | 'peserta_pi' | 'pembina' | 'berkas' | 'kirim'`
  - `type PembinaStepData = { key: StepKey; title: string; subtitle: string; value: string; href: string; state: 'done' | 'current' | 'todo' }`
  - Shared props `pembinaSteps: PembinaStepData[] | null` dan `pembinaStep: StepKey | 'ringkasan' | null`.
  - `<StepTimeline variant="vertical" | "strip" />`, membaca shared props sendiri.
  - `<StepNav />`, membaca shared props sendiri dan merender footer Kembali (langkah sebelumnya, atau Beranda dari langkah 1) / **Lanjut: {judul berikutnya}** (tidak ada di `kirim`). Tidak merender apa pun bila `pembinaStep` adalah `ringkasan` atau `null`.
  - `stepIcon: Record<StepKey | 'ringkasan', LucideIcon>` di `step-timeline.tsx` (ekspor juga untuk dashboard).

- [ ] **Step 1: Buat tipe dan deklarasi shared props.** Run: `npm run types:check`. Expected: PASS.
- [ ] **Step 2: Implementasi `StepTimeline`.**
  - **Vertical:** item Ringkasan, lalu langkah 1–6 di garis vertikal.
    - Bulatan 40 px dengan ikon.
    - `done`: bg-gold-gradient, ikon centang kecil di sudut, garis emas.
    - Halaman aktif: bg-maroon-gradient, `ring-4 ring-maroon-100`, judul `text-maroon-700 font-bold`.
    - `todo`: border-line, ikon text-ink-soft.
    - `current` (bukan halaman aktif): label kecil "Lanjutkan di sini" text-maroon-600.
    - `value` di kanan judul.
    - Bawah panel: nama `auth.user.name` dan tombol Keluar (`Link href="/logout" method="post" as="button"`).
  - **Strip:** `overflow-x-auto` horizontal, item bulatan 36 px bernomor dan label pendek (Ringkasan, Gudep, Putra, Putri, Pembina, Berkas, Kirim). Item aktif di-`scrollIntoView({inline: 'center', block: 'nearest'})` saat mount.
  - Aksesibilitas sesuai Global Constraints.
- [ ] **Step 3: Implementasi `StepNav`.** Dua tombol: `Button variant="outline" size="xl"` dan `Button size="xl"` dengan `Link`, dibungkus `px-6 pb-8 pt-2 flex gap-3`. Tombol Lanjut berisi ikon `ArrowRight`.
- [ ] **Step 4: Cek.** Run: `npm run types:check && npx vp check`. Expected: lolos.
- [ ] **Step 5: Commit.** `git commit -m "Komponen StepTimeline dan StepNav untuk alur pembina"`

### Task 3: Layout dan halaman pembina memakai alur

**Files:**
- Modify: `resources/js/layouts/pembina-layout.tsx`
- Modify: `resources/js/pages/pembina/members/index.tsx`
- Modify: `resources/js/pages/pembina/documents/index.tsx`, `resources/js/pages/pembina/submit.tsx`
- Modify: `resources/js/pages/pembina/dashboard.tsx` (ikon langkah dari `stepIcon`)
- Modify: `resources/js/components/ui/sonner.tsx` (`mobileOffset` bottom kembali ke bawaan, karena menu bawah sudah tidak ada)

**Interfaces:**
- Consumes: `StepTimeline`, `StepNav`, `stepIcon` (Task 2).

- [ ] **Step 1: Layout.**
  - Hapus tautan menu di header desktop (brand saja) dan hapus menu bawah serta ekspor `pembinaNav` (cari pemakaiannya dengan grep, lalu hapus).
  - Struktur baru:
    - di bawah `md`: `<StepTimeline variant="strip" />` sticky top-0 z-30 di atas `main`;
    - kontainer `mx-auto max-w-5xl md:grid md:grid-cols-[minmax(0,1fr)_280px] md:gap-8 md:px-6`, berisi `main` (`pb-12`, bukan `pb-28`) lalu `<aside className="hidden md:block"><div className="sticky top-20">` berisi `<StepTimeline variant="vertical" />`.
- [ ] **Step 2: Halaman Anggota.**
  - Hapus `nav` tab.
  - `PembinaHeader` diberi judul `{ pa: 'Peserta Putra', pi: 'Peserta Putri', pembina: 'Pembina Pendamping' }[tab]` dan subtitle nama gudep.
  - Kurangi `-mt-10` bila perlu agar kartu pertama tetap menumpuk header.
  - Tambah `<StepNav />` di akhir.
- [ ] **Step 3:** Tambah `<StepNav />` di akhir halaman Dokumen dan Kirim.
- [ ] **Step 4: Cek.** Run: `npm run types:check && npx vp check --fix && npm run build`. Expected: lolos.
- [ ] **Step 5: Tes fitur.** Run: `php artisan test --compact tests/Feature/Domain`. Expected: PASS (`MemberManagementTest`, `DataLockTest`, `MemberImportTest` tidak berubah perilaku).
- [ ] **Step 6: Commit.** `git commit -m "Area pembina: timeline vertikal di kanan, strip langkah di ponsel, footer Kembali/Lanjut"`

### Task 4: Verifikasi visual, dokumentasi, dan pipeline penuh

**Files:**
- Modify: `docs/TASKS.md` (task baru `T9.1 Alur pembina per langkah + timeline`, dicentang beserta catatan), `docs/PANDUAN-PEMBINA.md` (navigasi timeline / strip langkah, hapus penyebutan menu Anggota/Dokumen/Kirim)
- Add: `docs/superpowers/specs/…-design.md`, `docs/superpowers/plans/…md`

- [ ] **Step 1: Browser 1440 px** (dev-login pembina demo, `npm run build`, server preview `app`):
  - Timeline di kanan.
  - Status Data gudep `done`, Putra `current` dengan nilai benar.
  - Klik **Lanjut** berurutan dari Putra sampai Kirim: setiap langkah aktif berpindah.
- [ ] **Step 2: Browser 375 px:**
  - Strip tampil di atas dan langkah aktif terlihat.
  - `document.documentElement.scrollWidth === innerWidth` di semua halaman pembina.
  - Notifikasi setelah simpan tidak tertutup.
  - Audit aksesibilitas lokal (label, nama tombol, kontrast) tanpa temuan baru.
- [ ] **Step 3: Pipeline penuh.** Run: `composer test && npm run check && npm run types:check`. Expected: semua lolos.
- [ ] **Step 4: Commit.** `git commit -m "T9.1: dokumentasi alur pembina dan timeline"`
