import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowRight,
    Camera,
    Check,
    ChevronDown,
    FileText,
    UserRound,
    Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { GoogleIcon } from '@/components/brand/google-icon';
import { SerialForm } from '@/components/serial-form';
import type { RegistrationWindow } from '@/components/registration-pill';
import { pad, useCountdown } from '@/hooks/use-countdown';
import PublicLayout, { PublicNav } from '@/layouts/public-layout';
import { cn } from '@/lib/utils';

type Rules = {
    pesertaPerRegu: number;
    pembinaMin: number;
    maxUploadMb: number;
};

type Stats = { gudep: number; peserta: number; pembina: number };

type Props = {
    rules: Rules;
    stats?: Stats | null;
};

type SharedRegistration = RegistrationWindow & { now: string };

/** Beranda publik (desain P1 desktop, P2 mobile). */
export default function Welcome({ rules, stats }: Props) {
    const { registration } = usePage<{ registration: SharedRegistration }>()
        .props;

    return (
        <PublicLayout>
            <Head title="Pendaftaran Gugus Depan" />
            <Hero registration={registration} stats={stats} />
            <Requirements rules={rules} />
            <Steps rules={rules} />
            <VerifySection />
            <Faq />
        </PublicLayout>
    );
}

function Hero({
    registration,
    stats,
}: {
    registration: SharedRegistration;
    stats?: Stats | null;
}) {
    return (
        <section className="relative overflow-hidden bg-maroon-gradient text-white">
            <div aria-hidden className="pointer-events-none absolute inset-0">
                <div className="absolute -top-64 -left-56 size-[760px] rounded-full border border-gold-300/15" />
                <div className="absolute top-[360px] -right-40 size-[620px] rounded-full border border-gold-300/10" />
            </div>

            <PublicNav
                actions={
                    <>
                        <Link
                            href="/admin/login"
                            className="hidden rounded-xl border border-gold-300/50 px-5 py-3 text-sm font-semibold text-gold-300 transition hover:bg-white/10 sm:inline-flex"
                        >
                            Masuk Admin
                        </Link>
                        <Link
                            href="/login"
                            className="inline-flex items-center gap-2 rounded-xl bg-gold-gradient px-5 py-3 text-sm font-bold text-maroon-900 shadow-sm transition hover:brightness-105"
                        >
                            Daftarkan Gudep
                            <ArrowRight className="size-4" />
                        </Link>
                    </>
                }
            />

            <div className="relative mx-auto grid max-w-[1200px] gap-12 px-6 pt-14 pb-20 lg:grid-cols-[1fr_500px] lg:gap-16 lg:pt-20 lg:pb-24 xl:px-0">
                <div>
                    <StatusPill registration={registration} />
                    <p className="mt-6 text-xs font-semibold tracking-[0.26em] text-gold-300 uppercase sm:text-[13px]">
                        Lomba Tingkat 2 · Pendaftaran Gugus Depan
                    </p>
                    <h1 className="mt-3 font-display text-[40px] leading-[1.08] font-bold sm:text-6xl lg:text-[72px]">
                        Perkemahan
                        <br />
                        Lomba Tingkat 2
                    </h1>
                    <div className="mt-6 h-0.5 w-20 bg-[linear-gradient(90deg,#e8cf8a,transparent)]" />
                    <p className="mt-6 max-w-[540px] text-[15px] leading-relaxed text-maroon-50 sm:text-lg">
                        Satu tempat untuk mendaftarkan gugus depan: data peserta
                        putra dan putri, pembina pendamping, dokumen
                        persyaratan, hingga ID Card dan sertifikat.
                    </p>

                    <div className="mt-9 flex flex-col gap-4 sm:flex-row sm:items-center sm:gap-6">
                        <Link
                            href="/login"
                            className="flex h-14 items-center justify-center gap-3 rounded-[14px] bg-white px-8 text-base font-bold text-ink shadow-[0_10px_24px_rgba(26,2,4,0.25)] transition hover:bg-ivory"
                        >
                            <GoogleIcon className="size-5" />
                            {registration.state === 'open'
                                ? 'Daftar dengan Google'
                                : 'Masuk Pembina'}
                        </Link>
                        <a
                            href="#alur"
                            className="flex items-center justify-center gap-2 text-[15px] font-semibold text-gold-300 underline decoration-gold-300/50 underline-offset-[6px] hover:decoration-gold-300"
                        >
                            Lihat alur pendaftaran
                            <ArrowDown className="size-4" />
                        </a>
                    </div>

                    <ul className="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-[13px] text-gold-300/80">
                        <li>✦ Tanpa batas jumlah gudep</li>
                        <li>✦ Akun dibuat otomatis</li>
                        <li>✦ Dokumen tersimpan privat</li>
                    </ul>
                </div>

                <div className="lg:self-start">
                    <ScheduleCard registration={registration} stats={stats} />
                </div>
            </div>
        </section>
    );
}

function StatusPill({ registration }: { registration: SharedRegistration }) {
    const text = {
        open: `Pendaftaran dibuka · ditutup ${registration.closes_label ?? ''}`,
        not_open: registration.opens_label
            ? `Pendaftaran dibuka ${registration.opens_label}`
            : 'Jadwal pendaftaran segera diumumkan',
        closed: 'Pendaftaran sudah ditutup',
    }[registration.state];

    return (
        <p className="inline-flex items-center gap-2.5 rounded-full border border-gold-300/25 bg-white/[0.07] py-2 pr-4 pl-3.5 text-xs font-semibold text-gold-300 sm:text-[13px]">
            <span
                className={cn(
                    'size-2 rounded-full',
                    registration.state === 'open'
                        ? 'bg-[#7fd69a]'
                        : 'bg-gold-300',
                )}
            />
            {text}
        </p>
    );
}

function ScheduleCard({
    registration,
    stats,
}: {
    registration: SharedRegistration;
    stats?: Stats | null;
}) {
    const target =
        registration.state === 'open'
            ? registration.closes_at
            : registration.state === 'not_open'
              ? registration.opens_at
              : null;
    const countdown = useCountdown(target, registration.now);
    const progress = scheduleProgress(registration);

    return (
        <div
            id="jadwal"
            className="scroll-mt-8 rounded-[28px] border border-gold-300/25 bg-white/[0.06] p-6 backdrop-blur-md sm:p-10"
        >
            <p className="text-[11px] font-semibold tracking-[0.22em] text-gold-300 uppercase sm:text-xs">
                {registration.state === 'open'
                    ? 'Pendaftaran ditutup dalam'
                    : registration.state === 'not_open'
                      ? 'Pendaftaran dibuka dalam'
                      : 'Status pendaftaran'}
            </p>

            {countdown ? (
                <div className="mt-4 grid grid-cols-4 gap-2 sm:gap-3">
                    <CountdownBox value={countdown.days} label="Hari" />
                    <CountdownBox value={countdown.hours} label="Jam" />
                    <CountdownBox value={countdown.minutes} label="Menit" />
                    <CountdownBox
                        value={countdown.seconds}
                        label="Detik"
                        highlight
                    />
                </div>
            ) : (
                <p className="mt-4 font-display text-3xl font-bold text-white">
                    {registration.state === 'closed'
                        ? 'Pendaftaran sudah ditutup'
                        : 'Jadwal segera diumumkan'}
                </p>
            )}

            <div className="my-6 h-px bg-gold-300/20 sm:my-7" />

            <dl className="grid grid-cols-2 gap-4">
                <div>
                    <dt className="text-[10px] font-semibold tracking-[0.18em] text-gold-300/70 uppercase sm:text-[11px]">
                        Dibuka
                    </dt>
                    <dd className="mt-1 text-sm font-bold sm:text-[17px]">
                        {registration.opens_label ?? '—'}
                    </dd>
                </div>
                <div>
                    <dt className="text-[10px] font-semibold tracking-[0.18em] text-gold-300/70 uppercase sm:text-[11px]">
                        Ditutup
                    </dt>
                    <dd className="mt-1 text-sm font-bold sm:text-[17px]">
                        {registration.closes_label ?? '—'}
                    </dd>
                </div>
            </dl>

            {progress && (
                <div className="mt-6">
                    <div className="h-1.5 overflow-hidden rounded-full bg-white/10">
                        <div
                            className="h-full rounded-full bg-[linear-gradient(90deg,#f3e2ae,#c9a24a)]"
                            style={{ width: `${progress.percent}%` }}
                        />
                    </div>
                    <p className="mt-2 text-xs text-maroon-50/70">
                        Hari ke-{progress.day} dari {progress.total} hari masa
                        pendaftaran
                    </p>
                </div>
            )}

            {stats && (
                <>
                    <div className="my-6 h-px bg-gold-300/20 sm:my-7" />
                    <dl className="grid grid-cols-3 gap-4">
                        <Stat value={stats.gudep} label="Gudep terdaftar" />
                        <Stat value={stats.peserta} label="Peserta Pa & Pi" />
                        <Stat value={stats.pembina} label="Pembina" />
                    </dl>
                </>
            )}

            <p className="mt-6 text-xs text-maroon-50/60">
                Jadwal diatur panitia · zona waktu Asia/Jakarta
            </p>
        </div>
    );
}

function scheduleProgress(registration: SharedRegistration) {
    if (
        registration.state !== 'open' ||
        !registration.opens_at ||
        !registration.closes_at
    ) {
        return null;
    }

    const day = 86_400_000;
    const opens = new Date(registration.opens_at).getTime();
    const closes = new Date(registration.closes_at).getTime();
    const now = new Date(registration.now).getTime();
    const total = Math.max(1, Math.ceil((closes - opens) / day));
    const current = Math.min(total, Math.floor((now - opens) / day) + 1);

    return {
        day: current,
        total,
        percent: Math.round((current / total) * 100),
    };
}

function CountdownBox({
    value,
    label,
    highlight = false,
}: {
    value: number;
    label: string;
    highlight?: boolean;
}) {
    return (
        <div
            className={cn(
                'flex aspect-square flex-col items-center justify-center rounded-2xl sm:aspect-auto sm:h-[104px] sm:rounded-[18px]',
                highlight
                    ? 'bg-gold-gradient text-maroon-900'
                    : 'border border-gold-300/20 bg-maroon-900/60 text-white',
            )}
        >
            <span className="font-display text-3xl leading-none font-bold tabular-nums sm:text-[44px]">
                {pad(value)}
            </span>
            <span
                className={cn(
                    'mt-1 text-[11px] font-semibold sm:text-xs',
                    highlight ? 'text-maroon-900' : 'text-maroon-50',
                )}
            >
                {label}
            </span>
        </div>
    );
}

function Stat({ value, label }: { value: number; label: string }) {
    return (
        <div>
            <dd className="font-display text-2xl font-bold text-gold-300 sm:text-4xl">
                {value.toLocaleString('id-ID')}
            </dd>
            <dt className="mt-1 text-xs text-maroon-50 sm:text-[13px]">
                {label}
            </dt>
        </div>
    );
}

function SectionHeading({
    eyebrow,
    title,
    className,
}: {
    eyebrow: string;
    title: string;
    className?: string;
}) {
    return (
        <div className={className}>
            <p className="text-[11px] font-semibold tracking-[0.26em] text-gold-700 uppercase sm:text-[13px]">
                {eyebrow}
            </p>
            <h2 className="mt-2 font-display text-[28px] leading-tight font-bold text-ink sm:text-[44px]">
                {title}
            </h2>
        </div>
    );
}

function Requirements({ rules }: { rules: Rules }) {
    const items: { icon: LucideIcon; title: string; text: string }[] = [
        {
            icon: Users,
            title: `${rules.pesertaPerRegu} Putra · ${rules.pesertaPerRegu} Putri`,
            text: `Kuota tetap per gudep: ${rules.pesertaPerRegu} peserta putra dan ${rules.pesertaPerRegu} peserta putri.`,
        },
        {
            icon: UserRound,
            title: 'Pembina pendamping',
            text: `Minimal ${rules.pembinaMin}, tanpa batas jumlah. Pembina pemilik akun tercatat otomatis.`,
        },
        {
            icon: FileText,
            title: 'Dokumen digital',
            text: 'KTA, izin orang tua, dan surat kesehatan per anggota, plus surat kesiapan sekolah.',
        },
        {
            icon: Camera,
            title: 'Pas foto',
            text: 'Wajib untuk setiap anggota, dipakai untuk mencetak ID Card peserta dan pembina.',
        },
    ];

    return (
        <section
            id="ketentuan"
            className="mx-auto max-w-[1200px] scroll-mt-4 px-6 py-16 sm:py-24 xl:px-0"
        >
            <div className="flex flex-wrap items-end justify-between gap-6">
                <SectionHeading
                    eyebrow="Ketentuan peserta"
                    title="Yang perlu disiapkan setiap gudep"
                />
                <p className="max-w-[420px] text-[15px] leading-relaxed text-ink-muted">
                    Pendaftaran baru bisa dikirim setelah kuota, pembina, pas
                    foto, dan semua dokumen wajib lengkap.
                </p>
            </div>

            <div className="mt-10 grid grid-cols-2 gap-3 sm:gap-6 lg:grid-cols-4">
                {items.map((item) => (
                    <div
                        key={item.title}
                        className="rounded-[18px] border border-line bg-white p-4 sm:rounded-[20px] sm:p-7"
                    >
                        <span className="grid size-10 place-items-center rounded-xl bg-maroon-100 text-maroon-600 sm:size-12 sm:rounded-[14px]">
                            <item.icon className="size-5" strokeWidth={1.8} />
                        </span>
                        <h3 className="mt-3 font-display text-lg font-bold text-ink sm:mt-4 sm:text-[22px]">
                            {item.title}
                        </h3>
                        <p className="mt-2 text-xs leading-relaxed text-ink-muted sm:text-sm">
                            {item.text}
                        </p>
                    </div>
                ))}
            </div>

            <p className="mt-6 text-xs font-semibold text-gold-700 sm:text-[13px]">
                Format file PDF, JPG, atau PNG · maksimal {rules.maxUploadMb} MB
                per file · izin orang tua hanya untuk peserta
            </p>
        </section>
    );
}

function Steps({ rules }: { rules: Rules }) {
    const steps = [
        {
            title: 'Masuk dengan Google',
            text: 'Akun pembina dibuat otomatis saat login pertama. Satu akun untuk satu gudep.',
        },
        {
            title: 'Isi data gudep',
            text: 'Nomor gudep, nama pangkalan atau sekolah, dan kwartir.',
        },
        {
            title: 'Peserta & pembina',
            text: `Lengkapi ${rules.pesertaPerRegu} putra, ${rules.pesertaPerRegu} putri, pembina pendamping, dan pas foto tiap anggota.`,
        },
        {
            title: 'Unggah dokumen',
            text: 'Pantau kelengkapan lewat matriks dokumen per anggota dan per gudep.',
        },
        {
            title: 'Kirim & verifikasi',
            text: 'Panitia memeriksa. Setelah disetujui, ID Card dan sertifikat diterbitkan.',
        },
    ];

    return (
        <section
            id="alur"
            className="scroll-mt-4 border-t border-line bg-white px-6 py-16 sm:py-24"
        >
            <div className="mx-auto max-w-[1200px]">
                <SectionHeading
                    eyebrow="Alur pendaftaran"
                    title="Lima langkah sampai terverifikasi"
                />

                <ol className="relative mt-10 grid gap-8 lg:mt-14 lg:grid-cols-5 lg:gap-[30px]">
                    <span
                        aria-hidden
                        className="absolute top-5 bottom-5 left-5 border-l-[1.5px] border-dashed border-gold-500 lg:top-7 lg:right-[10%] lg:bottom-auto lg:left-7 lg:border-t-[1.5px] lg:border-l-0"
                    />
                    {steps.map((step, index) => {
                        const last = index === steps.length - 1;

                        return (
                            <li
                                key={step.title}
                                className="relative flex gap-4 lg:block"
                            >
                                <span
                                    className={cn(
                                        'relative grid size-10 shrink-0 place-items-center rounded-full font-display text-lg font-bold lg:size-14 lg:text-[22px]',
                                        last
                                            ? 'bg-gold-gradient text-maroon-900 shadow-[0_6px_16px_rgba(156,122,43,0.35)]'
                                            : 'border-[1.5px] border-gold-500 bg-ivory text-maroon-700',
                                    )}
                                >
                                    {last ? (
                                        <Check
                                            className="size-5"
                                            strokeWidth={2.5}
                                        />
                                    ) : (
                                        index + 1
                                    )}
                                </span>
                                <div className="lg:mt-6">
                                    <h3 className="text-[15px] font-bold text-ink lg:text-[17px]">
                                        {step.title}
                                    </h3>
                                    <p className="mt-1 text-[13px] leading-relaxed text-ink-muted lg:mt-2 lg:text-sm">
                                        {step.text}
                                    </p>
                                </div>
                            </li>
                        );
                    })}
                </ol>
            </div>
        </section>
    );
}

/** Verifikasi publik ID Card & Sertifikat (desain P1/P2, FR-GEN-05). */
function VerifySection() {
    return (
        <section
            id="verifikasi"
            className="relative scroll-mt-4 overflow-hidden bg-[linear-gradient(135deg,#6e1218,#3e070b_55%,#2a0407)] px-6 py-16 text-white sm:py-24"
        >
            <div
                aria-hidden
                className="pointer-events-none absolute -bottom-40 -left-40 size-[520px] rounded-full border border-gold-300/10"
            />
            <div className="relative mx-auto grid max-w-[1200px] items-center gap-10 lg:grid-cols-[1fr_560px] lg:gap-20">
                <div>
                    <p className="text-[11px] font-semibold tracking-[0.26em] text-gold-300 uppercase sm:text-[13px]">
                        Verifikasi publik
                    </p>
                    <h2 className="mt-2 font-display text-[28px] leading-tight font-bold sm:text-[44px]">
                        Cek keaslian ID Card
                        <br />
                        dan sertifikat
                    </h2>
                    <p className="mt-4 max-w-[520px] text-[15px] leading-relaxed text-maroon-50 sm:text-base">
                        Pindai QR pada sertifikat atau masukkan nomor seri.
                        Halaman verifikasi hanya menampilkan nama, gudep, dan
                        status keabsahan.
                    </p>
                    <SerialForm tone="dark" className="mt-8 max-w-[540px]" />
                    <p className="mt-3 text-xs text-maroon-50/65">
                        Format nomor seri: LT2-2026-ID-0001 untuk ID Card,
                        LT2-2026-CERT-0001 untuk sertifikat
                    </p>
                </div>

                <div className="hidden rounded-3xl bg-ivory p-8 text-ink shadow-[0_24px_48px_rgba(18,1,3,0.4)] lg:block">
                    <div className="-mx-8 -mt-8 mb-6 h-1.5 rounded-t-3xl bg-[linear-gradient(90deg,#9c7a2b,#e8cf8a,#9c7a2b)]" />
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-success-soft px-3 py-1 text-xs font-bold text-success">
                        <Check className="size-3" strokeWidth={3} /> Contoh
                        hasil · sah
                    </span>
                    <p className="mt-4 text-xs font-semibold tracking-[0.16em] text-gold-700 uppercase">
                        Sertifikat · LT2-2026-CERT-0001
                    </p>
                    <p className="mt-1 font-display text-[30px] font-bold">
                        Nama Peserta
                    </p>
                    <div className="my-4 h-px bg-line" />
                    <dl className="grid grid-cols-[110px_1fr] gap-y-2 text-sm">
                        <dt className="text-ink-soft">Gudep</dt>
                        <dd className="font-semibold">Nomor gudep · sekolah</dd>
                        <dt className="text-ink-soft">Peran</dt>
                        <dd className="font-semibold">Peserta Putra</dd>
                        <dt className="text-ink-soft">Diterbitkan</dt>
                        <dd className="font-semibold">Tanggal terbit</dd>
                    </dl>
                    <p className="mt-5 text-xs text-ink-soft">
                        Tanggal lahir, dokumen, dan kontak tidak pernah
                        ditampilkan di halaman publik.
                    </p>
                </div>
            </div>
        </section>
    );
}

function Faq() {
    const items = [
        {
            q: 'Berapa gudep yang bisa mendaftar?',
            a: 'Tidak ada batas jumlah gudep. Pendaftaran hanya dibatasi tanggal buka dan tutup yang diatur panitia. Gudep yang terlambat dapat meminta perpanjangan kepada panitia.',
        },
        {
            q: 'Bolehkah mengirim pendaftaran dengan peserta kurang dari 8?',
            a: 'Belum bisa. Pendaftaran baru dapat dikirim setelah regu putra dan regu putri masing-masing berisi 8 peserta, minimal satu pembina pendamping, serta semua pas foto dan dokumen wajib lengkap.',
        },
        {
            q: 'Bagaimana jika dokumen ditolak panitia?',
            a: 'Status pendaftaran berubah menjadi perlu revisi dan catatan panitia tampil di beranda pembina. Unggah ulang dokumen yang ditolak, lalu kirim kembali pendaftaran.',
        },
        {
            q: 'Kapan ID Card dan sertifikat diterbitkan?',
            a: 'ID Card dan sertifikat dibuat panitia untuk semua anggota gudep yang sudah terverifikasi. Setiap sertifikat memiliki nomor seri dan QR untuk memeriksa keasliannya.',
        },
        {
            q: 'Apakah data dan dokumen anak aman?',
            a: 'Dokumen disimpan di penyimpanan privat dan hanya dapat dibuka oleh pembina pemilik gudep serta admin panitia. Tautan file tidak dapat ditebak atau dibagikan.',
        },
    ];

    return (
        <section
            id="faq"
            className="mx-auto grid max-w-[1200px] scroll-mt-4 gap-10 px-6 py-16 sm:py-24 lg:grid-cols-[340px_1fr] lg:gap-20 xl:px-0"
        >
            <div>
                <SectionHeading eyebrow="Bantuan" title="Pertanyaan umum" />
                <p className="mt-4 max-w-xs text-[15px] leading-relaxed text-ink-muted">
                    Masih ada yang belum jelas? Hubungi sekretariat panitia
                    Perkemahan LT 2.
                </p>
            </div>

            <div className="grid gap-3">
                {items.map((item, index) => (
                    <details
                        key={item.q}
                        open={index === 0}
                        className="group rounded-[18px] border border-line bg-white open:border-gold-500"
                    >
                        <summary className="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-5 text-[15px] font-bold text-ink sm:px-6 sm:text-[17px] [&::-webkit-details-marker]:hidden">
                            {item.q}
                            <ChevronDown className="size-4 shrink-0 text-gold-700 transition group-open:rotate-180 group-open:text-maroon-600" />
                        </summary>
                        <p className="px-5 pb-6 text-sm leading-relaxed text-ink-muted sm:px-6 sm:text-[15px]">
                            {item.a}
                        </p>
                    </details>
                ))}
            </div>
        </section>
    );
}
