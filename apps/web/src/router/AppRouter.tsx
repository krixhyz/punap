import { createBrowserRouter, RouterProvider } from 'react-router-dom';
import { AppLayout } from '../layouts/AppLayout';
import { AuthLayout } from '../layouts/AuthLayout';
import { AdminLayout } from '../layouts/AdminLayout';
import { ProtectedRoute } from './ProtectedRoute';
import { AdminRoute } from './AdminRoute';

import { lazy, Suspense } from 'react';
import { Spinner } from '../components/Spinner';

// Auth pages
const LoginPage = lazy(() => import('../pages/auth/LoginPage'));
const RegisterPage = lazy(() => import('../pages/auth/RegisterPage'));
const ForgotPasswordPage = lazy(() => import('../pages/auth/ForgotPasswordPage'));
const ResetPasswordPage = lazy(() => import('../pages/auth/ResetPasswordPage'));
const VerifyEmailPage = lazy(() => import('../pages/auth/VerifyEmailPage'));
const ResendVerificationPage = lazy(() => import('../pages/auth/ResendVerificationPage'));

// Discovery
const HomePage = lazy(() => import('../pages/home/HomePage'));
const SearchPage = lazy(() => import('../pages/search/SearchPage'));
const ProductDetailPage = lazy(() => import('../pages/product/ProductDetailPage'));

// Payment callbacks
const PaymentSuccessPage = lazy(() => import('../pages/payment/PaymentSuccessPage'));
const PaymentFailurePage = lazy(() => import('../pages/payment/PaymentFailurePage'));

// Protected user pages
const NotificationsPage = lazy(() => import('../pages/notifications/NotificationsPage'));
const OrdersPage = lazy(() => import('../pages/orders/OrdersPage'));
const OrderDetailPage = lazy(() => import('../pages/orders/OrderDetailPage'));
const RentalsPage = lazy(() => import('../pages/rentals/RentalsPage'));
const RentalDetailPage = lazy(() => import('../pages/rentals/RentalDetailPage'));
const SwapsPage = lazy(() => import('../pages/swaps/SwapsPage'));
const SwapDetailPage = lazy(() => import('../pages/swaps/SwapDetailPage'));
const WalletPage = lazy(() => import('../pages/wallet/WalletPage'));
const ProfilePage = lazy(() => import('../pages/profile/ProfilePage'));
const EditProfilePage = lazy(() => import('../pages/profile/EditProfilePage'));
const MyListingsPage = lazy(() => import('../pages/profile/MyListingsPage'));
const DisputesPage = lazy(() => import('../pages/disputes/DisputesPage'));
const DisputeDetailPage = lazy(() => import('../pages/disputes/DisputeDetailPage'));

// Admin pages
const AdminDashboardPage = lazy(() => import('../pages/admin/AdminDashboardPage'));
const AdminUsersPage = lazy(() => import('../pages/admin/AdminUsersPage'));
const AdminProductsPage = lazy(() => import('../pages/admin/AdminProductsPage'));
const AdminDisputesPage = lazy(() => import('../pages/admin/AdminDisputesPage'));
const AdminPayoutsPage = lazy(() => import('../pages/admin/AdminPayoutsPage'));
const AdminSettingsPage = lazy(() => import('../pages/admin/AdminSettingsPage'));

function SuspenseWrapper({ children }: { children: React.ReactNode }) {
    return (
        <Suspense
            fallback={
                <div className="flex items-center justify-center h-64">
                    <Spinner size="lg" className="text-[#1a6b3c]" />
                </div>
            }
        >
            {children}
        </Suspense>
    );
}

const router = createBrowserRouter([
    // Auth routes (centered card layout)
    {
        element: <AuthLayout />,
        children: [
            { path: '/login', element: <SuspenseWrapper><LoginPage /></SuspenseWrapper> },
            { path: '/register', element: <SuspenseWrapper><RegisterPage /></SuspenseWrapper> },
            { path: '/forgot-password', element: <SuspenseWrapper><ForgotPasswordPage /></SuspenseWrapper> },
            { path: '/reset-password', element: <SuspenseWrapper><ResetPasswordPage /></SuspenseWrapper> },
            { path: '/verify-email', element: <SuspenseWrapper><VerifyEmailPage /></SuspenseWrapper> },
            { path: '/resend-verification', element: <SuspenseWrapper><ResendVerificationPage /></SuspenseWrapper> },
        ],
    },

    // Payment callback pages (no header/footer)
    {
        path: '/payment/success',
        element: <SuspenseWrapper><PaymentSuccessPage /></SuspenseWrapper>,
    },
    {
        path: '/payment/failure',
        element: <SuspenseWrapper><PaymentFailurePage /></SuspenseWrapper>,
    },

    // Main app (header + footer)
    {
        element: <AppLayout />,
        children: [
            // Public
            { path: '/', element: <SuspenseWrapper><HomePage /></SuspenseWrapper> },
            { path: '/search', element: <SuspenseWrapper><SearchPage /></SuspenseWrapper> },
            { path: '/products/:id', element: <SuspenseWrapper><ProductDetailPage /></SuspenseWrapper> },

            // Protected
            {
                element: <ProtectedRoute />,
                children: [
                    { path: '/notifications', element: <SuspenseWrapper><NotificationsPage /></SuspenseWrapper> },
                    { path: '/orders', element: <SuspenseWrapper><OrdersPage /></SuspenseWrapper> },
                    { path: '/orders/:id', element: <SuspenseWrapper><OrderDetailPage /></SuspenseWrapper> },
                    { path: '/rentals', element: <SuspenseWrapper><RentalsPage /></SuspenseWrapper> },
                    { path: '/rentals/:id', element: <SuspenseWrapper><RentalDetailPage /></SuspenseWrapper> },
                    { path: '/swaps', element: <SuspenseWrapper><SwapsPage /></SuspenseWrapper> },
                    { path: '/swaps/:id', element: <SuspenseWrapper><SwapDetailPage /></SuspenseWrapper> },
                    { path: '/wallet', element: <SuspenseWrapper><WalletPage /></SuspenseWrapper> },
                    { path: '/disputes', element: <SuspenseWrapper><DisputesPage /></SuspenseWrapper> },
                    { path: '/disputes/:id', element: <SuspenseWrapper><DisputeDetailPage /></SuspenseWrapper> },
                    { path: '/profile/:userId', element: <SuspenseWrapper><ProfilePage /></SuspenseWrapper> },
                    { path: '/settings/profile', element: <SuspenseWrapper><EditProfilePage /></SuspenseWrapper> },
                    { path: '/settings/listings', element: <SuspenseWrapper><MyListingsPage /></SuspenseWrapper> },
                ],
            },
        ],
    },

    // Admin routes (sidebar layout)
    {
        element: <AdminRoute />,
        children: [
            {
                element: <AdminLayout />,
                children: [
                    { path: '/admin', element: <SuspenseWrapper><AdminDashboardPage /></SuspenseWrapper> },
                    { path: '/admin/users', element: <SuspenseWrapper><AdminUsersPage /></SuspenseWrapper> },
                    { path: '/admin/products', element: <SuspenseWrapper><AdminProductsPage /></SuspenseWrapper> },
                    { path: '/admin/disputes', element: <SuspenseWrapper><AdminDisputesPage /></SuspenseWrapper> },
                    { path: '/admin/payouts', element: <SuspenseWrapper><AdminPayoutsPage /></SuspenseWrapper> },
                    { path: '/admin/settings', element: <SuspenseWrapper><AdminSettingsPage /></SuspenseWrapper> },
                ],
            },
        ],
    },
]);

export function AppRouter() {
    return <RouterProvider router={router} />;
}
