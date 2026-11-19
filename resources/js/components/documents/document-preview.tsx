import { ExternalLink } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { DocumentData } from '@/types';

/**
 * Pratinjau dokumen tanpa mengunduh (FR-DOC-05): gambar sebagai <img>, PDF
 * lewat penampil PDF bawaan browser. Selalu ada tautan buka di tab baru
 * untuk browser ponsel yang tidak menampilkan PDF di dalam halaman.
 */
export function DocumentPreview({
    document,
    className,
}: {
    document: DocumentData;
    className?: string;
}) {
    const isImage = document.mime.startsWith('image/');
    const isPdf = document.mime === 'application/pdf';

    return (
        <div className={cn('grid gap-2', className)}>
            <div className="overflow-hidden rounded-2xl border border-line bg-white">
                {isImage && (
                    <img
                        src={document.url}
                        alt={document.original_name}
                        className="mx-auto max-h-[55vh] w-full object-contain"
                    />
                )}
                {isPdf && (
                    <iframe
                        src={`${document.url}#toolbar=0&view=FitH`}
                        title={document.original_name}
                        className="h-[55vh] w-full"
                    />
                )}
                {!isImage && !isPdf && (
                    <p className="px-4 py-6 text-center text-sm text-ink-soft">
                        Pratinjau tidak tersedia untuk format ini.
                    </p>
                )}
            </div>
            <a
                href={document.url}
                target="_blank"
                rel="noreferrer"
                className="flex items-center justify-center gap-2 text-sm font-semibold text-maroon-700 hover:underline"
            >
                <ExternalLink className="size-4" /> Buka di tab baru
            </a>
        </div>
    );
}
