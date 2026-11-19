import { ArrowRight, Search } from 'lucide-react';
import { cn } from '@/lib/utils';

/** Form cek nomor seri; dipakai halaman verifikasi dan beranda publik. */
export function SerialForm({
    defaultValue = '',
    className,
    tone = 'light',
}: {
    defaultValue?: string;
    className?: string;
    tone?: 'light' | 'dark';
}) {
    return (
        <form
            action="/verify"
            method="get"
            className={cn('flex gap-3', className)}
        >
            <label className="relative flex-1">
                <Search className="absolute top-1/2 left-4 size-4 -translate-y-1/2 text-gold-700" />
                <span className="sr-only">Nomor seri</span>
                <input
                    name="serial"
                    defaultValue={defaultValue}
                    required
                    placeholder="LT2-2026-CERT-0001"
                    pattern="[A-Za-z0-9\-]{3,40}"
                    autoCapitalize="characters"
                    className={cn(
                        'h-14 w-full rounded-[14px] border bg-white pr-4 pl-11 font-mono text-[15px] tracking-wide text-ink uppercase outline-none placeholder:text-ink-soft/60 focus:border-maroon-600 focus:ring-4 focus:ring-maroon-100',
                        tone === 'dark' ? 'border-transparent' : 'border-line',
                    )}
                />
            </label>
            <button
                type="submit"
                className={cn(
                    'flex h-14 shrink-0 items-center gap-2 rounded-[14px] px-5 text-[15px] font-bold sm:px-8',
                    tone === 'dark'
                        ? 'bg-gold-gradient text-maroon-900'
                        : 'bg-[linear-gradient(135deg,#a51c24,#7a1219)] text-white',
                )}
            >
                <span className="hidden sm:inline">Periksa</span>
                <ArrowRight className="size-4 sm:hidden" />
            </button>
        </form>
    );
}
