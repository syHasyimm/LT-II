# Formulir Pendaftaran (admin) dan ID Card Pembina — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Setelah gudep diverifikasi, admin bisa mengunduh Formulir Pendaftaran berkop, dan pembina bisa mengunduh ID Card gudepnya (A4, 9 kartu per lembar) yang nomor serinya dibuat otomatis saat verifikasi.

**Architecture:** Logika cetak ID Card per gudep di `Admin\GenerateController::print` dipindah ke satu service yang dipakai admin dan pembina. `ApproveGudep` men-dispatch batch `GenerateFileJob` yang sudah ada setelah transaksi selesai. Formulir dirender dompdf dari Blade baru saat diunduh; tidak ada file yang disimpan.

**Tech Stack:** Laravel 12 + Pest, barryvdh/laravel-dompdf, Inertia v3 + React/TS, Wayfinder (`@/routes/...`).

**Spec:** `docs/superpowers/specs/2026-11-20-formulir-dan-idcard-pembina-design.md`

## Global Constraints

- Pengguna memilih bekerja di `main` **tanpa commit**; langkah "Commit" di bawah diganti dengan "catat di ledger". Jangan commit.
- Pembina hanya menerima ID Card, bukan formulir dan bukan Sertifikat. Sertifikat tetap khusus admin.
- ID Card pembina: mode `sheet` (A4, 3 × 3 kartu dengan garis potong).
- Teks kop (persis):
  - `GERAKAN PRAMUKA`
  - `KWARTIR RANTING KEPENUHAN`
  - `Jalan Syech Abdul Wahab Rokan-Kota Tengah No. Tlp.- Kodepos: 28559`
  - `Email : kwartirranting03kepenuhan@gmail.com`
- Logo kop: `public/images/kop-kiri.png` (tunas kelapa) dan `public/images/kop-kanan.png` (lambang ungu), dipotong dari `C:\Users\hasyi\AppData\Local\Temp\claude\2225618a48174722\c90e9483-0ab5-4b81-a591-9e2518cd3893\images\1.png` (790 × 180 px).
- Gambar di PDF disisipkan sebagai data URI base64 (pola `RenderSubject`), karena `enable_remote = false`.
- Judul formulir: `Formulir Pendaftaran Perkemahan LT 2`.
- Teks halaman pembina saat kartu belum ada: `ID Card sedang disiapkan panitia.`

## Review Focus

1. **Pembina membuka unduhan ID Card sebelum gudep terverifikasi**, atau saat kartu belum dibuat → 404, tanpa error 500. Tes ada di Task 1.
2. **Template aktif hanya ada untuk sebagian peran** (mis. peserta saja) saat verifikasi → batch hanya berisi anggota yang perannya punya template, dan verifikasi tetap berhasil. Tes ada di Task 2.
3. **Tidak ada template ID Card sama sekali** → verifikasi berhasil, tanpa batch, tanpa exception. Tes ada di Task 2.
4. **Nama atau alamat berisi `&` atau `<`** dan kolom kosong (tanpa No. HP, tanpa `accompanies`, tanpa tanggal lahir) → formulir tetap ter-render, teks di-escape, kolom kosong tampil `-`. Tes ada di Task 3.
5. **Formulir diminta untuk gudep yang belum terverifikasi, atau oleh pembina** → 404 untuk admin pada status selain terverifikasi, 403 untuk pembina. Tes ada di Task 3.

---

### Task 1: Service cetak ID Card per gudep + unduhan pembina

**Files:**
- Create: `app/Services/Pdf/GudepCardPrinter.php`
- Modify: `app/Http/Controllers/Admin/GenerateController.php` (`print()` memakai service; pindahkan `mergeHtml()` ke service)
- Create: `app/Http/Controllers/Pembina/IdCardController.php` (invokable)
- Modify: `routes/pembina.php` (di luar grup `registration.open`): `Route::get('id-card', IdCardController::class)->name('id-card');`
- Modify: `app/Http/Controllers/Pembina/SubmissionController.php` (prop `idCardReady`)
- Modify: `resources/js/pages/pembina/submitted.tsx`
- Test: `tests/Feature/Domain/PembinaIdCardTest.php` (baru); `tests/Feature/Domain/GenerateTest.php` tetap lulus

**Interfaces:**
- Produces: `GudepCardPrinter::pdf(Gudep $gudep, TemplateType $type, string $mode = 'single'): ?\Barryvdh\DomPDF\PDF` — `null` bila belum ada `generated_files` untuk gudep itu atau tak ada template aktif untuk berkasnya.
- Produces: `GudepCardPrinter::hasFiles(Gudep $gudep, TemplateType $type): bool`.
- Produces: rute `pembina.id-card` (GET `/pembina/id-card`), nama file `id-card-{slug gudep}-a4.pdf`.

- [ ] **Step 1: Tulis tes `PembinaIdCardTest.php`** (`beforeEach`: `Storage::fake('local')`, `DefaultTemplates::install()`, admin)
  - `pembina mengunduh ID Card gudep terverifikasi dalam lembar A4`: gudep `Verified` `withOwner()`, `GenerateMemberFile` untuk pembina pemilik; GET `pembina.id-card` sebagai `$gudep->user` → `assertOk()`, content-type `application/pdf`, content-disposition berisi `-a4.pdf`.
  - `menolak unduhan ID Card sebelum gudep terverifikasi` (dataset Draft, Submitted, Revision) → `assertNotFound()`.
  - `menolak unduhan saat ID Card belum dibuat`: gudep Verified tanpa generated file → `assertNotFound()`.
  - `halaman terverifikasi memberi tahu kesiapan ID Card`: GET `pembina.submission.show` → `where('idCardReady', false)`; setelah `GenerateMemberFile` → `where('idCardReady', true)`.
  - `admin tidak memakai rute ID Card pembina`: admin GET `pembina.id-card` → `assertForbidden()` (middleware `role:pembina`).

- [ ] **Step 2: Jalankan, pastikan gagal**
  Run: `php artisan test --compact tests/Feature/Domain/PembinaIdCardTest.php`
  Expected: FAIL (rute `pembina.id-card` tidak ada).

- [ ] **Step 3: Buat `GudepCardPrinter`** dengan isi `print()` saat ini (kelompokkan per template aktif, gabung HTML bila >1 template) dan `mergeHtml()`; ubah `GenerateController::print()` menjadi `abort_if($pdf === null, 404, ...)` + `stream(...)` dengan nama file seperti sekarang.

- [ ] **Step 4: Buat `IdCardController`**: gudep milik pengguna; `abort_unless($gudep?->status === GudepStatus::Verified, 404)`; `$printer->pdf($gudep, TemplateType::IdCard, 'sheet')` → 404 bila `null`; `stream(Str::slug("ID Card {$gudep->name}").'-a4.pdf')`.

- [ ] **Step 5: `SubmissionController`**: tambah `'idCardReady' => $gudep->status === GudepStatus::Verified && $printer->hasFiles($gudep, TemplateType::IdCard)`.

- [ ] **Step 6: `submitted.tsx`**: prop `idCardReady: boolean`. Bila `verified`:
  - ubah kalimat menjadi `Selamat! Semua dokumen sudah disetujui panitia. Unduh ID Card peserta dan pembina di bawah ini.`;
  - `idCardReady` → tautan `<a href={idCard().url} target="_blank">` bergaya tombol emas `Unduh ID Card (A4)` di atas "Kembali ke Beranda" (import `idCard` dari `@/routes/pembina`);
  - belum siap → teks `ID Card sedang disiapkan panitia.`
  Jalankan `php artisan wayfinder:generate` bila rute belum muncul di `@/routes/pembina`.

- [ ] **Step 7: Jalankan tes, pastikan lulus**
  Run: `php artisan test --compact tests/Feature/Domain/PembinaIdCardTest.php tests/Feature/Domain/GenerateTest.php tests/Feature/Domain/SubmissionTest.php`
  Expected: PASS. Lalu `npm run types:check` dan `npx vp check --fix resources/js/pages/pembina/submitted.tsx` tanpa error.

- [ ] **Step 8: Catat di ledger** (tanpa commit).

---

### Task 2: ID Card otomatis saat verifikasi

**Files:**
- Create: `app/Actions/Generate/QueueGudepIdCards.php`
- Modify: `app/Actions/Verification/ApproveGudep.php`
- Modify: `app/Http/Controllers/Admin/VerificationController.php` (pesan toast setelah verifikasi menyebut ID Card sedang dibuat bila batch terkirim)
- Test: `tests/Feature/Domain/VerificationTest.php`

**Interfaces:**
- Produces: `QueueGudepIdCards::handle(Gudep $gudep, ?User $by = null): int` — jumlah anggota yang dikirim ke batch (0 bila tak ada template aktif untuk peran mana pun; tidak men-dispatch batch kosong).
- Consumes: `GenerateFileJob(int $memberId, TemplateType $type, ?int $userId)`, `Template::activeFor(TemplateType, MemberRole)`.

- [ ] **Step 1: Tulis tes di `VerificationTest.php`** (pakai `Bus::fake()`)
  - `membuat ID Card otomatis untuk semua anggota saat gudep diverifikasi`: `DefaultTemplates::install()`, `submittedGudep(DocumentStatus::Approved)`, POST approve → `Bus::assertBatched(fn (PendingBatch $batch) => $batch->jobs->count() === $gudep->members()->count() && $batch->jobs->every(fn ($job) => $job instanceof GenerateFileJob))`.
  - `hanya mengantrikan anggota yang perannya punya template aktif`: install, lalu nonaktifkan/hapus template ID Card untuk pembina (sesuaikan dengan kolom target di `templates`) → batch berisi 16 job (peserta saja).
  - `tetap memverifikasi tanpa template ID Card`: tanpa `DefaultTemplates::install()` → status `Verified`, `Bus::assertNothingBatched()`.

- [ ] **Step 2: Jalankan, pastikan gagal**
  Run: `php artisan test --compact tests/Feature/Domain/VerificationTest.php`
  Expected: FAIL (tidak ada batch).

- [ ] **Step 3: Implement `QueueGudepIdCards`** (filter anggota per `Template::activeFor(TemplateType::IdCard, $role) !== null`; `Bus::batch(...)->name("ID Card · {$gudep->name}")->allowFailures()->dispatch()`; `ActivityLog::record('generate.started', ...)` seperti `GenerateController::store`). Panggil dari `ApproveGudep::handle()` **setelah** `DB::transaction` selesai (bukan di dalamnya), dengan `$admin` sebagai `$by`.

- [ ] **Step 4: Toast** di `VerificationController::approve`: bila hasil > 0, pesan sukses ditambah ` ID Card sedang dibuat.`

- [ ] **Step 5: Jalankan tes domain**
  Run: `php artisan test --compact tests/Feature/Domain`
  Expected: PASS (termasuk `RegistrationFlowTest`; bila ia ikut men-generate karena template terpasang, sesuaikan dengan `Bus::fake()` atau biarkan sync bila cepat).

- [ ] **Step 6: Catat di ledger.**

---

### Task 3: Formulir Pendaftaran berkop untuk admin

**Files:**
- Create: `public/images/kop-kiri.png`, `public/images/kop-kanan.png` (potong dengan GD dari gambar sumber; lihat Global Constraints)
- Modify: `config/camp.php` — kunci `kop` berisi `title`, `subtitle`, `address`, `email`, `logo_left`, `logo_right` (nilai dari Global Constraints; path relatif `public/`)
- Create: `app/Services/Pdf/RegistrationForm.php`
- Create: `resources/views/pdf/formulir.blade.php`
- Create: `app/Http/Controllers/Admin/RegistrationFormController.php` (invokable)
- Modify: `routes/admin.php`: `Route::get('gudep/{gudep}/formulir', RegistrationFormController::class)->name('gudeps.form');`
- Modify: `resources/js/pages/admin/gudeps/show.tsx` (tombol di samping `VerifyButton`)
- Test: `tests/Feature/Domain/RegistrationFormTest.php` (baru)

**Interfaces:**
- Produces: `RegistrationForm::html(Gudep $gudep): string`, `RegistrationForm::pdf(Gudep $gudep): \Barryvdh\DomPDF\PDF` (A4 portrait).
- Produces: rute `admin.gudeps.form`, nama file `formulir-pendaftaran-{slug gudep}.pdf`.

- [ ] **Step 1: Tulis tes `RegistrationFormTest.php`**
  - `admin mengunduh formulir pendaftaran gudep terverifikasi`: gudep `Verified` `complete()` → GET `admin.gudeps.form` → `assertOk()`, content-type `application/pdf`, content-disposition berisi `formulir-pendaftaran-`.
  - `formulir memuat kop, identitas gudep, dan semua anggota`: `RegistrationForm::html()` berisi `KWARTIR RANTING KEPENUHAN`, `kwartirranting03kepenuhan@gmail.com`, `Formulir Pendaftaran Perkemahan LT 2`, NPSN gudep, nama setiap anggota, `data:image/png;base64,` (logo).
  - `formulir aman untuk karakter khusus dan kolom kosong`: anggota bernama `Siti & <b>Ayu</b>`, pembina tanpa `phone` dan tanpa `accompanies` → html berisi `Siti &amp; &lt;b&gt;Ayu&lt;/b&gt;` dan tidak berisi `<b>Ayu</b>`; PDF ter-render tanpa exception.
  - `menolak formulir untuk gudep belum terverifikasi` (dataset Draft, Submitted, Revision) → admin `assertNotFound()`.
  - `pembina tidak dapat mengunduh formulir` → `assertForbidden()`.

- [ ] **Step 2: Jalankan, pastikan gagal**
  Run: `php artisan test --compact tests/Feature/Domain/RegistrationFormTest.php`
  Expected: FAIL (rute/kelas tidak ada).

- [ ] **Step 3: Potong logo** dengan skrip PHP GD sekali jalan (koordinat ditentukan dengan memeriksa gambar; kotak logo kiri ≈ x 70–130, logo kanan ≈ x 650–750, y ≈ 35–150; sisakan latar putih, simpan PNG). Pastikan kedua file tampil benar dengan membuka gambarnya.

- [ ] **Step 4: Implement `RegistrationForm` + `formulir.blade.php`**
  - Kop: tabel 3 kolom (logo kiri 2,2 cm · teks tengah · logo kanan 2,2 cm), `GERAKAN PRAMUKA` dan `KWARTIR RANTING KEPENUHAN` tebal besar, alamat dan email kecil, lalu garis ganda (border-bottom tebal + tipis).
  - Identitas gudep (nama, nomor gudep, NPSN, instansi, penanggung jawab, alamat, pemilik akun), tabel Peserta Putra dan Peserta Putri (No, Nama, Tanggal lahir `d/m/Y`, No. KTA), tabel Pembina pendamping (No, Nama, L/P, Tanggal lahir, No. KTA, No. HP, Mendampingi), lalu `Dikirim … · Diverifikasi …` (format `j F Y`, zona `camp.timezone`).
  - Nilai kosong → `-`. Font DejaVu Sans (bawaan dompdf), warna judul marun `#7A1219`, garis tabel abu; tetap terbaca saat dicetak hitam-putih.

- [ ] **Step 5: Controller + rute**: `abort_unless($gudep->status === GudepStatus::Verified, 404)`; `stream('formulir-pendaftaran-'.Str::slug($gudep->name).'.pdf')`; catat `ActivityLog::record('form.downloaded', ...)` hanya bila pola `ActivityLog` mendukung subjek gudep (lihat `ExportController`), jika tidak lewati.

- [ ] **Step 6: Tombol admin**: di `show.tsx`, bila `gudep.status === 'verified'`, tampilkan `<a href={form(gudep.id).url} target="_blank">` bergaya tombol outline dengan ikon `FileDown` dan label `Unduh formulir`, di dalam `div` yang sama dengan `VerifyButton`. Jalankan `php artisan wayfinder:generate` bila perlu.

- [ ] **Step 7: Jalankan tes + cek**
  Run: `php artisan test --compact tests/Feature/Domain/RegistrationFormTest.php`, `npm run types:check`, `npx vp check --fix resources/js/pages/admin/gudeps/show.tsx`
  Expected: PASS / tanpa error. Simpan satu PDF contoh ke scratchpad lewat tinker (`RegistrationForm::pdf(Gudep::where('status','verified')->first())->save(...)`) dan baca hasilnya (Read PDF) untuk memeriksa kop dan tabel.

- [ ] **Step 8: Catat di ledger.**

---

### Task 4: Dokumentasi

**Files:**
- Modify: `PRD-Pendaftaran-Perkemahan-LT2.md` (BR-10, FR-GEN-04, tambah baris FR-VER untuk formulir)
- Modify: `docs/PANDUAN-PEMBINA.md`, `docs/PANDUAN-ADMIN.md`, `docs/TASKS.md`

- [ ] **Step 1: Perbarui teks**
  - BR-10: `ID Card gudep terverifikasi dapat diunduh admin dan pembina gudep itu (lembar A4). Sertifikat hanya dapat diakses admin.`
  - FR-GEN-04: kriteria `Admin; pembina hanya ID Card gudepnya sendiri setelah terverifikasi.`
  - Tambah FR-VER: `Formulir Pendaftaran (PDF berkop Kwartir Ranting Kepenuhan) dapat diunduh admin setelah gudep terverifikasi.`
  - Panduan pembina: setelah terverifikasi, tombol **Unduh ID Card (A4)**; bila belum ada, tunggu panitia.
  - Panduan admin: verifikasi otomatis mengantrikan ID Card (butuh template aktif); tombol **Unduh formulir**; logo kop bisa diganti dengan menimpa `public/images/kop-kiri.png` dan `kop-kanan.png`.
  - TASKS: butir T9.4 dengan tautan spec.

- [ ] **Step 2: Seluruh tes**
  Run: `php artisan test --compact`
  Expected: PASS semua.

- [ ] **Step 3: Catat di ledger.**
