import { Form } from '@inertiajs/react';
import { FileText, MessageSquareWarning, Upload } from 'lucide-react';
import { useState } from 'react';
import { DocumentPreview } from '@/components/documents/document-preview';
import { DocumentStatusBadge } from '@/components/status-badge';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/pembina/documents';
import type { DocumentSlot } from '@/types';

/**
 * Dialog satu slot dokumen: status, catatan admin, buka file, dan unggah
 * atau ganti dokumen (FR-DOC-01, FR-DOC-03).
 */
export function UploadDialog({
    slot,
    editable,
    maxUploadMb,
    onClose,
}: {
    slot: DocumentSlot | null;
    editable: boolean;
    maxUploadMb: number;
    onClose: () => void;
}) {
    const [fileName, setFileName] = useState<string | null>(null);
    const document = slot?.document ?? null;

    return (
        <Dialog
            open={slot !== null}
            onOpenChange={(open) => {
                if (!open) {
                    setFileName(null);
                    onClose();
                }
            }}
        >
            <DialogContent className="max-h-[92vh] overflow-y-auto rounded-3xl border-line bg-ivory sm:max-w-lg">
                {slot && (
                    <>
                        <DialogTitle className="font-display text-2xl text-ink">
                            {slot.typeLabel}
                        </DialogTitle>
                        <DialogDescription className="text-sm text-ink-muted">
                            {slot.ownerName}
                        </DialogDescription>

                        {document ? (
                            <div className="flex items-center gap-3 rounded-2xl border border-line bg-white p-4">
                                <span className="grid size-11 shrink-0 place-items-center rounded-xl bg-maroon-100 text-maroon-600">
                                    <FileText className="size-5" />
                                </span>
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-semibold text-ink">
                                        {document.original_name}
                                    </p>
                                    <p className="text-xs text-ink-soft">
                                        {document.size_label} ·{' '}
                                        {document.uploaded_at}
                                    </p>
                                </div>
                                <DocumentStatusBadge status={document.status} />
                            </div>
                        ) : (
                            <p className="rounded-2xl border border-dashed border-maroon-600/40 bg-white px-4 py-3 text-sm text-maroon-700">
                                Dokumen belum diunggah.
                            </p>
                        )}

                        {document?.status === 'rejected' && document.note && (
                            <div className="flex gap-3 rounded-2xl bg-maroon-100 px-4 py-3 text-sm text-maroon-700">
                                <MessageSquareWarning className="mt-0.5 size-4 shrink-0" />
                                <p>
                                    <span className="font-bold">
                                        Catatan panitia:
                                    </span>{' '}
                                    {document.note}
                                </p>
                            </div>
                        )}

                        {document && <DocumentPreview document={document} />}

                        {editable && (
                            <Form
                                {...store.form()}
                                options={{ preserveScroll: true }}
                                onSuccess={() => {
                                    setFileName(null);
                                    onClose();
                                }}
                                className="grid gap-3"
                            >
                                {({ processing, progress, errors }) => (
                                    <>
                                        <input
                                            type="hidden"
                                            name="document_type_id"
                                            value={slot.typeId}
                                        />
                                        {slot.memberId !== null && (
                                            <input
                                                type="hidden"
                                                name="member_id"
                                                value={slot.memberId}
                                            />
                                        )}
                                        <label className="flex cursor-pointer items-center gap-3 rounded-2xl border-[1.5px] border-dashed border-gold-500 bg-white px-4 py-4">
                                            <Upload className="size-5 text-gold-700" />
                                            <span className="min-w-0 flex-1">
                                                <span className="block truncate text-sm font-bold text-maroon-700">
                                                    {fileName ??
                                                        (document
                                                            ? 'Pilih file pengganti'
                                                            : 'Pilih file')}
                                                </span>
                                                <span className="block text-xs text-ink-soft">
                                                    PDF, JPG, atau PNG · maks.{' '}
                                                    {maxUploadMb} MB
                                                </span>
                                            </span>
                                            <input
                                                type="file"
                                                name="file"
                                                accept="application/pdf,image/jpeg,image/png"
                                                className="sr-only"
                                                required
                                                onChange={(event) =>
                                                    setFileName(
                                                        event.target.files?.[0]
                                                            ?.name ?? null,
                                                    )
                                                }
                                            />
                                        </label>
                                        <InputError
                                            message={
                                                errors.file ??
                                                errors.member_id ??
                                                errors.document_type_id
                                            }
                                        />
                                        {processing && progress && (
                                            <div
                                                role="progressbar"
                                                aria-label="Kemajuan unggah"
                                                aria-valuemin={0}
                                                aria-valuemax={100}
                                                aria-valuenow={
                                                    progress.percentage ?? 0
                                                }
                                                className="grid gap-1"
                                            >
                                                <div className="h-2 overflow-hidden rounded-full bg-sand">
                                                    <div
                                                        className="h-full rounded-full bg-gold-500 transition-[width]"
                                                        style={{
                                                            width: `${progress.percentage ?? 0}%`,
                                                        }}
                                                    />
                                                </div>
                                                <p className="text-center text-xs text-ink-soft">
                                                    Mengunggah{' '}
                                                    {progress.percentage ?? 0}%
                                                </p>
                                            </div>
                                        )}
                                        <Button
                                            type="submit"
                                            size="xl"
                                            disabled={processing || !fileName}
                                        >
                                            {processing && <Spinner />}
                                            {document
                                                ? 'Ganti dokumen'
                                                : 'Unggah dokumen'}
                                        </Button>
                                        {document && (
                                            <p className="text-center text-xs text-ink-soft">
                                                File lama diarsipkan dan status
                                                kembali menunggu verifikasi.
                                            </p>
                                        )}
                                    </>
                                )}
                            </Form>
                        )}
                    </>
                )}
            </DialogContent>
        </Dialog>
    );
}
