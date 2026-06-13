import { forwardRef, SelectHTMLAttributes } from 'react';

interface SelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
    label?: string;
    error?: string;
}

export const Select = forwardRef<HTMLSelectElement, SelectProps>(
    ({ label, error, children, className = '', ...props }, ref) => {
        return (
            <div className="flex flex-col gap-1">
                {label && <label className="text-sm font-medium text-gray-700">{label}</label>}
                <select
                    ref={ref}
                    className={[
                        'rounded-lg border px-3 py-2 text-sm bg-white transition-colors',
                        'focus:outline-none focus:ring-2 focus:ring-[#1a6b3c] focus:border-transparent',
                        error ? 'border-red-500' : 'border-gray-300',
                        className,
                    ].join(' ')}
                    {...props}
                >
                    {children}
                </select>
                {error && <span className="text-xs text-red-600">{error}</span>}
            </div>
        );
    },
);
Select.displayName = 'Select';
