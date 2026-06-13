import { useState } from 'react';
import { Link } from 'react-router-dom';
import { ArrowLeftRight } from 'lucide-react';
import { useSwaps, type SwapRequest } from '../../api/swaps';
import { Badge } from '../../components/Badge';
import { Pagination } from '../../components/Pagination';
import { Skeleton } from '../../components/Skeleton';
import { usePagination } from '../../hooks/usePagination';

const STATUS_VARIANT: Record<string, 'success' | 'warning' | 'danger' | 'neutral'> = {
    PENDING: 'warning',
    COUNTERED: 'warning',
    ACCEPTED: 'success',
    AWAITING_PAYMENT: 'warning',
    CONFIRMATION_PENDING: 'neutral',
    COMPLETED: 'success',
    REJECTED: 'danger',
    CANCELLED: 'danger',
};

function SwapCard({ swap, role }: { swap: SwapRequest; role: 'sent' | 'received' }) {
    const other = role === 'sent' ? swap.owner : swap.requester;
    return (
        <Link
            to={`/swaps/${swap.id}`}
            className="flex items-center gap-4 bg-white border border-gray-200 rounded-xl p-4 hover:shadow-md transition-shadow"
        >
            <div className="flex items-center gap-2 shrink-0">
                <div className="w-12 h-12 rounded-lg bg-gray-100 overflow-hidden">
                    {swap.product.images?.[0] ? (
                        <img src={swap.product.images[0]} alt={swap.product.title} className="w-full h-full object-cover" />
                    ) : (
                        <div className="w-full h-full flex items-center justify-center text-gray-300 text-xs">No img</div>
                    )}
                </div>
                <ArrowLeftRight className="w-4 h-4 text-purple-400" />
                <div className="w-12 h-12 rounded-lg bg-gray-100 overflow-hidden">
                    {swap.offeredProduct.images?.[0] ? (
                        <img src={swap.offeredProduct.images[0]} alt={swap.offeredProduct.title} className="w-full h-full object-cover" />
                    ) : (
                        <div className="w-full h-full flex items-center justify-center text-gray-300 text-xs">No img</div>
                    )}
                </div>
            </div>

            <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-gray-900 truncate">
                    {swap.product.title} ↔ {swap.offeredProduct.title}
                </p>
                <p className="text-xs text-gray-500 mt-0.5">
                    {role === 'sent' ? `To: ${other.name}` : `From: ${other.name}`}
                </p>
                {swap.moneyDirection !== 'NONE' && (
                    <p className="text-xs text-purple-600 mt-0.5">
                        {swap.moneyDirection === 'REQUESTER_OFFERS_CASH'
                            ? `+ Rs ${swap.offeredAmount?.toLocaleString() ?? 0} cash`
                            : `Owner asks Rs ${swap.askedAmount?.toLocaleString() ?? 0}`}
                    </p>
                )}
            </div>

            <div className="flex flex-col items-end gap-1 shrink-0">
                <Badge variant={STATUS_VARIANT[swap.status] ?? 'neutral'} size="sm">
                    {swap.status.replace('_', ' ')}
                </Badge>
                <span className="text-xs text-gray-400">
                    {new Date(swap.updatedAt).toLocaleDateString()}
                </span>
            </div>
        </Link>
    );
}

function SwapList({ type }: { type: 'sent' | 'received' }) {
    const { page, goToPage } = usePagination();
    const { data, isLoading } = useSwaps(type, page);

    if (isLoading) {
        return (
            <div className="space-y-3">
                {Array.from({ length: 4 }).map((_, i) => (
                    <Skeleton key={i} className="h-20 rounded-xl" />
                ))}
            </div>
        );
    }

    if (!data || data.data.length === 0) {
        return (
            <div className="text-center py-16 text-gray-400">
                <ArrowLeftRight className="w-10 h-10 mx-auto mb-3 opacity-40" />
                <p className="text-sm">No swaps here yet.</p>
            </div>
        );
    }

    return (
        <div className="space-y-3">
            {data.data.map((swap) => (
                <SwapCard key={swap.id} swap={swap} role={type} />
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

export default function SwapsPage() {
    const [tab, setTab] = useState<'sent' | 'received'>('sent');

    return (
        <div className="max-w-3xl mx-auto px-4 py-8">
            <h1 className="text-2xl font-heading font-bold text-gray-900 mb-6">My Swaps</h1>

            <div className="flex gap-1 bg-gray-100 p-1 rounded-lg w-fit mb-6">
                {(['sent', 'received'] as const).map((t) => (
                    <button
                        key={t}
                        onClick={() => setTab(t)}
                        className={[
                            'px-5 py-2 text-sm font-medium rounded-md transition-colors capitalize',
                            tab === t ? 'bg-white text-[#1a6b3c] shadow-sm' : 'text-gray-600 hover:text-gray-800',
                        ].join(' ')}
                    >
                        {t}
                    </button>
                ))}
            </div>

            <SwapList type={tab} />
        </div>
    );
}
