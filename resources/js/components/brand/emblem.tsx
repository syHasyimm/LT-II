import { useId } from 'react';
import { cn } from '@/lib/utils';

type EmblemProps = {
    className?: string;
    /** `dark` untuk latar marun (default), `light` untuk latar terang. */
    tone?: 'dark' | 'light';
};

/** Lambang lingkaran emas dengan bintang, sesuai logo di desain FigCraft. */
export function Emblem({ className, tone = 'dark' }: EmblemProps) {
    const id = useId();

    return (
        <svg
            viewBox="0 0 44 44"
            aria-hidden="true"
            className={cn('size-11 shrink-0', className)}
        >
            <defs>
                <linearGradient id={`${id}-g`} x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stopColor="#F3E2AE" />
                    <stop offset="0.5" stopColor="#C9A24A" />
                    <stop offset="1" stopColor="#9C7A2B" />
                </linearGradient>
            </defs>
            <circle
                cx="22"
                cy="22"
                r="21"
                fill={tone === 'dark' ? 'rgba(255,255,255,0.08)' : '#FBF7F0'}
                stroke={`url(#${id}-g)`}
                strokeWidth="1.5"
            />
            <path
                fill={`url(#${id}-g)`}
                d="M22 10.5l3.3 7.3 7.9.9-5.9 5.3 1.7 7.8-7-4.1-7 4.1 1.7-7.8-5.9-5.3 7.9-.9z"
            />
        </svg>
    );
}
