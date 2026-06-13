import { forwardRef, TextareaHTMLAttributes } from 'react';

interface TextareaProps extends TextareaHTMLAttributes<HTMLTextAreaElement> {
    label?: string;
    error?: string;
}

export const Textarea = forwardRef<HTMLTextAreaElement, TextareaProps>(
    ({ label, error, className = '', ...props }, ref) => {
        return (
            <div className="flex flex-col gap-1">
                {label && <label className="text-sm font-medium text-gray-700">{label}</label>}
                <textarea
                    ref={ref}
                    rows={4}
                    className={[
                        'rounded-lg border px-3 py-2 text-sm transition-colors resize-vertical',
                        'focus:outline-none focus:ring-2 focus:ring-[#1a6b3c] focus:border-transparent',
                        error ? 'border-red-500' : 'border-gray-300',
                        className,
                    ].join(' ')}
                    {...props}
                />
                {error && <span className="text-xs text-red-600">{error}</span>}
            </div>
        );
    },
);
Textarea.displayName = 'Textarea';
