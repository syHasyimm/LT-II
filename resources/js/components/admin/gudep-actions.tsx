import { Form } from '@inertiajs/react';
import { CalendarClock, Check, RotateCcw } from 'lucide-react';
import { Field } from '@/components/form/field';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { approve, extend, reject } from '@/routes/admin/gudeps';

type GudepInfo = {
    id: number;
    short_name: string;
    deadline_extended_until: string | null;
    revision_deadline: string | null;
    extension_label: string | null;
};

/** Minta revisi dengan catatan dan tenggat (FR-VER-04, FR-PRD-05). */
export function RequestRevisionDialog({
    gudep,
    disabled,
    registrationOpen,
}: {
    gudep: GudepInfo;
    disabled: boolean;
    registrationOpen: boolean;
}) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="outline-maroon" size="xl" disabled={disabled}>
                    <RotateCcw className="size-4" /> Minta revisi
                </Button>
            </DialogTrigger>
            <DialogContent className="rounded-3xl border-line bg-ivory sm:max-w-lg">
                <DialogTitle className="font-display text-2xl text-ink">
                    Minta revisi
                </DialogTitle>
                <DialogDescription className="text-sm text-ink-muted">
                    Status {gudep.short_name} menjadi Perlu revisi dan catatan
                    tampil di beranda pembina. Pembina mendapat email
                    pemberitahuan.
                </DialogDescription>
                <Form {...reject.form(gudep.id)} className="grid gap-4">
                    {({ processing, errors }) => (
                        <>
                            <Field
                                id="revision-note"
                                label="Catatan untuk pembina"
                                error={errors.note}
                            >
                                <textarea
                                    id="revision-note"
                                    name="note"
                                    rows={5}
                                    required
                                    placeholder="Contoh: surat kesehatan dua peserta putri tidak terbaca, mohon unggah ulang."
                                    className="w-full rounded-xl border border-line bg-white px-4 py-3 text-[15px] outline-none focus:border-maroon-600 focus:ring-4 focus:ring-maroon-100"
                                />
                            </Field>
                            <Field
                                id="revision-deadline"
                                label={
                                    registrationOpen
                                        ? 'Tenggat revisi (opsional)'
                                        : 'Tenggat revisi'
                                }
                                error={errors.revision_deadline}
                                hint={
                                    registrationOpen
                                        ? 'Selama pendaftaran dibuka, pembina bisa memperbaiki sampai jadwal tutup.'
                                        : 'Pendaftaran sudah ditutup: tanpa tenggat, gudep tetap terkunci.'
                                }
                            >
                                <Input
                                    id="revision-deadline"
                                    name="revision_deadline"
                                    type="datetime-local"
                                    defaultValue={
                                        gudep.revision_deadline ?? undefined
                                    }
                                    required={!registrationOpen}
                                />
                            </Field>
                            <Button
                                type="submit"
                                size="xl"
                                disabled={processing}
                            >
                                {processing && <Spinner />}
                                Kembalikan untuk revisi
                            </Button>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

/** Perpanjangan batas pendaftaran untuk satu gudep (FR-PRD-04). */
export function ExtensionDialog({ gudep }: { gudep: GudepInfo }) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="outline" size="xl" className="rounded-[14px]">
                    <CalendarClock className="size-4" />
                    {gudep.extension_label
                        ? `Perpanjangan s.d. ${gudep.extension_label}`
                        : 'Perpanjangan'}
                </Button>
            </DialogTrigger>
            <DialogContent className="rounded-3xl border-line bg-ivory sm:max-w-md">
                <DialogTitle className="font-display text-2xl text-ink">
                    Perpanjangan
                </DialogTitle>
                <DialogDescription className="text-sm text-ink-muted">
                    {gudep.short_name} tetap bisa mengubah dan mengirim
                    pendaftaran sampai batas ini walau jadwal umum sudah
                    ditutup. Kosongkan untuk menghapus perpanjangan.
                </DialogDescription>
                <Form {...extend.form(gudep.id)} className="grid gap-4">
                    {({ processing, errors }) => (
                        <>
                            <Field
                                id="extension"
                                label="Diperpanjang sampai"
                                error={errors.deadline_extended_until}
                                hint="Zona waktu Asia/Jakarta (WIB)"
                            >
                                <Input
                                    id="extension"
                                    name="deadline_extended_until"
                                    type="datetime-local"
                                    defaultValue={
                                        gudep.deadline_extended_until ??
                                        undefined
                                    }
                                />
                            </Field>
                            <Button
                                type="submit"
                                size="xl"
                                disabled={processing}
                            >
                                {processing && <Spinner />}
                                Simpan perpanjangan
                            </Button>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

/** Verifikasi gudep: aktif setelah semua dokumen disetujui (FR-VER-03). */
export function VerifyButton({
    gudepId,
    enabled,
}: {
    gudepId: number;
    enabled: boolean;
}) {
    return (
        <Form {...approve.form(gudepId)}>
            {({ processing }) => (
                <Button
                    type="submit"
                    variant="gold"
                    size="xl"
                    disabled={!enabled || processing}
                    className="disabled:opacity-50"
                >
                    {processing ? <Spinner /> : <Check className="size-4" />}
                    Verifikasi gudep
                </Button>
            )}
        </Form>
    );
}
