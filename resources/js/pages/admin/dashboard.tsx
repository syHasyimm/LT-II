import { Head } from '@inertiajs/react';
import { AdminPageHeader } from '@/layouts/admin-layout';

/** Dashboard admin. Isi lengkap (desain D1) dibangun pada T4.4. */
export default function AdminDashboard() {
    return (
        <>
            <Head title="Dashboard" />
            <AdminPageHeader
                title="Dashboard"
                description="Ringkasan pendaftaran gugus depan."
            />
        </>
    );
}
