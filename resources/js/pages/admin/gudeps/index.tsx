import { Head } from '@inertiajs/react';
import { FileSpreadsheet } from 'lucide-react';
import { GudepTable } from '@/components/admin/gudep-table';
import type {
    GudepRow,
    Paginated,
    StatusCounts,
} from '@/components/admin/gudep-table';
import { Button } from '@/components/ui/button';
import { AdminPageHeader } from '@/layouts/admin-layout';
import { excel } from '@/routes/admin/gudeps';

type Props = {
    counts: StatusCounts;
    filters: { status: string | null; q: string | null };
    rows: Paginated<GudepRow>;
};

/** Gudep & Verifikasi: daftar semua gudep dengan filter dan pencarian (FR-VER-02). */
export default function GudepIndex({ counts, filters, rows }: Props) {
    const query = {
        ...(filters.status ? { status: filters.status } : {}),
        ...(filters.q ? { q: filters.q } : {}),
    };
    const filtered = Object.keys(query).length > 0;

    return (
        <>
            <Head title="Gudep & Verifikasi" />
            <AdminPageHeader
                title="Gudep & Verifikasi"
                description="Pilih gudep untuk memeriksa anggota dan dokumen. Gudep berstatus Dikirim tampil paling atas."
                actions={
                    <Button asChild variant="outline-maroon" size="xl">
                        <a
                            href={excel.url({ query })}
                            title={
                                filtered
                                    ? 'Ekspor gudep sesuai filter yang sedang aktif'
                                    : 'Ekspor semua gudep dan anggotanya'
                            }
                        >
                            <FileSpreadsheet className="size-4" aria-hidden />
                            {filtered ? 'Ekspor hasil filter' : 'Ekspor Excel'}
                        </a>
                    </Button>
                }
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
