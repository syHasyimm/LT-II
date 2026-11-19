import { Form, Head, Link, router } from '@inertiajs/react';
import {
    Download,
    FileArchive,
    FileText,
    LayoutGrid,
    Loader2,
    Sparkles,
    TriangleAlert,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Segmented } from '@/components/ui/segmented';
import { Spinner } from '@/components/ui/spinner';
import { AdminPageHeader } from '@/layouts/admin-layout';
import { cn } from '@/lib/utils';
import { download } from '@/routes/admin/generated';
import { print, store, zip } from '@/routes/admin/generate';
import type { Option } from '@/types';

type Props = {
    type: 'id_card' | 'certificate';
    slug: 'id-card' | 'sertifikat';
    label: string;
    templates: { target: string; name: string | null }[];
    stats: { gudeps: number; members: number; generated: number };
    gudeps: {
        id: number;
        name: string;
        institution: string;
        members: number;
        generated: number;
        last_at: string | null;
    }[];
    members: Option[];
    recent: {
        id: number;
        serial: string;
        name: string;
        gudep: string;
        generated_at: string | null;
    }[];
    batch: {
        id: string;
        name: string;
        total: number;
        processed: number;
        failed: number;
        progress: number;
        finished: boolean;
    } | null;
};

/** Generate dan unduh ID Card / Sertifikat (FR-GEN-01..06). */
export default function Generate({
    type,
    slug,
    label,
    templates,
    stats,
    gudeps,
    members,
    recent,
    batch,
}: Props) {
    const [scope, setScope] = useState<'member' | 'gudep' | 'all'>('gudep');
    const isIdCard = type === 'id_card';

    // FR-GEN-02: pantau progres generate di background.
    useEffect(() => {
        if (!batch || batch.finished) {
            return;
        }

        const id = window.setInterval(
            () =>
                router.reload({ only: ['batch', 'gudeps', 'stats', 'recent'] }),
            1500,
        );

        return () => window.clearInterval(id);
    }, [batch]);

    return (
        <>
            <Head title={label} />
            <AdminPageHeader
                title={label}
                description={`Dibuat untuk semua anggota gudep terverifikasi. ${isIdCard ? 'Ukuran 55 × 85 mm, cetak satuan atau lembar A4 berisi 9 kartu.' : 'Ukuran A4 lanskap dengan QR verifikasi.'}`}
                actions={
                    stats.generated > 0 && (
                        <Button asChild variant="outline-maroon" size="xl">
                            <a href={zip.url(slug)}>
                                <FileArchive className="size-4" /> Unduh ZIP
                                semua
                            </a>
                        </Button>
                    )
                }
            />

            <div className="grid gap-5 lg:grid-cols-3">
                <Stat label="Gudep terverifikasi" value={stats.gudeps} />
                <Stat label="Anggota penerima" value={stats.members} />
                <Stat
                    label={`${label} sudah dibuat`}
                    value={stats.generated}
                    highlight
                />
            </div>

            <div className="mt-5 grid gap-5 xl:grid-cols-[1fr_380px]">
                <Form
                    {...store.form(slug)}
                    options={{ preserveScroll: true }}
                    className="rounded-3xl border border-line bg-white p-6"
                >
                    {({ processing, errors }) => (
                        <div className="grid gap-5">
                            <div>
                                <h2 className="font-display text-2xl font-bold text-ink">
                                    Generate {label}
                                </h2>
                                <p className="mt-1 text-sm text-ink-muted">
                                    Nomor seri diberikan saat pertama dibuat dan
                                    tetap sama saat generate ulang. File lama
                                    ditimpa.
                                </p>
                            </div>
                            <div className="grid gap-2">
                                <span className="text-sm font-semibold text-ink">
                                    Penerima
                                </span>
                                <div
                                    onChange={(event) =>
                                        setScope(
                                            (event.target as HTMLInputElement)
                                                .value as typeof scope,
                                        )
                                    }
                                >
                                    <Segmented
                                        name="scope"
                                        defaultValue="gudep"
                                        options={[
                                            {
                                                value: 'member',
                                                label: 'Satu orang',
                                            },
                                            {
                                                value: 'gudep',
                                                label: 'Satu gudep',
                                            },
                                            {
                                                value: 'all',
                                                label: 'Semua gudep',
                                            },
                                        ]}
                                    />
                                </div>
                            </div>
                            {scope === 'member' && (
                                <select
                                    name="member_id"
                                    aria-label="Anggota penerima"
                                    required
                                    className="h-12 rounded-xl border border-line bg-white px-4 text-[15px]"
                                >
                                    <option value="">Pilih anggota…</option>
                                    {members.map((member) => (
                                        <option
                                            key={member.value}
                                            value={member.value}
                                        >
                                            {member.label}
                                        </option>
                                    ))}
                                </select>
                            )}
                            {scope === 'gudep' && (
                                <select
                                    name="gudep_id"
                                    aria-label="Gudep penerima"
                                    required
                                    className="h-12 rounded-xl border border-line bg-white px-4 text-[15px]"
                                >
                                    <option value="">Pilih gudep…</option>
                                    {gudeps.map((gudep) => (
                                        <option key={gudep.id} value={gudep.id}>
                                            {gudep.name} · {gudep.institution} (
                                            {gudep.members} anggota)
                                        </option>
                                    ))}
                                </select>
                            )}
                            {scope === 'all' && (
                                <p className="rounded-2xl bg-gold-100/60 px-4 py-3 text-sm text-gold-800">
                                    {stats.members} anggota dari {stats.gudeps}{' '}
                                    gudep terverifikasi akan diproses di
                                    background.
                                </p>
                            )}
                            <InputError
                                message={
                                    errors.scope ??
                                    errors.member_id ??
                                    errors.gudep_id
                                }
                            />
                            <div className="flex justify-end">
                                <Button
                                    type="submit"
                                    size="xl"
                                    disabled={processing || stats.members === 0}
                                >
                                    {processing ? (
                                        <Spinner />
                                    ) : (
                                        <Sparkles className="size-4" />
                                    )}
                                    Generate
                                </Button>
                            </div>
                        </div>
                    )}
                </Form>

                <div className="grid content-start gap-5">
                    {batch && <BatchProgress batch={batch} />}
                    <div className="rounded-3xl border border-line bg-white p-6">
                        <h2 className="text-[15px] font-bold text-ink">
                            Template aktif
                        </h2>
                        <ul className="mt-3 grid gap-2 text-sm">
                            {templates.map((template) => (
                                <li
                                    key={template.target}
                                    className="flex items-center justify-between gap-3"
                                >
                                    <span className="text-ink-muted">
                                        {template.target}
                                    </span>
                                    <span
                                        className={cn(
                                            'truncate font-semibold',
                                            template.name
                                                ? 'text-ink'
                                                : 'text-ink-soft',
                                        )}
                                    >
                                        {template.name ?? '—'}
                                    </span>
                                </li>
                            ))}
                        </ul>
                        <Link
                            href="/admin/template"
                            className="mt-4 inline-block text-sm font-bold text-maroon-700 hover:underline"
                        >
                            Kelola template →
                        </Link>
                    </div>
                </div>
            </div>

            <section className="mt-5 overflow-hidden rounded-3xl border border-line bg-white">
                <h2 className="px-6 pt-6 pb-4 font-display text-2xl font-bold text-ink">
                    Per gudep
                </h2>
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[760px] text-left text-sm">
                        <thead>
                            <tr className="bg-[#f6efe3] text-[11px] font-semibold tracking-[0.14em] text-ink-muted uppercase">
                                <th className="px-6 py-3.5">Gugus depan</th>
                                <th className="px-3 py-3.5">Dibuat</th>
                                <th className="px-3 py-3.5">Terakhir</th>
                                <th className="px-6 py-3.5 text-right">
                                    Unduh
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {gudeps.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="px-6 py-10 text-center text-ink-soft"
                                    >
                                        Belum ada gudep terverifikasi.
                                    </td>
                                </tr>
                            )}
                            {gudeps.map((gudep) => {
                                const ready = gudep.generated > 0;

                                return (
                                    <tr
                                        key={gudep.id}
                                        className="border-t border-line"
                                    >
                                        <td className="px-6 py-3.5">
                                            <p className="font-bold text-ink">
                                                {gudep.name}
                                            </p>
                                            <p className="text-xs text-ink-soft">
                                                {gudep.institution}
                                            </p>
                                        </td>
                                        <td
                                            className={cn(
                                                'px-3 py-3.5 font-semibold tabular-nums',
                                                gudep.generated ===
                                                    gudep.members
                                                    ? 'text-success'
                                                    : 'text-ink-muted',
                                            )}
                                        >
                                            {gudep.generated}/{gudep.members}
                                        </td>
                                        <td className="px-3 py-3.5 text-ink">
                                            {gudep.last_at ?? '—'}
                                        </td>
                                        <td className="px-6 py-3.5">
                                            {ready ? (
                                                <div className="flex justify-end gap-2">
                                                    <DownloadLink
                                                        href={print.url({
                                                            jenis: slug,
                                                            gudep: gudep.id,
                                                        })}
                                                        icon={
                                                            <FileText className="size-3.5" />
                                                        }
                                                    >
                                                        PDF
                                                    </DownloadLink>
                                                    {isIdCard && (
                                                        <DownloadLink
                                                            href={print.url(
                                                                {
                                                                    jenis: slug,
                                                                    gudep: gudep.id,
                                                                },
                                                                {
                                                                    query: {
                                                                        mode: 'sheet',
                                                                    },
                                                                },
                                                            )}
                                                            icon={
                                                                <LayoutGrid className="size-3.5" />
                                                            }
                                                        >
                                                            A4 3×3
                                                        </DownloadLink>
                                                    )}
                                                    <DownloadLink
                                                        href={zip.url(slug, {
                                                            query: {
                                                                gudep: gudep.id,
                                                            },
                                                        })}
                                                        icon={
                                                            <FileArchive className="size-3.5" />
                                                        }
                                                    >
                                                        ZIP
                                                    </DownloadLink>
                                                </div>
                                            ) : (
                                                <p className="text-right text-xs text-ink-soft">
                                                    Belum dibuat
                                                </p>
                                            )}
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            </section>

            {recent.length > 0 && (
                <section className="mt-5 rounded-3xl border border-line bg-white p-6">
                    <h2 className="font-display text-2xl font-bold text-ink">
                        Terakhir dibuat
                    </h2>
                    <ul className="mt-4 divide-y divide-line">
                        {recent.map((file) => (
                            <li
                                key={file.id}
                                className="flex items-center gap-4 py-3 text-sm"
                            >
                                <span className="font-mono font-semibold text-gold-800">
                                    {file.serial}
                                </span>
                                <span className="min-w-0 flex-1 truncate text-ink">
                                    {file.name}{' '}
                                    <span className="text-ink-soft">
                                        · {file.gudep}
                                    </span>
                                </span>
                                <span className="hidden text-xs text-ink-soft sm:block">
                                    {file.generated_at}
                                </span>
                                <a
                                    href={download.url(file.id)}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="flex items-center gap-1 font-semibold text-maroon-700 hover:underline"
                                >
                                    <Download className="size-3.5" /> Unduh
                                </a>
                            </li>
                        ))}
                    </ul>
                </section>
            )}
        </>
    );
}

function Stat({
    label,
    value,
    highlight = false,
}: {
    label: string;
    value: number;
    highlight?: boolean;
}) {
    return (
        <div
            className={cn(
                'rounded-3xl border p-5',
                highlight
                    ? 'border-transparent bg-[linear-gradient(150deg,#8a1820,#5a0d12)] text-white'
                    : 'border-line bg-white',
            )}
        >
            <p
                className={cn(
                    'text-sm',
                    highlight ? 'text-maroon-50' : 'text-ink-muted',
                )}
            >
                {label}
            </p>
            <p
                className={cn(
                    'mt-2 font-display text-[40px] leading-none font-bold',
                    highlight ? 'text-gold-300' : 'text-ink',
                )}
            >
                {value}
            </p>
        </div>
    );
}

function BatchProgress({ batch }: { batch: NonNullable<Props['batch']> }) {
    return (
        <div className="rounded-3xl border border-gold-500 bg-white p-6">
            <div className="flex items-center justify-between gap-3">
                <p className="flex items-center gap-2 text-[15px] font-bold text-ink">
                    {!batch.finished && (
                        <Loader2 className="size-4 animate-spin text-gold-700" />
                    )}
                    {batch.finished ? 'Generate selesai' : 'Sedang membuat…'}
                </p>
                <p className="font-display text-2xl font-bold text-gold-800">
                    {batch.progress}%
                </p>
            </div>
            <div className="mt-3 h-2 overflow-hidden rounded-full bg-sand">
                <div
                    className="h-full rounded-full bg-[linear-gradient(90deg,#c9a24a,#2f6b45)] transition-all"
                    style={{ width: `${batch.progress}%` }}
                />
            </div>
            <p className="mt-2 text-xs text-ink-soft">
                {batch.processed}/{batch.total} berkas · {batch.name}
            </p>
            {batch.failed > 0 && (
                <p className="mt-2 flex items-center gap-1.5 text-xs font-semibold text-maroon-600">
                    <TriangleAlert className="size-3.5" /> {batch.failed} gagal.
                    Periksa log queue lalu generate ulang.
                </p>
            )}
            {!batch.finished && (
                <p className="mt-2 text-xs text-ink-soft">
                    Pastikan queue worker berjalan (php artisan queue:work).
                </p>
            )}
        </div>
    );
}

function DownloadLink({
    href,
    icon,
    children,
}: {
    href: string;
    icon: React.ReactNode;
    children: React.ReactNode;
}) {
    return (
        <a
            href={href}
            target="_blank"
            rel="noreferrer"
            className="flex items-center gap-1.5 rounded-lg border border-line px-3 py-1.5 text-xs font-semibold text-ink hover:border-gold-500"
        >
            {icon}
            {children}
        </a>
    );
}
