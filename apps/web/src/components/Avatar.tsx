interface AvatarProps {
    src?: string | null;
    name?: string | null;
    size?: 'sm' | 'md' | 'lg' | 'xl';
    className?: string;
}

const sizeClasses = {
    sm: 'w-8 h-8 text-xs',
    md: 'w-10 h-10 text-sm',
    lg: 'w-12 h-12 text-base',
    xl: 'w-16 h-16 text-lg',
};

function getInitials(name?: string | null) {
    if (!name) return '?';
    return name
        .split(' ')
        .slice(0, 2)
        .map((n) => n[0])
        .join('')
        .toUpperCase();
}

export function Avatar({ src, name, size = 'md', className = '' }: AvatarProps) {
    const sizeClass = sizeClasses[size];

    if (src) {
        return (
            <img
                src={src}
                alt={name ?? 'User avatar'}
                className={['rounded-full object-cover', sizeClass, className].join(' ')}
            />
        );
    }

    return (
        <div
            className={[
                'rounded-full bg-[#e8f5ee] text-[#1a6b3c] font-semibold',
                'flex items-center justify-center select-none',
                sizeClass,
                className,
            ].join(' ')}
        >
            {getInitials(name)}
        </div>
    );
}
