import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    AlignCenter,
    AlignLeft,
    AlignRight,
    Eye,
    ImageIcon,
    Minus,
    Plus,
    QrCode,
    Star,
    Trash2,
    Type,
    UserSquare,
} from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import type { PointerEvent as ReactPointerEvent, ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { activate, preview, update } from '@/routes/admin/templates';
import { upload as uploadBackground } from '@/routes/admin/templates/background';
import type {
    Option,
    TemplateField,
    TemplateSample,
    TemplateSummary,
} from '@/types';

type Props = {
    template: TemplateSummary & { fields: TemplateField[] };
    samples: TemplateSample[];
    targets: Option[];
};

const PT_TO_MM = 0.3528;

const fontFamily: Record<string, string> = {
    display: "'Playfair Display', serif",
    sans: "'Plus Jakarta Sans', sans-serif",
    mono: "'DejaVu Sans Mono', ui-monospace, monospace",
};

const presets: { label: string; build: () => Omit<TemplateField, 'id'> }[] = [
    {
        label: 'Nama',
        build: () =>
            textPreset('Nama', '{nama}', {
                font: 'display',
                weight: 700,
                size: 10,
            }),
    },
    {
        label: 'Peran',
        build: () =>
            textPreset('Peran', '{peran}', {
                weight: 700,
                uppercase: true,
                size: 5,
                bg: '#A51C24',
                color: '#E8CF8A',
                radius: 2,
            }),
    },
    {
        label: 'Nama gudep',
        build: () =>
            textPreset('Nama gudep', '{gudep}', { weight: 700, size: 6.5 }),
    },
    {
        label: 'Asal sekolah',
        build: () =>
            textPreset('Asal sekolah', '{sekolah} · {kwartir}', {
                size: 5.2,
                color: '#7A6A65',
            }),
    },
    {
        label: 'Nomor seri',
        build: () =>
            textPreset('Nomor seri', '{nomor_seri}', {
                font: 'mono',
                weight: 700,
                size: 7.5,
                align: 'left',
            }),
    },
    {
        label: 'Keterangan sertifikat',
        build: () =>
            textPreset(
                'Keterangan',
                'atas partisipasinya sebagai {peran_lengkap}, {gudep} – {sekolah}.',
                { size: 12 },
            ),
    },
    {
        label: 'Teks bebas',
        build: () => textPreset('Teks', 'Teks bebas', { size: 8 }),
    },
    {
        label: 'Foto',
        build: () => ({
            kind: 'photo',
            label: 'Foto',
            enabled: true,
            x: 5,
            y: 5,
            w: 22,
            h: 27,
            radius: 2,
            border: '#C9A24A',
        }),
    },
    {
        label: 'QR verifikasi',
        build: () => ({
            kind: 'qr',
            label: 'QR verifikasi',
            enabled: true,
            x: 5,
            y: 5,
            w: 12,
            h: 12,
            radius: 1,
        }),
    },
];

function textPreset(
    label: string,
    content: string,
    extra: Partial<TemplateField>,
): Omit<TemplateField, 'id'> {
    return {
        kind: 'text',
        label,
        enabled: true,
        x: 3,
        y: 5,
        w: 40,
        h: 5,
        content,
        font: 'sans',
        weight: 400,
        italic: false,
        size: 8,
        color: '#2A1416',
        align: 'center',
        uppercase: false,
        letter_spacing: 0,
        bg: null,
        radius: 0,
        border: null,
        ...extra,
    };
}

const round = (value: number) => Math.round(value * 10) / 10;

/** Sama dengan App\Services\Pdf\TextFit: perkecil font teks satu baris yang terlalu panjang. */
function fitSize(text: string, field: TemplateField): number {
    const size = field.size ?? 8;
    const sizeMm = size * PT_TO_MM;

    if (!text || text.includes('\n') || field.h >= sizeMm * 1.2 * 1.9) {
        return size;
    }

    let factor = { display: 0.54, sans: 0.56, mono: 0.6 }[field.font ?? 'sans'];
    factor += field.uppercase ? 0.08 : 0;
    factor += (field.weight ?? 400) >= 700 ? 0.03 : 0;
    const available = field.w - (field.bg ? sizeMm * 1.2 : 0);
    const estimated =
        text.length * (factor + (field.letter_spacing ?? 0)) * sizeMm;

    return estimated <= available
        ? size
        : Math.max(size * 0.6, (size * available) / estimated);
}

/** Editor visual template ID Card / Sertifikat (desain D3, FR-TPL-04). */
export default function TemplateEditor({ template, samples, targets }: Props) {
    const form = useForm({
        name: template.name,
        applies_to: template.applies_to,
        fields: template.fields,
    });
    const fields = form.data.fields;
    const [selectedId, setSelectedId] = useState<string | null>(
        fields[0]?.id ?? null,
    );
    const [sampleIndex, setSampleIndex] = useState(0);
    const [scale, setScale] = useState(template.type === 'id_card' ? 6 : 2.4);
    const selected = fields.find((field) => field.id === selectedId) ?? null;
    const sample = samples[sampleIndex] ?? samples[0];

    const setFields = useCallback(
        (updater: (current: TemplateField[]) => TemplateField[]) =>
            form.setData('fields', updater(form.data.fields)),
        [form],
    );

    const patch = useCallback(
        (id: string, changes: Partial<TemplateField>) =>
            setFields((current) =>
                current.map((field) =>
                    field.id === id ? { ...field, ...changes } : field,
                ),
            ),
        [setFields],
    );

    const save = (then?: () => void) =>
        form.put(update.url(template.id), {
            preserveScroll: true,
            onSuccess: () => then?.(),
        });

    const openPreview = () =>
        save(() =>
            window.open(
                preview.url(template.id, {
                    query: sample?.id ? { member: sample.id } : {},
                }),
                '_blank',
            ),
        );

    useEffect(() => {
        const onKey = (event: KeyboardEvent) => {
            const target = event.target as HTMLElement;

            if (
                !selected ||
                ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)
            ) {
                return;
            }

            const step = event.shiftKey ? 1 : 0.1;
            const moves: Record<string, Partial<TemplateField>> = {
                ArrowLeft: { x: round(selected.x - step) },
                ArrowRight: { x: round(selected.x + step) },
                ArrowUp: { y: round(selected.y - step) },
                ArrowDown: { y: round(selected.y + step) },
            };

            if (moves[event.key]) {
                event.preventDefault();
                patch(selected.id, moves[event.key]);
            }
        };

        window.addEventListener('keydown', onKey);

        return () => window.removeEventListener('keydown', onKey);
    }, [selected, patch]);

    return (
        <>
            <Head title={`Editor ${template.name}`} />

            <h1 className="sr-only">Editor {template.name}</h1>
            <nav
                aria-label="Breadcrumb"
                className="text-sm font-medium text-gold-700"
            >
                <Link href="/admin/template" className="hover:underline">
                    Template
                </Link>{' '}
                / {template.type_label}
            </nav>
            <div className="mt-2 mb-6 flex flex-wrap items-start justify-between gap-4">
                <div className="min-w-0">
                    <input
                        value={form.data.name}
                        onChange={(event) =>
                            form.setData('name', event.target.value)
                        }
                        aria-label="Nama template"
                        className="w-full max-w-xl bg-transparent font-display text-[32px] leading-tight font-bold text-ink outline-none focus:underline focus:decoration-gold-500"
                    />
                    <div className="mt-2 flex flex-wrap items-center gap-2 text-xs font-semibold">
                        {template.is_active ? (
                            <span className="rounded-full bg-success-soft px-2.5 py-1 text-success">
                                ● Aktif
                            </span>
                        ) : (
                            <span className="rounded-full bg-sand px-2.5 py-1 text-ink-muted">
                                Tidak aktif
                            </span>
                        )}
                        <span className="rounded-full bg-sand px-2.5 py-1 text-ink-muted">
                            {template.width_mm} × {template.height_mm} mm
                        </span>
                        <label className="flex items-center gap-1 rounded-full bg-sand py-1 pr-1 pl-2.5 text-ink-muted">
                            Sasaran:
                            <select
                                value={form.data.applies_to}
                                onChange={(event) =>
                                    form.setData(
                                        'applies_to',
                                        event.target
                                            .value as TemplateSummary['applies_to'],
                                    )
                                }
                                className="rounded-full bg-white px-2 py-0.5 text-ink outline-none"
                            >
                                {targets.map((target) => (
                                    <option
                                        key={target.value}
                                        value={target.value}
                                    >
                                        {target.label}
                                    </option>
                                ))}
                            </select>
                        </label>
                        {form.isDirty && (
                            <span className="text-gold-800">
                                Perubahan belum disimpan
                            </span>
                        )}
                    </div>
                    {Object.keys(form.errors).length > 0 && (
                        <p className="mt-2 text-sm text-maroon-600">
                            {Object.values(form.errors)[0]}
                        </p>
                    )}
                </div>
                <div className="flex flex-wrap gap-3">
                    {!template.is_active && (
                        <Button
                            variant="gold"
                            size="xl"
                            onClick={() =>
                                router.post(
                                    activate.url(template.id),
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            <Star className="size-4" /> Aktifkan
                        </Button>
                    )}
                    <Button
                        variant="outline-maroon"
                        size="xl"
                        onClick={openPreview}
                        disabled={form.processing}
                    >
                        <Eye className="size-4" /> Pratinjau PDF
                    </Button>
                    <Button
                        size="xl"
                        onClick={() => save()}
                        disabled={form.processing}
                    >
                        {form.processing && <Spinner />}
                        Simpan template
                    </Button>
                </div>
            </div>

            <div className="grid gap-5 xl:grid-cols-[260px_minmax(0,1fr)_280px]">
                <FieldPanel
                    template={template}
                    fields={fields}
                    selectedId={selectedId}
                    onSelect={setSelectedId}
                    onToggle={(field) =>
                        patch(field.id, { enabled: !field.enabled })
                    }
                    onAdd={(preset) => {
                        const field = {
                            ...preset,
                            id: crypto.randomUUID(),
                        } as TemplateField;
                        setFields((current) => [...current, field]);
                        setSelectedId(field.id);
                    }}
                />

                <div className="flex min-h-[640px] flex-col items-center rounded-3xl border border-line bg-sand/60 p-5">
                    <label className="flex w-full max-w-md items-center gap-2 rounded-xl border border-line bg-white px-3 py-2 text-sm">
                        <span className="shrink-0 text-ink-soft">
                            Data contoh:
                        </span>
                        <select
                            value={sampleIndex}
                            onChange={(event) =>
                                setSampleIndex(Number(event.target.value))
                            }
                            className="min-w-0 flex-1 bg-transparent font-semibold text-ink outline-none"
                        >
                            {samples.map((item, index) => (
                                <option
                                    key={`${item.id}-${index}`}
                                    value={index}
                                >
                                    {item.label}
                                </option>
                            ))}
                        </select>
                    </label>

                    <div className="mt-6 flex-1 overflow-auto">
                        <p className="mb-2 text-center text-xs font-semibold text-gold-700">
                            ← {template.width_mm} mm →
                        </p>
                        <div className="flex items-center gap-3">
                            <Canvas
                                template={template}
                                fields={fields}
                                scale={scale}
                                sample={sample}
                                selectedId={selectedId}
                                onSelect={setSelectedId}
                                onChange={patch}
                            />
                            <p className="text-xs font-semibold text-gold-700 [writing-mode:vertical-rl]">
                                {template.height_mm} mm
                            </p>
                        </div>
                    </div>

                    <div className="mt-5 flex items-center gap-3 rounded-full bg-maroon-900 px-4 py-2 text-sm text-white">
                        <button
                            type="button"
                            aria-label="Perkecil"
                            onClick={() =>
                                setScale((value) =>
                                    Math.max(1.5, round(value - 0.5)),
                                )
                            }
                        >
                            <Minus className="size-4" />
                        </button>
                        <span className="tabular-nums">
                            {Math.round(
                                (scale /
                                    (template.type === 'id_card' ? 6 : 2.4)) *
                                    100,
                            )}
                            % · {scale} px/mm
                        </span>
                        <button
                            type="button"
                            aria-label="Perbesar"
                            onClick={() =>
                                setScale((value) =>
                                    Math.min(12, round(value + 0.5)),
                                )
                            }
                        >
                            <Plus className="size-4" />
                        </button>
                    </div>
                </div>

                <PropertyPanel
                    field={selected}
                    onChange={(changes) =>
                        selected && patch(selected.id, changes)
                    }
                    onDelete={() => {
                        if (!selected) {
                            return;
                        }

                        setFields((current) =>
                            current.filter((field) => field.id !== selected.id),
                        );
                        setSelectedId(null);
                    }}
                />
            </div>
        </>
    );
}

function FieldPanel({
    template,
    fields,
    selectedId,
    onSelect,
    onToggle,
    onAdd,
}: {
    template: Props['template'];
    fields: TemplateField[];
    selectedId: string | null;
    onSelect: (id: string) => void;
    onToggle: (field: TemplateField) => void;
    onAdd: (preset: Omit<TemplateField, 'id'>) => void;
}) {
    const fileRef = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);
    const icon = (field: TemplateField) =>
        field.kind === 'photo' ? (
            <UserSquare className="size-4" />
        ) : field.kind === 'qr' ? (
            <QrCode className="size-4" />
        ) : (
            <Type className="size-4" />
        );

    return (
        <aside className="rounded-3xl border border-line bg-white p-5">
            <h2 className="font-display text-xl font-bold text-ink">Field</h2>
            <p className="text-xs text-ink-soft">
                Tarik field di kanvas untuk mengatur posisi
            </p>

            <button
                type="button"
                onClick={() => fileRef.current?.click()}
                className="mt-4 flex w-full items-center gap-3 rounded-2xl border border-dashed border-gold-500 p-3 text-left"
            >
                {template.background_url ? (
                    <img
                        src={template.background_url}
                        alt=""
                        className="h-12 w-9 rounded object-cover"
                    />
                ) : (
                    <span className="grid h-12 w-9 place-items-center rounded bg-sand">
                        <ImageIcon className="size-4 text-ink-soft" />
                    </span>
                )}
                <span className="min-w-0">
                    <span className="block truncate text-sm font-semibold text-ink">
                        {template.background_url
                            ? 'Gambar latar'
                            : 'Belum ada latar'}
                    </span>
                    <span className="block text-xs font-semibold text-maroon-700">
                        {uploading
                            ? 'Mengunggah…'
                            : template.background_url
                              ? 'Ganti gambar latar'
                              : 'Unggah gambar latar'}
                    </span>
                </span>
            </button>
            <input
                ref={fileRef}
                type="file"
                accept="image/png,image/jpeg"
                aria-label="Unggah gambar latar"
                tabIndex={-1}
                className="sr-only"
                onChange={(event) => {
                    const file = event.target.files?.[0];

                    if (file) {
                        router.post(
                            uploadBackground.url(template.id),
                            { background: file },
                            {
                                forceFormData: true,
                                preserveScroll: true,
                                preserveState: true,
                                onStart: () => setUploading(true),
                                onFinish: () => setUploading(false),
                            },
                        );
                    }
                }}
            />
            <p className="mt-1 text-[11px] text-ink-soft">
                PNG/JPG, rasio sama dengan {template.width_mm} ×{' '}
                {template.height_mm} mm.
            </p>

            <ul className="mt-4 grid gap-1">
                {fields.map((field) => (
                    <li key={field.id}>
                        <div
                            className={cn(
                                'flex items-center gap-3 rounded-xl border px-3 py-2.5 text-sm',
                                field.id === selectedId
                                    ? 'border-gold-500 bg-gold-100/60 font-semibold'
                                    : 'border-transparent hover:bg-ivory',
                            )}
                        >
                            <button
                                type="button"
                                onClick={() => onSelect(field.id)}
                                className="flex min-w-0 flex-1 items-center gap-3 text-left text-ink"
                            >
                                <span className="text-ink-soft">
                                    {icon(field)}
                                </span>
                                <span className="truncate">{field.label}</span>
                            </button>
                            <Switch
                                checked={field.enabled}
                                onChange={() => onToggle(field)}
                                label={`Tampilkan ${field.label}`}
                            />
                        </div>
                    </li>
                ))}
            </ul>

            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <button
                        type="button"
                        className="mt-3 w-full rounded-xl border border-dashed border-gold-500 py-2.5 text-sm font-bold text-maroon-700 hover:bg-gold-100/40"
                    >
                        + Tambah field
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="start" className="w-56">
                    {presets.map((preset) => (
                        <DropdownMenuItem
                            key={preset.label}
                            onSelect={() => onAdd(preset.build())}
                        >
                            {preset.label}
                        </DropdownMenuItem>
                    ))}
                </DropdownMenuContent>
            </DropdownMenu>
        </aside>
    );
}

function Canvas({
    template,
    fields,
    scale,
    sample,
    selectedId,
    onSelect,
    onChange,
}: {
    template: Props['template'];
    fields: TemplateField[];
    scale: number;
    sample: TemplateSample | undefined;
    selectedId: string | null;
    onSelect: (id: string | null) => void;
    onChange: (id: string, changes: Partial<TemplateField>) => void;
}) {
    const drag = useRef<{
        id: string;
        mode: 'move' | 'resize';
        startX: number;
        startY: number;
        field: TemplateField;
    } | null>(null);

    const startDrag = (
        event: ReactPointerEvent,
        field: TemplateField,
        mode: 'move' | 'resize',
    ) => {
        event.stopPropagation();
        (event.target as HTMLElement).setPointerCapture(event.pointerId);
        onSelect(field.id);
        drag.current = {
            id: field.id,
            mode,
            startX: event.clientX,
            startY: event.clientY,
            field,
        };
    };

    const onMove = (event: ReactPointerEvent) => {
        const state = drag.current;

        if (!state) {
            return;
        }

        const dx = (event.clientX - state.startX) / scale;
        const dy = (event.clientY - state.startY) / scale;

        onChange(
            state.id,
            state.mode === 'move'
                ? { x: round(state.field.x + dx), y: round(state.field.y + dy) }
                : {
                      w: Math.max(1, round(state.field.w + dx)),
                      h: Math.max(1, round(state.field.h + dy)),
                  },
        );
    };

    const interpolate = (content: string) =>
        content.replace(
            /\{([a-z_]+)\}/g,
            (match, key: string) => sample?.values[key] ?? match,
        );

    return (
        <div
            className="relative shrink-0 overflow-hidden rounded-xl bg-white shadow-[0_20px_50px_rgba(62,7,11,0.25)] select-none"
            style={{
                width: template.width_mm * scale,
                height: template.height_mm * scale,
            }}
            onPointerMove={onMove}
            onPointerUp={() => (drag.current = null)}
            onPointerDown={() => onSelect(null)}
        >
            {template.background_url && (
                <img
                    src={template.background_url}
                    alt=""
                    draggable={false}
                    className="pointer-events-none absolute inset-0 size-full"
                />
            )}

            {fields
                .filter((field) => field.enabled)
                .map((field) => {
                    const selected = field.id === selectedId;
                    const box = {
                        left: field.x * scale,
                        top: field.y * scale,
                        width: field.w * scale,
                        height: field.h * scale,
                    };

                    let content: ReactNode = null;

                    if (field.kind === 'photo') {
                        content = (
                            <div
                                className="size-full overflow-hidden bg-[#e9d7c4]"
                                style={{
                                    borderRadius: (field.radius ?? 0) * scale,
                                    border: field.border
                                        ? `${0.5 * scale}px solid ${field.border}`
                                        : undefined,
                                }}
                            >
                                {sample?.photo_url ? (
                                    <img
                                        src={sample.photo_url}
                                        alt=""
                                        draggable={false}
                                        className="size-full object-cover"
                                    />
                                ) : (
                                    <div className="grid size-full place-items-center text-[#a88b6e]">
                                        <UserSquare
                                            style={{
                                                width: field.w * scale * 0.5,
                                                height: field.w * scale * 0.5,
                                            }}
                                            strokeWidth={1.2}
                                        />
                                    </div>
                                )}
                            </div>
                        );
                    } else if (field.kind === 'qr') {
                        content = (
                            <div
                                className="grid size-full place-items-center bg-white text-ink"
                                style={{
                                    borderRadius: (field.radius ?? 0) * scale,
                                }}
                            >
                                <QrCode
                                    style={{
                                        width: field.w * scale * 0.85,
                                        height: field.h * scale * 0.85,
                                    }}
                                    strokeWidth={1.4}
                                />
                            </div>
                        );
                    } else {
                        const text = interpolate(field.content ?? '');
                        content = (
                            <div
                                className="size-full whitespace-pre-line"
                                style={{
                                    fontFamily:
                                        fontFamily[field.font ?? 'sans'],
                                    fontWeight: field.weight ?? 400,
                                    fontStyle: field.italic
                                        ? 'italic'
                                        : 'normal',
                                    fontSize:
                                        fitSize(text, field) * PT_TO_MM * scale,
                                    color: field.color ?? '#2A1416',
                                    textAlign: field.align ?? 'center',
                                    textTransform: field.uppercase
                                        ? 'uppercase'
                                        : 'none',
                                    letterSpacing: `${field.letter_spacing ?? 0}em`,
                                    lineHeight: field.bg ? 1.15 : 1.2,
                                    background: field.bg ?? undefined,
                                    borderRadius: (field.radius ?? 0) * scale,
                                    display: field.bg ? 'flex' : undefined,
                                    alignItems: field.bg ? 'center' : undefined,
                                    justifyContent: field.bg
                                        ? (
                                              {
                                                  left: 'flex-start',
                                                  center: 'center',
                                                  right: 'flex-end',
                                              } as const
                                          )[field.align ?? 'center']
                                        : undefined,
                                }}
                            >
                                {text}
                            </div>
                        );
                    }

                    return (
                        <div
                            key={field.id}
                            className={cn(
                                'absolute cursor-move',
                                selected
                                    ? 'outline-[1.5px] outline-offset-1 outline-gold-500 outline-dashed'
                                    : 'hover:outline hover:outline-1 hover:outline-gold-300',
                            )}
                            style={box}
                            onPointerDown={(event) =>
                                startDrag(event, field, 'move')
                            }
                        >
                            {content}
                            {selected && (
                                <>
                                    {field.kind === 'text' && (
                                        <span className="absolute -top-5 left-0 rounded bg-gold-700 px-1.5 text-[10px] leading-4 font-semibold text-white">
                                            {field.label}
                                        </span>
                                    )}
                                    <span
                                        aria-label="Ubah ukuran"
                                        className="absolute -right-1.5 -bottom-1.5 size-3 cursor-nwse-resize border border-gold-700 bg-white"
                                        onPointerDown={(event) =>
                                            startDrag(event, field, 'resize')
                                        }
                                    />
                                </>
                            )}
                        </div>
                    );
                })}
        </div>
    );
}

function PropertyPanel({
    field,
    onChange,
    onDelete,
}: {
    field: TemplateField | null;
    onChange: (changes: Partial<TemplateField>) => void;
    onDelete: () => void;
}) {
    const placeholders = useMemo(
        () => [
            '{nama}',
            '{peran}',
            '{peran_lengkap}',
            '{gudep}',
            '{sekolah}',
            '{kwartir}',
            '{nomor_seri}',
            '{tahun}',
        ],
        [],
    );

    if (!field) {
        return (
            <aside className="rounded-3xl border border-line bg-white p-5">
                <h2 className="font-display text-xl font-bold text-ink">
                    Properti
                </h2>
                <p className="mt-2 text-sm text-ink-soft">
                    Pilih field di kanvas atau di daftar untuk mengubah posisi
                    dan gaya.
                </p>
                <Note />
            </aside>
        );
    }

    const number = (key: keyof TemplateField, label: string, step = 0.1) => (
        <label className="flex items-center gap-2 rounded-xl border border-line bg-white px-3 py-2 text-sm">
            <span className="w-4 text-ink-soft">{label}</span>
            <input
                type="number"
                step={step}
                value={Number(field[key] ?? 0)}
                onChange={(event) =>
                    onChange({
                        [key]: Number(event.target.value),
                    } as Partial<TemplateField>)
                }
                className="w-full min-w-0 bg-transparent tabular-nums outline-none"
            />
        </label>
    );

    return (
        <aside className="flex flex-col gap-5 rounded-3xl border border-line bg-white p-5">
            <div>
                <h2 className="font-display text-xl font-bold text-ink">
                    Properti
                </h2>
                <input
                    value={field.label}
                    onChange={(event) =>
                        onChange({ label: event.target.value })
                    }
                    aria-label="Label field"
                    className="mt-1 w-full rounded-lg bg-ivory px-2 py-1 font-mono text-xs text-gold-800 outline-none"
                />
            </div>

            <div>
                <p className="mb-2 text-[11px] font-semibold tracking-[0.18em] text-ink-muted uppercase">
                    Posisi & ukuran (mm)
                </p>
                <div className="grid grid-cols-2 gap-2">
                    {number('x', 'X')}
                    {number('y', 'Y')}
                    {number('w', 'L')}
                    {number('h', 'T')}
                </div>
            </div>

            {field.kind === 'text' && (
                <>
                    <div>
                        <p className="mb-2 text-[11px] font-semibold tracking-[0.18em] text-ink-muted uppercase">
                            Isi
                        </p>
                        <textarea
                            rows={3}
                            aria-label="Isi teks field"
                            value={field.content ?? ''}
                            onChange={(event) =>
                                onChange({ content: event.target.value })
                            }
                            className="w-full rounded-xl border border-line px-3 py-2 text-sm outline-none focus:border-maroon-600"
                        />
                        <div className="mt-1 flex flex-wrap gap-1">
                            {placeholders.map((placeholder) => (
                                <button
                                    key={placeholder}
                                    type="button"
                                    onClick={() =>
                                        onChange({
                                            content: `${field.content ?? ''}${placeholder}`,
                                        })
                                    }
                                    className="rounded bg-gold-100 px-1.5 py-0.5 font-mono text-[10px] text-gold-800 hover:bg-gold-200"
                                >
                                    {placeholder}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <p className="text-[11px] font-semibold tracking-[0.18em] text-ink-muted uppercase">
                            Tipografi
                        </p>
                        <select
                            aria-label="Jenis huruf"
                            value={field.font ?? 'sans'}
                            onChange={(event) =>
                                onChange({
                                    font: event.target
                                        .value as TemplateField['font'],
                                })
                            }
                            className="rounded-xl border border-line bg-white px-3 py-2 text-sm"
                            style={{
                                fontFamily: fontFamily[field.font ?? 'sans'],
                            }}
                        >
                            <option value="display">Playfair Display</option>
                            <option value="sans">Plus Jakarta Sans</option>
                            <option value="mono">Monospace</option>
                        </select>
                        <div className="grid grid-cols-2 gap-2">
                            <select
                                aria-label="Ketebalan huruf"
                                value={field.weight ?? 400}
                                onChange={(event) =>
                                    onChange({
                                        weight: Number(event.target.value),
                                    })
                                }
                                className="rounded-xl border border-line bg-white px-3 py-2 text-sm"
                            >
                                <option value={400}>Regular</option>
                                <option value={500}>Medium</option>
                                <option value={600}>SemiBold</option>
                                <option value={700}>Bold</option>
                                <option value={800}>ExtraBold</option>
                            </select>
                            <label className="flex items-center gap-1 rounded-xl border border-line px-3 py-2 text-sm">
                                <input
                                    type="number"
                                    step={0.1}
                                    min={3}
                                    value={field.size ?? 8}
                                    onChange={(event) =>
                                        onChange({
                                            size: Number(event.target.value),
                                        })
                                    }
                                    className="w-full min-w-0 bg-transparent outline-none"
                                />
                                pt
                            </label>
                        </div>
                        <div className="grid grid-cols-3 gap-1 rounded-xl bg-sand p-1">
                            {(['left', 'center', 'right'] as const).map(
                                (align) => {
                                    const Icon = {
                                        left: AlignLeft,
                                        center: AlignCenter,
                                        right: AlignRight,
                                    }[align];

                                    return (
                                        <button
                                            key={align}
                                            type="button"
                                            aria-label={`Rata ${align}`}
                                            onClick={() => onChange({ align })}
                                            className={cn(
                                                'grid place-items-center rounded-lg py-1.5',
                                                (field.align ?? 'center') ===
                                                    align &&
                                                    'bg-white shadow-sm',
                                            )}
                                        >
                                            <Icon className="size-4" />
                                        </button>
                                    );
                                },
                            )}
                        </div>
                        <ColorInput
                            label="Warna teks"
                            value={field.color ?? '#2A1416'}
                            onChange={(color) => onChange({ color })}
                        />
                        <div className="flex flex-wrap gap-x-4 gap-y-2 text-sm text-ink">
                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={Boolean(field.italic)}
                                    onChange={(event) =>
                                        onChange({
                                            italic: event.target.checked,
                                        })
                                    }
                                />
                                Miring
                            </label>
                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={Boolean(field.uppercase)}
                                    onChange={(event) =>
                                        onChange({
                                            uppercase: event.target.checked,
                                        })
                                    }
                                />
                                Huruf kapital
                            </label>
                        </div>
                        <label className="flex items-center gap-2 text-sm text-ink-muted">
                            Jarak huruf
                            <input
                                type="number"
                                step={0.01}
                                value={field.letter_spacing ?? 0}
                                onChange={(event) =>
                                    onChange({
                                        letter_spacing: Number(
                                            event.target.value,
                                        ),
                                    })
                                }
                                className="w-20 rounded-lg border border-line px-2 py-1 text-ink"
                            />
                            em
                        </label>
                    </div>

                    <div className="grid gap-2">
                        <p className="text-[11px] font-semibold tracking-[0.18em] text-ink-muted uppercase">
                            Latar (pita)
                        </p>
                        <label className="flex items-center gap-2 text-sm text-ink">
                            <input
                                type="checkbox"
                                checked={Boolean(field.bg)}
                                onChange={(event) =>
                                    onChange({
                                        bg: event.target.checked
                                            ? '#A51C24'
                                            : null,
                                    })
                                }
                            />
                            Pakai warna latar
                        </label>
                        {field.bg && (
                            <ColorInput
                                label="Warna latar"
                                value={field.bg}
                                onChange={(bg) => onChange({ bg })}
                            />
                        )}
                    </div>
                </>
            )}

            {(field.kind === 'photo' || field.bg || field.kind === 'qr') && (
                <label className="flex items-center gap-2 text-sm text-ink-muted">
                    Sudut membulat
                    <input
                        type="number"
                        step={0.1}
                        min={0}
                        value={field.radius ?? 0}
                        onChange={(event) =>
                            onChange({ radius: Number(event.target.value) })
                        }
                        className="w-20 rounded-lg border border-line px-2 py-1 text-ink"
                    />
                    mm
                </label>
            )}

            {field.kind === 'photo' && (
                <ColorInput
                    label="Warna bingkai"
                    value={field.border ?? '#C9A24A'}
                    onChange={(border) => onChange({ border })}
                />
            )}

            <Note />
            <button
                type="button"
                onClick={onDelete}
                className="flex items-center gap-2 text-sm font-semibold text-maroon-600 hover:underline"
            >
                <Trash2 className="size-4" /> Hapus field ini
            </button>
        </aside>
    );
}

function ColorInput({
    label,
    value,
    onChange,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
}) {
    return (
        <label className="flex items-center gap-3 rounded-xl border border-line px-3 py-2 text-sm">
            <input
                type="color"
                value={value}
                onChange={(event) => onChange(event.target.value.toUpperCase())}
                aria-label={label}
                className="size-6 cursor-pointer rounded border-0 bg-transparent p-0"
            />
            <input
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="w-full min-w-0 bg-transparent font-mono uppercase outline-none"
                aria-label={`${label} (hex)`}
            />
        </label>
    );
}

function Switch({
    checked,
    onChange,
    label,
}: {
    checked: boolean;
    onChange: () => void;
    label: string;
}) {
    return (
        <button
            type="button"
            role="switch"
            aria-checked={checked}
            aria-label={label}
            onClick={onChange}
            className={cn(
                'relative h-5 w-9 shrink-0 rounded-full transition',
                checked ? 'bg-maroon-600' : 'bg-line',
            )}
        >
            <span
                className={cn(
                    'absolute top-0.5 size-4 rounded-full bg-white shadow transition',
                    checked ? 'left-[18px]' : 'left-0.5',
                )}
            />
        </button>
    );
}

function Note() {
    return (
        <p className="rounded-2xl bg-gold-100/60 px-4 py-3 text-xs leading-relaxed text-gold-800">
            Ukuran di editor sama persis dengan PDF hasil. Uji cetak satu kartu
            sebelum generate massal. Geser field dengan tetikus atau tombol
            panah (Shift = 1 mm).
        </p>
    );
}
