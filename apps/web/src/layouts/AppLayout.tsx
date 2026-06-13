import { Outlet, Link, useNavigate } from 'react-router-dom';
import { Bell, Search, User, LogOut, Settings, ShoppingBag, Leaf } from 'lucide-react';
import { useAuth } from '../hooks/useAuth';
import { useSocket } from '../hooks/useSocket';
import { useNotificationStore } from '../store/notificationSlice';
import { Avatar } from '../components/Avatar';
import { useState, useRef, useEffect } from 'react';
import { api } from '../lib/api';

export function AppLayout() {
    const { user, isAuthenticated, isAdmin, clearAuth } = useAuth();
    const unreadCount = useNotificationStore((s) => s.unreadCount);
    const navigate = useNavigate();
    const [menuOpen, setMenuOpen] = useState(false);
    const menuRef = useRef<HTMLDivElement>(null);

    // Connect socket when authenticated
    useSocket();

    useEffect(() => {
        function handler(e: MouseEvent) {
            if (menuRef.current && !menuRef.current.contains(e.target as Node)) {
                setMenuOpen(false);
            }
        }
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, []);

    const handleLogout = async () => {
        try {
            await api.post('/auth/logout');
        } catch {}
        clearAuth();
        navigate('/login');
    };

    return (
        <div className="min-h-screen flex flex-col">
            {/* Header */}
            <header className="bg-white border-b border-gray-200 sticky top-0 z-40">
                <div className="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between gap-4">
                    {/* Logo */}
                    <Link to="/" className="flex items-center gap-2 shrink-0">
                        <Leaf className="w-6 h-6 text-[#1a6b3c]" />
                        <span className="font-heading font-bold text-xl text-[#1a6b3c]">PUNAP</span>
                    </Link>

                    {/* Search bar */}
                    <Link
                        to="/search"
                        className="flex-1 max-w-xl hidden md:flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-400 hover:border-[#1a6b3c] transition-colors"
                    >
                        <Search className="w-4 h-4 shrink-0" />
                        <span>Search products…</span>
                    </Link>

                    {/* Nav right */}
                    <nav className="flex items-center gap-2">
                        <Link to="/search" className="md:hidden p-2 hover:bg-gray-100 rounded-lg">
                            <Search className="w-5 h-5" />
                        </Link>

                        {isAuthenticated ? (
                            <>
                                <Link to="/notifications" className="relative p-2 hover:bg-gray-100 rounded-lg">
                                    <Bell className="w-5 h-5" />
                                    {unreadCount > 0 && (
                                        <span className="absolute top-1 right-1 w-4 h-4 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                                            {unreadCount > 9 ? '9+' : unreadCount}
                                        </span>
                                    )}
                                </Link>

                                <div className="relative" ref={menuRef}>
                                    <button
                                        onClick={() => setMenuOpen((v) => !v)}
                                        className="flex items-center gap-2 p-1 rounded-full hover:bg-gray-100 transition-colors"
                                    >
                                        <Avatar src={user?.avatarUrl} name={user?.name} size="sm" />
                                    </button>

                                    {menuOpen && (
                                        <div className="absolute right-0 mt-2 w-52 bg-white rounded-xl border border-gray-200 shadow-lg py-1 z-50">
                                            <div className="px-4 py-2 border-b border-gray-100">
                                                <p className="text-sm font-medium text-gray-900 truncate">{user?.name}</p>
                                                <p className="text-xs text-gray-500 truncate">{user?.ecoLevel ?? 'No level'}</p>
                                            </div>
                                            <Link to="/orders" className="flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-50" onClick={() => setMenuOpen(false)}>
                                                <ShoppingBag className="w-4 h-4 text-gray-400" />
                                                My Orders
                                            </Link>
                                            <Link to="/wallet" className="flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-50" onClick={() => setMenuOpen(false)}>
                                                Wallet
                                            </Link>
                                            <Link to="/settings/profile" className="flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-50" onClick={() => setMenuOpen(false)}>
                                                <Settings className="w-4 h-4 text-gray-400" />
                                                Settings
                                            </Link>
                                            {isAdmin && (
                                                <Link to="/admin" className="flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-50 text-[#1a6b3c] font-medium" onClick={() => setMenuOpen(false)}>
                                                    Admin Panel
                                                </Link>
                                            )}
                                            <button onClick={handleLogout} className="flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-50 text-red-600 w-full">
                                                <LogOut className="w-4 h-4" />
                                                Sign out
                                            </button>
                                        </div>
                                    )}
                                </div>
                            </>
                        ) : (
                            <div className="flex items-center gap-2">
                                <Link to="/login" className="text-sm font-medium text-gray-700 hover:text-[#1a6b3c] px-3 py-2">
                                    Sign in
                                </Link>
                                <Link to="/register" className="text-sm font-medium bg-[#1a6b3c] text-white px-4 py-2 rounded-lg hover:bg-[#124d2b] transition-colors">
                                    Get started
                                </Link>
                            </div>
                        )}
                    </nav>
                </div>
            </header>

            {/* Main content */}
            <main className="flex-1">
                <Outlet />
            </main>

            {/* Footer */}
            <footer className="bg-white border-t border-gray-200 mt-auto">
                <div className="max-w-7xl mx-auto px-4 py-6 text-center text-sm text-gray-400">
                    &copy; {new Date().getFullYear()} PUNAP — Sustainable Marketplace
                </div>
            </footer>
        </div>
    );
}
