import { forwardRef, InputHTMLAttributes } from 'react';

interface CheckboxProps extends InputHTMLAttributes<HTMLInputElement> {
    label?: string;
}

export const Checkbox = forwardRef<HTMLInputElement, CheckboxProps>(
    ({ label, className = '', ...props }, ref) => {
        return (
            <label className="flex items-center gap-2 cursor-pointer select-none">
                <input
                    ref={ref}
                    type="checkbox"
                    className={[
                        'w-4 h-4 rounded border-gray-300 text-[#1a6b3c]',
                        'focus:ring-2 focus:ring-[#1a6b3c] focus:ring-offset-0',
                        className,
                    ].join(' ')}
                    {...props}
                />
                {label && <span className="text-sm text-gray-700">{label}</span>}
            </label>
        );
    },
);
Checkbox.displayName = 'Checkbox';
