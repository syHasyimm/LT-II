import { Link, usePage } from '@inertiajs/react';
import { ChevronLeft, FileText, Home, LogOut, Send, Users } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { BrandMark } from '@/components/brand/brand-mark';
import { SkipLink } from '@/components/skip-link';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';

type PembinaNavItem = {
    href: string;
    label: string;
    icon: LucideIcon;
    exact?: boolean;
};

export const pembinaNav: PembinaNavItem[] = [
    { href: '/pembina', label: 'Beranda', icon: Home, exact: true },
    { href: '/pembina/anggota', label: 'Anggota', icon: Users },
    { href: '/pembina/dokumen', label: 'Dokumen', icon: FileText },
    { href: '/pembina/kirim', label: 'Kirim', icon: Send },
];

/**
 * Kerangka area Pembina, mobile-first (desain M2–M6): navigasi bawah di
 * ponsel, bilah atas di layar lebar. Halaman merender header marunnya sendiri.
 */
export default function PembinaLayout({ children }: { children: ReactNode }) {
    const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();
    const isActive = (item: PembinaNavItem) =>
        item.exact ? isCurrentUrl(item.href) : isCurrentOrParentUrl(item.href);
    const { auth } = usePage().props;

    return (
        <div className="min-h-screen bg-ivory text-ink">
            <SkipLink />
            <header className="sticky top-0 z-30 hidden bg-maroon-900 md:block">
                <div className="mx-auto flex max-w-3xl items-center justify-between px-6 py-3">
                    <Link href="/pembina">
                        <BrandMark size="sm" subtitle="Area Pembina" />
                    </Link>
                    <nav className="flex items-center gap-1">
                        {pembinaNav.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                prefetch
                                className={cn(
                                    'flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-maroon-50/80 transition hover:bg-white/10 hover:text-white',
                                    isActive(item) &&
                                        'bg-white/10 text-gold-300',
                                )}
                            >
                                <item.icon className="size-4" />
                                {item.label}
                            </Link>
                        ))}
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            title={`Keluar (${auth.user?.email ?? ''})`}
                            className="ml-2 rounded-xl p-2 text-maroon-50/70 hover:bg-white/10 hover:text-white"
                        >
                            <LogOut className="size-4" />
                            <span className="sr-only">Keluar</span>
                        </Link>
                    </nav>
                </div>
            </header>

            <main
                id="konten"
                tabIndex={-1}
                className="mx-auto max-w-3xl pb-28 outline-none md:pb-12"
            >
                {children}
            </main>

            <nav
                aria-label="Navigasi pembina"
                className="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur md:hidden"
            >
                <ul className="mx-auto grid max-w-md grid-cols-4">
                    {pembinaNav.map((item) => (
                        <li key={item.href}>
                            <Link
                                href={item.href}
                                prefetch
                                className={cn(
                                    'flex flex-col items-center gap-1 py-3 text-[11px] font-semibold text-ink-soft',
                                    isActive(item) && 'text-maroon-600',
                                )}
                            >
                                <item.icon
                                    className="size-[22px]"
                                    strokeWidth={isActive(item) ? 2.2 : 1.6}
                                />
                                {item.label}
                            </Link>
                        </li>
                    ))}
                </ul>
            </nav>
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
        <div className="relative overflow-hidden rounded-b-[28px] bg-maroon-gradient px-6 pt-8 pb-16 text-white">
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
