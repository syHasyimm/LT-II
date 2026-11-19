import { Check } from 'lucide-react';
import { cn } from '@/lib/utils';

export type GudepStatus = 'draft' | 'submitted' | 'revision' | 'verified';
export type DocumentStatus = 'missing' | 'pending' | 'approved' | 'rejected';

const gudepStyles: Record<GudepStatus, { label: string; className: string }> = {
    draft: { label: 'Draft', className: 'bg-sand text-ink-muted' },
    submitted: { label: 'Dikirim', className: 'bg-gold-100 text-gold-800' },
    revision: {
        label: 'Perlu revisi',
        className: 'bg-maroon-100 text-maroon-600',
    },
    verified: {
        label: 'Terverifikasi',
        className: 'bg-success-soft text-success',
    },
};

const documentStyles: Record<
    DocumentStatus,
    { label: string; className: string }
> = {
    missing: { label: 'Belum', className: 'bg-sand text-ink-muted' },
    pending: { label: 'Diunggah', className: 'bg-gold-100 text-gold-800' },
    approved: {
        label: 'Disetujui',
        className: 'bg-success-soft text-success',
    },
    rejected: { label: 'Ditolak', className: 'bg-maroon-100 text-maroon-600' },
};

function Pill({
    className,
    children,
}: {
    className: string;
    children: React.ReactNode;
}) {
    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap',
                className,
            )}
        >
            {children}
        </span>
    );
}

/** Badge status pendaftaran gudep (desain: Badge/Draft, Dikirim, Revisi, Terverifikasi). */
export function GudepStatusBadge({
    status,
    className,
}: {
    status: GudepStatus;
    className?: string;
}) {
    const style = gudepStyles[status];

    return (
        <Pill className={cn(style.className, className)}>
            {status === 'verified' ? (
                <Check className="size-3" strokeWidth={3} />
            ) : (
                <span className="size-1.5 rounded-full bg-current" />
            )}
            {style.label}
        </Pill>
    );
}

export function DocumentStatusBadge({
    status,
    className,
}: {
    status: DocumentStatus;
    className?: string;
}) {
    const style = documentStyles[status];

    return (
        <Pill className={cn(style.className, className)}>{style.label}</Pill>
    );
}
