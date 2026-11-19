import { Form, Head, Link, router } from '@inertiajs/react';
import { Copy, IdCard, FileBadge2, Plus, Star, Trash2 } from 'lucide-react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { Field } from '@/components/form/field';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Segmented } from '@/components/ui/segmented';
import { Spinner } from '@/components/ui/spinner';
import { AdminPageHeader } from '@/layouts/admin-layout';
import { cn } from '@/lib/utils';
import {
    activate,
    destroy,
    duplicate,
    edit,
    store,
} from '@/routes/admin/templates';
import type { Option, TemplateSummary } from '@/types';

type Props = {
    templates: TemplateSummary[];
    targets: Option[];
};

/** Daftar template ID Card dan Sertifikat (FR-TPL-01). */
export default function TemplateIndex({ templates, targets }: Props) {
    const groups: {
        type: TemplateSummary['type'];
        title: string;
        icon: typeof IdCard;
    }[] = [
        { type: 'id_card', title: 'ID Card · 55 × 85 mm', icon: IdCard },
        {
            type: 'certificate',
            title: 'Sertifikat · A4 lanskap',
            icon: FileBadge2,
        },
    ];

    return (
        <>
            <Head title="Template" />
            <AdminPageHeader
                title="Template"
                description="Hanya satu template aktif per jenis dan sasaran. Template peserta atau pembina didahulukan daripada template untuk keduanya."
                actions={<NewTemplateDialog targets={targets} />}
            />

            <div className="grid gap-8">
                {groups.map((group) => (
                    <section key={group.type}>
                        <h2 className="mb-4 flex items-center gap-2 font-display text-2xl font-bold text-ink">
                            <group.icon className="size-5 text-gold-700" />
                            {group.title}
                        </h2>
                        <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                            {templates
                                .filter(
                                    (template) => template.type === group.type,
                                )
                                .map((template) => (
                                    <TemplateCard
                                        key={template.id}
                                        template={template}
                                    />
                                ))}
                        </div>
                    </section>
                ))}
            </div>
        </>
    );
}

function TemplateCard({ template }: { template: TemplateSummary }) {
    const portrait = template.height_mm > template.width_mm;

    return (
        <div
            className={cn(
                'flex flex-col overflow-hidden rounded-3xl border bg-white',
                template.is_active
                    ? 'border-[1.5px] border-gold-500'
                    : 'border-line',
            )}
        >
            <Link
                href={edit(template.id)}
                aria-label={`Buka editor ${template.name}`}
                className="grid h-56 place-items-center bg-sand/60 p-4"
            >
                {template.background_url ? (
                    <img
                        src={template.background_url}
                        alt=""
                        className={cn(
                            'rounded-lg shadow-md',
                            portrait ? 'h-full' : 'w-full',
                        )}
                    />
                ) : (
                    <span className="text-sm text-ink-soft">
                        Belum ada gambar latar
                    </span>
                )}
            </Link>
            <div className="flex flex-1 flex-col gap-3 p-5">
                <div className="flex items-start justify-between gap-3">
                    <div>
                        <Link
                            href={edit(template.id)}
                            className="text-[15px] font-bold text-ink hover:underline"
                        >
                            {template.name}
                        </Link>
                        <p className="text-sm text-ink-soft">
                            Sasaran: {template.applies_label} ·{' '}
                            {template.width_mm} × {template.height_mm} mm
                        </p>
                    </div>
                    {template.is_active && (
                        <span className="shrink-0 rounded-full bg-success-soft px-2.5 py-1 text-xs font-semibold text-success">
                            ● Aktif
                        </span>
                    )}
                </div>
                <p className="text-xs text-ink-soft">
                    Diubah {template.updated_at}
                </p>
                <div className="mt-auto flex flex-wrap gap-2">
                    <Button asChild size="sm" className="rounded-lg">
                        <Link href={edit(template.id)}>Ubah</Link>
                    </Button>
                    {!template.is_active && (
                        <Button
                            size="sm"
                            variant="gold"
                            className="rounded-lg"
                            onClick={() =>
                                router.post(
                                    activate.url(template.id),
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            <Star className="size-3.5" /> Aktifkan
                        </Button>
                    )}
                    <Button
                        size="sm"
                        variant="outline"
                        className="rounded-lg"
                        onClick={() => router.post(duplicate.url(template.id))}
                    >
                        <Copy className="size-3.5" /> Duplikat
                    </Button>
                    {!template.is_active && (
                        <ConfirmDialog
                            trigger={
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    className="rounded-lg text-maroon-600"
                                >
                                    <Trash2 className="size-3.5" /> Hapus
                                </Button>
                            }
                            title="Hapus template?"
                            description={`${template.name} beserta gambar latarnya akan dihapus.`}
                            confirmLabel="Hapus"
                            onConfirm={() =>
                                router.delete(destroy.url(template.id))
                            }
                        />
                    )}
                </div>
            </div>
        </div>
    );
}

function NewTemplateDialog({ targets }: { targets: Option[] }) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button size="xl">
                    <Plus className="size-4" /> Template baru
                </Button>
            </DialogTrigger>
            <DialogContent className="rounded-3xl border-line bg-ivory sm:max-w-md">
                <DialogTitle className="font-display text-2xl text-ink">
                    Template baru
                </DialogTitle>
                <DialogDescription className="text-sm text-ink-muted">
                    Dimulai dari tata letak bawaan. Setelah dibuat, unggah
                    gambar latar dan atur posisi field.
                </DialogDescription>
                <Form {...store.form()} className="grid gap-4">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <span className="text-sm font-semibold text-ink">
                                    Jenis
                                </span>
                                <Segmented
                                    name="type"
                                    defaultValue="id_card"
                                    options={[
                                        { value: 'id_card', label: 'ID Card' },
                                        {
                                            value: 'certificate',
                                            label: 'Sertifikat',
                                        },
                                    ]}
                                />
                            </div>
                            <div className="grid gap-2">
                                <span className="text-sm font-semibold text-ink">
                                    Sasaran
                                </span>
                                <Segmented
                                    name="applies_to"
                                    defaultValue="peserta"
                                    options={targets}
                                />
                            </div>
                            <Field
                                id="template-name"
                                label="Nama template"
                                error={errors.name}
                            >
                                <Input
                                    id="template-name"
                                    name="name"
                                    placeholder="Contoh: ID Card Peserta 2026"
                                    required
                                />
                            </Field>
                            <Button
                                type="submit"
                                size="xl"
                                disabled={processing}
                            >
                                {processing && <Spinner />}
                                Buat template
                            </Button>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
