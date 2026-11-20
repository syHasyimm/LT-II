<?php

use App\Services\Files\PrivateFileService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * KTA, Surat Izin Orang Tua, dan Surat Keterangan Sehat menjadi satu berkas
 * gabungan per gudep. Dokumen per anggota yang lama dihapus beserta file-nya
 * (tahap dev); file yang masih dirujuk dokumen lain tidak disentuh.
 */
return new class extends Migration
{
    /** @var array<string, array{label: string, required_for: list<string>}> */
    private const ORIGINAL = [
        'kta' => ['label' => 'Kartu Tanda Anggota (KTA)', 'required_for' => ['peserta', 'pembina_pendamping']],
        'izin_ortu' => ['label' => 'Surat Izin Orang Tua', 'required_for' => ['peserta']],
        'kesehatan' => ['label' => 'Surat Keterangan Sehat', 'required_for' => ['peserta', 'pembina_pendamping']],
    ];

    private const MERGED_LABELS = [
        'kta' => 'KTA seluruh anggota (digabung 1 PDF)',
        'izin_ortu' => 'Surat Izin Orang Tua seluruh peserta (digabung 1 PDF)',
        'kesehatan' => 'Surat Keterangan Sehat seluruh anggota (digabung 1 PDF)',
    ];

    public function up(): void
    {
        // Perubahan data dalam satu transaksi (MySQL tidak membungkus migrasi
        // otomatis); file baru dihapus setelah transaksi berhasil, sehingga
        // kegagalan tidak meninggalkan baris yang menunjuk file yang hilang.
        $orphans = DB::transaction(function () {
            foreach (self::MERGED_LABELS as $code => $label) {
                DB::table('document_types')->where('code', $code)->update([
                    'scope' => 'gudep',
                    'required_for' => null,
                    'label' => $label,
                    'updated_at' => now(),
                ]);
            }

            $paths = DB::table('documents')->whereNotNull('member_id')->pluck('path')->filter()->unique();
            DB::table('documents')->whereNotNull('member_id')->delete();

            $stillUsed = DB::table('documents')->whereIn('path', $paths)->pluck('path')->all();

            return $paths->diff($stillUsed);
        });

        $files = app(PrivateFileService::class);

        foreach ($orphans as $path) {
            $files->delete($path);
        }
    }

    public function down(): void
    {
        foreach (self::ORIGINAL as $code => $original) {
            DB::table('document_types')->where('code', $code)->update([
                'scope' => 'member',
                'required_for' => json_encode($original['required_for']),
                'label' => $original['label'],
                'updated_at' => now(),
            ]);
        }
    }
};
