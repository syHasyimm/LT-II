import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Check, Lock, X } from 'lucide-react';
import { BrandMark } from '@/components/brand/brand-mark';
import { SerialForm } from '@/components/serial-form';
import { SkipLink } from '@/components/skip-link';
import { cn } from '@/lib/utils';

type Result = {
    type: 'id_card' | 'certificate';
    type_label: string;
    document_label: string;
    name: string;
    gudep: string;
    role: string;
    issued_at: string | null;
    qr: string;
};

type Props = {
    serial: string;
    url: string;
    result: Result | null;
};

/** Verifikasi publik ID Card / Sertifikat (desain V1 desktop, V2–V3 mobile). */
export default function Verify({ serial, url, result }: Props) {
    const valid = result !== null;

    return (
        <>
            <Head
                title={
                    valid
                        ? `${result.type_label} sah`
                        : 'Nomor seri tidak ditemukan'
                }
            />
            <div className="min-h-screen bg-ivory text-ink">
                <SkipLink />
                <div className="relative overflow-hidden bg-maroon-gradient pb-36 text-white">
                    <div
                        aria-hidden
                        className="pointer-events-none absolute -top-72 -left-52 size-[640px] rounded-full border border-gold-300/15"
                    />
                    <div
                        aria-hidden
                        className="pointer-events-none absolute top-10 -right-40 size-[520px] rounded-full border border-gold-300/10"
                    />
                    <header className="relative mx-auto flex max-w-[1200px] items-center justify-between gap-4 border-b border-gold-300/15 px-6 py-5 xl:px-0">
                        <Link href="/">
                            <BrandMark size="sm" subtitle="Verifikasi Publik" />
                        </Link>
                        <Link
                            href="/"
                            className="flex items-center gap-2 rounded-xl border border-gold-300/50 px-3 py-2.5 text-sm font-semibold text-gold-300 hover:bg-white/10 sm:px-5"
                        >
                            <ArrowLeft className="size-4" />
                            <span className="hidden sm:inline">
                                Kembali ke beranda
                            </span>
                        </Link>
                    </header>
                    <p className="relative mt-6 px-6 text-center text-xs tracking-wide break-all text-gold-300/75 sm:text-[13px]">
                        {url.replace(/^https?:\/\//, '')}
                    </p>
                </div>

                <main
                    id="konten"
                    tabIndex={-1}
                    className="relative mx-auto -mt-28 max-w-[760px] px-4 pb-10 outline-none sm:px-6"
                >
                    <div className="overflow-hidden rounded-[28px] bg-white shadow-[0_30px_60px_rgba(62,7,11,0.18)]">
                        <div
                            className={cn(
                                'h-1.5',
                                valid
                                    ? 'bg-[linear-gradient(90deg,#9c7a2b,#e8cf8a,#9c7a2b)]'
                                    : 'bg-maroon-600',
                            )}
                        />

                        <div className="px-6 pt-8 pb-6 text-center sm:px-14">
                            <Seal valid={valid} />
                            <span
                                className={cn(
                                    'mt-6 inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-[11px] font-bold tracking-[0.14em] uppercase',
                                    valid
                                        ? 'bg-success-soft text-success'
                                        : 'bg-maroon-100 text-maroon-600',
                                )}
                            >
                                <span className="size-1.5 rounded-full bg-current" />
                                {valid ? 'Terverifikasi' : 'Tidak ditemukan'}
                            </span>
                            <h1 className="mt-3 font-display text-[28px] leading-tight font-bold sm:text-[38px]">
                                {valid
                                    ? `${result.type_label} ini sah`
                                    : 'Nomor seri tidak ditemukan'}
                            </h1>
                            <p className="mx-auto mt-2 max-w-[560px] text-[13px] leading-relaxed text-ink-muted sm:text-[15px]">
                                {valid
                                    ? 'Diterbitkan resmi oleh Panitia Perkemahan Lomba Tingkat 2 dan tercatat di sistem pendaftaran.'
                                    : 'Nomor ini tidak tercatat sebagai ID Card maupun sertifikat resmi Perkemahan LT 2.'}
                            </p>
                        </div>

                        {valid ? (
                            <>
                                <div className="mx-6 border-t border-line sm:mx-14" />
                                <div className="grid gap-6 px-6 py-6 sm:grid-cols-[1fr_180px] sm:px-14">
                                    <dl className="grid grid-cols-[110px_1fr] gap-x-4 gap-y-2.5 text-[13px] sm:grid-cols-[140px_1fr] sm:text-sm">
                                        <Row
                                            label="Jenis dokumen"
                                            value={result.document_label}
                                        />
                                        <Row
                                            label="Nomor seri"
                                            value={serial}
                                            mono
                                        />
                                        <Row label="Nama" value={result.name} />
                                        <Row
                                            label="Gudep"
                                            value={result.gudep}
                                        />
                                        <Row
                                            label="Peran"
                                            value={result.role}
                                        />
                                        <Row
                                            label="Diterbitkan"
                                            value={result.issued_at ?? '—'}
                                        />
                                    </dl>
                                    <div className="hidden flex-col items-center gap-2 rounded-2xl border border-line bg-ivory p-4 sm:flex">
                                        <img
                                            src={result.qr}
                                            alt={`QR verifikasi ${serial}`}
                                            className="size-28 rounded-lg"
                                        />
                                        <p className="text-center text-[11px] text-ink-soft">
                                            Pindai untuk membuka halaman ini
                                        </p>
                                    </div>
                                </div>
                                <p className="flex items-start gap-3 bg-maroon-100 px-6 py-4 text-xs font-semibold text-maroon-700 sm:px-14 sm:text-[13px]">
                                    <Lock className="mt-0.5 size-4 shrink-0" />
                                    Demi privasi peserta, tanggal lahir,
                                    dokumen, dan kontak tidak ditampilkan di
                                    halaman ini.
                                </p>
                            </>
                        ) : (
                            <div className="grid gap-4 px-6 pb-8 sm:px-14">
                                <div className="rounded-2xl border border-dashed border-line bg-ivory px-4 py-3">
                                    <p className="text-[10px] font-semibold tracking-[0.18em] text-gold-700 uppercase">
                                        Nomor yang diperiksa
                                    </p>
                                    <p className="mt-1 font-mono text-[15px] font-bold tracking-wide text-ink">
                                        {serial}
                                    </p>
                                </div>
                                <ul className="grid gap-1.5 text-sm text-ink-muted">
                                    <li>
                                        • Periksa kembali huruf dan angkanya,
                                        misalnya LT2-2026-CERT-0001.
                                    </li>
                                    <li>
                                        • Masih tidak ditemukan? Hubungi
                                        sekretariat panitia Perkemahan LT 2.
                                    </li>
                                </ul>
                            </div>
                        )}
                    </div>

                    <section id="cek" className="mx-auto mt-10 max-w-[560px]">
                        <h2 className="text-center font-display text-xl font-bold text-ink sm:text-[22px]">
                            {valid
                                ? 'Periksa nomor seri lain'
                                : 'Coba nomor seri lain'}
                        </h2>
                        <SerialForm defaultValue="" className="mt-4" />
                        <p className="mt-3 text-center text-xs text-ink-soft">
                            Format: LT2-2026-ID-0001 atau LT2-2026-CERT-0001
                        </p>
                    </section>

                    <p className="mt-12 text-center text-xs text-ink-soft">
                        © {new Date().getFullYear()} Panitia Perkemahan Lomba
                        Tingkat 2
                    </p>
                </main>
            </div>
        </>
    );
}

function Row({
    label,
    value,
    mono = false,
}: {
    label: string;
    value: string;
    mono?: boolean;
}) {
    return (
        <>
            <dt className="text-ink-soft">{label}</dt>
            <dd
                className={cn(
                    'font-semibold text-ink',
                    mono && 'font-mono tracking-wide',
                )}
            >
                {value}
            </dd>
        </>
    );
}

function Seal({ valid }: { valid: boolean }) {
    return (
        <span className="relative mx-auto grid size-[88px] place-items-center rounded-full border border-dashed border-gold-500 sm:size-[100px]">
            <span
                className={cn(
                    'grid size-[70px] place-items-center rounded-full sm:size-20',
                    valid
                        ? 'bg-gold-gradient text-maroon-900 shadow-[0_8px_20px_rgba(156,122,43,0.35)]'
                        : 'bg-maroon-100 text-maroon-600',
                )}
            >
                {valid ? (
                    <Check className="size-8" strokeWidth={2.2} />
                ) : (
                    <X className="size-7" strokeWidth={2.2} />
                )}
            </span>
        </span>
    );
}
