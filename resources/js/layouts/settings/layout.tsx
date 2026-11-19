import { Link } from '@inertiajs/react';
import { KeyRound, UserRound } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { AdminPageHeader } from '@/layouts/admin-layout';
import { cn, toUrl } from '@/lib/utils';
import { edit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';

const items = [
    { title: 'Profil', href: edit(), icon: UserRound },
    { title: 'Keamanan', href: editSecurity(), icon: KeyRound },
];

/** Pengaturan akun admin: profil, kata sandi, 2FA, dan passkey (FR-AUTH-05). */
export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <>
            <AdminPageHeader
                title="Akun"
                description="Kelola profil, kata sandi, dan autentikasi dua langkah akun admin Anda."
            />

            <nav
                aria-label="Pengaturan akun"
                className="mb-6 inline-flex gap-1 rounded-2xl bg-sand p-1.5"
            >
                {items.map((item) => {
                    const active = isCurrentOrParentUrl(item.href);

                    return (
                        <Link
                            key={toUrl(item.href)}
                            href={item.href}
                            aria-current={active ? 'page' : undefined}
                            className={cn(
                                'flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-ink-muted transition hover:text-maroon-700',
                                active && 'bg-white text-maroon-600 shadow-sm',
                            )}
                        >
                            <item.icon className="size-4" aria-hidden />
                            {item.title}
                        </Link>
                    );
                })}
            </nav>

            <section className="grid max-w-3xl gap-6">{children}</section>
        </>
    );
}
