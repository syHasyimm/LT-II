import { Form, Head, Link, router } from '@inertiajs/react';
import { Check, ChevronRight, Lock, Plus, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { ImportPeserta } from '@/components/gudep/import-peserta';
import { StepNav } from '@/components/pembina/step-nav';
import { MemberAvatar } from '@/components/gudep/member-avatar';
import { MemberFields } from '@/components/gudep/member-fields';
import { PhotoUpload } from '@/components/gudep/photo-upload';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { PembinaHeader } from '@/layouts/pembina-layout';
import { cn } from '@/lib/utils';
import { create, destroy, edit, store } from '@/routes/pembina/members';
import type { MemberData, Regu } from '@/types';

type Props = {
    gudep: { name: string };
    tab: Regu;
    max: number;
    editable: boolean;
    members: Record<Regu, MemberData[]>;
    maxUploadMb: number;
};

const stepTitle: Record<Regu, string> = {
    pa: 'Peserta Putra',
    pi: 'Peserta Putri',
    pembina: 'Pembina Pendamping',
};

const label: Record<Regu, string> = {
    pa: 'Putra',
    pi: 'Putri',
    pembina: 'Pembina',
};

/** Anggota gudep: tab Putra / Putri / Pembina (desain M3 dan M3c). */
export default function Members({
    gudep,
    tab,
    max,
    editable,
    members,
    maxUploadMb,
}: Props) {
    return (
        <>
            <Head title={stepTitle[tab]} />
            <PembinaHeader title={stepTitle[tab]} subtitle={gudep.name} />

            <div className="relative -mt-10 px-6">
                {tab === 'pembina' ? (
                    <PembinaList
                        members={members.pembina}
                        editable={editable}
                        maxUploadMb={maxUploadMb}
                    />
                ) : (
                    <PesertaList
                        regu={tab}
                        members={members[tab]}
                        max={max}
                        editable={editable}
                        importSlots={
                            max * 2 - members.pa.length - members.pi.length
                        }
                    />
                )}
            </div>
            <StepNav />
        </>
    );
}

function PesertaList({
    regu,
    members,
    max,
    editable,
    importSlots,
}: {
    regu: 'pa' | 'pi';
    members: MemberData[];
    max: number;
    editable: boolean;
    importSlots: number;
}) {
    const remaining = Math.max(0, max - members.length);
    const percent = Math.round((members.length / max) * 100);

    return (
        <div className="grid grid-cols-[minmax(0,1fr)] gap-3 pb-6">
            <div className="rounded-2xl border border-line bg-white px-5 py-4">
                <div className="flex items-baseline justify-between gap-3">
                    <p className="text-[15px] font-bold text-ink">
                        Regu {label[regu]}
                        <span
                            className={cn(
                                'ml-2 text-sm font-medium',
                                remaining ? 'text-maroon-600' : 'text-success',
                            )}
                        >
                            ·{' '}
                            {remaining
                                ? `butuh ${remaining} peserta lagi`
                                : 'lengkap'}
                        </span>
                    </p>
                    <p
                        className={cn(
                            'font-display text-2xl font-bold',
                            remaining ? 'text-maroon-600' : 'text-success',
                        )}
                    >
                        {members.length}/{max}
                    </p>
                </div>
                <div className="mt-3 h-1.5 overflow-hidden rounded-full bg-sand">
                    <div
                        className={cn(
                            'h-full rounded-full',
                            remaining
                                ? 'bg-[linear-gradient(90deg,#a51c24,#c9a24a)]'
                                : 'bg-success',
                        )}
                        style={{ width: `${percent}%` }}
                    />
                </div>
            </div>

            {members.length === 0 && (
                <p className="rounded-2xl border border-dashed border-line px-5 py-8 text-center text-sm text-ink-soft">
                    Belum ada peserta {label[regu].toLowerCase()}.
                </p>
            )}

            {members.map((member) => (
                <Link
                    key={member.id}
                    href={edit(member.id)}
                    className="flex items-center gap-4 rounded-2xl border border-line bg-white px-5 py-3.5 transition hover:border-gold-500"
                >
                    <MemberAvatar
                        name={member.name}
                        photoUrl={member.photo_url}
                    />
                    <div className="min-w-0 flex-1">
                        <p className="truncate text-[15px] font-bold text-ink">
                            {member.name}
                        </p>
                        <p className="truncate text-sm text-ink-soft">
                            {member.kta_number
                                ? `KTA ${member.kta_number}`
                                : 'KTA belum diisi'}
                            {member.age !== null && ` · ${member.age} th`}
                        </p>
                    </div>
                    <MissingChip missing={member.missing} />
                </Link>
            ))}

            {editable && remaining > 0 && (
                <Link
                    href={create(regu)}
                    className="flex items-center justify-center gap-2 rounded-2xl border-[1.5px] border-dashed border-gold-500 py-5 text-[15px] font-bold text-maroon-700 transition hover:bg-gold-100/40"
                >
                    <Plus className="size-5" />
                    Tambah peserta {label[regu]} · {remaining} slot tersisa
                </Link>
            )}

            {editable && importSlots > 0 && (
                <ImportPeserta remaining={importSlots} />
            )}
        </div>
    );
}

function MissingChip({ missing }: { missing: string[] }) {
    if (missing.length === 0) {
        return (
            <Check
                className="size-5 shrink-0 text-success"
                aria-label="Lengkap"
            />
        );
    }

    const text =
        missing.includes('pas foto') && missing.length === 1
            ? 'Foto belum'
            : 'Belum lengkap';

    return (
        <span className="shrink-0 rounded-full bg-maroon-100 px-3 py-1 text-xs font-semibold text-maroon-600">
            {text}
        </span>
    );
}

function PembinaList({
    members,
    editable,
    maxUploadMb,
}: {
    members: MemberData[];
    editable: boolean;
    maxUploadMb: number;
}) {
    const [drafts, setDrafts] = useState<number[]>([]);
    const [deleting, setDeleting] = useState<number | null>(null);

    const addDraft = () => setDrafts((current) => [...current, Date.now()]);
    const removeDraft = (key: number) =>
        setDrafts((current) => current.filter((draft) => draft !== key));

    return (
        <div className="grid grid-cols-[minmax(0,1fr)] gap-3 pb-6">
            <div className="flex items-baseline justify-between rounded-2xl border border-line bg-white px-5 py-4">
                <h2 className="text-[15px] font-bold text-ink">
                    Pembina pendamping
                </h2>
                <p className="text-sm font-medium text-gold-700">
                    Minimal 1 · tanpa batas
                </p>
            </div>

            {members.map((member) => (
                <div
                    key={member.id}
                    className={cn(
                        'flex items-center gap-4 rounded-2xl border bg-white px-5 py-4',
                        member.is_owner
                            ? 'border-[1.5px] border-gold-500'
                            : 'border-line',
                    )}
                >
                    <Link
                        href={edit(member.id)}
                        className="flex min-w-0 flex-1 items-center gap-4"
                    >
                        <MemberAvatar
                            name={member.name}
                            photoUrl={member.photo_url}
                            owner={member.is_owner}
                            className="size-14"
                        />
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-[15px] font-bold text-ink">
                                {member.name}
                            </p>
                            <p className="truncate text-sm text-ink-soft">
                                {[
                                    member.phone,
                                    member.kta_number &&
                                        `KTA ${member.kta_number}`,
                                ]
                                    .filter(Boolean)
                                    .join(' · ') || 'Lengkapi data pembina'}
                            </p>
                            <div className="mt-1.5 flex flex-wrap gap-1.5">
                                {member.is_owner && (
                                    <span className="rounded-full bg-gold-100 px-2.5 py-0.5 text-xs font-semibold text-gold-800">
                                        ★ Pemilik akun
                                    </span>
                                )}
                                {member.accompanies_label && (
                                    <span className="rounded-full bg-maroon-100 px-2.5 py-0.5 text-xs font-semibold text-maroon-700">
                                        {member.accompanies === 'keduanya'
                                            ? 'Pa & Pi'
                                            : `Mendampingi ${member.accompanies === 'pa' ? 'Pa' : 'Pi'}`}
                                    </span>
                                )}
                                {member.missing.length > 0 && (
                                    <span className="rounded-full bg-sand px-2.5 py-0.5 text-xs font-semibold text-ink-muted">
                                        Belum lengkap
                                    </span>
                                )}
                            </div>
                        </div>
                    </Link>

                    {member.is_owner ? (
                        <Lock
                            className="size-5 shrink-0 text-ink-soft"
                            aria-label="Tidak dapat dihapus"
                        />
                    ) : editable ? (
                        <ConfirmDialog
                            trigger={
                                <button
                                    type="button"
                                    aria-label={`Hapus ${member.name}`}
                                    className="grid size-11 shrink-0 place-items-center rounded-xl bg-maroon-100 text-maroon-600 transition hover:bg-maroon-600 hover:text-white"
                                >
                                    <Trash2 className="size-5" />
                                </button>
                            }
                            title="Hapus pembina?"
                            description={`${member.name} beserta pas fotonya akan dihapus dari gudep ini.`}
                            confirmLabel="Hapus"
                            processing={deleting === member.id}
                            onConfirm={() =>
                                router.delete(destroy.url(member.id), {
                                    preserveScroll: true,
                                    onStart: () => setDeleting(member.id),
                                    onFinish: () => setDeleting(null),
                                })
                            }
                        />
                    ) : (
                        <ChevronRight className="size-5 shrink-0 text-ink-soft" />
                    )}
                </div>
            ))}

            {drafts.map((key, position) => (
                <NewPembinaCard
                    key={key}
                    number={members.length + position + 1}
                    maxUploadMb={maxUploadMb}
                    onRemove={() => removeDraft(key)}
                    onSaved={() => removeDraft(key)}
                />
            ))}

            {editable && (
                <button
                    type="button"
                    onClick={addDraft}
                    className="flex items-center justify-center gap-2 rounded-2xl border-[1.5px] border-dashed border-gold-500 py-5 text-[15px] font-bold text-maroon-700 transition hover:bg-gold-100/40"
                >
                    <Plus className="size-5" />
                    Tambah pembina
                </button>
            )}
        </div>
    );
}

/** Baris repeater pembina baru yang diisi langsung di daftar (desain M3c). */
function NewPembinaCard({
    number,
    maxUploadMb,
    onRemove,
    onSaved,
}: {
    number: number;
    maxUploadMb: number;
    onRemove: () => void;
    onSaved: () => void;
}) {
    return (
        <Form
            {...store.form()}
            options={{ preserveScroll: true, preserveState: true }}
            onSuccess={onSaved}
            className="rounded-2xl border-[1.5px] border-maroon-600 bg-white p-5"
        >
            {({ processing, errors }) => (
                <>
                    <input type="hidden" name="regu" value="pembina" />
                    <div className="mb-4 flex items-center justify-between">
                        <p className="text-[17px] font-bold text-maroon-600">
                            Pembina {number} · baru
                        </p>
                        <button
                            type="button"
                            onClick={onRemove}
                            aria-label="Batalkan pembina baru"
                            className="grid size-10 place-items-center rounded-xl bg-maroon-100 text-maroon-600"
                        >
                            <X className="size-5" />
                        </button>
                    </div>

                    <div className="grid gap-5">
                        <MemberFields
                            regu="pembina"
                            errors={errors}
                            compact
                            idPrefix={`pembina-baru-${number}`}
                        />
                        <PhotoUpload
                            variant="bar"
                            error={errors.photo}
                            maxUploadMb={maxUploadMb}
                        />
                        <Button type="submit" size="xl" disabled={processing}>
                            {processing && <Spinner />}
                            Simpan pembina
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
