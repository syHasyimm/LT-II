import { Link, usePage } from '@inertiajs/react';
import {
    CalendarClock,
    FileBadge2,
    Home,
    IdCard,
    LayoutTemplate,
    LogOut,
    Menu,
    Settings,
    Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { BrandMark } from '@/components/brand/brand-mark';
import {
    Sheet,
    SheetContent,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';

type AdminNavItem = {
    href: string;
    label: string;
    icon: LucideIcon;
    exact?: boolean;
    soon?: boolean;
    badgeKey?: 'submitted';
};

const adminNav: AdminNavItem[] = [
    { href: '/admin', label: 'Dashboard', icon: Home, exact: true },
    {
        href: '/admin/gudep',
        label: 'Gudep & Verifikasi',
        icon: Users,
        badgeKey: 'submitted',
    },
    { href: '/admin/template', label: 'Template', icon: LayoutTemplate },
    { href: '/admin/id-card', label: 'ID Card', icon: IdCard },
    { href: '/admin/sertifikat', label: 'Sertifikat', icon: FileBadge2 },
    { href: '/admin/jadwal', label: 'Jadwal Pendaftaran', icon: CalendarClock },
];

/** Kerangka panel admin dengan sidebar marun (desain D1–D3). */
export default function AdminLayout({ children }: { children: ReactNode }) {
    return (
        <div className="min-h-screen bg-ivory text-ink lg:pl-[264px]">
            <aside className="fixed inset-y-0 left-0 hidden w-[264px] lg:block">
                <AdminSidebar />
            </aside>

            <div className="sticky top-0 z-30 flex items-center justify-between bg-maroon-900 px-4 py-3 lg:hidden">
                <BrandMark size="sm" subtitle="Panel Admin · 2026" />
                <Sheet>
                    <SheetTrigger
                        className="rounded-xl border border-gold-300/30 p-2 text-gold-300"
                        aria-label="Buka menu"
                    >
                        <Menu className="size-5" />
                    </SheetTrigger>
                    <SheetContent
                        side="left"
                        className="w-[264px] border-0 p-0"
                    >
                        <SheetTitle className="sr-only">Menu admin</SheetTitle>
                        <AdminSidebar />
                    </SheetContent>
                </Sheet>
            </div>

            <main className="px-4 py-8 sm:px-8 lg:px-10 lg:py-10">
                {children}
            </main>
        </div>
    );
}

function AdminSidebar() {
    const { auth, adminCounters } = usePage<{
        adminCounters?: { submitted?: number };
    }>().props;
    const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();
    const getInitials = useInitials();

    return (
        <div className="relative flex h-full flex-col overflow-hidden bg-[linear-gradient(170deg,#6e1218_0%,#3e070b_55%,#2a0407_100%)] px-4 py-6 text-maroon-50">
            <div className="pointer-events-none absolute -bottom-40 -left-40 size-96 rounded-full border border-gold-300/10" />
            <Link href="/admin" className="px-2">
                <BrandMark size="sm" subtitle="Panel Admin · 2026" />
            </Link>
            <div className="mx-2 my-6 h-px bg-gold-300/20" />

            <nav className="relative grid gap-1">
                {adminNav.map((item) => {
                    const active = item.exact
                        ? isCurrentUrl(item.href)
                        : isCurrentOrParentUrl(item.href);
                    const badge = item.badgeKey
                        ? adminCounters?.[item.badgeKey]
                        : undefined;
                    const className = cn(
                        'flex items-center gap-3 rounded-xl border px-3.5 py-2.5 text-[15px] transition',
                        active
                            ? 'border-gold-300/60 bg-white/10 font-bold text-white'
                            : 'border-transparent text-maroon-50/85 hover:bg-white/5',
                        item.soon &&
                            'cursor-not-allowed opacity-45 hover:bg-transparent',
                    );

                    const content = (
                        <>
                            <item.icon
                                className="size-[18px]"
                                strokeWidth={1.7}
                            />
                            <span className="flex-1">{item.label}</span>
                            {item.soon && (
                                <span className="text-[10px] tracking-wider uppercase">
                                    Segera
                                </span>
                            )}
                            {!!badge && (
                                <span className="rounded-full bg-gold-500 px-2 text-xs font-bold text-maroon-900">
                                    {badge}
                                </span>
                            )}
                        </>
                    );

                    return item.soon ? (
                        <span
                            key={item.href}
                            className={className}
                            aria-disabled
                        >
                            {content}
                        </span>
                    ) : (
                        <Link
                            key={item.href}
                            href={item.href}
                            prefetch
                            className={className}
                        >
                            {content}
                        </Link>
                    );
                })}
            </nav>

            {auth.user && (
                <div className="relative mt-auto rounded-2xl border border-gold-300/20 bg-white/5 p-3">
                    <div className="flex items-center gap-3">
                        <span className="grid size-9 place-items-center rounded-full bg-gold-gradient text-sm font-bold text-maroon-900">
                            {getInitials(auth.user.name)}
                        </span>
                        <div className="min-w-0 flex-1 leading-tight">
                            <p className="truncate text-sm font-bold text-white">
                                {auth.user.name}
                            </p>
                            <p className="truncate text-xs text-maroon-50/70">
                                {auth.user.email}
                            </p>
                        </div>
                    </div>
                    <div className="mt-3 flex gap-2">
                        <Link
                            href="/settings/profile"
                            className="flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-white/5 py-1.5 text-xs font-semibold hover:bg-white/10"
                        >
                            <Settings className="size-3.5" /> Akun
                        </Link>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            className="flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-white/5 py-1.5 text-xs font-semibold hover:bg-white/10"
                        >
                            <LogOut className="size-3.5" /> Keluar
                        </Link>
                    </div>
                </div>
            )}
        </div>
    );
}

/** Judul halaman admin (Playfair) dengan subjudul dan aksi di kanan. */
export function AdminPageHeader({
    title,
    description,
    actions,
}: {
    title: ReactNode;
    description?: ReactNode;
    actions?: ReactNode;
}) {
    return (
        <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 className="font-display text-[34px] leading-tight font-bold text-ink">
                    {title}
                </h1>
                {description && (
                    <p className="mt-1 text-[15px] text-ink-muted">
                        {description}
                    </p>
                )}
            </div>
            {actions && (
                <div className="flex items-center gap-3">{actions}</div>
            )}
        </div>
    );
}
