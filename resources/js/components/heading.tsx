export default function Heading({
    title,
    description,
    variant = 'default',
}: {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
}) {
    return (
        <header className={variant === 'small' ? '' : 'mb-8 space-y-1'}>
            <h2
                className={
                    variant === 'small'
                        ? 'font-display text-xl font-bold text-ink'
                        : 'font-display text-[28px] leading-tight font-bold text-ink'
                }
            >
                {title}
            </h2>
            {description && (
                <p className="mt-1 text-sm text-ink-muted">{description}</p>
            )}
        </header>
    );
}
