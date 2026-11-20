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
    subtitle = 'Gerakan Pramuka Kwartir Ranting Kepenuhan',
    tone = 'dark',
    className,
    size = 'md',
}: BrandMarkProps) {
    return (
        <span className={cn('flex items-center gap-3', className)}>
            <Emblem className={size === 'sm' ? 'size-10' : 'size-12'} />
            <span className="grid min-w-0 leading-tight">
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
                        'max-w-[200px] text-[10px] leading-[1.4] font-semibold tracking-[0.16em] whitespace-normal uppercase',
                        tone === 'dark' ? 'text-gold-300' : 'text-gold-700',
                    )}
                >
                    {subtitle}
                </span>
            </span>
        </span>
    );
}
