import { Head, Link, router } from '@inertiajs/react';
import {
    Check,
    ChevronLeft,
    ChevronRight,
    FileDown,
    ImageOff,
    MessageSquareWarning,
    X,
} from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
    ExtensionDialog,
    RequestRevisionDialog,
    VerifyButton,
} from '@/components/admin/gudep-actions';
import { DocumentPreview } from '@/components/documents/document-preview';
import { MemberAvatar } from '@/components/gudep/member-avatar';
import { GudepStatusBadge } from '@/components/status-badge';
import type { GudepStatus } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { review } from '@/routes/admin/documents';
import { form as registrationForm } from '@/routes/admin/gudeps';
import type {
    DocumentColumn,
    DocumentData,
    DocumentRow,
    GudepDocument,
} from '@/types';

type Props = {
    gudep: {
        id: number;
        name: string;
        short_name: string;
        npsn: string | null;
        institution: string;
        pic_name: string | null;
        address: string | null;
        gudep_number: string;
        status: GudepStatus;
        owner_name: string;
        owner_email: string;
        submitted_at: string | null;
        admin_note: string | null;
        revision_deadline: string | null;
        deadline_extended_until: string | null;
        extension_label: string | null;
    };
    summary: {
        approved: number;
        total: number;
        pending: number;
        rejected: number;
        missing: number;
    };
    canReview: boolean;
    canVerify: boolean;
    registrationOpen: boolean;
    activity: {
        id: number;
        text: string;
        note: string | null;
        by: string;
        at: string | null;
    }[];
    columns: DocumentColumn[];
    rows: DocumentRow[];
    gudepDocuments: GudepDocument[];
};

type Tab = 'pa' | 'pi' | 'pembina' | 'gudep';

/** Satu dokumen yang bisa ditinjau: per anggota atau dokumen gudep. */
type Slot = {
    key: string;
    tab: Tab;
    subjectKey: string;
    typeId: number | 'photo';
    label: string;
    document: DocumentData | null;
};

const tabLabel: Record<Tab, string> = {
    pa: 'Putra',
    pi: 'Putri',
    pembina: 'Pembina',
    gudep: 'Berkas',
};

/** Panel verifikasi gudep (desain D2). */
export default function GudepVerification({
    gudep,
    summary,
    canReview,
    canVerify,
    registrationOpen,
    activity,
    columns,
    rows,
    gudepDocuments,
}: Props) {
    const slots = useMemo(
        () => buildSlots(rows, columns, gudepDocuments),
        [rows, columns, gudepDocuments],
    );
    const firstPending =
        slots.find((slot) => slot.document?.status === 'pending') ??
        slots.find((slot) => slot.typeId !== 'photo') ??
        slots[0];
    const [selectedKey, setSelectedKey] = useState<string | undefined>(
        firstPending?.key,
    );
    const selected =
        slots.find((slot) => slot.key === selectedKey) ?? firstPending;
    const [tab, setTab] = useState<Tab>(selected?.tab ?? 'pa');
    const [note, setNote] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [processing, setProcessing] = useState(false);
    const noteRef = useRef<HTMLTextAreaElement>(null);

    const subjectSlots = slots.filter(
        (slot) => slot.subjectKey === selected?.subjectKey,
    );
    const subjectRow = rows.find(
        (row) => `m${row.member.id}` === selected?.subjectKey,
    );
    const percent = summary.total
        ? Math.round((summary.approved / summary.total) * 100)
        : 0;

    const select = useCallback((slot: Slot | undefined) => {
        if (!slot) {
            return;
        }

        setSelectedKey(slot.key);
        setTab(slot.tab);
        setNote('');
        setError(null);
    }, []);

    const goNext = useCallback(() => {
        const index = slots.findIndex((slot) => slot.key === selected?.key);
        const after = [...slots.slice(index + 1), ...slots.slice(0, index + 1)];
        // Dokumen menunggu lebih dulu, lalu dokumen lain; pas foto tanpa
        // dokumen untuk ditinjau hanya dilewati bila tak ada pilihan lain.
        select(
            after.find((slot) => slot.document?.status === 'pending') ??
                after.find((slot) => slot.document !== null) ??
                slots[(index + 1) % slots.length],
        );
    }, [slots, selected, select]);

    const submitReview = useCallback(
        (decision: 'approved' | 'rejected') => {
            if (!selected?.document || !canReview || processing) {
                return;
            }

            if (decision === 'rejected' && !note.trim()) {
                setError('Tuliskan alasan penolakan untuk pembina.');
                noteRef.current?.focus();

                return;
            }

            router.post(
                review.url(selected.document.id),
                { decision, note },
                {
                    preserveScroll: true,
                    preserveState: true,
                    onStart: () => setProcessing(true),
                    onFinish: () => setProcessing(false),
                    onError: (errors) =>
                        setError(
                            errors.note ??
                                errors.document ??
                                'Gagal menyimpan.',
                        ),
                    onSuccess: () => goNext(),
                },
            );
        },
        [selected, canReview, processing, note, goNext],
    );

    useEffect(() => {
        const onKey = (event: KeyboardEvent) => {
            const target = event.target as HTMLElement;

            if (
                ['TEXTAREA', 'INPUT'].includes(target.tagName) ||
                event.metaKey ||
                event.ctrlKey
            ) {
                return;
            }

            if (event.key.toLowerCase() === 'a') {
                submitReview('approved');
            } else if (event.key.toLowerCase() === 'r') {
                event.preventDefault();
                noteRef.current?.focus();
            } else if (event.key === 'ArrowRight') {
                goNext();
            }
        };

        window.addEventListener('keydown', onKey);

        return () => window.removeEventListener('keydown', onKey);
    }, [submitReview, goNext]);

    const tabRows = (key: Tab) =>
        rows.filter((row) => row.member.group === key);

    return (
        <>
            <Head title={`Verifikasi ${gudep.short_name}`} />

            <nav className="text-sm font-medium text-gold-700">
                <Link href="/admin/gudep" className="hover:underline">
                    Gudep & Verifikasi
                </Link>{' '}
                / {gudep.short_name}
            </nav>

            <div className="mt-2 mb-6 flex flex-wrap items-start justify-between gap-5">
                <div>
                    <div className="flex flex-wrap items-center gap-3">
                        <h1 className="font-display text-[30px] leading-tight font-bold text-ink">
                            {gudep.name}
                        </h1>
                        <GudepStatusBadge status={gudep.status} />
                    </div>
                    <p className="mt-1 text-[15px] text-ink-muted">
                        {gudep.institution} · Pembina: {gudep.owner_name} (
                        {gudep.owner_email})
                        {gudep.submitted_at &&
                            ` · Dikirim ${gudep.submitted_at}`}
                    </p>
                </div>
                <div className="grid justify-items-end gap-1.5">
                    <div className="flex flex-wrap justify-end gap-3">
                        <ExtensionDialog gudep={gudep} />
                        <RequestRevisionDialog
                            gudep={gudep}
                            disabled={!canReview}
                            registrationOpen={registrationOpen}
                        />
                        <VerifyButton gudepId={gudep.id} enabled={canVerify} />
                        {gudep.status === 'verified' && (
                            <Button
                                asChild
                                variant="outline"
                                size="xl"
                                className="rounded-[14px]"
                            >
                                <a
                                    href={registrationForm(gudep.id).url}
                                    target="_blank"
                                    rel="noopener"
                                >
                                    <FileDown className="size-4" />
                                    Unduh formulir
                                </a>
                            </Button>
                        )}
                    </div>
                    {gudep.status === 'submitted' && (
                        <p className="text-xs text-ink-soft">
                            Aktif setelah semua dokumen disetujui (
                            {summary.approved}/{summary.total})
                        </p>
                    )}
                </div>
            </div>

            {gudep.status === 'revision' && gudep.admin_note && (
                <div className="mb-6 flex gap-3 rounded-2xl border border-maroon-600/30 bg-maroon-100 px-5 py-4 text-maroon-700">
                    <MessageSquareWarning className="mt-0.5 size-5 shrink-0" />
                    <p className="text-sm whitespace-pre-line">
                        <span className="font-bold">
                            Catatan revisi terkirim:
                        </span>{' '}
                        {gudep.admin_note}
                    </p>
                </div>
            )}

            <div className="mb-6 grid gap-x-8 gap-y-3 rounded-3xl border border-line bg-white px-5 py-4 sm:grid-cols-2 xl:grid-cols-5">
                {[
                    { label: 'No gudep', value: gudep.gudep_number },
                    { label: 'NPSN', value: gudep.npsn ?? '—' },
                    { label: 'Nama instansi', value: gudep.institution },
                    { label: 'Penanggung jawab', value: gudep.pic_name ?? '—' },
                    { label: 'Alamat', value: gudep.address ?? '—' },
                ].map((item) => (
                    <div key={item.label}>
                        <p className="text-[11px] font-semibold tracking-[0.14em] text-ink-muted uppercase">
                            {item.label}
                        </p>
                        <p className="mt-1 text-sm font-semibold text-ink">
                            {item.value}
                        </p>
                    </div>
                ))}
            </div>

            <div className="grid gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
                <aside className="rounded-3xl border border-line bg-white p-5">
                    <div className="flex items-baseline justify-between">
                        <p className="text-sm font-semibold text-ink">
                            Dokumen disetujui
                        </p>
                        <p className="font-display text-2xl font-bold text-success">
                            {summary.approved}/{summary.total}
                        </p>
                    </div>
                    <div className="mt-2 h-2 overflow-hidden rounded-full bg-sand">
                        <div
                            className="h-full rounded-full bg-[linear-gradient(90deg,#c9a24a,#2f6b45)]"
                            style={{ width: `${percent}%` }}
                        />
                    </div>
                    <p className="mt-2 text-xs text-ink-soft">
                        {summary.pending} menunggu · {summary.rejected} ditolak
                        · {summary.missing} belum diunggah
                    </p>

                    <div className="mt-5 grid grid-cols-4 gap-1 rounded-2xl bg-sand p-1">
                        {(['gudep', 'pa', 'pi', 'pembina'] as Tab[]).map(
                            (key) => (
                                <button
                                    key={key}
                                    type="button"
                                    onClick={() => setTab(key)}
                                    className={cn(
                                        'rounded-xl py-2 text-[13px] font-semibold text-ink-muted transition',
                                        tab === key &&
                                            'bg-white text-maroon-600 shadow-sm',
                                    )}
                                >
                                    {tabLabel[key]}{' '}
                                    {key === 'gudep'
                                        ? gudepDocuments.length
                                        : tabRows(key).length}
                                </button>
                            ),
                        )}
                    </div>

                    <ul className="mt-4 grid max-h-[620px] gap-1 overflow-y-auto pr-1">
                        {tab === 'gudep'
                            ? gudepDocuments.map((item) => {
                                  const slot = slots.find(
                                      (candidate) =>
                                          candidate.key === `g:${item.type.id}`,
                                  );
                                  const active = selected?.key === slot?.key;

                                  return (
                                      <li key={item.type.id}>
                                          <button
                                              type="button"
                                              onClick={() => select(slot)}
                                              className={cn(
                                                  'flex w-full items-center gap-3 rounded-2xl border px-3 py-3 text-left',
                                                  active
                                                      ? 'border-maroon-600/40 bg-maroon-100/50'
                                                      : 'border-transparent hover:bg-ivory',
                                              )}
                                          >
                                              <span className="flex-1 text-sm font-semibold text-ink">
                                                  {item.type.label}
                                              </span>
                                              <StatusChip
                                                  label="Berkas"
                                                  document={item.document}
                                              />
                                          </button>
                                      </li>
                                  );
                              })
                            : tabRows(tab).map((row) => {
                                  const active =
                                      selected?.subjectKey ===
                                      `m${row.member.id}`;

                                  return (
                                      <li key={row.member.id}>
                                          <button
                                              type="button"
                                              onClick={() => {
                                                  const own = slots.filter(
                                                      (slot) =>
                                                          slot.subjectKey ===
                                                          `m${row.member.id}`,
                                                  );

                                                  // Dokumen anggota (bila ada) lebih dulu, lalu pas foto.
                                                  select(
                                                      own.find(
                                                          (slot) =>
                                                              slot.typeId !==
                                                              'photo',
                                                      ) ?? own[0],
                                                  );
                                              }}
                                              className={cn(
                                                  'flex w-full items-center gap-3 rounded-2xl border px-3 py-2.5 text-left transition',
                                                  active
                                                      ? 'border-maroon-600/40 bg-maroon-100/50'
                                                      : 'border-transparent hover:bg-ivory',
                                              )}
                                          >
                                              <MemberAvatar
                                                  name={row.member.name}
                                                  photoUrl={
                                                      row.member.photo_url
                                                  }
                                                  owner={row.member.is_owner}
                                                  className="size-10"
                                              />
                                              <span className="min-w-0 flex-1">
                                                  <span className="block truncate text-sm font-semibold text-ink">
                                                      {row.member.name}
                                                  </span>
                                                  <span className="mt-1 flex flex-wrap gap-1">
                                                      <span
                                                          className={cn(
                                                              'inline-flex items-center rounded-md px-1.5 py-0.5 text-[11px] font-semibold',
                                                              row.member
                                                                  .has_photo
                                                                  ? 'bg-gold-100 text-gold-800'
                                                                  : 'border border-dashed border-line text-ink-soft',
                                                          )}
                                                      >
                                                          {row.member.has_photo
                                                              ? 'Pas foto'
                                                              : 'Pas foto belum'}
                                                      </span>
                                                      {columns
                                                          .filter(
                                                              (column) =>
                                                                  row.cells[
                                                                      column.id
                                                                  ]?.required,
                                                          )
                                                          .map((column) => (
                                                              <StatusChip
                                                                  key={
                                                                      column.id
                                                                  }
                                                                  label={
                                                                      column.short
                                                                  }
                                                                  document={
                                                                      row.cells[
                                                                          column
                                                                              .id
                                                                      ].document
                                                                  }
                                                              />
                                                          ))}
                                                  </span>
                                              </span>
                                              <ChevronRight className="size-4 shrink-0 text-ink-soft" />
                                          </button>
                                      </li>
                                  );
                              })}
                    </ul>
                </aside>

                <section className="rounded-3xl border border-line bg-white p-6">
                    {selected && (
                        <>
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <h2 className="font-display text-2xl font-bold text-ink">
                                        {subjectRow
                                            ? subjectRow.member.name
                                            : 'Dokumen gudep'}
                                    </h2>
                                    <p className="mt-1 text-sm text-ink-soft">
                                        {subjectRow
                                            ? [
                                                  subjectRow.member.role_label,
                                                  subjectRow.member
                                                      .kta_number &&
                                                      `KTA ${subjectRow.member.kta_number}`,
                                                  subjectRow.member
                                                      .birth_label &&
                                                      `Lahir ${subjectRow.member.birth_label}`,
                                              ]
                                                  .filter(Boolean)
                                                  .join(' · ')
                                            : gudep.name}
                                    </p>
                                </div>
                                <div className="flex gap-2">
                                    <button
                                        type="button"
                                        aria-label="Sebelumnya"
                                        onClick={() =>
                                            select(
                                                slots[
                                                    (slots.findIndex(
                                                        (slot) =>
                                                            slot.key ===
                                                            selected.key,
                                                    ) -
                                                        1 +
                                                        slots.length) %
                                                        slots.length
                                                ],
                                            )
                                        }
                                        className="grid size-10 place-items-center rounded-xl border border-line hover:border-gold-500"
                                    >
                                        <ChevronLeft className="size-4" />
                                    </button>
                                    <button
                                        type="button"
                                        aria-label="Berikutnya"
                                        onClick={goNext}
                                        className="grid size-10 place-items-center rounded-xl border border-line hover:border-gold-500"
                                    >
                                        <ChevronRight className="size-4" />
                                    </button>
                                </div>
                            </div>

                            <div className="mt-5 flex gap-1 overflow-x-auto border-b border-line">
                                {subjectSlots.map((slot) => (
                                    <button
                                        key={slot.key}
                                        type="button"
                                        onClick={() => select(slot)}
                                        className={cn(
                                            '-mb-px flex shrink-0 items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium text-ink-muted',
                                            slot.key === selected.key
                                                ? 'border-maroon-600 font-semibold text-maroon-600'
                                                : 'border-transparent hover:text-ink',
                                        )}
                                    >
                                        <SlotIcon slot={slot} />
                                        {slot.label}
                                    </button>
                                ))}
                            </div>

                            <div className="mt-5 grid gap-4">
                                {selected.typeId === 'photo' ? (
                                    subjectRow?.member.photo_url ? (
                                        <img
                                            src={subjectRow.member.photo_url}
                                            alt={`Pas foto ${subjectRow.member.name}`}
                                            className="mx-auto max-h-[480px] rounded-2xl border border-line object-contain"
                                        />
                                    ) : (
                                        <EmptyState
                                            icon={
                                                <ImageOff className="size-8" />
                                            }
                                            text="Pas foto belum diunggah."
                                        />
                                    )
                                ) : selected.document ? (
                                    <>
                                        <div className="rounded-3xl bg-sand/60 p-4">
                                            <DocumentPreview
                                                document={selected.document}
                                            />
                                        </div>
                                        <p className="text-xs text-ink-soft">
                                            {selected.document.original_name} ·{' '}
                                            {selected.document.size_label} ·
                                            diunggah{' '}
                                            {selected.document.uploaded_at}
                                        </p>
                                        {selected.document.status ===
                                            'rejected' &&
                                            selected.document.note && (
                                                <p className="rounded-xl bg-maroon-100 px-4 py-2.5 text-sm text-maroon-700">
                                                    <span className="font-bold">
                                                        Ditolak:
                                                    </span>{' '}
                                                    {selected.document.note}
                                                </p>
                                            )}
                                        {canReview && (
                                            <div className="grid gap-2">
                                                <label
                                                    htmlFor="review-note"
                                                    className="text-sm font-semibold text-ink"
                                                >
                                                    Catatan untuk pembina (wajib
                                                    jika ditolak)
                                                </label>
                                                <textarea
                                                    id="review-note"
                                                    ref={noteRef}
                                                    rows={3}
                                                    value={note}
                                                    onChange={(event) => {
                                                        setNote(
                                                            event.target.value,
                                                        );
                                                        setError(null);
                                                    }}
                                                    placeholder="Contoh: tanda tangan dokter tidak terbaca, mohon unggah ulang."
                                                    className="w-full rounded-xl border border-line bg-white px-4 py-3 text-[15px] outline-none focus:border-maroon-600 focus:ring-4 focus:ring-maroon-100"
                                                />
                                                {error && (
                                                    <p className="text-sm font-medium text-maroon-600">
                                                        {error}
                                                    </p>
                                                )}
                                                <div className="mt-1 flex flex-wrap items-center justify-between gap-3">
                                                    <p className="text-xs text-ink-soft">
                                                        Pintasan: A setujui · R
                                                        tolak · → berikutnya
                                                    </p>
                                                    <div className="flex gap-3">
                                                        <Button
                                                            variant="outline-maroon"
                                                            size="xl"
                                                            disabled={
                                                                processing
                                                            }
                                                            onClick={() =>
                                                                submitReview(
                                                                    'rejected',
                                                                )
                                                            }
                                                        >
                                                            <X className="size-4" />{' '}
                                                            Tolak
                                                        </Button>
                                                        <Button
                                                            size="xl"
                                                            disabled={
                                                                processing
                                                            }
                                                            onClick={() =>
                                                                submitReview(
                                                                    'approved',
                                                                )
                                                            }
                                                        >
                                                            <Check className="size-4" />{' '}
                                                            Setujui dokumen
                                                        </Button>
                                                    </div>
                                                </div>
                                            </div>
                                        )}
                                    </>
                                ) : (
                                    <EmptyState
                                        icon={<X className="size-8" />}
                                        text="Dokumen belum diunggah pembina."
                                    />
                                )}
                            </div>
                        </>
                    )}
                </section>
            </div>

            <section className="mt-6 rounded-3xl border border-line bg-white p-6">
                <h2 className="font-display text-2xl font-bold text-ink">
                    Riwayat aktivitas
                </h2>
                {activity.length === 0 ? (
                    <p className="mt-3 text-sm text-ink-soft">
                        Belum ada aktivitas verifikasi.
                    </p>
                ) : (
                    <ol className="mt-4 grid gap-3 border-l-2 border-line pl-5">
                        {activity.map((item) => (
                            <li key={item.id} className="relative">
                                <span className="absolute top-1.5 -left-[27px] size-3 rounded-full border-2 border-white bg-gold-500" />
                                <p className="text-sm font-semibold text-ink">
                                    {item.text}
                                </p>
                                {item.note && (
                                    <p className="text-sm text-ink-muted">
                                        “{item.note}”
                                    </p>
                                )}
                                <p className="text-xs text-ink-soft">
                                    {item.by} · {item.at}
                                </p>
                            </li>
                        ))}
                    </ol>
                )}
            </section>
        </>
    );
}

function buildSlots(
    rows: DocumentRow[],
    columns: DocumentColumn[],
    gudepDocuments: GudepDocument[],
): Slot[] {
    const slots: Slot[] = [];

    // Urutan slot mengikuti urutan tab: Berkas, Putra, Putri, Pembina.
    for (const item of gudepDocuments) {
        slots.push({
            key: `g:${item.type.id}`,
            tab: 'gudep',
            subjectKey: 'gudep',
            typeId: item.type.id,
            label: item.type.label,
            document: item.document,
        });
    }

    for (const key of ['pa', 'pi', 'pembina'] as const) {
        for (const row of rows.filter(
            (candidate) => candidate.member.group === key,
        )) {
            slots.push({
                key: `m${row.member.id}:photo`,
                tab: key,
                subjectKey: `m${row.member.id}`,
                typeId: 'photo',
                label: 'Pas foto',
                document: null,
            });

            for (const column of columns) {
                const cell = row.cells[column.id];

                if (cell?.required) {
                    slots.push({
                        key: `m${row.member.id}:${column.id}`,
                        tab: key,
                        subjectKey: `m${row.member.id}`,
                        typeId: column.id,
                        label: column.label.replace(/\s*\(.*\)/, ''),
                        document: cell.document,
                    });
                }
            }
        }
    }

    return slots;
}

function StatusChip({
    label,
    document,
}: {
    label: string;
    document: DocumentData | null;
}) {
    const status = document?.status ?? 'missing';

    return (
        <span
            className={cn(
                'inline-flex items-center gap-0.5 rounded-md px-1.5 py-0.5 text-[11px] font-semibold',
                status === 'approved' && 'bg-success-soft text-success',
                status === 'pending' && 'bg-gold-100 text-gold-800',
                status === 'rejected' && 'bg-maroon-100 text-maroon-600',
                status === 'missing' &&
                    'border border-dashed border-line text-ink-soft',
            )}
        >
            {label}
            {status === 'rejected' && <X className="size-3" />}
        </span>
    );
}

function SlotIcon({ slot }: { slot: Slot }) {
    if (slot.typeId === 'photo') {
        return null;
    }

    const status = slot.document?.status;

    if (status === 'approved') {
        return <Check className="size-3.5 text-success" />;
    }

    if (status === 'rejected') {
        return <X className="size-3.5 text-maroon-600" />;
    }

    return (
        <span
            className={cn(
                'size-2 rounded-full',
                status === 'pending' ? 'bg-gold-500' : 'border border-ink-soft',
            )}
        />
    );
}

function EmptyState({ icon, text }: { icon: React.ReactNode; text: string }) {
    return (
        <div className="grid h-72 place-items-center rounded-3xl bg-sand/60 text-center text-ink-soft">
            <div className="grid justify-items-center gap-2">
                {icon}
                <p className="text-sm">{text}</p>
            </div>
        </div>
    );
}
