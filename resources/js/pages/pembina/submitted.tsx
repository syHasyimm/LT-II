import { Head, Link } from '@inertiajs/react';
import { Check, Download } from 'lucide-react';
import { GudepStatusBadge } from '@/components/status-badge';
import { idCard } from '@/routes/pembina';

type Props = {
    status: 'submitted' | 'verified';
    submitted_at: string | null;
    idCardReady: boolean;
    stats: {
        peserta: number;
        pembina: number;
        dokumen: number;
        disetujui: number;
    };
};

/** Layar setelah pendaftaran terkirim atau terverifikasi (desain M6). */
export default function Submitted({
    status,
    submitted_at,
    idCardReady,
    stats,
}: Props) {
    const verified = status === 'verified';

    return (
        <>
            <Head
                title={
                    verified
                        ? 'Pendaftaran Terverifikasi'
                        : 'Pendaftaran Terkirim'
                }
            />
            <div className="relative -mb-12 flex min-h-[calc(100svh-0px)] flex-col overflow-hidden bg-[radial-gradient(circle_at_50%_30%,#7a1219_0%,#3e070b_55%,#2a0407_100%)] px-6 pt-16 pb-36 text-center text-white md:mb-0 md:min-h-0 md:rounded-[28px] md:pb-16">
                <div
                    aria-hidden
                    className="pointer-events-none absolute top-24 left-1/2 size-[300px] -translate-x-1/2 rounded-full border border-gold-300/20"
                />
                <div
                    aria-hidden
                    className="pointer-events-none absolute top-8 left-1/2 size-[430px] -translate-x-1/2 rounded-full border border-gold-300/10"
                />

                <Seal />

                <h1 className="relative mt-10 font-display text-[34px] leading-tight font-bold">
                    {verified
                        ? 'Pendaftaran Terverifikasi'
                        : 'Pendaftaran Terkirim'}
                </h1>
                <GudepStatusBadge
                    status={status}
                    className="relative mx-auto mt-3 px-4 py-1.5 text-sm"
                />
                <p className="relative mx-auto mt-5 max-w-sm text-[15px] leading-relaxed text-maroon-50">
                    {verified
                        ? 'Selamat! Semua dokumen sudah disetujui panitia. Unduh ID Card peserta dan pembina di bawah ini.'
                        : 'Data gudep kini terkunci sampai admin memverifikasi. Anda akan menerima email saat status berubah.'}
                </p>
                {submitted_at && (
                    <p className="relative mt-2 text-xs text-maroon-50/70">
                        Dikirim {submitted_at}
                    </p>
                )}

                <dl className="relative mx-auto mt-8 grid w-full max-w-sm grid-cols-3 divide-x divide-gold-300/25 rounded-2xl border border-gold-300/30 bg-white/5 py-5">
                    <div>
                        <dd className="font-display text-3xl font-bold text-gold-300">
                            {stats.peserta}
                        </dd>
                        <dt className="text-sm text-maroon-50">Peserta</dt>
                    </div>
                    <div>
                        <dd className="font-display text-3xl font-bold text-gold-300">
                            {stats.pembina}
                        </dd>
                        <dt className="text-sm text-maroon-50">Pembina</dt>
                    </div>
                    <div>
                        <dd className="font-display text-3xl font-bold text-gold-300">
                            {stats.dokumen}
                        </dd>
                        <dt className="text-sm text-maroon-50">Dokumen</dt>
                    </div>
                </dl>

                {verified &&
                    (idCardReady ? (
                        <a
                            href={idCard().url}
                            target="_blank"
                            rel="noopener"
                            className="relative mx-auto mt-8 flex h-14 w-full max-w-sm items-center justify-center gap-2 rounded-2xl border border-gold-300/60 bg-white/10 text-base font-bold text-gold-300 transition hover:bg-white/15"
                        >
                            <Download className="size-5" />
                            Unduh ID Card (A4)
                        </a>
                    ) : (
                        <p className="relative mx-auto mt-8 max-w-sm rounded-2xl border border-gold-300/30 bg-white/5 px-4 py-3 text-sm text-maroon-50">
                            ID Card sedang disiapkan panitia.
                        </p>
                    ))}

                <Link
                    href="/pembina"
                    className="relative mx-auto mt-auto flex h-14 w-full max-w-sm items-center justify-center rounded-2xl bg-gold-gradient text-base font-bold text-maroon-900 md:mt-10"
                >
                    Kembali ke Beranda
                </Link>
            </div>
        </>
    );
}

function Seal() {
    const points = Array.from({ length: 16 }, (_, index) => {
        const angle = (index / 16) * Math.PI * 2;
        const radius = index % 2 === 0 ? 64 : 54;

        return `${64 + radius * Math.cos(angle)},${64 + radius * Math.sin(angle)}`;
    }).join(' ');

    return (
        <svg
            viewBox="-4 -4 136 136"
            className="relative mx-auto mt-6 size-32 drop-shadow-[0_10px_30px_rgba(0,0,0,0.35)]"
            aria-hidden
        >
            <defs>
                <linearGradient id="seal-gold" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stopColor="#F3E2AE" />
                    <stop offset="0.5" stopColor="#C9A24A" />
                    <stop offset="1" stopColor="#9C7A2B" />
                </linearGradient>
            </defs>
            <polygon points={points} fill="url(#seal-gold)" />
            <circle
                cx="64"
                cy="64"
                r="44"
                fill="#7A1219"
                stroke="#F3E2AE"
                strokeWidth="1.5"
            />
            <foreignObject x="40" y="40" width="48" height="48">
                <Check className="size-12 text-gold-300" strokeWidth={2.5} />
            </foreignObject>
        </svg>
    );
}
