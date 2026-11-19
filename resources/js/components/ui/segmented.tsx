import { cn } from '@/lib/utils';

type Option = { value: string; label: string };

/**
 * Pilihan tersegmentasi berbasis radio (desain M3c "Mendampingi"). Nilai
 * dikirim lewat input radio bernama `name`, jadi bekerja di dalam <Form>.
 */
export function Segmented({
    name,
    options,
    defaultValue,
    disabled = false,
    id,
}: {
    name: string;
    options: Option[];
    defaultValue?: string | null;
    disabled?: boolean;
    id?: string;
}) {
    return (
        <div
            id={id}
            role="radiogroup"
            className="grid auto-cols-fr grid-flow-col gap-1 rounded-[14px] bg-sand p-1"
        >
            {options.map((option) => (
                <label key={option.value} className="relative">
                    <input
                        type="radio"
                        name={name}
                        value={option.value}
                        defaultChecked={defaultValue === option.value}
                        disabled={disabled}
                        className="peer sr-only"
                    />
                    <span className="flex h-11 cursor-pointer items-center justify-center rounded-[11px] text-sm font-semibold text-ink-muted transition peer-checked:bg-[linear-gradient(135deg,#a51c24,#7a1219)] peer-checked:text-white peer-focus-visible:ring-4 peer-focus-visible:ring-maroon-100 peer-disabled:cursor-not-allowed">
                        {option.label}
                    </span>
                </label>
            ))}
        </div>
    );
}
