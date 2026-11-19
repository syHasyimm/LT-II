import { Lock, Phone } from 'lucide-react';
import { Field } from '@/components/form/field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Segmented } from '@/components/ui/segmented';
import InputError from '@/components/input-error';
import type { MemberData, Regu } from '@/types';

type Errors = Partial<Record<string, string>>;

/**
 * Isian data anggota (FR-GDP-04). Peserta: jenis kelamin mengikuti regu dan
 * terkunci (M3b). Pembina: tambah jenis kelamin, nomor HP, dan keterangan
 * mendampingi (M3c).
 */
export function MemberFields({
    regu,
    member,
    errors,
    disabled = false,
    compact = false,
    idPrefix = 'member',
}: {
    regu: Regu;
    member?: MemberData | null;
    errors: Errors;
    disabled?: boolean;
    compact?: boolean;
    idPrefix?: string;
}) {
    const id = (field: string) => `${idPrefix}-${field}`;
    const isPembina = regu === 'pembina';

    return (
        <div className="grid gap-5">
            <Field id={id('name')} label="Nama lengkap" error={errors.name}>
                <Input
                    id={id('name')}
                    name="name"
                    defaultValue={member?.name}
                    placeholder="Sesuai KTA"
                    autoComplete="off"
                    required
                    readOnly={disabled}
                />
            </Field>

            {isPembina ? (
                <div className="grid gap-2">
                    <Label htmlFor={id('gender')}>Jenis kelamin</Label>
                    <Segmented
                        id={id('gender')}
                        name="gender"
                        defaultValue={member?.gender}
                        disabled={disabled}
                        options={[
                            { value: 'L', label: 'Laki-laki' },
                            { value: 'P', label: 'Perempuan' },
                        ]}
                    />
                    <InputError message={errors.gender} />
                </div>
            ) : (
                <Field id={id('gender')} label="Jenis kelamin">
                    <div className="relative">
                        <Input
                            id={id('gender')}
                            value={
                                regu === 'pa'
                                    ? 'Laki-laki (Regu Putra)'
                                    : 'Perempuan (Regu Putri)'
                            }
                            readOnly
                            tabIndex={-1}
                        />
                        <Lock className="absolute top-1/2 right-4 size-4 -translate-y-1/2 text-ink-soft" />
                    </div>
                </Field>
            )}

            <div className={compact ? 'grid grid-cols-2 gap-3' : 'grid gap-5'}>
                <Field
                    id={id('birth_date')}
                    label="Tanggal lahir"
                    error={errors.birth_date}
                >
                    <Input
                        id={id('birth_date')}
                        type="date"
                        name="birth_date"
                        defaultValue={member?.birth_date ?? undefined}
                        max={new Date().toISOString().slice(0, 10)}
                        required
                        readOnly={disabled}
                    />
                </Field>

                <Field
                    id={id('kta_number')}
                    label="Nomor KTA"
                    error={errors.kta_number}
                >
                    <Input
                        id={id('kta_number')}
                        name="kta_number"
                        defaultValue={member?.kta_number ?? undefined}
                        placeholder="Contoh: 12.04.041.0027"
                        inputMode="decimal"
                        required
                        readOnly={disabled}
                    />
                </Field>
            </div>

            {isPembina && (
                <>
                    <Field
                        id={id('phone')}
                        label="Nomor HP"
                        error={errors.phone}
                    >
                        <div className="relative">
                            <Phone className="absolute top-1/2 left-4 size-4 -translate-y-1/2 text-gold-700" />
                            <Input
                                id={id('phone')}
                                name="phone"
                                type="tel"
                                inputMode="tel"
                                defaultValue={member?.phone ?? undefined}
                                placeholder="0812-3456-7890"
                                className="pl-11"
                                required
                                readOnly={disabled}
                            />
                        </div>
                    </Field>

                    <div className="grid gap-2">
                        <Label htmlFor={id('accompanies')}>Mendampingi</Label>
                        <Segmented
                            id={id('accompanies')}
                            name="accompanies"
                            defaultValue={member?.accompanies}
                            disabled={disabled}
                            options={[
                                { value: 'pa', label: 'Putra' },
                                { value: 'pi', label: 'Putri' },
                                { value: 'keduanya', label: 'Keduanya' },
                            ]}
                        />
                        <InputError message={errors.accompanies} />
                    </div>
                </>
            )}
        </div>
    );
}
