import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type FieldProps = {
    id: string;
    label: string;
    error?: string;
    hint?: ReactNode;
    className?: string;
    children: ReactNode;
};

/** Label + kontrol + pesan galat, gaya form desain M3b. */
export function Field({
    id,
    label,
    error,
    hint,
    className,
    children,
}: FieldProps) {
    return (
        <div className={cn('grid gap-2', className)}>
            <Label htmlFor={id}>{label}</Label>
            {children}
            {hint && !error && <p className="text-xs text-ink-soft">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}

/** Kotak info emas, misalnya aturan kuota (desain M3b). */
export function InfoNote({
    children,
    tone = 'gold',
    className,
}: {
    children: ReactNode;
    tone?: 'gold' | 'maroon' | 'green';
    className?: string;
}) {
    return (
        <div
            className={cn(
                'flex gap-3 rounded-2xl px-4 py-3.5 text-sm leading-relaxed',
                tone === 'gold' && 'bg-gold-100/70 text-gold-800',
                tone === 'maroon' && 'bg-maroon-100 text-maroon-700',
                tone === 'green' && 'bg-success-soft text-success',
                className,
            )}
        >
            {children}
        </div>
    );
}
