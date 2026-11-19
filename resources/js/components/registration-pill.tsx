import { cn } from '@/lib/utils';

export type RegistrationWindow = {
    state: 'not_open' | 'open' | 'closed';
    opens_at: string | null;
    closes_at: string | null;
    opens_label: string | null;
    closes_label: string | null;
};

/** Pita status jadwal pendaftaran (desain M1: "Pendaftaran dibuka · tutup ..."). */
export function RegistrationPill({
    registration,
    className,
}: {
    registration: RegistrationWindow;
    className?: string;
}) {
    const text = {
        open: `Pendaftaran dibuka · tutup ${registration.closes_label ?? ''}`,
        not_open: `Pendaftaran belum dibuka · buka ${registration.opens_label ?? ''}`,
        closed: 'Pendaftaran sudah ditutup',
    }[registration.state];

    return (
        <p
            className={cn(
                'inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold',
                registration.state === 'open'
                    ? 'bg-gold-100 text-gold-800'
                    : 'bg-maroon-100 text-maroon-600',
                className,
            )}
        >
            <span
                className={cn(
                    'size-2 rounded-full',
                    registration.state === 'open'
                        ? 'bg-success'
                        : 'bg-maroon-600',
                )}
            />
            {text}
        </p>
    );
}
