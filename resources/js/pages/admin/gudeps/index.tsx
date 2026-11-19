import { Head } from '@inertiajs/react';
import { GudepTable } from '@/components/admin/gudep-table';
import type {
    GudepRow,
    Paginated,
    StatusCounts,
} from '@/components/admin/gudep-table';
import { AdminPageHeader } from '@/layouts/admin-layout';

type Props = {
    counts: StatusCounts;
    filters: { status: string | null; q: string | null };
    rows: Paginated<GudepRow>;
};

/** Gudep & Verifikasi: daftar semua gudep dengan filter dan pencarian (FR-VER-02). */
export default function GudepIndex({ counts, filters, rows }: Props) {
    return (
        <>
            <Head title="Gudep & Verifikasi" />
            <AdminPageHeader
                title="Gudep & Verifikasi"
                description="Pilih gudep untuk memeriksa anggota dan dokumen. Gudep berstatus Dikirim tampil paling atas."
            />
            <GudepTable
                baseUrl="/admin/gudep"
                rows={rows}
                counts={counts}
                filters={filters}
            />
        </>
    );
}
