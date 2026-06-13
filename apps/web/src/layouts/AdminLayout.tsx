import { Outlet, Link, useLocation, Navigate } from 'react-router-dom';
import {
    LayoutDashboard,
    Users,
    Package,
    AlertTriangle,
    Wallet,
    Settings,
    Leaf,
} from 'lucide-react';
import { useAuth } from '../hooks/useAuth';

const navItems = [
    { to: '/admin', label: 'Dashboard', icon: LayoutDashboard, exact: true },
    { to: '/admin/users', label: 'Users', icon: Users },
    { to: '/admin/products', label: 'Products', icon: Package },
    { to: '/admin/disputes', label: 'Disputes', icon: AlertTriangle },
    { to: '/admin/payouts', label: 'Payouts', icon: Wallet },
    { to: '/admin/settings', label: 'Settings', icon: Settings },
];

export function AdminLayout() {
    const { isAdmin } = useAuth();
    const location = useLocation();

    if (!isAdmin) return <Navigate to="/" replace />;

    return (
        <div className="flex min-h-screen">
            {/* Sidebar */}
            <aside className="w-56 bg-[#1a6b3c] text-white flex flex-col shrink-0">
                <Link to="/" className="flex items-center gap-2 px-5 py-5 border-b border-white/10">
                    <Leaf className="w-5 h-5" />
                    <span className="font-heading font-bold text-lg">PUNAP Admin</span>
                </Link>

                <nav className="flex-1 py-4">
                    {navItems.map(({ to, label, icon: Icon, exact }) => {
                        const active = exact
                            ? location.pathname === to
                            : location.pathname.startsWith(to) && to !== '/admin';
                        return (
                            <Link
                                key={to}
                                to={to}
                                className={[
                                    'flex items-center gap-3 px-5 py-3 text-sm transition-colors',
                                    active ? 'bg-white/20 font-medium' : 'hover:bg-white/10',
                                ].join(' ')}
                            >
                                <Icon className="w-4 h-4 shrink-0" />
                                {label}
                            </Link>
                        );
                    })}
                </nav>
            </aside>

            {/* Content */}
            <main className="flex-1 bg-[#f8f9fa] overflow-auto">
                <Outlet />
            </main>
        </div>
    );
}
