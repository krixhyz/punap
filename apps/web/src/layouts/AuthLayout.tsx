import { Outlet, Link } from 'react-router-dom';
import { Leaf } from 'lucide-react';

export function AuthLayout() {
    return (
        <div className="min-h-screen bg-[#f8f9fa] flex flex-col items-center justify-center p-4">
            <Link to="/" className="flex items-center gap-2 mb-8">
                <Leaf className="w-8 h-8 text-[#1a6b3c]" />
                <span className="font-heading font-bold text-2xl text-[#1a6b3c]">PUNAP</span>
            </Link>
            <div className="w-full max-w-md">
                <Outlet />
            </div>
        </div>
    );
}
