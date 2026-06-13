import { HTMLAttributes } from 'react';

interface CardProps extends HTMLAttributes<HTMLDivElement> {
    padding?: boolean;
}

export function Card({ children, padding = true, className = '', ...props }: CardProps) {
    return (
        <div
            className={['bg-white rounded-xl border border-gray-200 shadow-sm', padding ? 'p-4' : '', className].join(' ')}
            {...props}
        >
            {children}
        </div>
    );
}

export function CardHeader({ children, className = '' }: { children: React.ReactNode; className?: string }) {
    return (
        <div className={['border-b border-gray-100 pb-3 mb-4', className].join(' ')}>
            {children}
        </div>
    );
}

export function CardBody({ children, className = '' }: { children: React.ReactNode; className?: string }) {
    return <div className={className}>{children}</div>;
}
