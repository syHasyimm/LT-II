import { Check, Minus, Plus, X } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { DocumentData } from '@/types';

export type CellState = 'missing' | 'pending' | 'approved' | 'rejected' | 'na';

export function cellState(
    required: boolean,
    document: DocumentData | null,
): CellState {
    if (!required) {
        return 'na';
    }

    return document?.status ?? 'missing';
}

const labels: Record<CellState, string> = {
    missing: 'belum diunggah',
    pending: 'diunggah, menunggu verifikasi',
    approved: 'disetujui',
    rejected: 'ditolak',
    na: 'tidak wajib',
};

/** Sel matriks dokumen (desain M4): ✓ diunggah, + belum, — tidak wajib. */
export function DocCell({
    state,
    label,
    onClick,
}: {
    state: CellState;
    label: string;
    onClick?: () => void;
}) {
    if (state === 'na') {
        return (
            <span
                className="grid place-items-center text-line"
                aria-label={`${label}: ${labels.na}`}
            >
                <Minus className="size-5" />
            </span>
        );
    }

    return (
        <button
            type="button"
            onClick={onClick}
            aria-label={`${label}: ${labels[state]}`}
            className={cn(
                'mx-auto grid size-9 place-items-center rounded-full transition hover:scale-105',
                state === 'pending' && 'bg-gold-100 text-gold-700',
                state === 'approved' && 'bg-success-soft text-success',
                state === 'rejected' && 'bg-maroon-600 text-white',
                state === 'missing' &&
                    'border-[1.5px] border-dashed border-maroon-600 text-maroon-600',
            )}
        >
            {state === 'missing' && <Plus className="size-4" />}
            {(state === 'pending' || state === 'approved') && (
                <Check className="size-4" />
            )}
            {state === 'rejected' && <X className="size-4" />}
        </button>
    );
}
