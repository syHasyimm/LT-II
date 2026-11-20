import { useState, type ComponentProps } from 'react';
import { Input } from '@/components/ui/input';

type Props = Omit<ComponentProps<typeof Input>, 'type' | 'defaultValue'> & {
    defaultValue?: string | null;
};

/** Isian tanggal dengan tampilan hari/bulan/tahun (dd/mm/yyyy). */
function toDisplay(iso?: string | null): string {
    if (!iso) {
        return '';
    }

    const [year, month, day] = iso.split('-');

    return year && month && day ? `${day}/${month}/${year}` : '';
}

function mask(value: string): string {
    const digits = value.replace(/\D/g, '').slice(0, 8);

    return [digits.slice(0, 2), digits.slice(2, 4), digits.slice(4, 8)]
        .filter(Boolean)
        .join('/');
}

export function DateInput({ defaultValue, ...props }: Props) {
    const [value, setValue] = useState(() => toDisplay(defaultValue));

    return (
        <Input
            {...props}
            type="text"
            inputMode="numeric"
            autoComplete="off"
            placeholder="hh/bb/tttt"
            maxLength={10}
            pattern="[0-9]{2}/[0-9]{2}/[0-9]{4}"
            title="Gunakan format hari/bulan/tahun, misalnya 15/03/2013"
            value={value}
            onChange={(event) => setValue(mask(event.target.value))}
        />
    );
}
