import { cn } from '@/lib/utils';

type QuotaIndicatorProps = {
    label: string;
    count: number;
    max: number;
    className?: string;
};

/** Indikator kuota regu "x/8" (desain: Quota indicator). */
export function QuotaIndicator({
    label,
    count,
    max,
    className,
}: QuotaIndicatorProps) {
    const full = count >= max;
    const percent = Math.min(100, Math.round((count / max) * 100));

    return (
        <div className={cn('grid gap-2', className)}>
            <div className="flex items-baseline justify-between text-sm">
                <span className="font-semibold text-ink">{label}</span>
                <span
                    className={cn(
                        'font-bold tabular-nums',
                        full ? 'text-success' : 'text-gold-800',
                    )}
                >
                    {count}/{max}
                </span>
            </div>
            <div
                className="h-1.5 overflow-hidden rounded-full bg-sand"
                role="progressbar"
                aria-label={label}
                aria-valuenow={count}
                aria-valuemin={0}
                aria-valuemax={max}
            >
                <div
                    className={cn(
                        'h-full rounded-full',
                        full ? 'bg-success' : 'bg-gold-gradient',
                    )}
                    style={{ width: `${percent}%` }}
                />
            </div>
        </div>
    );
}
