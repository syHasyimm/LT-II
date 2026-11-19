import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

/** Dialog konfirmasi untuk aksi yang tidak bisa dibatalkan (hapus, kirim). */
export function ConfirmDialog({
    trigger,
    title,
    description,
    confirmLabel,
    onConfirm,
    processing = false,
    tone = 'danger',
}: {
    trigger: ReactNode;
    title: string;
    description: ReactNode;
    confirmLabel: string;
    onConfirm: () => void;
    processing?: boolean;
    tone?: 'danger' | 'primary';
}) {
    return (
        <Dialog>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent className="rounded-3xl border-line bg-ivory sm:max-w-md">
                <DialogTitle className="font-display text-2xl text-ink">
                    {title}
                </DialogTitle>
                <DialogDescription className="text-sm leading-relaxed text-ink-muted">
                    {description}
                </DialogDescription>
                <DialogFooter className="mt-2 gap-3 sm:gap-3">
                    <DialogClose asChild>
                        <Button
                            variant="outline"
                            size="lg"
                            className="rounded-xl"
                        >
                            Batal
                        </Button>
                    </DialogClose>
                    <Button
                        size="lg"
                        variant={tone === 'danger' ? 'destructive' : 'default'}
                        className="rounded-xl"
                        disabled={processing}
                        onClick={onConfirm}
                    >
                        {confirmLabel}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
