import { Form } from '@inertiajs/react';
import {
    CircleAlert,
    CircleCheck,
    Download,
    FileSpreadsheet,
    Upload,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { store, template } from '@/routes/pembina/members/import';

type ImportReport = {
    added: number;
    skipped: { row: number; name: string; reason: string }[];
};

/**
 * Impor peserta dari Excel/CSV (FR-GDP-06): unduh template, unggah file, lalu
 * tampilkan baris yang dilewati beserta alasannya.
 */
export function ImportPeserta({ remaining }: { remaining: number }) {
    const [open, setOpen] = useState(false);
    const [report, setReport] = useState<ImportReport | null>(null);
    const [fileName, setFileName] = useState<string | null>(null);

    const reset = (next: boolean) => {
        setOpen(next);

        if (!next) {
            setReport(null);
            setFileName(null);
        }
    };

    return (
        <Dialog open={open} onOpenChange={reset}>
            <DialogTrigger asChild>
                <button
                    type="button"
                    className="flex items-center justify-center gap-2 rounded-2xl border border-line bg-white py-4 text-[15px] font-semibold text-ink-muted transition hover:border-gold-500 hover:text-maroon-700"
                >
                    <FileSpreadsheet className="size-5" aria-hidden />
                    Impor banyak peserta dari Excel
                </button>
            </DialogTrigger>
            <DialogContent className="max-h-[90dvh] overflow-y-auto rounded-3xl border-line bg-ivory sm:max-w-lg">
                <DialogTitle className="font-display text-2xl text-ink">
                    Impor peserta
                </DialogTitle>
                <DialogDescription className="text-sm leading-relaxed text-ink-muted">
                    Tambahkan peserta Putra dan Putri sekaligus dari file Excel
                    atau CSV. Masih ada {remaining} slot kosong. Pas foto
                    diunggah satu per satu setelahnya.
                </DialogDescription>

                {report ? (
                    <ReportView report={report} onDone={() => reset(false)} />
                ) : (
                    <Form
                        {...store.form()}
                        options={{ preserveScroll: true }}
                        onSuccess={(page) => {
                            const flash = page.flash as {
                                import?: ImportReport;
                            };
                            setReport(flash.import ?? null);
                        }}
                        className="grid gap-4"
                    >
                        {({ processing, errors }) => (
                            <>
                                <ol className="grid gap-3 text-sm text-ink">
                                    <li className="flex items-start gap-3 rounded-2xl border border-line bg-white p-4">
                                        <Step n={1} />
                                        <div className="min-w-0 flex-1">
                                            <p className="font-semibold">
                                                Unduh dan isi template
                                            </p>
                                            <p className="mt-0.5 text-ink-muted">
                                                Kolom: Regu (Putra/Putri), Nama
                                                lengkap, Tanggal lahir
                                                (15/03/2013), Nomor KTA.
                                            </p>
                                            <a
                                                href={template.url()}
                                                className="mt-2 inline-flex items-center gap-1.5 font-semibold text-maroon-700 underline-offset-4 hover:underline"
                                            >
                                                <Download
                                                    className="size-4"
                                                    aria-hidden
                                                />
                                                Template impor (.xlsx)
                                            </a>
                                        </div>
                                    </li>
                                    <li className="flex items-start gap-3 rounded-2xl border border-line bg-white p-4">
                                        <Step n={2} />
                                        <div className="min-w-0 flex-1">
                                            <label
                                                htmlFor="import-file"
                                                className="font-semibold"
                                            >
                                                Pilih file yang sudah diisi
                                            </label>
                                            <p className="mt-0.5 text-ink-muted">
                                                Format .xlsx atau .csv, maksimal
                                                1 MB.
                                            </p>
                                            <label className="mt-2 flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-gold-500 px-4 py-3 focus-within:ring-2 focus-within:ring-gold-500">
                                                <Upload
                                                    className="size-5 shrink-0 text-maroon-700"
                                                    aria-hidden
                                                />
                                                <span className="min-w-0 flex-1 truncate font-medium text-ink">
                                                    {fileName ??
                                                        'Ketuk untuk memilih file'}
                                                </span>
                                                <input
                                                    id="import-file"
                                                    type="file"
                                                    name="file"
                                                    required
                                                    accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv"
                                                    className="sr-only"
                                                    aria-invalid={
                                                        errors.file
                                                            ? true
                                                            : undefined
                                                    }
                                                    aria-describedby={
                                                        errors.file
                                                            ? 'import-file-error'
                                                            : undefined
                                                    }
                                                    onChange={(event) =>
                                                        setFileName(
                                                            event.target
                                                                .files?.[0]
                                                                ?.name ?? null,
                                                        )
                                                    }
                                                />
                                            </label>
                                            {errors.file && (
                                                <p
                                                    id="import-file-error"
                                                    role="alert"
                                                    className="mt-2 text-sm font-medium text-maroon-600"
                                                >
                                                    {errors.file}
                                                </p>
                                            )}
                                        </div>
                                    </li>
                                </ol>
                                <Button
                                    type="submit"
                                    size="xl"
                                    disabled={processing || !fileName}
                                >
                                    {processing && <Spinner />}
                                    Impor peserta
                                </Button>
                            </>
                        )}
                    </Form>
                )}
            </DialogContent>
        </Dialog>
    );
}

function Step({ n }: { n: number }) {
    return (
        <span
            aria-hidden
            className="grid size-7 shrink-0 place-items-center rounded-full bg-maroon-gradient text-xs font-bold text-white"
        >
            {n}
        </span>
    );
}

function ReportView({
    report,
    onDone,
}: {
    report: ImportReport;
    onDone: () => void;
}) {
    return (
        <div className="grid gap-4" role="status">
            <div className="flex items-center gap-3 rounded-2xl border border-line bg-white p-4">
                <CircleCheck
                    className={
                        report.added > 0
                            ? 'size-6 shrink-0 text-success'
                            : 'size-6 shrink-0 text-ink-soft'
                    }
                    aria-hidden
                />
                <p className="text-[15px] font-semibold text-ink">
                    {report.added > 0
                        ? `${report.added} peserta ditambahkan.`
                        : 'Tidak ada peserta yang ditambahkan.'}
                </p>
            </div>

            {report.skipped.length > 0 && (
                <div className="rounded-2xl border border-maroon-600/30 bg-white">
                    <p className="flex items-center gap-2 border-b border-line px-4 py-3 text-sm font-bold text-maroon-700">
                        <CircleAlert className="size-4" aria-hidden />
                        {report.skipped.length} baris dilewati
                    </p>
                    <ul className="max-h-64 divide-y divide-line overflow-y-auto text-sm">
                        {report.skipped.map((row) => (
                            <li key={row.row} className="px-4 py-2.5">
                                <p className="font-semibold text-ink">
                                    Baris {row.row} · {row.name}
                                </p>
                                <p className="text-ink-muted">{row.reason}</p>
                            </li>
                        ))}
                    </ul>
                    <p className="border-t border-line px-4 py-3 text-xs text-ink-soft">
                        Perbaiki baris tersebut di file, lalu impor ulang. Baris
                        yang sudah masuk tidak akan terduplikasi.
                    </p>
                </div>
            )}

            <Button size="xl" onClick={onDone}>
                Selesai
            </Button>
        </div>
    );
}
