import { Head, Link } from '@inertiajs/react';
import { FileText, Lock } from 'lucide-react';
import { useState } from 'react';
import { UploadDialog } from '@/components/documents/upload-dialog';
import { InfoNote } from '@/components/form/field';
import { DocumentStatusBadge } from '@/components/status-badge';
import { StepNav } from '@/components/pembina/step-nav';
import { PembinaHeader } from '@/layouts/pembina-layout';
import { cn } from '@/lib/utils';
import { edit as editMember } from '@/routes/pembina/members';
import type {
    DocumentCounts,
    DocumentRow,
    DocumentSlot,
    GudepDocument,
} from '@/types';

type Props = {
    counts: DocumentCounts;
    gudepDocuments: GudepDocument[];
    rows: DocumentRow[];
    editable: boolean;
    maxUploadMb: number;
};

/** Petunjuk isi berkas per jenis dokumen gudep. */
const hints: Record<string, string> = {
    kta: 'Pindai KTA semua peserta dan pembina pendamping, gabungkan jadi satu PDF.',
    izin_ortu: 'Surat izin orang tua setiap peserta, digabung jadi satu PDF.',
    kesehatan:
        'Surat keterangan sehat semua peserta dan pembina pendamping, digabung jadi satu PDF.',
    kesiapan_sekolah:
        'Surat dari sekolah atau instansi yang menyatakan kesiapan mengikuti kegiatan.',
};

/** Dokumen persyaratan gudep (berkas gabungan) dan pas foto anggota (desain M4). */
export default function Documents({
    counts,
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
                subtitle={`Satu berkas per jenis · PDF, JPG, atau PNG · maksimal ${maxUploadMb} MB`}
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
                            {document && (
                                <span className="block truncate text-sm font-semibold text-ink-muted">
                                    {document.original_name} ·{' '}
                                    {document.size_label}
                                </span>
                            )}
                            <span className="block text-sm text-ink-soft">
                                {hints[type.code] ?? 'Ketuk untuk mengunggah'}
                            </span>
                        </span>
                        <DocumentStatusBadge
                            status={document ? document.status : 'missing'}
                        />
                    </button>
                ))}

                <h2 className="mt-4 font-display text-[26px] font-bold text-ink">
                    Pas foto anggota
                </h2>

                {rows.length === 0 ? (
                    <p className="rounded-2xl border border-dashed border-line px-5 py-8 text-center text-sm text-ink-soft">
                        Tambahkan anggota terlebih dahulu di menu Anggota.
                    </p>
                ) : (
                    <ul className="overflow-hidden rounded-2xl border border-line bg-white">
                        {rows.map((row) => (
                            <li
                                key={row.member.id}
                                className={cn(
                                    'flex items-center gap-3 border-t border-line px-4 py-3 first:border-t-0',
                                    row.member.is_pembina && 'bg-gold-100/25',
                                )}
                            >
                                <span className="min-w-0 flex-1">
                                    <span className="block truncate text-[15px] font-bold text-ink">
                                        {row.member.name}
                                    </span>
                                    <span
                                        className={cn(
                                            'block text-xs',
                                            row.member.is_pembina
                                                ? 'font-semibold text-gold-700'
                                                : 'text-ink-soft',
                                        )}
                                    >
                                        {row.member.role_label}
                                    </span>
                                </span>
                                <Link
                                    href={editMember(row.member.id)}
                                    aria-label={`Pas foto ${row.member.name}: ${row.member.has_photo ? 'sudah' : 'belum'} diunggah`}
                                    className={cn(
                                        'grid size-9 shrink-0 place-items-center rounded-full transition hover:scale-105',
                                        row.member.has_photo
                                            ? 'bg-gold-100 text-gold-700'
                                            : 'border-[1.5px] border-dashed border-maroon-600 text-maroon-600',
                                    )}
                                >
                                    {row.member.has_photo ? '✓' : '+'}
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}

                <p className="text-sm text-ink-soft">
                    Ketuk + untuk mengunggah pas foto di data anggota.
                </p>
            </div>

            <UploadDialog
                slot={slot}
                editable={editable}
                maxUploadMb={maxUploadMb}
                onClose={() => setSlot(null)}
            />
            <StepNav />
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
