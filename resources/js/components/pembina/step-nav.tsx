import { Link, usePage } from '@inertiajs/react';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { Button } from '@/components/ui/button';

/**
 * Footer Kembali / Lanjut di setiap langkah alur pembina. Semua langkah boleh
 * dibuka kapan saja; tombol ini hanya memandu ke urutan berikutnya.
 */
export function StepNav() {
    const { pembinaSteps, pembinaStep } = usePage().props;
    const steps = pembinaSteps ?? [];
    const index = steps.findIndex((step) => step.key === pembinaStep);

    if (index === -1) {
        return null;
    }

    const previous = index > 0 ? steps[index - 1] : null;
    const next = steps[index + 1] ?? null;

    return (
        <nav
            aria-label="Pindah langkah"
            className="flex flex-wrap gap-3 px-6 pt-2 pb-8"
        >
            <Button asChild variant="outline" size="xl" className="flex-1">
                <Link href={previous?.href ?? '/pembina'}>
                    <ArrowLeft className="size-4" aria-hidden />
                    Kembali
                    <span className="sr-only">
                        {`: ${previous ? previous.title : 'Ringkasan'}`}
                    </span>
                </Link>
            </Button>
            {next && (
                <Button asChild size="xl" className="flex-[2]">
                    <Link href={next.href}>
                        Lanjut: {next.title}
                        <ArrowRight className="size-4" aria-hidden />
                    </Link>
                </Button>
            )}
        </nav>
    );
}
