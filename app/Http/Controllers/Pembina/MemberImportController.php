<?php

namespace App\Http\Controllers\Pembina;

use App\Actions\Gudep\ImportPeserta;
use App\Http\Controllers\Controller;
use App\Models\Gudep;
use App\Models\User;
use App\Services\Import\SpreadsheetReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Shuchkin\SimpleXLSXGen;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Impor peserta dari Excel/CSV beserta template unduhannya (FR-GDP-06). */
class MemberImportController extends Controller
{
    public function template(): StreamedResponse
    {
        $header = fn (string $label) => '<style bgcolor="#7A1219" color="#FFFFFF"><b>'.$label.'</b></style>';
        $max = (int) config('camp.peserta_per_regu');

        $xlsx = SimpleXLSXGen::create('Template Impor Peserta')
            ->addSheet([[$header('Regu'), $header('Nama lengkap'), $header('Tanggal lahir'), $header('Nomor KTA')]], 'Peserta')
            ->freezePanes('A2')
            ->setColWidth(1, 12)
            ->setColWidth(2, 34)
            ->setColWidth(3, 16)
            ->setColWidth(4, 20)
            ->addSheet([
                ['<b>Petunjuk impor peserta</b>'],
                [''],
                ['1. Isi sheet "Peserta" mulai baris ke-2, satu baris per peserta. Jangan ubah judul kolom.'],
                ['2. Regu: tulis Putra atau Putri (boleh juga Pa / Pi).'],
                ['3. Tanggal lahir: format hari/bulan/tahun, misalnya 15/03/2013.'],
                ['4. Nomor KTA: sesuai Kartu Tanda Anggota, misalnya 12.04.041.0001.'],
                ["5. Kuota tiap regu {$max} peserta, termasuk yang sudah terdaftar. Baris yang melebihi kuota dilewati."],
                ['6. Pas foto dan dokumen tetap diunggah satu per satu setelah impor.'],
                ['7. Simpan sebagai .xlsx (atau .csv), lalu unggah di halaman Anggota → Impor dari Excel.'],
                [''],
                ['<b>Contoh</b>'],
                ['<b>Regu</b>', '<b>Nama lengkap</b>', '<b>Tanggal lahir</b>', '<b>Nomor KTA</b>'],
                ['Putra', 'Ahmad Fauzan', SimpleXLSXGen::raw('15/03/2013'), SimpleXLSXGen::raw('12.04.041.0101')],
                ['Putri', 'Nadia Putri Lestari', SimpleXLSXGen::raw('02/11/2012'), SimpleXLSXGen::raw('12.04.041.0102')],
            ], 'Petunjuk')
            ->setColWidth(1, 12)
            ->setColWidth(2, 34)
            ->setColWidth(3, 16)
            ->setColWidth(4, 20);

        $content = (string) $xlsx;

        return response()->streamDownload(fn () => print ($content), 'template-impor-peserta.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function store(Request $request, ImportPeserta $import): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $gudep = $user->gudep;

        abort_if(! $gudep instanceof Gudep, 404);
        Gate::authorize('update', $gudep);

        $request->validate(
            ['file' => ['required', 'file', 'max:1024', 'extensions:xlsx,csv,txt']],
            ['file.extensions' => 'Format file harus .xlsx atau .csv.', 'file.max' => 'Ukuran file maksimal 1 MB.'],
            ['file' => 'file impor'],
        );

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $report = $import->handle($gudep, SpreadsheetReader::rows($file));

        Inertia::flash('import', $report);
        Inertia::flash('toast', $report['added'] > 0
            ? ['type' => 'success', 'message' => "{$report['added']} peserta berhasil diimpor.".($report['skipped'] !== [] ? ' '.count($report['skipped']).' baris dilewati.' : '')]
            : ['type' => 'warning', 'message' => 'Tidak ada peserta yang diimpor. Periksa baris yang dilewati.']);

        return to_route('pembina.members.index');
    }
}
