type Variant = 'buy' | 'rent' | 'swap' | 'success' | 'warning' | 'danger' | 'neutral';
type Size = 'sm' | 'md';

interface BadgeProps {
    variant?: Variant;
    size?: Size;
    children: React.ReactNode;
    className?: string;
}

const variantClasses: Record<Variant, string> = {
    buy: 'bg-orange-100 text-orange-700',
    rent: 'bg-blue-100 text-blue-700',
    swap: 'bg-purple-100 text-purple-700',
    success: 'bg-green-100 text-green-700',
    warning: 'bg-yellow-100 text-yellow-700',
    danger: 'bg-red-100 text-red-700',
    neutral: 'bg-gray-100 text-gray-600',
};

export function Badge({ variant = 'neutral', size = 'md', children, className = '' }: BadgeProps) {
    return (
        <span
            className={[
                'inline-flex items-center rounded-full font-medium',
                size === 'sm' ? 'px-1.5 py-0.5 text-[10px]' : 'px-2 py-0.5 text-xs',
                variantClasses[variant],
                className,
            ].join(' ')}
        >
            {children}
        </span>
    );
}
