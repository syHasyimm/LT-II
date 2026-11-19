import { Head, Link } from '@inertiajs/react';
import { FileText, Lock } from 'lucide-react';
import { useState } from 'react';
import { cellState, DocCell } from '@/components/documents/doc-cell';
import { UploadDialog } from '@/components/documents/upload-dialog';
import { InfoNote } from '@/components/form/field';
import { DocumentStatusBadge } from '@/components/status-badge';
import { PembinaHeader } from '@/layouts/pembina-layout';
import { cn } from '@/lib/utils';
import { edit as editMember } from '@/routes/pembina/members';
import type {
    DocumentColumn,
    DocumentCounts,
    DocumentRow,
    DocumentSlot,
    GudepDocument,
} from '@/types';

type Props = {
    counts: DocumentCounts;
    columns: DocumentColumn[];
    gudepDocuments: GudepDocument[];
    rows: DocumentRow[];
    editable: boolean;
    maxUploadMb: number;
};

/** Dokumen persyaratan dan matriks kelengkapan per anggota (desain M4). */
export default function Documents({
    counts,
    columns,
    gudepDocuments,
    rows,
    editable,
    maxUploadMb,
}: Props) {
    const [slot, setSlot] = useState<DocumentSlot | null>(null);

    return (
        <>
            <Head title="Dokumen" />
            <PembinaHeader
                title="Dokumen Persyaratan"
                subtitle={`PDF, JPG, atau PNG · maksimal ${maxUploadMb} MB per file`}
            />

            <div className="relative -mt-10 grid grid-cols-[minmax(0,1fr)] gap-4 px-6 pb-8">
                <div className="grid grid-cols-3 gap-3">
                    <Stat
                        value={counts.uploaded}
                        label="Diunggah"
                        className="text-gold-700"
                    />
                    <Stat
                        value={counts.missing}
                        label="Belum diunggah"
                        className="text-maroon-600"
                    />
                    <Stat
                        value={counts.rejected}
                        label="Ditolak"
                        className="text-success"
                        alert={counts.rejected > 0}
                    />
                </div>

                {!editable && (
                    <InfoNote tone="maroon">
                        <Lock className="mt-0.5 size-4 shrink-0" />
                        Dokumen terkunci karena pendaftaran sudah dikirim atau
                        terverifikasi.
                    </InfoNote>
                )}

                {gudepDocuments.map(({ type, document }) => (
                    <button
                        key={type.id}
                        type="button"
                        onClick={() =>
                            setSlot({
                                typeId: type.id,
                                typeLabel: type.label,
                                memberId: null,
                                ownerName: 'Dokumen gudep',
                                document,
                            })
                        }
                        className="flex items-center gap-4 rounded-2xl border border-line bg-white px-5 py-4 text-left transition hover:border-gold-500"
                    >
                        <span className="grid size-12 shrink-0 place-items-center rounded-xl bg-maroon-100 text-maroon-600">
                            <FileText className="size-5" />
                        </span>
                        <span className="min-w-0 flex-1">
                            <span className="block text-[15px] font-bold text-ink">
                                {type.label}
                            </span>
                            <span className="block truncate text-sm text-ink-soft">
                                {document
                                    ? `${document.original_name} · ${document.size_label}`
                                    : 'Ketuk untuk mengunggah'}
                            </span>
                        </span>
                        {document ? (
                            <DocumentStatusBadge status={document.status} />
                        ) : (
                            <DocumentStatusBadge status="missing" />
                        )}
                    </button>
                ))}

                <h2 className="mt-4 font-display text-[26px] font-bold text-ink">
                    Kelengkapan per anggota
                </h2>

                {rows.length === 0 ? (
                    <p className="rounded-2xl border border-dashed border-line px-5 py-8 text-center text-sm text-ink-soft">
                        Tambahkan anggota terlebih dahulu di menu Anggota.
                    </p>
                ) : (
                    <div className="overflow-x-auto rounded-2xl border border-line bg-white">
                        <table className="w-full min-w-[300px] border-collapse text-left">
                            <thead>
                                <tr className="bg-[#f6efe3] text-[10px] font-semibold tracking-[0.1em] text-ink-muted uppercase">
                                    <th className="py-3.5 pr-1 pl-4 font-semibold">
                                        Anggota
                                    </th>
                                    <th className="w-11 px-0.5 text-center font-semibold">
                                        Foto
                                    </th>
                                    {columns.map((column) => (
                                        <th
                                            key={column.id}
                                            className="w-11 px-0.5 text-center font-semibold"
                                            title={column.label}
                                        >
                                            {column.short}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map((row) => (
                                    <tr
                                        key={row.member.id}
                                        className={cn(
                                            'border-t border-line',
                                            row.member.is_pembina &&
                                                'bg-gold-100/25',
                                        )}
                                    >
                                        <td className="py-3 pr-1 pl-4">
                                            <p
                                                className="text-[15px] font-bold text-ink"
                                                title={row.member.name}
                                            >
                                                {row.member.short_name}
                                            </p>
                                            <p
                                                className={cn(
                                                    'text-xs',
                                                    row.member.is_pembina
                                                        ? 'font-semibold text-gold-700'
                                                        : 'text-ink-soft',
                                                )}
                                            >
                                                {row.member.role_label}
                                            </p>
                                        </td>
                                        <td className="px-0.5 py-3 text-center">
                                            <Link
                                                href={editMember(row.member.id)}
                                                aria-label={`Pas foto ${row.member.name}: ${row.member.has_photo ? 'sudah' : 'belum'} diunggah`}
                                                className={cn(
                                                    'mx-auto grid size-9 place-items-center rounded-full transition hover:scale-105',
                                                    row.member.has_photo
                                                        ? 'bg-gold-100 text-gold-700'
                                                        : 'border-[1.5px] border-dashed border-maroon-600 text-maroon-600',
                                                )}
                                            >
                                                {row.member.has_photo
                                                    ? '✓'
                                                    : '+'}
                                            </Link>
                                        </td>
                                        {columns.map((column) => {
                                            const cell = row.cells[column.id];

                                            return (
                                                <td
                                                    key={column.id}
                                                    className="px-0.5 py-3 text-center"
                                                >
                                                    <DocCell
                                                        state={cellState(
                                                            cell.required,
                                                            cell.document,
                                                        )}
                                                        label={`${column.label} ${row.member.name}`}
                                                        onClick={() =>
                                                            setSlot({
                                                                typeId: column.id,
                                                                typeLabel:
                                                                    column.label,
                                                                memberId:
                                                                    row.member
                                                                        .id,
                                                                ownerName: `${row.member.name} · ${row.member.role_label}`,
                                                                document:
                                                                    cell.document,
                                                            })
                                                        }
                                                    />
                                                </td>
                                            );
                                        })}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <div className="text-sm text-ink-muted">
                    <p className="flex flex-wrap gap-x-5 gap-y-1">
                        <span>✓ Diunggah</span>
                        <span className="text-success">✓ Disetujui</span>
                        <span className="text-maroon-600">✕ Ditolak</span>
                        <span>+ Belum diunggah</span>
                        <span>— Tidak wajib</span>
                    </p>
                    <p className="mt-1 text-ink-soft">
                        Ketuk sel untuk mengunggah atau melihat dokumen.
                    </p>
                </div>
            </div>

            <UploadDialog
                slot={slot}
                editable={editable}
                maxUploadMb={maxUploadMb}
                onClose={() => setSlot(null)}
            />
        </>
    );
}

function Stat({
    value,
    label,
    className,
    alert = false,
}: {
    value: number;
    label: string;
    className?: string;
    alert?: boolean;
}) {
    return (
        <div
            className={cn(
                'rounded-2xl border bg-white px-4 py-3.5',
                alert ? 'border-maroon-600' : 'border-line',
            )}
        >
            <p
                className={cn(
                    'font-display text-[28px] leading-none font-bold',
                    alert ? 'text-maroon-600' : className,
                )}
            >
                {value}
            </p>
            <p className="mt-1.5 text-xs leading-tight text-ink-muted sm:text-sm">
                {label}
            </p>
        </div>
    );
}
