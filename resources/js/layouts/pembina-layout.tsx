import { Link } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import type { ReactNode } from 'react';
import { BrandMark } from '@/components/brand/brand-mark';
import { StepTimeline } from '@/components/pembina/step-timeline';
import { SkipLink } from '@/components/skip-link';

/**
 * Kerangka area Pembina sebagai alur per langkah: timeline vertikal berikon di
 * kanan konten (layar lebar) atau strip langkah di atas konten (ponsel).
 * Halaman merender header marunnya sendiri.
 */
export default function PembinaLayout({ children }: { children: ReactNode }) {
    return (
        <div className="min-h-screen bg-ivory text-ink">
            <SkipLink />
            <header className="sticky top-0 z-30 hidden bg-maroon-900 md:block">
                <div className="mx-auto flex max-w-5xl items-center px-6 py-3">
                    <Link href="/pembina">
                        <BrandMark size="sm" subtitle="Area Pembina" />
                    </Link>
                </div>
            </header>

            <div className="sticky top-0 z-30 md:hidden">
                <StepTimeline variant="strip" />
            </div>

            <div className="mx-auto max-w-5xl md:grid md:grid-cols-[minmax(0,1fr)_280px] md:gap-8 md:px-6 md:pt-6">
                <main
                    id="konten"
                    tabIndex={-1}
                    className="min-w-0 pb-12 outline-none"
                >
                    {children}
                </main>
                <aside className="hidden md:block">
                    <div className="sticky top-20">
                        <StepTimeline variant="vertical" />
                    </div>
                </aside>
            </div>
        </div>
    );
}

/** Header marun bergradasi di atas setiap halaman pembina (desain M2–M5). */
export function PembinaHeader({
    title,
    subtitle,
    children,
    back,
    actions,
}: {
    title: ReactNode;
    subtitle?: ReactNode;
    children?: ReactNode;
    back?: string;
    actions?: ReactNode;
}) {
    return (
        <div className="relative overflow-hidden rounded-b-[28px] bg-maroon-gradient px-6 pt-8 pb-16 text-white md:rounded-t-[28px]">
            <div className="pointer-events-none absolute -top-24 -right-24 size-72 rounded-full border border-gold-300/15" />
            <div className="relative flex items-start gap-3">
                {back && (
                    <Link
                        href={back}
                        className="mt-0.5 rounded-xl border border-gold-300/25 bg-white/10 p-2 text-gold-300"
                        aria-label="Kembali"
                    >
                        <ChevronLeft className="size-4" />
                    </Link>
                )}
                <div className="min-w-0 flex-1">
                    <h1 className="font-display text-2xl font-bold">{title}</h1>
                    {subtitle && (
                        <p className="mt-1 text-sm text-maroon-50/85">
                            {subtitle}
                        </p>
                    )}
                </div>
                {actions}
            </div>
            {children}
        </div>
    );
}
