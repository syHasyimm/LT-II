import { cn } from '@/lib/utils';

type EmblemProps = {
    className?: string;
};

/** Logo resmi Perkemahan LT 2 Kwarcan Kepenuhan 2026. */
export function Emblem({ className }: EmblemProps) {
    return (
        <span
            className={cn(
                'grid shrink-0 place-items-center overflow-hidden rounded-[22%] bg-white shadow-sm ring-1 ring-black/5',
                className,
            )}
        >
            <img
                src="/images/logo-lt2.png"
                alt=""
                className="size-full object-contain"
            />
        </span>
    );
}
