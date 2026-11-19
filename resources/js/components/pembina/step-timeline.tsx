import { Link, usePage } from '@inertiajs/react';
import {
    Camera,
    Check,
    ChevronDown,
    Flag,
    Home,
    LogOut,
    Send,
    User,
    UserRound,
    Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useState } from 'react';
import {
    Sheet,
    SheetContent,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
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
 * konten (layar lebar) atau bilah ringkas dengan bar progres (ponsel).
 */
export function StepTimeline({ variant }: { variant: 'vertical' | 'strip' }) {
    return variant === 'vertical' ? <VerticalTimeline /> : <StripTimeline />;
}

function VerticalTimeline() {
    return (
        <div className="rounded-3xl border border-line bg-white p-5 shadow-[0_10px_30px_rgba(62,7,11,0.06)]">
            <p className="text-[11px] font-semibold tracking-[0.2em] text-gold-700 uppercase">
                Langkah pendaftaran
            </p>
            <TimelineList />
        </div>
    );
}

/** Daftar timeline + nama/Keluar, dipakai kartu desktop dan panel ponsel. */
function TimelineList({ onNavigate }: { onNavigate?: () => void }) {
    const { items, active } = useTimelineItems();
    const { auth } = usePage().props;

    return (
        <>
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
                                    onClick={onNavigate}
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
                                    />
                                    <span className="min-w-0 flex-1 pt-1">
                                        <span className="flex items-baseline justify-between gap-2">
                                            <span
                                                className={cn(
                                                    'text-sm leading-snug font-semibold text-ink group-hover:text-maroon-700',
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
        </>
    );
}

/**
 * Bilah ringkas di ponsel: "Langkah n/6 · Judul" + 6 segmen progres, dan
 * tombol "Semua langkah" yang membuka timeline lengkap di panel bawah.
 */
function StripTimeline() {
    const { pembinaSteps, pembinaStep } = usePage().props;
    const [open, setOpen] = useState(false);
    const steps = pembinaSteps ?? [];
    const activeIndex = steps.findIndex((step) => step.key === pembinaStep);
    const doneCount = steps.filter((step) => step.state === 'done').length;

    return (
        <nav
            aria-label="Langkah pendaftaran"
            className="border-b border-gold-300/15 bg-maroon-950 px-4 pt-2.5 pb-3"
        >
            <div className="flex items-center gap-3">
                <p className="min-w-0 flex-1 truncate text-sm">
                    {activeIndex >= 0 ? (
                        <>
                            <span className="text-xs font-semibold text-gold-300">
                                Langkah {activeIndex + 1}/{steps.length}
                            </span>{' '}
                            <span className="font-bold text-white">
                                {steps[activeIndex].title}
                            </span>
                        </>
                    ) : (
                        <>
                            <span className="font-bold text-white">
                                Ringkasan
                            </span>{' '}
                            <span className="text-xs font-semibold text-gold-300">
                                · {doneCount} dari {steps.length} selesai
                            </span>
                        </>
                    )}
                </p>
                <Sheet open={open} onOpenChange={setOpen}>
                    <SheetTrigger className="-my-2 flex min-h-11 shrink-0 items-center gap-1 rounded-lg px-1 text-xs font-semibold text-gold-300">
                        Semua langkah
                        <ChevronDown className="size-3.5" aria-hidden />
                    </SheetTrigger>
                    <SheetContent
                        side="bottom"
                        className="max-h-[85dvh] overflow-y-auto rounded-t-3xl border-0 bg-ivory px-5 pt-6 pb-[calc(1.5rem+env(safe-area-inset-bottom))]"
                    >
                        <SheetTitle className="sr-only">
                            Langkah pendaftaran
                        </SheetTitle>
                        <TimelineList onNavigate={() => setOpen(false)} />
                    </SheetContent>
                </Sheet>
            </div>
            <div aria-hidden className="mt-2 grid grid-cols-6 gap-1">
                {steps.map((step, index) => (
                    <span
                        key={step.key}
                        className={cn(
                            'h-1 rounded-full',
                            index === activeIndex
                                ? 'bg-white'
                                : step.state === 'done'
                                  ? 'bg-gold-500'
                                  : 'bg-white/15',
                        )}
                    />
                ))}
            </div>
        </nav>
    );
}

function StepBullet({
    item,
    isActive,
    Icon,
}: {
    item: Item;
    isActive: boolean;
    Icon: LucideIcon;
}) {
    const done = item.state === 'done';

    return (
        <span
            aria-hidden
            className={cn(
                'relative grid size-10 shrink-0 place-items-center rounded-full border transition',
                isActive
                    ? 'border-transparent bg-maroon-gradient text-white ring-4 ring-maroon-100'
                    : done
                      ? 'border-transparent bg-gold-gradient text-maroon-900'
                      : 'border-line bg-white text-ink-soft',
            )}
        >
            <Icon className="size-[18px]" />
            {done && !isActive && (
                <span className="absolute -right-0.5 -bottom-0.5 grid size-4 place-items-center rounded-full bg-success text-white ring-2 ring-white">
                    <Check className="size-2.5" strokeWidth={3} />
                </span>
            )}
        </span>
    );
}
