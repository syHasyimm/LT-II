import { Form, Head, Link } from '@inertiajs/react';
import { Info, Lock } from 'lucide-react';
import { InfoNote } from '@/components/form/field';
import { MemberFields } from '@/components/gudep/member-fields';
import { PhotoUpload } from '@/components/gudep/photo-upload';
import InputError from '@/components/input-error';
import { FormHeader, StickyActions } from '@/components/pembina/form-header';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { index, store, update } from '@/routes/pembina/members';
import type { MemberData, Regu } from '@/types';

type Props = {
    member: MemberData | null;
    regu: Regu;
    slot: { number: number; max: number } | null;
    editable?: boolean;
    maxUploadMb: number;
};

const reguLabel: Record<Regu, string> = {
    pa: 'Putra',
    pi: 'Putri',
    pembina: 'Pembina',
};

/** Tambah atau ubah anggota (desain M3b). */
export default function MemberForm({
    member,
    regu,
    slot,
    editable = true,
    maxUploadMb,
}: Props) {
    const isNew = member === null;
    const back = index.url({ query: { tab: regu } });
    const title = isNew
        ? `Tambah Peserta ${reguLabel[regu]}`
        : regu === 'pembina'
          ? 'Data Pembina'
          : `Data Peserta ${reguLabel[regu]}`;
    const subtitle = slot
        ? `Slot ${slot.number} dari ${slot.max} · Regu ${reguLabel[regu]}`
        : member?.is_owner
          ? 'Pembina pemilik akun'
          : regu === 'pembina'
            ? 'Pembina pendamping'
            : `Regu ${reguLabel[regu]}`;
    const formProps = isNew ? store.form() : update.form(member.id);

    return (
        <>
            <Head title={title} />
            <FormHeader back={back} title={title} subtitle={subtitle} />

            <Form
                {...formProps}
                options={{ preserveScroll: true }}
                className="px-6 pt-6"
            >
                {({ processing, errors }) => (
                    <>
                        {isNew && (
                            <input type="hidden" name="regu" value={regu} />
                        )}

                        <div className="grid gap-6">
                            {!editable && (
                                <InfoNote tone="maroon">
                                    <Lock className="mt-0.5 size-4 shrink-0" />
                                    Data terkunci karena pendaftaran sudah
                                    dikirim atau terverifikasi.
                                </InfoNote>
                            )}

                            <PhotoUpload
                                currentUrl={member?.photo_url}
                                error={errors.photo}
                                maxUploadMb={maxUploadMb}
                                disabled={!editable}
                            />

                            <MemberFields
                                regu={regu}
                                member={member}
                                errors={errors}
                                disabled={!editable}
                            />

                            <InputError message={errors.regu} />

                            {regu !== 'pembina' && (
                                <InfoNote>
                                    <Info className="mt-0.5 size-4 shrink-0" />
                                    Setiap regu wajib tepat {slot?.max ??
                                        8}{' '}
                                    peserta. Peserta ke-{(slot?.max ?? 8) + 1}{' '}
                                    akan ditolak otomatis.
                                </InfoNote>
                            )}
                        </div>

                        {editable && (
                            <StickyActions>
                                <Button
                                    asChild
                                    variant="outline-maroon"
                                    size="xl"
                                    className="flex-1"
                                >
                                    <Link href={back}>Batal</Link>
                                </Button>
                                <Button
                                    type="submit"
                                    size="xl"
                                    className="flex-[2]"
                                    disabled={processing}
                                >
                                    {processing && <Spinner />}
                                    {isNew
                                        ? 'Simpan peserta'
                                        : 'Simpan perubahan'}
                                </Button>
                            </StickyActions>
                        )}
                    </>
                )}
            </Form>
        </>
    );
}
