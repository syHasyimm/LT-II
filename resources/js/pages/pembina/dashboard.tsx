import { Head, Link, usePage } from '@inertiajs/react';
import {
    Check,
    ChevronRight,
    Clock,
    LogOut,
    MessageSquareWarning,
} from 'lucide-react';
import { GudepStatusBadge } from '@/components/status-badge';
import type { GudepStatus } from '@/components/status-badge';
import { useCountdown } from '@/hooks/use-countdown';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';

type Step = {
    key: string;
    title: string;
    subtitle: string;
    value: string;
    state: 'done' | 'current' | 'todo' | 'warning';
    href: string;
};

type Props = {
    gudep: {
        name: string;
        region: string;
        status: GudepStatus;
        admin_note: string | null;
    } | null;
    percent: number;
    steps: Step[];
};

/** Beranda Pembina (desain M2). */
export default function PembinaDashboard({ gudep, percent, steps }: Props) {
    const { auth, registration } = usePage().props;
    const getInitials = useInitials();

    return (
        <>
            <Head title="Beranda" />

            <div className="relative overflow-hidden rounded-b-[28px] bg-maroon-gradient px-6 pt-8 pb-24 text-white">
                <div className="pointer-events-none absolute -top-24 -right-24 size-72 rounded-full border border-gold-300/15" />
                <div className="relative flex items-center gap-4">
                    {auth.user.avatar ? (
                        <img
                            src={auth.user.avatar}
                            alt=""
                            referrerPolicy="no-referrer"
                            className="size-12 rounded-full border-2 border-gold-300 object-cover"
                        />
                    ) : (
                        <span className="grid size-12 place-items-center rounded-full bg-gold-gradient text-lg font-bold text-maroon-900">
                            {getInitials(auth.user.name)}
                        </span>
                    )}
                    <div className="min-w-0 flex-1">
                        <p className="text-sm text-maroon-50">
                            Selamat datang,
                        </p>
                        <p className="truncate text-lg font-bold">
                            {auth.user.name}
                        </p>
                    </div>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        aria-label="Keluar"
                        className="grid size-11 place-items-center rounded-full bg-white/10 text-gold-300 md:hidden"
                    >
                        <LogOut className="size-5" />
                    </Link>
                </div>
            </div>

            <div className="relative -mt-16 grid grid-cols-[minmax(0,1fr)] gap-4 px-6 pb-8">
                <div className="rounded-3xl border border-line bg-white p-5 shadow-[0_12px_30px_rgba(62,7,11,0.08)]">
                    {gudep ? (
                        <>
                            <div className="flex items-start justify-between gap-3">
                                <p className="text-[11px] font-semibold tracking-[0.22em] text-gold-700 uppercase">
                                    Gugus depan Anda
                                </p>
                                <GudepStatusBadge status={gudep.status} />
                            </div>
                            <h1 className="mt-2 font-display text-2xl leading-tight font-bold text-ink">
                                {gudep.name}
                            </h1>
                            <p className="mt-1 text-sm text-ink-soft">
                                {gudep.region}
                            </p>
                            <div className="mt-5 flex items-baseline justify-between">
                                <p className="text-sm font-semibold text-ink">
                                    Kelengkapan pendaftaran
                                </p>
                                <p className="font-display text-2xl font-bold text-maroon-600">
                                    {percent}%
                                </p>
                            </div>
                            <div className="mt-2 h-2 overflow-hidden rounded-full bg-sand">
                                <div
                                    className="h-full rounded-full bg-[linear-gradient(90deg,#a51c24,#c9a24a)]"
                                    style={{ width: `${percent}%` }}
                                />
                            </div>
                        </>
                    ) : (
                        <>
                            <p className="text-[11px] font-semibold tracking-[0.22em] text-gold-700 uppercase">
                                Mulai pendaftaran
                            </p>
                            <h1 className="mt-2 font-display text-2xl font-bold text-ink">
                                Daftarkan gugus depan Anda
                            </h1>
                            <p className="mt-2 text-sm text-ink-muted">
                                Isi data gudep terlebih dahulu, lalu lengkapi
                                peserta, pembina, dan dokumen.
                            </p>
                            <Link
                                href="/pembina/gudep"
                                className="mt-4 flex h-12 items-center justify-center rounded-xl bg-[linear-gradient(135deg,#a51c24,#7a1219)] text-[15px] font-bold text-white"
                            >
                                Isi data gudep
                            </Link>
                        </>
                    )}
                </div>

                {gudep?.status === 'revision' && gudep.admin_note && (
                    <div className="flex gap-3 rounded-2xl border border-maroon-600/30 bg-maroon-100 px-5 py-4 text-maroon-700">
                        <MessageSquareWarning className="mt-0.5 size-5 shrink-0" />
                        <div>
                            <p className="text-sm font-bold">
                                Catatan revisi dari panitia
                            </p>
                            <p className="mt-1 text-sm whitespace-pre-line">
                                {gudep.admin_note}
                            </p>
                        </div>
                    </div>
                )}

                <DeadlineBanner registration={registration} />

                <h2 className="mt-4 font-display text-[26px] font-bold text-ink">
                    Langkah pendaftaran
                </h2>
                <ol className="grid grid-cols-[minmax(0,1fr)] gap-3">
                    {steps.map((step, index) => (
                        <li key={step.key}>
                            <StepCard
                                step={step}
                                number={index + 1}
                                disabled={!gudep && index > 0}
                            />
                        </li>
                    ))}
                </ol>
            </div>
        </>
    );
}

function DeadlineBanner({
    registration,
}: {
    registration: ReturnType<typeof usePage>['props']['registration'];
}) {
    const target =
        registration.state === 'open'
            ? registration.closes_at
            : registration.state === 'not_open'
              ? registration.opens_at
              : null;
    const countdown = useCountdown(target, registration.now);

    return (
        <div className="flex items-center gap-4 rounded-2xl border border-maroon-600/40 bg-[linear-gradient(135deg,#3e070b,#5a0d12)] px-5 py-4 text-white">
            <Clock
                className="size-8 shrink-0 text-gold-300"
                strokeWidth={1.6}
            />
            <div className="min-w-0">
                <p className="text-sm text-maroon-50">
                    {registration.state === 'open'
                        ? 'Pendaftaran ditutup dalam'
                        : registration.state === 'not_open'
                          ? 'Pendaftaran belum dibuka'
                          : 'Pendaftaran sudah ditutup'}
                </p>
                <p className="font-display text-xl leading-tight font-bold text-gold-300 sm:text-2xl">
                    {countdown
                        ? `${countdown.days} hari  ${countdown.hours} jam  ${countdown.minutes} menit`
                        : registration.state === 'closed'
                          ? 'Data hanya dapat dilihat'
                          : 'Jadwal segera diumumkan'}
                </p>
            </div>
        </div>
    );
}

function StepCard({
    step,
    number,
    disabled,
}: {
    step: Step;
    number: number;
    disabled: boolean;
}) {
    const content = (
        <>
            <span
                className={cn(
                    'grid size-11 shrink-0 place-items-center rounded-full font-display text-lg font-bold',
                    step.state === 'done' && 'bg-success-soft text-success',
                    step.state === 'current' &&
                        'bg-gold-gradient text-maroon-900',
                    step.state === 'todo' && 'bg-maroon-100 text-maroon-600',
                )}
            >
                {step.state === 'done' ? <Check className="size-5" /> : number}
            </span>
            <span className="min-w-0 flex-1">
                <span className="block text-[15px] font-bold text-ink">
                    {step.title}
                </span>
                <span className="block truncate text-sm text-ink-soft">
                    {step.subtitle}
                </span>
            </span>
            <span
                className={cn(
                    'shrink-0 text-sm font-bold',
                    step.state === 'done' && 'text-success',
                    step.state === 'current' && 'text-gold-800',
                    step.state === 'todo' && 'text-maroon-600',
                )}
            >
                {step.value}
            </span>
            <ChevronRight className="size-4 shrink-0 text-ink-soft" />
        </>
    );

    const className = cn(
        'flex items-center gap-4 rounded-2xl border bg-white px-5 py-4 transition',
        step.state === 'current'
            ? 'border-[1.5px] border-gold-500'
            : 'border-line',
        disabled ? 'pointer-events-none opacity-50' : 'hover:border-gold-500',
    );

    return disabled ? (
        <div className={className}>{content}</div>
    ) : (
        <Link href={step.href} className={className}>
            {content}
        </Link>
    );
}
