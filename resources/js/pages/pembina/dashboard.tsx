import { Head, usePage } from '@inertiajs/react';
import { PembinaHeader } from '@/layouts/pembina-layout';

/** Beranda Pembina. Isi lengkap (desain M2) dibangun pada T2.7. */
export default function PembinaDashboard() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Beranda Pembina" />
            <PembinaHeader
                title={`Selamat datang, ${auth.user.name}`}
                subtitle="Area Pembina Pendamping"
            />
        </>
    );
}
