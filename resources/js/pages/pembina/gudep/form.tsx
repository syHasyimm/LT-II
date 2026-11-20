import { Form, Head, Link } from '@inertiajs/react';
import { Info, Lock } from 'lucide-react';
import { Field, InfoNote } from '@/components/form/field';
import { FormHeader, StickyActions } from '@/components/pembina/form-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/pembina/gudep';

type GudepData = {
    gudep_number: string;
    npsn: string | null;
    institution: string;
    pic_name: string | null;
    address: string | null;
};

type Props = {
    gudep: GudepData | null;
    editable: boolean;
};

/** Form data gudep (FR-GDP-01), gaya form desain M3b. */
export default function GudepForm({ gudep, editable }: Props) {
    const isNew = gudep === null;

    return (
        <>
            <Head title="Data Gudep" />
            <FormHeader
                back="/pembina"
                title="Data Gudep"
                subtitle={
                    isNew
                        ? 'Langkah 1 · isi identitas gugus depan'
                        : 'NPSN, instansi, penanggung jawab, alamat, nomor gudep'
                }
            />

            <Form {...update.form()} className="px-6 pt-6">
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-5">
                            {!editable && (
                                <InfoNote tone="maroon">
                                    <Lock className="mt-0.5 size-4 shrink-0" />
                                    Data terkunci karena pendaftaran sudah
                                    dikirim atau terverifikasi.
                                </InfoNote>
                            )}

                            <Field id="npsn" label="NPSN" error={errors.npsn}>
                                <Input
                                    id="npsn"
                                    name="npsn"
                                    defaultValue={gudep?.npsn ?? ''}
                                    placeholder="Contoh: 20404123"
                                    inputMode="numeric"
                                    maxLength={8}
                                    pattern="[0-9]{8}"
                                    required
                                    readOnly={!editable}
                                    autoFocus={isNew}
                                />
                            </Field>

                            <Field
                                id="institution"
                                label="Nama instansi"
                                error={errors.institution}
                            >
                                <Input
                                    id="institution"
                                    name="institution"
                                    defaultValue={gudep?.institution}
                                    placeholder="Contoh: SMP Negeri 3 Sleman"
                                    required
                                    readOnly={!editable}
                                />
                            </Field>

                            <Field
                                id="pic_name"
                                label="Penanggung jawab"
                                error={errors.pic_name}
                            >
                                <Input
                                    id="pic_name"
                                    name="pic_name"
                                    defaultValue={gudep?.pic_name ?? ''}
                                    placeholder="Contoh: Drs. Sumarno, M.Pd."
                                    required
                                    readOnly={!editable}
                                />
                            </Field>

                            <Field
                                id="address"
                                label="Alamat"
                                error={errors.address}
                            >
                                <Input
                                    id="address"
                                    name="address"
                                    defaultValue={gudep?.address ?? ''}
                                    placeholder="Contoh: Jl. Magelang KM 5, Sleman"
                                    maxLength={255}
                                    required
                                    readOnly={!editable}
                                />
                            </Field>

                            <Field
                                id="gudep_number"
                                label="No gudep"
                                error={errors.gudep_number}
                            >
                                <Input
                                    id="gudep_number"
                                    name="gudep_number"
                                    defaultValue={gudep?.gudep_number}
                                    placeholder="Contoh: 07.041"
                                    inputMode="decimal"
                                    required
                                    readOnly={!editable}
                                />
                            </Field>

                            {isNew && (
                                <InfoNote>
                                    <Info className="mt-0.5 size-4 shrink-0" />
                                    Anda otomatis tercatat sebagai pembina
                                    pendamping pertama dan bisa menambah pembina
                                    lain setelah ini.
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
                                    <Link href="/pembina">Batal</Link>
                                </Button>
                                <Button
                                    type="submit"
                                    size="xl"
                                    className="flex-[2]"
                                    disabled={processing}
                                >
                                    {processing && <Spinner />}
                                    {isNew ? 'Simpan & lanjut' : 'Simpan'}
                                </Button>
                            </StickyActions>
                        )}
                    </>
                )}
            </Form>
        </>
    );
}
