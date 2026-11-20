# Dokumen Gabungan per Gudep — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** KTA, Surat Izin Orang Tua, dan Surat Keterangan Sehat diunggah sebagai satu berkas gabungan per gudep, bukan per anggota.

**Architecture:** Sistem sudah punya jalur dokumen `scope = gudep` (Surat Kesiapan Sekolah) yang menangani unggah, unggah ulang, verifikasi, dan daftar periksa. Satu migrasi memindah tiga jenis dokumen anggota ke `scope = gudep` dan menghapus dokumen per anggota lama; `DocumentChecklist` otomatis tidak lagi menghasilkan slot per anggota. Sisanya penyesuaian tampilan (pembina, admin), ekspor Excel, data demo, tes, dan dokumentasi.

**Tech Stack:** Laravel 12 + Pest, Inertia v3 + React/TypeScript, Tailwind, SimpleXLSXGen.

**Spec:** `docs/superpowers/specs/2026-11-20-dokumen-gabungan-design.md`

## Global Constraints

- Satu kotak = tepat satu berkas per jenis per gudep (unggah ulang mengarsipkan yang lama, perilaku yang sudah ada).
- Batas unggah **10 MB** (`config/camp.php` `max_upload_kb = 10240`), berlaku untuk semua dokumen. Format tetap PDF/JPG/PNG.
- Label jenis dokumen (persis):
  - `kta` → `KTA seluruh anggota (digabung 1 PDF)`, short `KTA`
  - `izin_ortu` → `Surat Izin Orang Tua seluruh peserta (digabung 1 PDF)`, short `Izin`
  - `kesehatan` → `Surat Keterangan Sehat seluruh anggota (digabung 1 PDF)`, short `Sehat`
  - `kesiapan_sekolah` tidak berubah.
- Urutan `sort` tetap: kta 1, izin_ortu 2, kesehatan 3, kesiapan_sekolah 4.
- Migrasi menghapus semua baris `documents` ber-`member_id` (aktif maupun arsip) beserta file-nya. `down()` hanya mengembalikan scope/label/required_for, tidak memulihkan data.
- Pas foto tetap per anggota (kolom `members.photo_path`), tidak berubah.
- Semua teks UI berbahasa Indonesia, mengikuti gaya halaman yang ada.

## Review Focus

1. **File demo dipakai bersama.** `DemoDataSeeder` memberi semua dokumen path yang sama (`self::PDF`). Migrasi hanya boleh menghapus file yang tidak lagi dirujuk baris `documents` mana pun yang tersisa — kalau tidak, pratinjau Surat Kesiapan Sekolah ikut rusak. Tes ada di Task 1.
2. **Unggah dengan `member_id` ke jenis gabungan** (klien lama/form usang) harus ditolak dengan pesan "… adalah dokumen gudep, bukan per anggota.", bukan tersimpan diam-diam. Tes ada di Task 1.
3. **File 2–10 MB** sekarang harus diterima, dan > 10 MB ditolak dengan pesan "Ukuran dokumen maksimal 10 MB.". Server produksi (`upload_max_filesize` 8M di DEPLOY.md) akan menolak lebih dulu dengan 413 bila tidak dinaikkan — Task 5.
4. **Panel verifikasi admin tanpa dokumen anggota:** mengetuk anggota tidak boleh diam saja (dulu memilih slot dokumen non-foto pertama, kini tidak ada). Harus memilih slot pas foto. Diperiksa di Task 4.
5. **Daftar periksa sebelum kirim** tidak boleh menampilkan butir "Semua dokumen anggota diunggah" yang selalu hijau; tiap dokumen gabungan muncul sebagai butir sendiri dengan tombol Unggah. Tes ada di Task 1.

---

### Task 1: Migrasi jenis dokumen, batas 10 MB, dan logika kelengkapan

**Files:**
- Create: `database/migrations/2026_11_20_000001_merge_member_documents_into_gudep.php`
- Modify: `config/camp.php:26` (`max_upload_kb` → `10240`)
- Modify: `app/Services/RegistrationProgress.php:154-163` (hapus butir `dokumen`)
- Modify: `app/Http/Controllers/Pembina/DocumentController.php:88-91` (tambah `code` pada `gudepDocuments[].type`)
- Modify: `database/seeders/DemoDataSeeder.php:84-107`
- Test: `tests/Feature/Domain/DocumentUploadTest.php`, `SubmissionTest.php`, `VerificationTest.php`, `RegistrationFlowTest.php`, `AuditAndNotificationTest.php`

**Interfaces:**
- Produces: `gudepDocuments: list<{type: {id: int, code: string, label: string}, document: DocumentData|null}>` (berisi 4 item, urut `sort`); `columns` kini `[]`; `counts.total` = 4 untuk setiap gudep.
- Produces: `RegistrationProgress::checklistItems()` tanpa key `dokumen`; key `gudep_kta`, `gudep_izin_ortu`, `gudep_kesehatan`, `gudep_kesiapan_sekolah` ada.

- [ ] **Step 1: Ubah tes `DocumentUploadTest.php`**

  - `memiliki jenis dokumen bawaan sesuai PRD`: urutan code tetap; tambahkan `expect(DocumentType::query()->pluck('scope')->unique()->all())->toBe(['gudep'])` dan `expect(docType('kta')->label)->toBe('KTA seluruh anggota (digabung 1 PDF)')`.
  - `menampilkan matriks kelengkapan dokumen` → ganti nama `menampilkan empat dokumen gudep dan daftar anggota`: `has('rows', 17)`, `has('columns', 0)`, `has('gudepDocuments', 4)`, `where('gudepDocuments.0.type.code', 'kta')`, `where('counts.total', 4)`, `where('counts.missing', 4)`.
  - `mengunggah dokumen anggota ke disk privat sebagai pending` → unggah `kta` **tanpa** `member_id`; `expect($document->member_id)->toBeNull()`; asersi path tetap.
  - Ganti `menolak izin orang tua untuk pembina` dan `menolak unggah untuk anggota gudep lain` dengan satu tes `menolak dokumen gabungan yang dikirim dengan member_id`, dataset `kta`, `izin_ortu`, `kesehatan`: POST dengan `member_id` anggota sendiri → `assertSessionHasErrors('member_id')`, `Document::count()` 0.
  - Dataset `terlalu besar`: `pdf('besar.pdf', 10241)`; ganti nama tes menjadi `… dan file di atas 10 MB`.
  - Tambah `menerima file 9 MB`: `pdf('kta.pdf', 9000)` untuk `kta` → `assertSessionHasNoErrors()`.
  - `mengarsipkan dokumen lama saat diganti…`: buat dokumen lama `type('kta')` tanpa `member_id`, POST tanpa `member_id`.
  - `menghitung dokumen diunggah, belum, dan ditolak`: dokumen `kta` (pending) dan `kesehatan` (rejected) tanpa `member_id` → `total 4, uploaded 1, rejected 1, missing 2`.
  - Tambah `migrasi menghapus dokumen per anggota beserta filenya kecuali file yang masih dirujuk`:
    ```php
    $gudep = Gudep::factory()->withOwner()->create();
    $member = $gudep->pembina()->sole();
    Storage::disk('local')->put('documents/x/lama.pdf', '%PDF');
    Storage::disk('local')->put('documents/x/bersama.pdf', '%PDF');
    $old = Document::factory()->for($gudep)->type('kta')->create(['member_id' => $member->id, 'path' => 'documents/x/lama.pdf']);
    $archived = Document::factory()->for($gudep)->type('kesehatan')->create(['member_id' => $member->id, 'path' => 'documents/x/bersama.pdf', 'archived_at' => now()]);
    $kept = Document::factory()->for($gudep)->type('kesiapan_sekolah')->create(['path' => 'documents/x/bersama.pdf']);

    (require database_path('migrations/2026_11_20_000001_merge_member_documents_into_gudep.php'))->up();

    expect(Document::query()->pluck('id')->all())->toBe([$kept->id]);
    Storage::disk('local')->assertMissing('documents/x/lama.pdf');
    Storage::disk('local')->assertExists('documents/x/bersama.pdf');
    ```
    (Pastikan disk `local` adalah disk yang dipakai `PrivateFileService`; bila berbeda, fake disk itu.)

- [ ] **Step 2: Ubah tes lain yang membuat dokumen per anggota**

  - `SubmissionTest.php` `readyGudep()` dan `VerificationTest.php` `submittedGudep()`: ganti loop anggota × `memberTypes` dengan satu dokumen per `DocumentType` (tanpa `member_id`), status mengikuti parameter yang ada; hapus pembuatan `kesiapan_sekolah` terpisah agar tidak dobel.
  - `SubmissionTest.php`: tambah tes `daftar periksa menampilkan tiap dokumen gabungan` → gudep `withOwner()`; `collect(items)->pluck('key')` berisi `gudep_kta`, `gudep_izin_ortu`, `gudep_kesehatan`, `gudep_kesiapan_sekolah` dan tidak berisi `dokumen`; butir `gudep_kta` punya `ok false`, `action 'Unggah'`, `detail 'Belum diunggah'`.
  - `RegistrationFlowTest.php:85-134`: unggah empat dokumen gudep tanpa `member_id`; dokumen yang ditolak lalu diunggah ulang adalah salah satunya (tanpa `member_id`); asersi akhir `->toBe(4)`.
  - `AuditAndNotificationTest.php:46`: buat dokumen `type('kta')` tanpa `member_id`.

- [ ] **Step 3: Jalankan tes, pastikan gagal**

  Run: `php artisan test --compact tests/Feature/Domain/DocumentUploadTest.php tests/Feature/Domain/SubmissionTest.php`
  Expected: FAIL (mis. `columns` masih 3, `counts.total` 51, migrasi tidak ditemukan).

- [ ] **Step 4: Tulis migrasi `2026_11_20_000001_merge_member_documents_into_gudep.php`**

  `up()`: (1) update tiga baris `document_types` ke `scope = 'gudep'`, `required_for = null`, label sesuai Global Constraints; (2) ambil `path` semua baris `documents` dengan `member_id` tidak null, hapus baris-baris itu, lalu untuk tiap path unik yang **tidak** lagi dirujuk baris `documents` mana pun, panggil `app(PrivateFileService::class)->delete($path)`. Pakai `DB::table`, bukan model.
  `down()`: kembalikan scope `member`, `required_for` (`kta`/`kesehatan`: `['peserta','pembina_pendamping']`, `izin_ortu`: `['peserta']`) dan label asli (`Kartu Tanda Anggota (KTA)`, `Surat Izin Orang Tua`, `Surat Keterangan Sehat`).

- [ ] **Step 5: Ubah config, `RegistrationProgress`, `DocumentController`, seeder**

  - `config/camp.php`: `'max_upload_kb' => 10240`.
  - `RegistrationProgress::checklistItems()`: hapus blok `$memberSlots`/butir `dokumen` (baris 154-163). Loop `gudepTypes()` yang sudah ada menangani keempatnya.
  - `DocumentController::matrix()`: `'type' => ['id' => $type->id, 'code' => $type->code, 'label' => $type->label]`.
  - `DemoDataSeeder`: hapus loop dokumen per anggota; buat satu dokumen per `DocumentType` (tanpa `member_id`, `original_name` `"{$type->code}.pdf"`, status `$documentStatus`, path `self::PDF`); pada status Revision, tolak dokumen `kesehatan` gudep itu (tanpa `limit(2)`) dengan catatan yang sama.

- [ ] **Step 6: Jalankan tes domain, pastikan lulus**

  Run: `php artisan test --compact tests/Feature/Domain`
  Expected: PASS (kecuali `ExportTest` boleh tetap lulus; perubahannya di Task 2).

- [ ] **Step 7: Verifikasi seeder**

  Run: `php artisan migrate:fresh --seed`
  Expected: tanpa error.

- [ ] **Step 8: Commit**

  ```bash
  git add database/migrations/2026_11_20_000001_merge_member_documents_into_gudep.php config/camp.php app/Services/RegistrationProgress.php app/Http/Controllers/Pembina/DocumentController.php database/seeders/DemoDataSeeder.php tests/Feature/Domain/DocumentUploadTest.php tests/Feature/Domain/SubmissionTest.php tests/Feature/Domain/VerificationTest.php tests/Feature/Domain/RegistrationFlowTest.php tests/Feature/Domain/AuditAndNotificationTest.php
  git commit -m "Dokumen KTA, izin, dan sehat menjadi satu berkas gabungan per gudep; batas unggah 10 MB"
  ```
  (Perhatikan: beberapa file ini sudah punya perubahan belum di-commit milik pengguna; tanyakan pengguna sebelum commit bila diff memuat perubahan di luar task ini.)

---

### Task 2: Ekspor Excel — status dokumen di lembar Gudep

**Files:**
- Modify: `app/Services/Export/RegistrationExport.php`
- Test: `tests/Feature/Domain/ExportTest.php`

**Interfaces:**
- Consumes: `DocumentChecklist::gudepTypes()`, `documentFor(null, $type)` (sudah ada).

- [ ] **Step 1: Ubah tes `mengekspor gudep dan anggota ke Excel dengan status dokumen`**

  - Header lembar Gudep (`$gudeps[0]`) berisi `'KTA', 'Izin', 'Sehat', 'Kesiapan'`; nilai baris gudep di kolom `KTA` (`array_search('KTA', $gudeps[0], true)`) adalah `'Belum diunggah'`.
  - Buat dokumen `type('kta')` disetujui untuk gudep itu → kolom `KTA` bernilai `'Disetujui'` (label `DocumentStatus::Approved->label()`).
  - Header lembar Anggota **tidak** berisi `'Status dokumen'`, `'KTA'`, `'Izin'`, `'Sehat'`; hapus asersi `belum diunggah` pada baris Budi. Kolom lain (indeks 2, 3, 6, 8) tetap.

- [ ] **Step 2: Jalankan, pastikan gagal**

  Run: `php artisan test --compact tests/Feature/Domain/ExportTest.php`
  Expected: FAIL (header Gudep belum berisi `KTA`).

- [ ] **Step 3: Ubah `RegistrationExport::workbook()`**

  - Lembar Gudep: setelah `'Belum diunggah'` sisipkan satu kolom per `gudepTypes()` (header `short_label`), nilainya `documentFor(null, $type)?->status->label() ?? 'Belum diunggah'`. Ambil daftar tipe sekali di luar loop (`DocumentType::query()->orderBy('sort')->get()->filter->isGudepScope()`), sesuaikan array lebar kolom (lebar 16 per kolom baru).
  - Lembar Anggota: hapus kolom per jenis dokumen dan kolom `'Status dokumen'` beserta `$required/$statuses`; hapus `summary()` bila tak terpakai lagi; sesuaikan `$widths`.

- [ ] **Step 4: Jalankan, pastikan lulus**

  Run: `php artisan test --compact tests/Feature/Domain/ExportTest.php`
  Expected: PASS

- [ ] **Step 5: Commit**

  ```bash
  git add app/Services/Export/RegistrationExport.php tests/Feature/Domain/ExportTest.php
  git commit -m "Ekspor: status dokumen gabungan di lembar Gudep"
  ```

---

### Task 3: Halaman Foto & Dokumen pembina

**Files:**
- Modify: `resources/js/pages/pembina/documents/index.tsx`
- Modify: `resources/js/types/documents.ts` (`GudepDocument.type` tambah `code: string`)
- Delete: `resources/js/components/documents/doc-cell.tsx` (hanya dipakai halaman ini)

**Interfaces:**
- Consumes: prop `gudepDocuments` (Task 1, kini ber-`code`), `rows`, `counts`, `editable`, `maxUploadMb`. Prop `columns` tidak dipakai lagi di halaman ini (boleh dihapus dari `Props`).

- [ ] **Step 1: Kartu dokumen dengan petunjuk**

  Pertahankan kartu yang ada untuk tiap `gudepDocuments`; di bawah judul tampilkan petunjuk dari peta `code → teks` (bila belum ada berkas; bila ada, tampilkan nama file · ukuran seperti sekarang, petunjuk di baris kedua):
  - `kta`: `Pindai KTA semua peserta dan pembina pendamping, gabungkan jadi satu PDF.`
  - `izin_ortu`: `Surat izin orang tua setiap peserta, digabung jadi satu PDF.`
  - `kesehatan`: `Surat keterangan sehat semua peserta dan pembina pendamping, digabung jadi satu PDF.`
  - `kesiapan_sekolah`: `Surat dari sekolah atau instansi yang menyatakan kesiapan mengikuti kegiatan.`
  `UploadDialog` dipanggil dengan `memberId: null`, `ownerName: 'Dokumen gudep'` seperti kartu sekarang.

- [ ] **Step 2: Ganti matriks dengan daftar pas foto**

  Judul `Pas foto anggota`. Satu baris per `rows[]`: nama lengkap (bukan `short_name`), `role_label`, dan di kanan tautan bulat yang ada (✓ / + ke `editMember(row.member.id)`, `aria-label` sama). Baris pembina tetap berlatar `bg-gold-100/25`. Kosong → pesan yang ada. Hapus legenda ✓/✕/—; ganti dengan `Ketuk + untuk mengunggah pas foto di data anggota.`

- [ ] **Step 3: Cek tipe dan lint**

  Run: `npm run types:check` lalu `npm run check`
  Expected: tanpa error.

- [ ] **Step 4: Periksa di browser**

  Login sebagai pembina demo, buka Foto & Dokumen: 4 kartu dengan petunjuk, daftar pas foto, unggah PDF 3–9 MB ke kartu KTA berhasil dan statusnya "Menunggu". Periksa juga lebar ponsel (375 px): tanpa scroll horizontal.

- [ ] **Step 5: Commit**

  ```bash
  git add resources/js/pages/pembina/documents/index.tsx resources/js/types/documents.ts resources/js/components/documents/doc-cell.tsx
  git commit -m "Halaman Foto & Dokumen: empat kartu berkas gabungan dan daftar pas foto"
  ```

---

### Task 4: Panel verifikasi admin

**Files:**
- Modify: `resources/js/pages/admin/gudeps/show.tsx`

**Interfaces:**
- Consumes: `columns` (kini `[]`), `rows`, `gudepDocuments` (Task 1).

- [ ] **Step 1: Sesuaikan perilaku tab dan pilihan anggota**

  - Label tab `gudep` → `Berkas`; urutan tab menjadi `gudep, pa, pi, pembina` sehingga Berkas di kiri.
  - Klik baris anggota: pilih slot non-foto pertama milik anggota itu, **jatuh ke slot `photo`** bila tidak ada (sekarang `select(undefined)` tidak melakukan apa pun).
  - `firstPending` tetap; dengan data baru ia memilih dokumen gudep, sehingga tab awal = Berkas.

- [ ] **Step 2: Cek tipe dan lint**

  Run: `npm run types:check` lalu `npm run check`
  Expected: tanpa error.

- [ ] **Step 3: Periksa di browser**

  Login admin, buka gudep demo berstatus Dikirim: tab Berkas aktif berisi 4 dokumen; setujui/tolak satu berkas berjalan; tab Putra → klik anggota menampilkan pas fotonya.

- [ ] **Step 4: Commit**

  ```bash
  git add resources/js/pages/admin/gudeps/show.tsx
  git commit -m "Panel verifikasi: tab Berkas untuk dokumen gabungan, anggota membuka pas foto"
  ```

---

### Task 5: Dokumentasi

**Files:**
- Modify: `PRD-Pendaftaran-Perkemahan-LT2.md` (BR-05, FR-DOC-01, FR-DOC-02, baris 169 diagram bila perlu)
- Modify: `docs/PANDUAN-PEMBINA.md` (baris 10-15, 85), `docs/PANDUAN-ADMIN.md` (bagian verifikasi dokumen)
- Modify: `README.md:108`, `docs/DEPLOY.md:12`, `docs/UAT.md:38`, `docs/TASKS.md` (tambahkan butir perubahan ini)

- [ ] **Step 1: Perbarui teks**

  - BR-05: KTA, Izin Orang Tua, dan Keterangan Sehat diunggah **per gudep sebagai satu berkas gabungan** (KTA dan sehat mencakup pembina pendamping; izin hanya peserta); Kesiapan Sekolah per gudep.
  - FR-DOC-01: empat dokumen per gudep, maksimal **10 MB**. FR-DOC-02: kelengkapan = empat dokumen gudep + pas foto per anggota.
  - Panduan pembina: tabel dokumen jadi "digabung 1 PDF untuk seluruh …", tips menggabungkan PDF dari ponsel (mis. fitur pindai/gabung di aplikasi pemindai), batas 10 MB.
  - Panduan admin: satu berkas per jenis; tolak bila ada halaman kurang/buram, sebut nama anggota di catatan.
  - README: batas unggah 10 MB. DEPLOY: `upload_max_filesize = 12M`, `post_max_size = 16M`, dan catatan `client_max_body_size 16M` Nginx. UAT P6: matriks → empat kartu, batas 10 MB.

- [ ] **Step 2: Jalankan seluruh tes**

  Run: `php artisan test --compact`
  Expected: PASS semua.

- [ ] **Step 3: Commit**

  ```bash
  git add PRD-Pendaftaran-Perkemahan-LT2.md docs/PANDUAN-PEMBINA.md docs/PANDUAN-ADMIN.md README.md docs/DEPLOY.md docs/UAT.md docs/TASKS.md
  git commit -m "Dokumentasi: dokumen gabungan per gudep dan batas unggah 10 MB"
  ```
