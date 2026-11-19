import { Head, Link, usePage } from '@inertiajs/react';
import { Check, Clock, UserRound, Users } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { GudepTable } from '@/components/admin/gudep-table';
import type {
    GudepRow,
    Paginated,
    StatusCounts,
} from '@/components/admin/gudep-table';
import { RegistrationPill } from '@/components/registration-pill';
import { AdminPageHeader } from '@/layouts/admin-layout';
import { cn } from '@/lib/utils';

type Props = {
    today: string;
    counts: StatusCounts;
    summary: {
        new_this_week: number;
        pending_documents: number;
        peserta: number;
        pembina: number;
        extensions: number;
    };
    filters: { status: string | null; q: string | null };
    rows: Paginated<GudepRow>;
};

/** Dashboard admin (desain D1). */
export default function AdminDashboard({
    today,
    counts,
    summary,
    filters,
    rows,
}: Props) {
    const { registration } = usePage().props;
    const verifiedPercent = counts.all
        ? Math.round((counts.verified / counts.all) * 100)
        : 0;

    return (
        <>
            <Head title="Dashboard" />
            <AdminPageHeader
                title="Dashboard"
                description={`Ringkasan pendaftaran per ${today}`}
                actions={
                    <RegistrationPill
                        registration={registration}
                        className="py-2"
                    />
                }
            />

            <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    label="Gudep terdaftar"
                    value={counts.all}
                    note={`▲ ${summary.new_this_week} gudep minggu ini`}
                    noteClass="text-success"
                    icon={Users}
                    iconClass="bg-maroon-100 text-maroon-600"
                />
                <StatCard
                    label="Menunggu verifikasi"
                    value={counts.submitted}
                    note={`${summary.pending_documents} dokumen perlu diperiksa`}
                    icon={Clock}
                    highlight
                    href="/admin/gudep?status=submitted"
                />
                <StatCard
                    label="Terverifikasi"
                    value={counts.verified}
                    note={`${verifiedPercent}% dari total gudep`}
                    icon={Check}
                    iconClass="bg-success-soft text-success"
                />
                <StatCard
                    label="Total anggota"
                    value={summary.peserta + summary.pembina}
                    note={`${summary.peserta} peserta · ${summary.pembina} pembina`}
                    icon={UserRound}
                    iconClass="bg-gold-100 text-gold-700"
                />
            </div>

            <div className="mt-5 grid gap-5 xl:grid-cols-[1fr_400px]">
                <StatusBar counts={counts} />
                <div className="relative overflow-hidden rounded-3xl bg-[linear-gradient(150deg,#6e1218,#3e070b)] p-6 text-white">
                    <div className="pointer-events-none absolute -top-16 -right-16 size-56 rounded-full border border-gold-300/20" />
                    <p className="text-[11px] font-semibold tracking-[0.22em] text-gold-300 uppercase">
                        Jadwal pendaftaran
                    </p>
                    <p className="mt-3 text-[15px] font-semibold">
                        Buka · {registration.opens_label ?? 'belum diatur'}
                    </p>
                    <p className="text-[15px] font-semibold">
                        Tutup · {registration.closes_label ?? 'belum diatur'}
                    </p>
                    <div className="mt-5 flex items-center justify-between gap-3">
                        <p className="text-sm text-maroon-50/80">
                            {summary.extensions} gudep mendapat perpanjangan
                        </p>
                        <Link
                            href="/admin/jadwal"
                            className="rounded-xl border border-gold-300/60 px-3.5 py-2 text-sm font-semibold text-gold-300 hover:bg-white/10"
                        >
                            Ubah jadwal
                        </Link>
                    </div>
                </div>
            </div>

            <div className="mt-5">
                <GudepTable
                    baseUrl="/admin"
                    rows={rows}
                    counts={counts}
                    filters={filters}
                    showPagination={false}
                    footer={
                        rows.total > rows.data.length && (
                            <div className="border-t border-line px-6 py-4 text-right">
                                <Link
                                    href="/admin/gudep"
                                    className="text-sm font-bold text-maroon-700 hover:underline"
                                >
                                    Lihat semua {rows.total} gudep →
                                </Link>
                            </div>
                        )
                    }
                />
            </div>
        </>
    );
}

function StatCard({
    label,
    value,
    note,
    noteClass,
    icon: Icon,
    iconClass,
    highlight = false,
    href,
}: {
    label: string;
    value: number;
    note: string;
    noteClass?: string;
    icon: LucideIcon;
    iconClass?: string;
    highlight?: boolean;
    href?: string;
}) {
    const body = (
        <>
            <div className="flex items-start justify-between">
                <p
                    className={cn(
                        'text-sm',
                        highlight ? 'text-maroon-50' : 'text-ink-muted',
                    )}
                >
                    {label}
                </p>
                <span
                    className={cn(
                        'grid size-10 place-items-center rounded-xl',
                        highlight ? 'bg-white/10 text-gold-300' : iconClass,
                    )}
                >
                    <Icon className="size-5" strokeWidth={1.8} />
                </span>
            </div>
            <p
                className={cn(
                    'mt-2 font-display text-[40px] leading-none font-bold',
                    highlight ? 'text-gold-300' : 'text-ink',
                )}
            >
                {value}
            </p>
            <p
                className={cn(
                    'mt-2 text-xs',
                    highlight
                        ? 'text-maroon-50'
                        : (noteClass ?? 'text-ink-muted'),
                )}
            >
                {note}
            </p>
        </>
    );
    const className = cn(
        'rounded-3xl border p-5 transition',
        highlight
            ? 'border-transparent bg-[linear-gradient(150deg,#8a1820,#5a0d12)] shadow-[0_12px_30px_rgba(122,18,25,0.3)]'
            : 'border-line bg-white',
        href && 'hover:-translate-y-0.5',
    );

    return href ? (
        <Link href={href} className={className}>
            {body}
        </Link>
    ) : (
        <div className={className}>{body}</div>
    );
}

function StatusBar({ counts }: { counts: StatusCounts }) {
    const segments = [
        {
            key: 'draft',
            label: 'Draft',
            value: counts.draft,
            className: 'bg-sand',
            text: 'text-ink-muted',
        },
        {
            key: 'submitted',
            label: 'Dikirim',
            value: counts.submitted,
            className: 'bg-gold-500',
            text: 'text-gold-800',
        },
        {
            key: 'revision',
            label: 'Revisi',
            value: counts.revision,
            className: 'bg-maroon-600',
            text: 'text-maroon-600',
        },
        {
            key: 'verified',
            label: 'Terverifikasi',
            value: counts.verified,
            className: 'bg-success',
            text: 'text-success',
        },
    ];
    const total = Math.max(1, counts.all);

    return (
        <div className="rounded-3xl border border-line bg-white p-6">
            <h2 className="font-display text-2xl font-bold text-ink">
                Status pendaftaran gudep
            </h2>
            <div className="mt-5 flex h-3.5 gap-1 overflow-hidden rounded-full bg-sand">
                {segments
                    .filter((segment) => segment.value > 0)
                    .map((segment) => (
                        <div
                            key={segment.key}
                            className={cn(
                                'h-full first:rounded-l-full last:rounded-r-full',
                                segment.className,
                            )}
                            style={{
                                width: `${(segment.value / total) * 100}%`,
                            }}
                            title={`${segment.label}: ${segment.value}`}
                        />
                    ))}
            </div>
            <ul className="mt-4 flex flex-wrap gap-x-8 gap-y-2 text-sm">
                {segments.map((segment) => (
                    <li
                        key={segment.key}
                        className={cn('flex items-center gap-2', segment.text)}
                    >
                        <span
                            className={cn(
                                'size-2.5 rounded-sm',
                                segment.className,
                            )}
                        />
                        {segment.label} {segment.value}
                    </li>
                ))}
            </ul>
        </div>
    );
}
