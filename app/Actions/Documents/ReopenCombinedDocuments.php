<?php

namespace App\Actions\Documents;

use App\Enums\DocumentStatus;
use App\Models\Member;

/**
 * Berkas gabungan (KTA, izin orang tua, keterangan sehat) yang sudah disetujui
 * tidak lagi mencakup anggota yang ditambahkan sesudahnya. Kembalikan ke
 * "menunggu" agar admin memeriksa ulang dan pembina tahu berkasnya perlu
 * diperbarui.
 */
class ReopenCombinedDocuments
{
    /** Jenis berkas gabungan yang harus memuat anggota baru, per peran. */
    private const PESERTA = ['kta', 'izin_ortu', 'kesehatan'];

    private const PEMBINA = ['kta', 'kesehatan'];

    public function handle(Member $member): void
    {
        $codes = $member->isPembina() ? self::PEMBINA : self::PESERTA;

        $member->gudep->documents()
            ->active()
            ->where('status', DocumentStatus::Approved)
            ->whereHas('type', fn ($query) => $query->whereIn('code', $codes))
            ->update([
                'status' => DocumentStatus::Pending,
                'note' => "Anggota baru ({$member->name}) ditambahkan setelah berkas disetujui. Pastikan berkas mencakup semua anggota.",
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);
    }
}
