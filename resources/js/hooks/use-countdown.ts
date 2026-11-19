import { useEffect, useState } from 'react';

export type Countdown = {
    days: number;
    hours: number;
    minutes: number;
    seconds: number;
    done: boolean;
};

/**
 * Hitung mundur ke `target` (ISO 8601). `serverNow` dipakai untuk mengoreksi
 * selisih jam perangkat pengguna dengan jam server.
 */
export function useCountdown(
    target: string | null,
    serverNow?: string,
): Countdown | null {
    const [offset] = useState(() =>
        serverNow ? new Date(serverNow).getTime() - Date.now() : 0,
    );
    const [now, setNow] = useState(() => Date.now() + offset);

    useEffect(() => {
        if (!target) {
            return;
        }

        const id = window.setInterval(() => setNow(Date.now() + offset), 1000);

        return () => window.clearInterval(id);
    }, [target, offset]);

    if (!target) {
        return null;
    }

    const diff = Math.max(0, new Date(target).getTime() - now);
    const totalSeconds = Math.floor(diff / 1000);

    return {
        days: Math.floor(totalSeconds / 86400),
        hours: Math.floor((totalSeconds % 86400) / 3600),
        minutes: Math.floor((totalSeconds % 3600) / 60),
        seconds: totalSeconds % 60,
        done: diff === 0,
    };
}

export const pad = (value: number) => value.toString().padStart(2, '0');
