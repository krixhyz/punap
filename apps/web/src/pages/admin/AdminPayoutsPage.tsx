import { useState } from 'react';
import { Wallet } from 'lucide-react';
import toast from 'react-hot-toast';
import { useAdminPayouts, useApproveAdminPayout, useRejectAdminPayout, useMarkPayoutPaid } from '../../api/admin';
import { Badge } from '../../components/Badge';
import { Button } from '../../components/Button';
import { Pagination } from '../../components/Pagination';
import { Skeleton } from '../../components/Skeleton';
import { usePagination } from '../../hooks/usePagination';

const STATUS_VARIANT: Record<string, 'success' | 'warning' | 'danger' | 'neutral'> = {
    PENDING: 'warning',
    APPROVED: 'success',
    PAID: 'success',
    REJECTED: 'danger',
};

export default function AdminPayoutsPage() {
    const { page, goToPage } = usePagination();
    const { data, isLoading } = useAdminPayouts(page);
    const approve = useApproveAdminPayout();
    const reject = useRejectAdminPayout();
    const markPaid = useMarkPayoutPaid();

    async function handleApprove(id: string) {
        try {
            await approve.mutateAsync(id);
            toast.success('Payout approved');
        } catch { toast.error('Failed'); }
    }

    async function handleReject(id: string) {
        const reason = prompt('Reason for rejection:');
        if (!reason) return;
        try {
            await reject.mutateAsync({ id, reason });
            toast.success('Payout rejected');
        } catch { toast.error('Failed'); }
    }

    async function handleMarkPaid(id: string) {
        const ref = prompt('Enter payout reference (bank transfer ID, etc.):');
        if (!ref) return;
        try {
            await markPaid.mutateAsync({ id, payoutReference: ref });
            toast.success('Marked as paid');
        } catch { toast.error('Failed'); }
    }

    return (
        <div className="p-8">
            <h1 className="text-2xl font-heading font-bold text-gray-900 mb-6">Payouts</h1>

            {isLoading ? (
                <div className="space-y-2">{Array.from({ length: 5 }).map((_, i) => <Skeleton key={i} className="h-16 rounded-lg" />)}</div>
            ) : !data || data.data.length === 0 ? (
                <div className="text-center py-16 text-gray-400">
                    <Wallet className="w-10 h-10 mx-auto mb-3 opacity-30" />
                    <p className="text-sm">No payout requests.</p>
                </div>
            ) : (
                <>
                    <div className="space-y-3">
                        {data.data.map((payout) => (
                            <div key={payout.id} className="bg-white border border-gray-200 rounded-xl p-4">
                                <div className="flex items-center justify-between gap-4">
                                    <div>
                                        <p className="font-medium text-gray-900">
                                            Rs {payout.amount.toLocaleString()} — {payout.user.name}
                                        </p>
                                        <p className="text-xs text-gray-500">{payout.user.email}</p>
                                        {payout.note && <p className="text-xs text-gray-400 mt-0.5 italic">{payout.note}</p>}
                                        {payout.payoutReference && (
                                            <p className="text-xs text-[#1a6b3c] mt-0.5">Ref: {payout.payoutReference}</p>
                                        )}
                                        <p className="text-xs text-gray-400 mt-0.5">
                                            {new Date(payout.createdAt).toLocaleDateString()}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2 shrink-0">
                                        <Badge variant={STATUS_VARIANT[payout.status] ?? 'neutral'} size="sm">
                                            {payout.status}
                                        </Badge>
                                        {payout.status === 'PENDING' && (
                                            <>
                                                <Button size="sm" onClick={() => handleApprove(payout.id)} loading={approve.isPending}>
                                                    Approve
                                                </Button>
                                                <Button size="sm" variant="danger" onClick={() => handleReject(payout.id)} loading={reject.isPending}>
                                                    Reject
                                                </Button>
                                            </>
                                        )}
                                        {payout.status === 'APPROVED' && (
                                            <Button size="sm" onClick={() => handleMarkPaid(payout.id)} loading={markPaid.isPending}>
                                                Mark paid
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                    {(data.total ?? 0) > 10 && (
                        <div className="mt-4 flex justify-center">
                            <Pagination page={page} totalPages={Math.ceil(data.total / 10)} onPageChange={goToPage} />
                        </div>
                    )}
                </>
            )}
        </div>
    );
}
