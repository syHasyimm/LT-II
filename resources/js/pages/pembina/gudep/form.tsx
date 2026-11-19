import { Form, Head, Link } from '@inertiajs/react';
import { Info, Lock } from 'lucide-react';
import { Field, InfoNote } from '@/components/form/field';
import { FormHeader, StickyActions } from '@/components/pembina/form-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/pembina/gudep';

type GudepData = {
    name: string;
    gudep_number: string;
    institution: string;
    region: string;
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
                        : 'Nama, nomor, sekolah, kwartir'
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

                            <Field
                                id="name"
                                label="Nama gudep"
                                error={errors.name}
                            >
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={gudep?.name}
                                    placeholder="Contoh: Gudep 07.041"
                                    required
                                    readOnly={!editable}
                                    autoFocus={isNew}
                                />
                            </Field>

                            <Field
                                id="gudep_number"
                                label="Nomor gudep"
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

                            <Field
                                id="institution"
                                label="Asal sekolah / lembaga"
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
                                id="region"
                                label="Kwartir / wilayah"
                                error={errors.region}
                            >
                                <Input
                                    id="region"
                                    name="region"
                                    defaultValue={gudep?.region}
                                    placeholder="Contoh: Kwarcab Sleman"
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
