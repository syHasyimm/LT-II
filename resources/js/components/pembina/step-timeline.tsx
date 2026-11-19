import { Link, usePage } from '@inertiajs/react';
import {
    Camera,
    Check,
    Flag,
    Home,
    LogOut,
    Send,
    User,
    UserRound,
    Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useEffect, useRef } from 'react';
import { cn } from '@/lib/utils';
import type { PembinaStepData, StepKey } from '@/types';

type TimelineKey = StepKey | 'ringkasan';

export const stepIcon: Record<TimelineKey, LucideIcon> = {
    ringkasan: Home,
    gudep: Flag,
    peserta_pa: User,
    peserta_pi: UserRound,
    pembina: Users,
    berkas: Camera,
    kirim: Send,
};

const shortLabel: Record<TimelineKey, string> = {
    ringkasan: 'Ringkasan',
    gudep: 'Gudep',
    peserta_pa: 'Putra',
    peserta_pi: 'Putri',
    pembina: 'Pembina',
    berkas: 'Berkas',
    kirim: 'Kirim',
};

type Item = {
    key: TimelineKey;
    title: string;
    value: string | null;
    href: string;
    state: PembinaStepData['state'] | 'home';
    number: number | null;
};

function useTimelineItems(): { items: Item[]; active: TimelineKey | null } {
    const { pembinaSteps, pembinaStep } = usePage().props;
    const steps = pembinaSteps ?? [];

    return {
        active: pembinaStep,
        items: [
            {
                key: 'ringkasan',
                title: 'Ringkasan',
                value: null,
                href: '/pembina',
                state: 'home',
                number: null,
            },
            ...steps.map((step, index) => ({
                key: step.key,
                title: step.title,
                value: step.value,
                href: step.href,
                state: step.state,
                number: index + 1,
            })),
        ],
    };
}

function statusText(item: Item): string {
    if (item.state === 'done') {
        return 'selesai';
    }

    return item.state === 'home' ? '' : 'belum lengkap';
}

/**
 * Timeline langkah pendaftaran pembina: panel vertikal berikon di kanan
 * konten (layar lebar) atau strip horizontal di atas konten (ponsel).
 */
export function StepTimeline({ variant }: { variant: 'vertical' | 'strip' }) {
    return variant === 'vertical' ? <VerticalTimeline /> : <StripTimeline />;
}

function VerticalTimeline() {
    const { items, active } = useTimelineItems();
    const { auth } = usePage().props;

    return (
        <div className="rounded-3xl border border-line bg-white p-5 shadow-[0_10px_30px_rgba(62,7,11,0.06)]">
            <p className="text-[11px] font-semibold tracking-[0.2em] text-gold-700 uppercase">
                Langkah pendaftaran
            </p>
            <nav aria-label="Langkah pendaftaran" className="mt-4">
                <ol>
                    {items.map((item, index) => {
                        const Icon = stepIcon[item.key];
                        const isActive = item.key === active;
                        const last = index === items.length - 1;

                        return (
                            <li key={item.key} className="relative">
                                {!last && (
                                    <span
                                        aria-hidden
                                        className={cn(
                                            'absolute top-11 bottom-0 left-5 w-0.5 -translate-x-1/2',
                                            item.state === 'done'
                                                ? 'bg-gold-500'
                                                : 'bg-line',
                                        )}
                                    />
                                )}
                                <Link
                                    href={item.href}
                                    prefetch
                                    aria-current={isActive ? 'step' : undefined}
                                    className={cn(
                                        'group relative flex items-start gap-3 rounded-2xl pb-5',
                                        last && 'pb-0',
                                    )}
                                >
                                    <StepBullet
                                        item={item}
                                        isActive={isActive}
                                        Icon={Icon}
                                        size="lg"
                                    />
                                    <span className="min-w-0 flex-1 pt-1">
                                        <span className="flex items-baseline justify-between gap-2">
                                            <span
                                                className={cn(
                                                    'truncate text-sm font-semibold text-ink group-hover:text-maroon-700',
                                                    isActive &&
                                                        'font-bold text-maroon-700',
                                                    item.state === 'todo' &&
                                                        !isActive &&
                                                        'text-ink-muted',
                                                )}
                                            >
                                                {item.number !== null &&
                                                    `${item.number}. `}
                                                {item.title}
                                            </span>
                                            {item.value && (
                                                <span className="shrink-0 text-xs font-semibold text-ink-soft">
                                                    {item.value}
                                                </span>
                                            )}
                                        </span>
                                        {item.state === 'current' &&
                                            !isActive && (
                                                <span className="mt-0.5 block text-xs font-semibold text-maroon-600">
                                                    Lanjutkan di sini
                                                </span>
                                            )}
                                        {statusText(item) && (
                                            <span className="sr-only">
                                                {`, ${statusText(item)}`}
                                            </span>
                                        )}
                                    </span>
                                </Link>
                            </li>
                        );
                    })}
                </ol>
            </nav>

            <div className="mt-5 flex items-center gap-3 border-t border-line pt-4">
                <p className="min-w-0 flex-1 truncate text-sm font-semibold text-ink">
                    {auth.user?.name}
                </p>
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    className="flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-semibold text-maroon-700 transition hover:bg-maroon-100"
                >
                    <LogOut className="size-4" aria-hidden />
                    Keluar
                </Link>
            </div>
        </div>
    );
}

function StripTimeline() {
    const { items, active } = useTimelineItems();
    const activeRef = useRef<HTMLAnchorElement>(null);

    useEffect(() => {
        activeRef.current?.scrollIntoView({
            inline: 'center',
            block: 'nearest',
        });
    }, [active]);

    return (
        <nav
            aria-label="Langkah pendaftaran"
            className="[scrollbar-width:none] overflow-x-auto border-b border-gold-300/15 bg-maroon-950"
        >
            <ol className="flex w-max min-w-full gap-1 px-3 py-2.5">
                {items.map((item) => {
                    const Icon = stepIcon[item.key];
                    const isActive = item.key === active;

                    return (
                        <li key={item.key}>
                            <Link
                                ref={isActive ? activeRef : undefined}
                                href={item.href}
                                prefetch
                                aria-current={isActive ? 'step' : undefined}
                                className={cn(
                                    'flex w-16 flex-col items-center gap-1 rounded-xl py-1 text-[11px] font-semibold text-maroon-50/75',
                                    isActive && 'bg-white/10 text-gold-300',
                                )}
                            >
                                <StepBullet
                                    item={item}
                                    isActive={isActive}
                                    Icon={Icon}
                                    size="sm"
                                    dark
                                />
                                <span>{shortLabel[item.key]}</span>
                                {statusText(item) && (
                                    <span className="sr-only">
                                        {`${item.title}, ${statusText(item)}`}
                                    </span>
                                )}
                            </Link>
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}

function StepBullet({
    item,
    isActive,
    Icon,
    size,
    dark = false,
}: {
    item: Item;
    isActive: boolean;
    Icon: LucideIcon;
    size: 'sm' | 'lg';
    dark?: boolean;
}) {
    const done = item.state === 'done';

    return (
        <span
            aria-hidden
            className={cn(
                'relative grid shrink-0 place-items-center rounded-full border transition',
                size === 'lg' ? 'size-10' : 'size-9',
                isActive
                    ? 'border-transparent bg-maroon-gradient text-white ring-4 ring-maroon-100'
                    : done
                      ? 'border-transparent bg-gold-gradient text-maroon-900'
                      : dark
                        ? 'border-gold-300/30 bg-white/5 text-maroon-50/80'
                        : 'border-line bg-white text-ink-soft',
                isActive && dark && 'ring-2 ring-gold-300/40',
            )}
        >
            <Icon className={size === 'lg' ? 'size-[18px]' : 'size-4'} />
            {done && !isActive && (
                <span className="absolute -right-0.5 -bottom-0.5 grid size-4 place-items-center rounded-full bg-success text-white ring-2 ring-white">
                    <Check className="size-2.5" strokeWidth={3} />
                </span>
            )}
            {item.number !== null && size === 'sm' && !done && (
                <span className="absolute -top-1 -right-1 grid size-4 place-items-center rounded-full bg-maroon-600 text-[9px] font-bold text-white">
                    {item.number}
                </span>
            )}
        </span>
    );
}
