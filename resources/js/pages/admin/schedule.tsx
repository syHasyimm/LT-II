import { Form, Head, usePage } from '@inertiajs/react';
import { CalendarClock, Info } from 'lucide-react';
import { Field, InfoNote } from '@/components/form/field';
import { RegistrationPill } from '@/components/registration-pill';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { pad, useCountdown } from '@/hooks/use-countdown';
import { AdminPageHeader } from '@/layouts/admin-layout';
import { update } from '@/routes/admin/schedule';

type Props = {
    opens_at: string | null;
    closes_at: string | null;
    extensions: { id: number; name: string; until: string }[];
};

/** Pengaturan jadwal buka/tutup pendaftaran (FR-PRD-01). */
export default function Schedule({ opens_at, closes_at, extensions }: Props) {
    const { registration } = usePage().props;
    const target =
        registration.state === 'open'
            ? registration.closes_at
            : registration.state === 'not_open'
              ? registration.opens_at
              : null;
    const countdown = useCountdown(target, registration.now);

    return (
        <>
            <Head title="Jadwal Pendaftaran" />
            <AdminPageHeader
                title="Jadwal Pendaftaran"
                description="Atur kapan pembina bisa membuat gudep, mengubah anggota, mengunggah dokumen, dan mengirim pendaftaran."
            />

            <div className="grid gap-6 xl:grid-cols-[1fr_400px]">
                <Form
                    {...update.form()}
                    options={{ preserveScroll: true }}
                    className="rounded-3xl border border-line bg-white p-6 sm:p-8"
                >
                    {({ processing, errors }) => (
                        <div className="grid gap-6">
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <h2 className="font-display text-2xl font-bold text-ink">
                                    Waktu buka & tutup
                                </h2>
                                <RegistrationPill registration={registration} />
                            </div>

                            <div className="grid gap-5 sm:grid-cols-2">
                                <Field
                                    id="opens_at"
                                    label="Dibuka"
                                    error={errors.opens_at}
                                    hint="Zona waktu Asia/Jakarta (WIB)"
                                >
                                    <Input
                                        id="opens_at"
                                        name="opens_at"
                                        type="datetime-local"
                                        defaultValue={opens_at ?? undefined}
                                        required
                                    />
                                </Field>
                                <Field
                                    id="closes_at"
                                    label="Ditutup"
                                    error={errors.closes_at}
                                    hint="Penutupan berlaku otomatis tanpa penjadwal"
                                >
                                    <Input
                                        id="closes_at"
                                        name="closes_at"
                                        type="datetime-local"
                                        defaultValue={closes_at ?? undefined}
                                        required
                                    />
                                </Field>
                            </div>

                            <InfoNote>
                                <Info className="mt-0.5 size-4 shrink-0" />
                                Setelah ditutup, pembina hanya dapat melihat
                                data. Gudep yang mendapat perpanjangan atau
                                tenggat revisi tetap bisa mengubah data sampai
                                batasnya. Tidak ada batas jumlah gudep.
                            </InfoNote>

                            <div className="flex justify-end">
                                <Button
                                    type="submit"
                                    size="xl"
                                    disabled={processing}
                                >
                                    {processing && <Spinner />}
                                    Simpan jadwal
                                </Button>
                            </div>
                        </div>
                    )}
                </Form>

                <div className="grid content-start gap-6">
                    <div className="relative overflow-hidden rounded-3xl bg-[linear-gradient(150deg,#6e1218,#3e070b)] p-6 text-white">
                        <div className="pointer-events-none absolute -top-16 -right-16 size-48 rounded-full border border-gold-300/20" />
                        <p className="text-[11px] font-semibold tracking-[0.22em] text-gold-300 uppercase">
                            {registration.state === 'open'
                                ? 'Ditutup dalam'
                                : registration.state === 'not_open'
                                  ? 'Dibuka dalam'
                                  : 'Status'}
                        </p>
                        {countdown ? (
                            <p className="mt-3 font-display text-4xl font-bold tabular-nums">
                                {countdown.days}h {pad(countdown.hours)}:
                                {pad(countdown.minutes)}:
                                {pad(countdown.seconds)}
                            </p>
                        ) : (
                            <p className="mt-3 font-display text-3xl font-bold">
                                {registration.state === 'closed'
                                    ? 'Sudah ditutup'
                                    : 'Belum dijadwalkan'}
                            </p>
                        )}
                        <dl className="mt-5 grid gap-1 text-sm text-maroon-50">
                            <div>Buka · {registration.opens_label ?? '—'}</div>
                            <div>
                                Tutup · {registration.closes_label ?? '—'}
                            </div>
                        </dl>
                    </div>

                    <div className="rounded-3xl border border-line bg-white p-6">
                        <h2 className="flex items-center gap-2 text-[15px] font-bold text-ink">
                            <CalendarClock className="size-4 text-gold-700" />
                            Perpanjangan aktif
                        </h2>
                        {extensions.length === 0 ? (
                            <p className="mt-3 text-sm text-ink-soft">
                                Belum ada gudep yang mendapat perpanjangan. Atur
                                perpanjangan dari halaman detail gudep.
                            </p>
                        ) : (
                            <ul className="mt-3 grid gap-2">
                                {extensions.map((extension) => (
                                    <li
                                        key={extension.id}
                                        className="flex items-center justify-between gap-3 rounded-xl bg-ivory px-3 py-2 text-sm"
                                    >
                                        <span className="truncate font-semibold text-ink">
                                            {extension.name}
                                        </span>
                                        <span className="shrink-0 text-gold-800">
                                            s.d. {extension.until}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
