import { useState } from 'react';
import { Wallet as WalletIcon, ArrowDownCircle, Clock, CheckCircle, XCircle } from 'lucide-react';
import toast from 'react-hot-toast';
import { useWallet, useWalletLedger, usePayoutRequests, useCreatePayoutRequest } from '../../api/wallet';
import { Button } from '../../components/Button';
import { Input } from '../../components/Input';
import { Textarea } from '../../components/Textarea';
import { Badge } from '../../components/Badge';
import { Skeleton } from '../../components/Skeleton';
import { Pagination } from '../../components/Pagination';
import { usePagination } from '../../hooks/usePagination';

const PAYOUT_STATUS_VARIANT: Record<string, 'success' | 'warning' | 'danger' | 'neutral'> = {
    PENDING: 'warning',
    APPROVED: 'success',
    PAID: 'success',
    REJECTED: 'danger',
};

const ENTRY_TYPE_LABEL: Record<string, string> = {
    ORDER_SALE_CREDIT: 'Sale credit',
    RENTAL_SALE_CREDIT: 'Rental credit',
    SWAP_FUND_RELEASE: 'Swap funds',
    PLATFORM_FEE: 'Platform fee',
    PAYOUT_HOLD: 'Payout held',
    PAYOUT_RELEASE: 'Payout released',
    PAYOUT_PAID: 'Payout paid',
};

function PayoutRequestForm({ available }: { available: number }) {
    const [amount, setAmount] = useState('');
    const [note, setNote] = useState('');
    const createPayout = useCreatePayoutRequest();

    async function handleSubmit() {
        const parsed = parseFloat(amount);
        if (!parsed || parsed <= 0) {
            toast.error('Enter a valid amount');
            return;
        }
        if (parsed > available) {
            toast.error(`Amount exceeds available balance (Rs ${available.toLocaleString()})`);
            return;
        }
        try {
            await createPayout.mutateAsync({ amount: parsed, note: note || undefined });
            toast.success('Payout request submitted');
            setAmount('');
            setNote('');
        } catch (err: unknown) {
            const msg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message;
            toast.error(msg ?? 'Failed to request payout');
        }
    }

    return (
        <div className="bg-white border border-gray-200 rounded-2xl p-5 space-y-4">
            <h3 className="text-base font-semibold text-gray-900">Request Payout</h3>
            <Input
                label="Amount (Rs)"
                type="number"
                min="1"
                max={available}
                step="1"
                value={amount}
                onChange={(e) => setAmount(e.target.value)}
                placeholder="0"
            />
            <Textarea
                label="Note (optional)"
                value={note}
                onChange={(e) => setNote(e.target.value)}
                rows={2}
                placeholder="Bank account details or note for admin..."
            />
            <Button loading={createPayout.isPending} onClick={handleSubmit} className="w-full">
                <ArrowDownCircle className="w-4 h-4" />
                Request payout
            </Button>
        </div>
    );
}

function LedgerTable() {
    const { page, goToPage } = usePagination();
    const { data, isLoading } = useWalletLedger(page);

    if (isLoading) return <Skeleton className="h-48 rounded-xl" />;
    if (!data || data.data.length === 0) {
        return <p className="text-sm text-gray-400 text-center py-8">No ledger entries yet.</p>;
    }

    return (
        <div>
            <div className="bg-white border border-gray-200 rounded-2xl overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th className="text-left px-4 py-3 text-xs text-gray-500 uppercase tracking-wide">Type</th>
                            <th className="text-right px-4 py-3 text-xs text-gray-500 uppercase tracking-wide">Amount</th>
                            <th className="text-right px-4 py-3 text-xs text-gray-500 uppercase tracking-wide">Balance</th>
                            <th className="text-left px-4 py-3 text-xs text-gray-500 uppercase tracking-wide">Date</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {data.data.map((entry) => (
                            <tr key={entry.id} className="hover:bg-gray-50">
                                <td className="px-4 py-3">
                                    <p className="font-medium text-gray-800">
                                        {ENTRY_TYPE_LABEL[entry.entryType] ?? entry.entryType}
                                    </p>
                                    {entry.note && <p className="text-xs text-gray-400 truncate max-w-48">{entry.note}</p>}
                                </td>
                                <td className="px-4 py-3 text-right">
                                    <span className={entry.direction === 'CREDIT' ? 'text-green-600 font-medium' : 'text-red-500'}>
                                        {entry.direction === 'CREDIT' ? '+' : '-'}Rs {entry.amount.toLocaleString()}
                                    </span>
                                </td>
                                <td className="px-4 py-3 text-right text-gray-600">
                                    Rs {entry.balanceAfter.toLocaleString()}
                                </td>
                                <td className="px-4 py-3 text-gray-500 text-xs">
                                    {new Date(entry.createdAt).toLocaleDateString()}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            {data.total > 10 && (
                <div className="mt-4 flex justify-center">
                    <Pagination
                        page={page}
                        totalPages={Math.ceil(data.total / 10)}
                        onPageChange={goToPage}
                    />
                </div>
            )}
        </div>
    );
}

function PayoutHistory() {
    const { page, goToPage } = usePagination();
    const { data, isLoading } = usePayoutRequests(page);

    if (isLoading) return <Skeleton className="h-32 rounded-xl" />;
    if (!data || data.data.length === 0) {
        return <p className="text-sm text-gray-400 text-center py-6">No payout requests yet.</p>;
    }

    return (
        <div className="space-y-3">
            {data.data.map((req) => (
                <div key={req.id} className="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-4">
                    <div className="shrink-0">
                        {req.status === 'PAID' ? (
                            <CheckCircle className="w-5 h-5 text-green-500" />
                        ) : req.status === 'REJECTED' ? (
                            <XCircle className="w-5 h-5 text-red-500" />
                        ) : (
                            <Clock className="w-5 h-5 text-amber-500" />
                        )}
                    </div>
                    <div className="flex-1">
                        <p className="text-sm font-medium text-gray-900">Rs {req.amount.toLocaleString()}</p>
                        {req.note && <p className="text-xs text-gray-500 truncate">{req.note}</p>}
                        {req.adminNote && <p className="text-xs text-gray-400 italic">Admin: {req.adminNote}</p>}
                        {req.payoutReference && <p className="text-xs text-[#1a6b3c]">Ref: {req.payoutReference}</p>}
                    </div>
                    <div className="flex flex-col items-end gap-1 shrink-0">
                        <Badge variant={PAYOUT_STATUS_VARIANT[req.status] ?? 'neutral'} size="sm">
                            {req.status}
                        </Badge>
                        <span className="text-xs text-gray-400">
                            {new Date(req.createdAt).toLocaleDateString()}
                        </span>
                    </div>
                </div>
            ))}
            {data.total > 10 && (
                <div className="mt-4 flex justify-center">
                    <Pagination
                        page={page}
                        totalPages={Math.ceil(data.total / 10)}
                        onPageChange={goToPage}
                    />
                </div>
            )}
        </div>
    );
}

export default function WalletPage() {
    const { data: wallet, isLoading } = useWallet();
    const [activeTab, setActiveTab] = useState<'ledger' | 'payouts'>('ledger');

    return (
        <div className="max-w-3xl mx-auto px-4 py-8">
            <h1 className="text-2xl font-heading font-bold text-gray-900 mb-6">Wallet</h1>

            {/* Balance cards */}
            <div className="grid grid-cols-2 gap-4 mb-8">
                <div className="bg-white border border-gray-200 rounded-2xl p-5">
                    <p className="text-xs text-gray-500 uppercase tracking-wide mb-1">Available balance</p>
                    {isLoading ? (
                        <Skeleton className="h-8 w-32" />
                    ) : (
                        <p className="text-2xl font-bold text-gray-900">
                            Rs {wallet?.availableBalance?.toLocaleString() ?? '0'}
                        </p>
                    )}
                    <div className="flex items-center gap-1.5 mt-1.5">
                        <WalletIcon className="w-3.5 h-3.5 text-[#1a6b3c]" />
                        <span className="text-xs text-[#1a6b3c]">Ready to withdraw</span>
                    </div>
                </div>
                <div className="bg-gray-50 border border-gray-200 rounded-2xl p-5">
                    <p className="text-xs text-gray-500 uppercase tracking-wide mb-1">Pending payout</p>
                    {isLoading ? (
                        <Skeleton className="h-8 w-32" />
                    ) : (
                        <p className="text-2xl font-bold text-gray-500">
                            Rs {wallet?.pendingPayoutBalance?.toLocaleString() ?? '0'}
                        </p>
                    )}
                    <div className="flex items-center gap-1.5 mt-1.5">
                        <Clock className="w-3.5 h-3.5 text-gray-400" />
                        <span className="text-xs text-gray-400">Awaiting admin approval</span>
                    </div>
                </div>
            </div>

            {/* Payout form */}
            {wallet && wallet.availableBalance > 0 && (
                <div className="mb-8">
                    <PayoutRequestForm available={wallet.availableBalance} />
                </div>
            )}

            {/* Tabs */}
            <div className="flex gap-1 bg-gray-100 p-1 rounded-lg w-fit mb-5">
                {(['ledger', 'payouts'] as const).map((t) => (
                    <button
                        key={t}
                        onClick={() => setActiveTab(t)}
                        className={[
                            'px-5 py-2 text-sm font-medium rounded-md transition-colors capitalize',
                            activeTab === t ? 'bg-white text-[#1a6b3c] shadow-sm' : 'text-gray-600 hover:text-gray-800',
                        ].join(' ')}
                    >
                        {t === 'ledger' ? 'Transaction history' : 'Payout requests'}
                    </button>
                ))}
            </div>

            {activeTab === 'ledger' ? <LedgerTable /> : <PayoutHistory />}
        </div>
    );
}
