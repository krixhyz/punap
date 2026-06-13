import { useState } from 'react';
import { AlertTriangle } from 'lucide-react';
import toast from 'react-hot-toast';
import { useAdminDisputes, useResolveDispute } from '../../api/admin';
import { Badge } from '../../components/Badge';
import { Button } from '../../components/Button';
import { Modal } from '../../components/Modal';
import { Input } from '../../components/Input';
import { Textarea } from '../../components/Textarea';
import { Pagination } from '../../components/Pagination';
import { Skeleton } from '../../components/Skeleton';
import { usePagination } from '../../hooks/usePagination';
import type { Dispute } from '../../api/disputes';

const STATUS_VARIANT: Record<string, 'warning' | 'success' | 'danger' | 'neutral'> = {
    OPEN: 'warning',
    IN_REVIEW: 'warning',
    RESOLVED: 'success',
    DISMISSED: 'neutral',
};

function ResolveModal({ dispute, onClose }: { dispute: Dispute; onClose: () => void }) {
    const resolve = useResolveDispute();
    const [resolution, setResolution] = useState('');
    const [favoredUserId, setFavoredUserId] = useState('');
    const [claimAmount, setClaimAmount] = useState('');

    async function handleResolve() {
        if (!resolution.trim()) {
            toast.error('Enter resolution text');
            return;
        }
        try {
            await resolve.mutateAsync({
                id: dispute.id,
                resolution,
                favoredUserId: favoredUserId || undefined,
                rentalClaimAmount: claimAmount ? parseFloat(claimAmount) : undefined,
            });
            toast.success('Dispute resolved');
            onClose();
        } catch { toast.error('Failed to resolve'); }
    }

    return (
        <Modal open title="Resolve Dispute" onClose={onClose}>
            <div className="space-y-4">
                <div className="text-sm text-gray-700">
                    <strong>{dispute.subject}</strong>
                    <p className="text-gray-500 mt-1">{dispute.description}</p>
                </div>

                <div>
                    <p className="text-xs text-gray-500 mb-1">Favor (optional)</p>
                    <div className="flex gap-2">
                        {[dispute.claimant, dispute.respondent].map((party) => (
                            <button
                                key={party.id}
                                onClick={() => setFavoredUserId((f) => f === party.id ? '' : party.id)}
                                className={[
                                    'flex-1 py-2 px-3 text-sm rounded-lg border transition-colors',
                                    favoredUserId === party.id
                                        ? 'border-[#1a6b3c] bg-[#e8f5ee] text-[#1a6b3c]'
                                        : 'border-gray-300 text-gray-600',
                                ].join(' ')}
                            >
                                {party.name}
                            </button>
                        ))}
                    </div>
                </div>

                {dispute.transactionType === 'RENTAL' && (
                    <Input
                        label="Rental claim amount (Rs, optional)"
                        type="number"
                        value={claimAmount}
                        onChange={(e) => setClaimAmount(e.target.value)}
                        placeholder="0"
                    />
                )}

                <Textarea
                    label="Resolution *"
                    value={resolution}
                    onChange={(e) => setResolution(e.target.value)}
                    placeholder="Describe the resolution decision..."
                    rows={3}
                />

                <div className="flex gap-3 justify-end">
                    <Button variant="ghost" onClick={onClose}>Cancel</Button>
                    <Button loading={resolve.isPending} onClick={handleResolve}>Resolve dispute</Button>
                </div>
            </div>
        </Modal>
    );
}

export default function AdminDisputesPage() {
    const { page, goToPage } = usePagination();
    const { data, isLoading } = useAdminDisputes(page);
    const [resolving, setResolving] = useState<Dispute | null>(null);

    return (
        <div className="p-8">
            <h1 className="text-2xl font-heading font-bold text-gray-900 mb-6">Disputes</h1>

            {resolving && <ResolveModal dispute={resolving} onClose={() => setResolving(null)} />}

            {isLoading ? (
                <div className="space-y-2">{Array.from({ length: 5 }).map((_, i) => <Skeleton key={i} className="h-16 rounded-lg" />)}</div>
            ) : !data || data.data.length === 0 ? (
                <div className="text-center py-16 text-gray-400">
                    <AlertTriangle className="w-10 h-10 mx-auto mb-3 opacity-30" />
                    <p className="text-sm">No disputes to review.</p>
                </div>
            ) : (
                <>
                    <div className="space-y-3">
                        {data.data.map((dispute) => (
                            <div key={dispute.id} className="bg-white border border-gray-200 rounded-xl p-4">
                                <div className="flex items-start justify-between gap-4">
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center gap-2 mb-1">
                                            <Badge variant={STATUS_VARIANT[dispute.status] ?? 'neutral'} size="sm">
                                                {dispute.status.replace('_', ' ')}
                                            </Badge>
                                            <Badge variant="neutral" size="sm">{dispute.transactionType}</Badge>
                                        </div>
                                        <p className="font-medium text-gray-900 truncate">{dispute.subject}</p>
                                        <p className="text-xs text-gray-500 mt-0.5">
                                            {dispute.claimant.name} vs {dispute.respondent.name}
                                        </p>
                                        <p className="text-xs text-gray-400 mt-0.5">
                                            {new Date(dispute.createdAt).toLocaleDateString()}
                                        </p>
                                    </div>
                                    {['OPEN', 'IN_REVIEW'].includes(dispute.status) && (
                                        <Button size="sm" onClick={() => setResolving(dispute)}>
                                            Resolve
                                        </Button>
                                    )}
                                    {dispute.status === 'RESOLVED' && dispute.resolution && (
                                        <p className="text-xs text-gray-500 max-w-48 text-right italic">
                                            "{dispute.resolution.slice(0, 80)}{dispute.resolution.length > 80 ? '…' : ''}"
                                        </p>
                                    )}
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
