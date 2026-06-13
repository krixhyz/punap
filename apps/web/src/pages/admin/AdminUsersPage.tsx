import { useState } from 'react';
import { Search } from 'lucide-react';
import toast from 'react-hot-toast';
import { useAdminUsers, useSuspendUser, useBanUser, useActivateUser } from '../../api/admin';
import { Badge } from '../../components/Badge';
import { Button } from '../../components/Button';
import { Input } from '../../components/Input';
import { Pagination } from '../../components/Pagination';
import { Skeleton } from '../../components/Skeleton';
import { usePagination } from '../../hooks/usePagination';

const STATUS_VARIANT: Record<string, 'success' | 'warning' | 'danger' | 'neutral'> = {
    ACTIVE: 'success',
    SUSPENDED: 'warning',
    BANNED: 'danger',
    PENDING_VERIFICATION: 'neutral',
};

export default function AdminUsersPage() {
    const { page, goToPage } = usePagination();
    const [search, setSearch] = useState('');
    const [debouncedSearch, setDebouncedSearch] = useState('');
    const { data, isLoading } = useAdminUsers(page, debouncedSearch);
    const suspend = useSuspendUser();
    const ban = useBanUser();
    const activate = useActivateUser();

    function handleSearch(val: string) {
        setSearch(val);
        clearTimeout((handleSearch as unknown as { timer?: ReturnType<typeof setTimeout> }).timer);
        (handleSearch as unknown as { timer?: ReturnType<typeof setTimeout> }).timer = setTimeout(() => setDebouncedSearch(val), 400);
    }

    async function handleAction(action: 'suspend' | 'ban' | 'activate', userId: string) {
        try {
            if (action === 'suspend') {
                const reason = prompt('Reason for suspension:');
                if (!reason) return;
                await suspend.mutateAsync({ id: userId, reason });
            } else if (action === 'ban') {
                const reason = prompt('Reason for ban:');
                if (!reason) return;
                await ban.mutateAsync({ id: userId, reason });
            } else {
                await activate.mutateAsync(userId);
            }
            toast.success('User status updated');
        } catch {
            toast.error('Action failed');
        }
    }

    return (
        <div className="p-8">
            <h1 className="text-2xl font-heading font-bold text-gray-900 mb-6">Users</h1>

            <div className="flex gap-3 mb-6">
                <div className="relative flex-1 max-w-sm">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input
                        type="text"
                        placeholder="Search by name or email..."
                        value={search}
                        onChange={(e) => handleSearch(e.target.value)}
                        className="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#1a6b3c]"
                    />
                </div>
            </div>

            {isLoading ? (
                <div className="space-y-2">
                    {Array.from({ length: 8 }).map((_, i) => <Skeleton key={i} className="h-14 rounded-lg" />)}
                </div>
            ) : (
                <>
                    <div className="bg-white border border-gray-200 rounded-xl overflow-hidden">
                        <table className="w-full text-sm">
                            <thead className="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th className="text-left px-4 py-3 text-xs text-gray-500 uppercase">Name</th>
                                    <th className="text-left px-4 py-3 text-xs text-gray-500 uppercase">Email</th>
                                    <th className="text-left px-4 py-3 text-xs text-gray-500 uppercase">Role</th>
                                    <th className="text-left px-4 py-3 text-xs text-gray-500 uppercase">Status</th>
                                    <th className="text-left px-4 py-3 text-xs text-gray-500 uppercase">Eco</th>
                                    <th className="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {data?.data.map((user) => (
                                    <tr key={user.id} className="hover:bg-gray-50">
                                        <td className="px-4 py-3 font-medium text-gray-900">{user.name}</td>
                                        <td className="px-4 py-3 text-gray-500">{user.email}</td>
                                        <td className="px-4 py-3">
                                            <Badge variant="neutral" size="sm">{user.role}</Badge>
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge variant={STATUS_VARIANT[user.accountStatus] ?? 'neutral'} size="sm">
                                                {user.accountStatus}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-xs text-[#1a6b3c]">{user.ecoLevel}</td>
                                        <td className="px-4 py-3">
                                            <div className="flex gap-1 justify-end">
                                                {user.accountStatus === 'ACTIVE' && (
                                                    <>
                                                        <button
                                                            onClick={() => handleAction('suspend', user.id)}
                                                            className="text-xs text-amber-600 hover:underline px-2 py-1"
                                                        >
                                                            Suspend
                                                        </button>
                                                        <button
                                                            onClick={() => handleAction('ban', user.id)}
                                                            className="text-xs text-red-600 hover:underline px-2 py-1"
                                                        >
                                                            Ban
                                                        </button>
                                                    </>
                                                )}
                                                {['SUSPENDED', 'BANNED'].includes(user.accountStatus) && (
                                                    <button
                                                        onClick={() => handleAction('activate', user.id)}
                                                        className="text-xs text-[#1a6b3c] hover:underline px-2 py-1"
                                                    >
                                                        Activate
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    {(data?.total ?? 0) > 10 && (
                        <div className="mt-4 flex justify-center">
                            <Pagination
                                page={page}
                                totalPages={Math.ceil((data?.total ?? 0) / 10)}
                                onPageChange={goToPage}
                            />
                        </div>
                    )}
                </>
            )}
        </div>
    );
}
