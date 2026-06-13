import { ButtonHTMLAttributes, forwardRef } from 'react';
import { Spinner } from './Spinner';

type Variant = 'primary' | 'secondary' | 'ghost' | 'danger';
type Size = 'sm' | 'md' | 'lg';

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: Variant;
    size?: Size;
    loading?: boolean;
}

const variantClasses: Record<Variant, string> = {
    primary: 'bg-[#1a6b3c] text-white hover:bg-[#124d2b] focus:ring-[#1a6b3c]',
    secondary: 'bg-[#e8f5ee] text-[#1a6b3c] hover:bg-[#d0ebd9] focus:ring-[#1a6b3c]',
    ghost: 'bg-transparent text-gray-700 hover:bg-gray-100 focus:ring-gray-300',
    danger: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
};

const sizeClasses: Record<Size, string> = {
    sm: 'px-3 py-1.5 text-sm',
    md: 'px-4 py-2 text-sm',
    lg: 'px-6 py-3 text-base',
};

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(
    ({ variant = 'primary', size = 'md', loading, disabled, children, className = '', ...props }, ref) => {
        return (
            <button
                ref={ref}
                disabled={disabled || loading}
                className={[
                    'inline-flex items-center justify-center gap-2 rounded-lg font-medium transition-colors',
                    'focus:outline-none focus:ring-2 focus:ring-offset-2',
                    'disabled:opacity-50 disabled:cursor-not-allowed',
                    variantClasses[variant],
                    sizeClasses[size],
                    className,
                ].join(' ')}
                {...props}
            >
                {loading && <Spinner size="sm" />}
                {children}
            </button>
        );
    },
);
Button.displayName = 'Button';
