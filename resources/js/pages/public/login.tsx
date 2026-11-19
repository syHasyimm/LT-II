import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, FlaskConical } from 'lucide-react';
import { Emblem } from '@/components/brand/emblem';
import { GoogleIcon } from '@/components/brand/google-icon';
import {
    RegistrationPill,
    type RegistrationWindow,
} from '@/components/registration-pill';

type Props = {
    status?: string;
    devLogin?: boolean;
    registration?: RegistrationWindow | null;
};

/** Halaman masuk Pembina (desain M1). */
export default function Login({ status, devLogin, registration }: Props) {
    const { errors } = usePage<{ errors: Record<string, string> }>().props;

    return (
        <>
            <Head title="Masuk Pembina" />
            <div className="relative flex min-h-screen flex-col overflow-hidden bg-maroon-gradient md:items-center md:justify-center md:py-12">
                <Rings />

                <div className="relative mx-auto flex w-full max-w-md flex-1 flex-col md:flex-none md:overflow-hidden md:rounded-[28px] md:shadow-[0_30px_80px_rgba(20,2,4,0.45)]">
                    <div className="relative px-6 pt-8 pb-12 text-center text-white">
                        <Link
                            href="/"
                            aria-label="Kembali ke beranda"
                            className="absolute top-8 left-6 rounded-xl border border-gold-300/30 bg-white/10 p-2.5 text-gold-300"
                        >
                            <ArrowLeft className="size-4" />
                        </Link>
                        <Emblem className="mx-auto mt-12 size-24" />
                        <p className="mt-8 text-xs font-semibold tracking-[0.26em] text-gold-300 uppercase">
                            Pendaftaran Gugus Depan
                        </p>
                        <h1 className="mt-3 font-display text-[42px] leading-[1.1] font-bold">
                            Perkemahan
                            <br />
                            LT 2 · 2026
                        </h1>
                        <div className="mx-auto mt-6 h-0.5 w-16 bg-[linear-gradient(90deg,transparent,#e8cf8a,transparent)]" />
                        <p className="mx-auto mt-4 max-w-[300px] text-[15px] leading-relaxed text-maroon-50">
                            Daftarkan gudep, lengkapi peserta dan pembina, lalu
                            unggah dokumen dalam satu tempat.
                        </p>
                    </div>

                    <div className="mt-auto rounded-t-[28px] bg-ivory px-6 pt-3 pb-8 md:rounded-t-none">
                        <div className="mx-auto h-1 w-10 rounded-full bg-line" />
                        {registration && (
                            <RegistrationPill
                                registration={registration}
                                className="mt-5"
                            />
                        )}
                        <h2 className="mt-5 font-display text-2xl font-semibold text-ink">
                            Masuk sebagai Pembina
                        </h2>

                        {status && (
                            <p className="mt-3 rounded-xl bg-success-soft px-4 py-2.5 text-sm text-success">
                                {status}
                            </p>
                        )}
                        {errors.google && (
                            <p
                                role="alert"
                                className="mt-3 rounded-xl bg-maroon-100 px-4 py-2.5 text-sm font-medium text-maroon-600"
                            >
                                {errors.google}
                            </p>
                        )}

                        <a
                            href="/auth/google"
                            className="mt-4 flex h-14 w-full items-center justify-center gap-3 rounded-[14px] border border-line bg-white text-[15px] font-bold text-ink shadow-[0_4px_12px_rgba(62,7,11,0.08)] transition hover:border-gold-500"
                        >
                            <GoogleIcon className="size-5" />
                            Masuk dengan Google
                        </a>

                        {devLogin && (
                            <a
                                href="/dev/login-pembina"
                                className="mt-3 flex h-11 w-full items-center justify-center gap-2 rounded-[14px] border border-dashed border-gold-500 text-sm font-semibold text-gold-800"
                            >
                                <FlaskConical className="size-4" />
                                Masuk sebagai pembina demo (lokal)
                            </a>
                        )}

                        <p className="mt-4 text-center text-xs leading-relaxed text-ink-soft">
                            Akun dibuat otomatis saat login pertama. Satu akun
                            untuk satu gugus depan.
                        </p>
                        <div className="my-4 h-px bg-line" />
                        <Link
                            href="/admin/login"
                            className="flex items-center justify-center gap-1.5 text-[13px] font-semibold text-maroon-700"
                        >
                            Panitia? Masuk sebagai Admin
                            <ArrowRight className="size-3.5" />
                        </Link>
                    </div>
                </div>
            </div>
        </>
    );
}

function Rings() {
    return (
        <div className="pointer-events-none absolute inset-0" aria-hidden>
            <div className="absolute -top-32 -left-36 size-[440px] rounded-full border border-gold-300/20" />
            <div className="absolute top-56 -right-32 size-[320px] rounded-full border border-gold-300/15" />
        </div>
    );
}
