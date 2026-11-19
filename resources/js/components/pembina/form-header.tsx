import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import type { ReactNode } from 'react';

/** Judul halaman form terang dengan tombol kembali bulat (desain M3b). */
export function FormHeader({
    title,
    subtitle,
    back,
}: {
    title: ReactNode;
    subtitle?: ReactNode;
    back: string;
}) {
    return (
        <div className="flex items-center gap-4 px-6 pt-8 pb-2">
            <Link
                href={back}
                aria-label="Kembali"
                className="grid size-11 shrink-0 place-items-center rounded-full border border-line bg-white text-ink transition hover:border-gold-500"
            >
                <ArrowLeft className="size-5" />
            </Link>
            <div className="min-w-0">
                <h1 className="font-display text-[26px] leading-tight font-bold text-ink">
                    {title}
                </h1>
                {subtitle && (
                    <p className="mt-0.5 text-sm font-medium text-gold-700">
                        {subtitle}
                    </p>
                )}
            </div>
        </div>
    );
}

/** Bilah aksi bawah yang menempel di layar ponsel (desain M3b: Batal / Simpan). */
export function StickyActions({ children }: { children: ReactNode }) {
    return (
        <div className="sticky bottom-[72px] z-20 -mx-6 mt-8 border-t border-line bg-white/95 px-6 py-4 backdrop-blur md:static md:mx-0 md:mt-10 md:rounded-2xl md:border">
            <div className="flex gap-3">{children}</div>
        </div>
    );
}
