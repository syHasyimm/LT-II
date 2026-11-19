import { Head, Link } from '@inertiajs/react';
import { PublicNav } from '@/layouts/public-layout';

/** Beranda publik. Isi lengkap (desain P1/P2) dibangun pada T1.4. */
export default function Welcome() {
    return (
        <>
            <Head title="Pendaftaran Gugus Depan" />
            <div className="min-h-screen bg-maroon-gradient">
                <PublicNav
                    actions={
                        <Link
                            href="/login"
                            className="rounded-xl bg-gold-gradient px-5 py-3 text-sm font-bold text-maroon-900"
                        >
                            Daftarkan Gudep
                        </Link>
                    }
                />
            </div>
        </>
    );
}
