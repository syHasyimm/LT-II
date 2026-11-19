import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';

/** Avatar anggota: pas foto bila ada, selain itu inisial dengan cincin emas (desain M3). */
export function MemberAvatar({
    name,
    photoUrl,
    owner = false,
    className,
}: {
    name: string;
    photoUrl: string | null;
    owner?: boolean;
    className?: string;
}) {
    const getInitials = useInitials();

    if (photoUrl) {
        return (
            <img
                src={photoUrl}
                alt={`Pas foto ${name}`}
                className={cn(
                    'size-12 shrink-0 rounded-full border-2 border-gold-500 object-cover',
                    className,
                )}
            />
        );
    }

    return (
        <span
            aria-hidden
            className={cn(
                'grid size-12 shrink-0 place-items-center rounded-full border-2 border-gold-500 text-sm font-bold',
                owner
                    ? 'bg-gold-gradient text-maroon-900'
                    : 'bg-maroon-100 text-maroon-700',
                className,
            )}
        >
            {getInitials(name)}
        </span>
    );
}
