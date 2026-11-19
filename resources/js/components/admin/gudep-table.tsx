import { Link, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { GudepStatusBadge } from '@/components/status-badge';
import type { GudepStatus } from '@/components/status-badge';
import { cn } from '@/lib/utils';

export type GudepRow = {
    id: number;
    name: string;
    institution: string;
    region: string;
    peserta: number;
    pembina: number;
    documents: { uploaded: number; total: number; rejected: number };
    submitted_at: string | null;
    status: GudepStatus;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

export type StatusCounts = Record<'all' | GudepStatus, number>;

type Filters = { status: string | null; q: string | null };

const chips: { key: 'all' | GudepStatus; label: string }[] = [
    { key: 'all', label: 'Semua' },
    { key: 'submitted', label: 'Dikirim' },
    { key: 'revision', label: 'Perlu revisi' },
    { key: 'verified', label: 'Terverifikasi' },
    { key: 'draft', label: 'Draft' },
];

/** Tabel daftar gudep dengan filter status dan pencarian (desain D1). */
export function GudepTable({
    baseUrl,
    rows,
    counts,
    filters,
    showPagination = true,
    footer,
}: {
    baseUrl: string;
    rows: Paginated<GudepRow>;
    counts: StatusCounts;
    filters: Filters;
    showPagination?: boolean;
    footer?: React.ReactNode;
}) {
    const [search, setSearch] = useState(filters.q ?? '');
    const first = useRef(true);

    useEffect(() => {
        if (first.current) {
            first.current = false;

            return;
        }

        const id = window.setTimeout(() => {
            router.get(
                baseUrl,
                {
                    ...(filters.status ? { status: filters.status } : {}),
                    ...(search ? { q: search } : {}),
                },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => window.clearTimeout(id);
    }, [search, baseUrl, filters.status]);

    const chipHref = (key: 'all' | GudepStatus) => {
        const params = new URLSearchParams();

        if (key !== 'all') {
            params.set('status', key);
        }

        if (filters.q) {
            params.set('q', filters.q);
        }

        const query = params.toString();

        return query ? `${baseUrl}?${query}` : baseUrl;
    };

    return (
        <div className="overflow-hidden rounded-3xl border border-line bg-white">
            <div className="flex flex-wrap items-center justify-between gap-4 px-6 pt-6">
                <h2 className="font-display text-2xl font-bold text-ink">
                    Daftar gudep
                </h2>
                <label className="relative w-full sm:w-80">
                    <Search className="absolute top-1/2 left-4 size-4 -translate-y-1/2 text-ink-soft" />
                    <input
                        type="search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Cari nama gudep atau sekolah…"
                        className="h-11 w-full rounded-xl border border-line bg-white pr-4 pl-11 text-sm outline-none focus:border-maroon-600 focus:ring-4 focus:ring-maroon-100"
                    />
                </label>
            </div>

            <nav
                className="flex flex-wrap gap-2 px-6 pt-4 pb-5"
                aria-label="Filter status"
            >
                {chips.map((chip) => {
                    const active = (filters.status ?? 'all') === chip.key;

                    return (
                        <Link
                            key={chip.key}
                            href={chipHref(chip.key)}
                            preserveScroll
                            preserveState
                            className={cn(
                                'rounded-full border px-3.5 py-1.5 text-[13px] font-semibold transition',
                                active
                                    ? 'border-maroon-700 bg-[linear-gradient(135deg,#a51c24,#7a1219)] text-white'
                                    : 'border-line text-ink hover:border-gold-500',
                            )}
                        >
                            {chip.label} · {counts[chip.key]}
                        </Link>
                    );
                })}
            </nav>

            <div className="overflow-x-auto">
                <table className="w-full min-w-[860px] text-left text-sm">
                    <thead>
                        <tr className="bg-[#f6efe3] text-[11px] font-semibold tracking-[0.14em] text-ink-muted uppercase">
                            <th className="px-6 py-3.5">Gugus depan</th>
                            <th className="px-3 py-3.5">Kwartir</th>
                            <th className="px-3 py-3.5">Anggota</th>
                            <th className="px-3 py-3.5">Dokumen</th>
                            <th className="px-3 py-3.5">Dikirim</th>
                            <th className="px-3 py-3.5">Status</th>
                            <th className="w-10" />
                        </tr>
                    </thead>
                    <tbody>
                        {rows.data.length === 0 && (
                            <tr>
                                <td
                                    colSpan={7}
                                    className="px-6 py-10 text-center text-ink-soft"
                                >
                                    Tidak ada gudep yang cocok.
                                </td>
                            </tr>
                        )}
                        {rows.data.map((row) => {
                            const docsComplete =
                                row.documents.total > 0 &&
                                row.documents.uploaded === row.documents.total;

                            return (
                                <tr
                                    key={row.id}
                                    onClick={() =>
                                        router.visit(`/admin/gudep/${row.id}`)
                                    }
                                    className="cursor-pointer border-t border-line transition hover:bg-ivory"
                                >
                                    <td className="px-6 py-3.5">
                                        <Link
                                            href={`/admin/gudep/${row.id}`}
                                            className="font-bold text-ink hover:underline"
                                        >
                                            {row.name}
                                        </Link>
                                        <p className="text-xs text-ink-soft">
                                            {row.institution}
                                        </p>
                                    </td>
                                    <td className="px-3 py-3.5 text-ink">
                                        {row.region}
                                    </td>
                                    <td className="px-3 py-3.5 text-ink tabular-nums">
                                        {row.peserta} + {row.pembina}
                                    </td>
                                    <td
                                        className={cn(
                                            'px-3 py-3.5 font-semibold tabular-nums',
                                            row.documents.rejected > 0
                                                ? 'text-maroon-600'
                                                : docsComplete
                                                  ? 'text-success'
                                                  : 'text-ink-soft',
                                        )}
                                    >
                                        {row.documents.uploaded}/
                                        {row.documents.total}
                                    </td>
                                    <td className="px-3 py-3.5 text-ink">
                                        {row.submitted_at ?? '—'}
                                    </td>
                                    <td className="px-3 py-3.5">
                                        <GudepStatusBadge status={row.status} />
                                    </td>
                                    <td className="pr-6 text-ink-soft">
                                        <ChevronRight className="size-4" />
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>

            {showPagination && rows.last_page > 1 && (
                <div className="flex items-center justify-between border-t border-line px-6 py-4 text-sm text-ink-muted">
                    <span>
                        {rows.from}–{rows.to} dari {rows.total} gudep
                    </span>
                    <div className="flex gap-2">
                        <PageLink href={rows.prev_page_url} label="Sebelumnya">
                            <ChevronLeft className="size-4" />
                        </PageLink>
                        <PageLink href={rows.next_page_url} label="Berikutnya">
                            <ChevronRight className="size-4" />
                        </PageLink>
                    </div>
                </div>
            )}
            {footer}
        </div>
    );
}

function PageLink({
    href,
    label,
    children,
}: {
    href: string | null;
    label: string;
    children: React.ReactNode;
}) {
    if (!href) {
        return (
            <span
                className="grid size-9 place-items-center rounded-lg border border-line opacity-40"
                aria-hidden
            >
                {children}
            </span>
        );
    }

    return (
        <Link
            href={href}
            preserveScroll
            preserveState
            aria-label={label}
            className="grid size-9 place-items-center rounded-lg border border-line hover:border-gold-500"
        >
            {children}
        </Link>
    );
}
