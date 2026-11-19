import { Link } from '@inertiajs/react';
import { Emblem } from '@/components/brand/emblem';
import type { AuthLayoutProps } from '@/types';

/**
 * Kerangka halaman autentikasi admin (masuk, lupa kata sandi, 2FA): latar
 * marun bergradasi dengan kartu gading, mengikuti gaya desain M1.
 */
export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="relative flex min-h-svh flex-col items-center justify-center overflow-hidden bg-maroon-gradient p-6 md:p-10">
            <div
                aria-hidden
                className="pointer-events-none absolute -top-40 -left-40 size-[520px] rounded-full border border-gold-300/15"
            />
            <div
                aria-hidden
                className="pointer-events-none absolute -right-32 -bottom-32 size-[420px] rounded-full border border-gold-300/10"
            />

            <div className="relative w-full max-w-md">
                <Link
                    href="/"
                    className="mx-auto mb-6 flex w-fit flex-col items-center gap-3 text-center"
                >
                    <Emblem className="size-16" />
                    <span className="text-[11px] font-semibold tracking-[0.26em] text-gold-300 uppercase">
                        Perkemahan LT 2 · Panel Admin
                    </span>
                </Link>

                <div className="rounded-[28px] bg-ivory px-6 py-8 shadow-[0_30px_80px_rgba(20,2,4,0.45)] sm:px-9">
                    <div className="mb-6 space-y-1.5">
                        <h1 className="font-display text-[28px] leading-tight font-semibold text-ink">
                            {title}
                        </h1>
                        {description && (
                            <p className="text-sm text-ink-muted">
                                {description}
                            </p>
                        )}
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
