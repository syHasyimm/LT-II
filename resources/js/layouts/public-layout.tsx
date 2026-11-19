import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { BrandMark } from '@/components/brand/brand-mark';
import { Emblem } from '@/components/brand/emblem';

const sections = [
    { href: '#ketentuan', label: 'Ketentuan' },
    { href: '#alur', label: 'Alur' },
    { href: '#jadwal', label: 'Jadwal' },
    { href: '#faq', label: 'FAQ' },
];

/** Kerangka halaman publik: konten + footer marun (desain P1/P2). */
export default function PublicLayout({ children }: { children: ReactNode }) {
    return (
        <div className="min-h-screen bg-ivory text-ink">
            {children}
            <PublicFooter />
        </div>
    );
}

/** Navigasi atas yang diletakkan di dalam hero marun. */
export function PublicNav({ actions }: { actions?: ReactNode }) {
    return (
        <header className="relative z-10 mx-auto flex max-w-[1200px] items-center justify-between gap-6 border-b border-gold-300/15 px-6 py-5 xl:px-0">
            <Link href="/" aria-label="Beranda Perkemahan LT 2">
                <BrandMark size="sm" />
            </Link>
            <nav className="hidden items-center gap-9 text-sm font-semibold text-maroon-50 lg:flex">
                {sections.map((section) => (
                    <a
                        key={section.href}
                        href={section.href}
                        className="transition hover:text-gold-300"
                    >
                        {section.label}
                    </a>
                ))}
            </nav>
            <div className="flex items-center gap-3">{actions}</div>
        </header>
    );
}

function PublicFooter() {
    const { name } = usePage().props;

    return (
        <footer className="bg-maroon-950 text-maroon-50/80">
            <div className="mx-auto grid max-w-[1200px] gap-10 px-6 py-14 md:grid-cols-[1.4fr_1fr_1fr] xl:px-0">
                <div className="flex gap-3">
                    <Emblem className="size-10" />
                    <div>
                        <p className="font-display text-lg font-bold text-white">
                            {name}
                        </p>
                        <p className="mt-1 max-w-sm text-sm leading-relaxed text-maroon-50/65">
                            Sistem pendaftaran gugus depan untuk Perkemahan
                            Lomba Tingkat 2.
                        </p>
                    </div>
                </div>
                <FooterColumn
                    title="Navigasi"
                    links={[
                        { href: '#ketentuan', label: 'Ketentuan' },
                        { href: '#alur', label: 'Alur pendaftaran' },
                        { href: '#faq', label: 'Pertanyaan umum' },
                    ]}
                />
                <FooterColumn
                    title="Akses"
                    links={[
                        { href: '/login', label: 'Masuk Pembina' },
                        { href: '/admin/login', label: 'Masuk Admin' },
                    ]}
                />
            </div>
            <div className="mx-auto max-w-[1200px] border-t border-gold-300/10 px-6 py-5 text-xs text-maroon-50/50 xl:px-0">
                © {new Date().getFullYear()} Panitia Perkemahan Lomba Tingkat 2.
            </div>
        </footer>
    );
}

function FooterColumn({
    title,
    links,
}: {
    title: string;
    links: { href: string; label: string }[];
}) {
    return (
        <div>
            <p className="text-[11px] font-semibold tracking-[0.22em] text-gold-500 uppercase">
                {title}
            </p>
            <ul className="mt-3 grid gap-2 text-sm">
                {links.map((link) => (
                    <li key={link.href}>
                        <a href={link.href} className="hover:text-gold-300">
                            {link.label}
                        </a>
                    </li>
                ))}
            </ul>
        </div>
    );
}
