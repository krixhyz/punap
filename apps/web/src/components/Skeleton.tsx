interface SkeletonProps {
    className?: string;
    variant?: 'block' | 'text' | 'circle';
}

export function Skeleton({ className = '', variant = 'block' }: SkeletonProps) {
    const base = 'animate-pulse bg-gray-200 rounded';
    const variantClass =
        variant === 'text' ? 'h-4 rounded-full' :
        variant === 'circle' ? 'rounded-full' :
        'rounded-lg';

    return <div className={[base, variantClass, className].join(' ')} />;
}
