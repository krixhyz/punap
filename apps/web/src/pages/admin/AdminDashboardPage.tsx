import { Users, Package, AlertTriangle, Wallet, TrendingUp, Activity } from 'lucide-react';
import { useAdminStats } from '../../api/admin';
import { Skeleton } from '../../components/Skeleton';

interface StatCardProps {
    label: string;
    value: number | string;
    icon: React.ElementType;
    color: string;
    loading?: boolean;
}

function StatCard({ label, value, icon: Icon, color, loading }: StatCardProps) {
    return (
        <div className="bg-white border border-gray-200 rounded-xl p-5">
            <div className="flex items-center justify-between mb-3">
                <p className="text-sm text-gray-500">{label}</p>
                <div className={`w-8 h-8 rounded-lg flex items-center justify-center ${color}`}>
                    <Icon className="w-4 h-4 text-white" />
                </div>
            </div>
            {loading ? (
                <Skeleton className="h-8 w-20" />
            ) : (
                <p className="text-2xl font-bold text-gray-900">{value}</p>
            )}
        </div>
    );
}

export default function AdminDashboardPage() {
    const { data: stats, isLoading } = useAdminStats();

    return (
        <div className="p-8">
            <h1 className="text-2xl font-heading font-bold text-gray-900 mb-6">Dashboard</h1>

            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">
                <StatCard
                    label="Total users"
                    value={stats?.totalUsers?.toLocaleString() ?? 0}
                    icon={Users}
                    color="bg-blue-500"
                    loading={isLoading}
                />
                <StatCard
                    label="Total products"
                    value={stats?.totalProducts?.toLocaleString() ?? 0}
                    icon={Package}
                    color="bg-[#1a6b3c]"
                    loading={isLoading}
                />
                <StatCard
                    label="Active rentals"
                    value={stats?.activeRentals?.toLocaleString() ?? 0}
                    icon={Activity}
                    color="bg-blue-600"
                    loading={isLoading}
                />
                <StatCard
                    label="Open disputes"
                    value={stats?.openDisputes?.toLocaleString() ?? 0}
                    icon={AlertTriangle}
                    color="bg-amber-500"
                    loading={isLoading}
                />
                <StatCard
                    label="Pending payouts"
                    value={stats?.pendingPayouts?.toLocaleString() ?? 0}
                    icon={Wallet}
                    color="bg-purple-500"
                    loading={isLoading}
                />
                <StatCard
                    label="Platform balance"
                    value={`Rs ${(stats?.platformBalance ?? 0).toLocaleString()}`}
                    icon={Wallet}
                    color="bg-[#1a6b3c]"
                    loading={isLoading}
                />
                <StatCard
                    label="Monthly revenue"
                    value={`Rs ${(stats?.monthlyRevenue ?? 0).toLocaleString()}`}
                    icon={TrendingUp}
                    color="bg-emerald-500"
                    loading={isLoading}
                />
            </div>
        </div>
    );
}
