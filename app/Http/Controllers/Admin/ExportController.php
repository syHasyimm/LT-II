<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\Admin\GudepTable;
use App\Services\Export\RegistrationExport;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Ekspor daftar gudep dan anggota ke Excel (FR-DSH-02), mengikuti filter tabel. */
class ExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $filters = GudepTable::filters($request);
        $export = RegistrationExport::for(GudepTable::query($filters));
        $content = (string) $export->workbook();

        /** @var User $user */
        $user = $request->user();
        ActivityLog::record('export.registrations', $user, ['filters' => array_filter($filters)]);

        return response()->streamDownload(fn () => print ($content), $export->filename(), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
