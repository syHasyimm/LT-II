import { Emblem } from '@/components/brand/emblem';
import { cn } from '@/lib/utils';

type BrandMarkProps = {
    subtitle?: string;
    tone?: 'dark' | 'light';
    className?: string;
    size?: 'sm' | 'md';
};

/** Logo + nama acara. `tone="dark"` dipakai di atas latar marun. */
export function BrandMark({
    subtitle = 'Gugus Depan · 2026',
    tone = 'dark',
    className,
    size = 'md',
}: BrandMarkProps) {
    return (
        <span className={cn('flex items-center gap-3', className)}>
            <Emblem
                tone={tone}
                className={size === 'sm' ? 'size-9' : 'size-11'}
            />
            <span className="grid leading-tight">
                <span
                    className={cn(
                        'font-display font-bold whitespace-nowrap',
                        size === 'sm' ? 'text-[17px]' : 'text-xl',
                        tone === 'dark' ? 'text-white' : 'text-ink',
                    )}
                >
                    Perkemahan LT 2
                </span>
                <span
                    className={cn(
                        'text-[10px] font-semibold tracking-[0.24em] whitespace-nowrap uppercase',
                        tone === 'dark' ? 'text-gold-300' : 'text-gold-700',
                    )}
                >
                    {subtitle}
                </span>
            </span>
        </span>
    );
}
