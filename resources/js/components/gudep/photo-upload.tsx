import { Camera, RefreshCw } from 'lucide-react';
import { useEffect, useId, useState } from 'react';
import InputError from '@/components/input-error';
import { cn } from '@/lib/utils';

type PhotoUploadProps = {
    currentUrl?: string | null;
    error?: string;
    maxUploadMb: number;
    disabled?: boolean;
    /** `circle` untuk halaman form (M3b), `bar` untuk kartu pembina (M3c). */
    variant?: 'circle' | 'bar';
};

/**
 * Unggah pas foto dari kamera atau galeri dengan pratinjau. Input bernama
 * `photo`, sehingga <Form> Inertia otomatis mengirim multipart.
 */
export function PhotoUpload({
    currentUrl,
    error,
    maxUploadMb,
    disabled = false,
    variant = 'circle',
}: PhotoUploadProps) {
    const id = useId();
    const [preview, setPreview] = useState<string | null>(null);
    const shown = preview ?? currentUrl ?? null;

    useEffect(() => {
        return () => {
            if (preview) {
                URL.revokeObjectURL(preview);
            }
        };
    }, [preview]);

    const input = (
        <input
            id={id}
            type="file"
            name="photo"
            accept="image/jpeg,image/png"
            capture="user"
            disabled={disabled}
            className="sr-only"
            onChange={(event) => {
                const file = event.target.files?.[0];
                setPreview(file ? URL.createObjectURL(file) : null);
            }}
        />
    );

    if (variant === 'bar') {
        return (
            <div className="grid gap-1.5">
                <label
                    htmlFor={id}
                    className={cn(
                        'flex h-14 cursor-pointer items-center gap-3 rounded-[14px] border-[1.5px] border-dashed border-gold-500 bg-gold-100/30 px-4',
                        disabled && 'cursor-not-allowed opacity-60',
                    )}
                >
                    {shown ? (
                        <img
                            src={shown}
                            alt="Pratinjau pas foto"
                            className="size-9 rounded-full object-cover"
                        />
                    ) : (
                        <Camera className="size-5 text-gold-700" />
                    )}
                    <span className="flex-1 text-sm font-bold text-maroon-700">
                        {shown ? 'Ganti pas foto' : 'Unggah pas foto'}
                    </span>
                    <span className="text-xs text-ink-soft">
                        Wajib · ID Card
                    </span>
                    {input}
                </label>
                <InputError message={error} />
            </div>
        );
    }

    return (
        <div className="grid justify-items-center gap-3 text-center">
            <label
                htmlFor={id}
                className={cn(
                    'relative grid size-[104px] cursor-pointer place-items-center overflow-hidden rounded-full border-2 border-dashed border-gold-500 bg-gold-100/30 text-gold-700',
                    disabled && 'cursor-not-allowed opacity-60',
                )}
            >
                {shown ? (
                    <>
                        <img
                            src={shown}
                            alt="Pratinjau pas foto"
                            className="absolute inset-0 size-full object-cover"
                        />
                        {!disabled && (
                            <span className="absolute inset-x-0 bottom-0 flex items-center justify-center gap-1 bg-maroon-900/70 py-1 text-[11px] font-semibold text-white">
                                <RefreshCw className="size-3" /> Ganti
                            </span>
                        )}
                    </>
                ) : (
                    <span className="grid justify-items-center gap-1">
                        <Camera className="size-7" strokeWidth={1.6} />
                        <span className="text-xs font-semibold">Pas foto</span>
                    </span>
                )}
                {input}
            </label>
            <p className="text-xs leading-relaxed text-ink-soft">
                Ambil dari kamera atau galeri · JPG/PNG maks. {maxUploadMb} MB
                <br />
                Dipakai untuk ID Card
            </p>
            <InputError message={error} />
        </div>
    );
}
