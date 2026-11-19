import { Form, Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowRight,
    Check,
    CheckCircle2,
    Lock,
    MessageSquareWarning,
    Send,
    X,
} from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Spinner } from '@/components/ui/spinner';
import { PembinaHeader } from '@/layouts/pembina-layout';
import { cn } from '@/lib/utils';
import { store } from '@/routes/pembina/submission';

type Item = {
    key: string;
    label: string;
    detail: string | null;
    ok: boolean;
    action: string | null;
    href: string | null;
};

type Props = {
    status: 'draft' | 'revision';
    admin_note: string | null;
    items: Item[];
    remaining: number;
    canSubmit: boolean;
    editable: boolean;
};

/** Kirim pendaftaran dengan daftar periksa kelengkapan (desain M5). */
export default function Submit({
    status,
    admin_note,
    items,
    remaining,
    canSubmit,
    editable,
}: Props) {
    const [agreed, setAgreed] = useState(false);

    return (
        <>
            <Head title="Kirim Pendaftaran" />
            <PembinaHeader
                title="Kirim Pendaftaran"
                subtitle="Periksa kelengkapan sebelum mengirim"
            />

            <div className="relative -mt-10 grid grid-cols-[minmax(0,1fr)] gap-4 px-6 pb-8">
                {remaining > 0 ? (
                    <div className="flex gap-4 rounded-2xl border border-maroon-600/30 bg-maroon-100 px-5 py-4">
                        <AlertTriangle className="mt-0.5 size-6 shrink-0 text-maroon-600" />
                        <div>
                            <p className="text-[17px] font-bold text-maroon-700">
                                Pendaftaran belum lengkap
                            </p>
                            <p className="mt-0.5 text-sm text-maroon-700">
                                Lengkapi {remaining} hal di bawah. Status tetap{' '}
                                {status === 'revision'
                                    ? 'Perlu revisi'
                                    : 'Draft'}
                                .
                            </p>
                        </div>
                    </div>
                ) : (
                    <div className="flex gap-4 rounded-2xl border border-success/30 bg-success-soft px-5 py-4">
                        <CheckCircle2 className="mt-0.5 size-6 shrink-0 text-success" />
                        <div>
                            <p className="text-[17px] font-bold text-success">
                                Semua persyaratan lengkap
                            </p>
                            <p className="mt-0.5 text-sm text-success">
                                Setelah dikirim, data terkunci sampai panitia
                                memverifikasi.
                            </p>
                        </div>
                    </div>
                )}

                {status === 'revision' && admin_note && (
                    <div className="flex gap-3 rounded-2xl border border-gold-500/40 bg-gold-100/60 px-5 py-4 text-gold-800">
                        <MessageSquareWarning className="mt-0.5 size-5 shrink-0" />
                        <div>
                            <p className="text-sm font-bold">
                                Catatan revisi dari panitia
                            </p>
                            <p className="mt-1 text-sm whitespace-pre-line">
                                {admin_note}
                            </p>
                        </div>
                    </div>
                )}

                <h2 className="mt-4 font-display text-[26px] font-bold text-ink">
                    Daftar periksa
                </h2>
                <ul className="overflow-hidden rounded-2xl border border-line bg-white">
                    {items.map((item) => (
                        <li
                            key={item.key}
                            className={cn(
                                'flex items-center gap-4 border-b border-line px-5 py-4 last:border-b-0',
                                !item.ok && 'bg-maroon-100/30',
                            )}
                        >
                            <span
                                className={cn(
                                    'grid size-10 shrink-0 place-items-center rounded-full',
                                    item.ok
                                        ? 'bg-success-soft text-success'
                                        : 'bg-maroon-100 text-maroon-600',
                                )}
                            >
                                {item.ok ? (
                                    <Check className="size-4" />
                                ) : (
                                    <X className="size-4" />
                                )}
                            </span>
                            <span className="min-w-0 flex-1">
                                <span className="block text-[15px] font-semibold text-ink">
                                    {item.label}
                                </span>
                                {item.detail && (
                                    <span className="block text-sm text-maroon-600">
                                        {item.detail}
                                    </span>
                                )}
                            </span>
                            {item.action && item.href && editable && (
                                <Link
                                    href={item.href}
                                    className="flex shrink-0 items-center gap-1 text-sm font-bold text-maroon-700 hover:underline"
                                >
                                    {item.action}
                                    <ArrowRight className="size-3.5" />
                                </Link>
                            )}
                        </li>
                    ))}
                </ul>

                <Form
                    {...store.form()}
                    options={{ preserveScroll: true }}
                    className="mt-2 grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <label className="flex items-start gap-3 text-sm leading-relaxed text-ink-muted">
                                <Checkbox
                                    name="agreement"
                                    value="1"
                                    checked={agreed}
                                    onCheckedChange={(value) =>
                                        setAgreed(value === true)
                                    }
                                    disabled={!editable}
                                    className="mt-0.5 size-5 rounded-md border-line"
                                />
                                Saya menyatakan data benar dan menyetujui
                                kebijakan privasi data peserta.
                            </label>
                            <InputError message={errors.agreement} />
                            {errors.submit && (
                                <div className="rounded-2xl bg-maroon-100 px-4 py-3 text-sm text-maroon-700">
                                    {errors.submit}
                                </div>
                            )}
                            {errors.registration && (
                                <InputError message={errors.registration} />
                            )}
                            <Button
                                type="submit"
                                size="xl"
                                className="h-16 w-full text-base"
                                disabled={!canSubmit || !agreed || processing}
                            >
                                {processing ? (
                                    <Spinner />
                                ) : canSubmit ? (
                                    <Send className="size-5" />
                                ) : (
                                    <Lock className="size-5" />
                                )}
                                Kirim pendaftaran
                            </Button>
                            {!editable && (
                                <p className="text-center text-sm text-ink-soft">
                                    Pengiriman tidak tersedia di luar jadwal
                                    pendaftaran.
                                </p>
                            )}
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
