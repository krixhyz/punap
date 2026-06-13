import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useOrders, useSellingOrders } from '../../api/orders';
import { Badge } from '../../components/Badge';
import { Skeleton } from '../../components/Skeleton';
import { Pagination } from '../../components/Pagination';
import { usePagination } from '../../hooks/usePagination';

const STATUS_VARIANTS: Record<string, 'success' | 'warning' | 'danger' | 'neutral'> = {
    PENDING: 'warning',
    PAID: 'success',
    COMPLETED: 'success',
    CANCELLED: 'danger',
    DISPUTED: 'danger',
};

function OrderRow({ order }: { order: { id: string; status: string; totalAmount: number; product: { title: string; images: string[] }; createdAt: string } }) {
    const image = order.product.images?.[0];
    return (
        <Link
            to={`/orders/${order.id}`}
            className="flex items-center gap-4 p-4 bg-white border border-gray-200 rounded-xl hover:shadow-sm transition-shadow"
        >
            <div className="w-14 h-14 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                {image ? (
                    <img src={image} alt={order.product.title} className="w-full h-full object-cover" />
                ) : (
                    <div className="w-full h-full bg-gray-200" />
                )}
            </div>
            <div className="flex-1 min-w-0">
                <p className="font-medium text-gray-900 text-sm truncate">{order.product.title}</p>
                <p className="text-xs text-gray-400 mt-0.5">
                    {new Date(order.createdAt).toLocaleDateString()}
                </p>
            </div>
            <div className="text-right flex-shrink-0">
                <p className="font-semibold text-gray-900 text-sm">Rs {order.totalAmount.toLocaleString()}</p>
                <Badge variant={STATUS_VARIANTS[order.status] ?? 'neutral'} className="mt-1">
                    {order.status}
                </Badge>
            </div>
        </Link>
    );
}

function OrderList({ type }: { type: 'buying' | 'selling' }) {
    const { page, setPage } = usePagination();
    const buying = useOrders(page);
    const selling = useSellingOrders(page);
    const { data, isLoading } = type === 'buying' ? buying : selling;

    if (isLoading) {
        return (
            <div className="space-y-3">
                {Array.from({ length: 5 }).map((_, i) => (
                    <div key={i} className="flex items-center gap-4 p-4 border border-gray-200 rounded-xl">
                        <Skeleton className="w-14 h-14 rounded-lg" />
                        <div className="flex-1 space-y-2">
                            <Skeleton variant="text" className="w-2/3" />
                            <Skeleton variant="text" className="w-1/3" />
                        </div>
                    </div>
                ))}
            </div>
        );
    }

    if (!data || data.data.length === 0) {
        return (
            <div className="text-center py-16 text-gray-500">
                <p className="font-medium">No orders yet</p>
                <Link to="/" className="text-sm text-[#1a6b3c] hover:underline mt-2 block">
                    Browse products
                </Link>
            </div>
        );
    }

    return (
        <>
            <div className="space-y-3">
                {data.data.map((order) => (
                    <OrderRow key={order.id} order={order} />
                ))}
            </div>
            {data.total > 10 && (
                <div className="mt-6 flex justify-center">
                    <Pagination
                        page={page}
                        totalPages={Math.ceil(data.total / 10)}
                        onPageChange={setPage}
                    />
                </div>
            )}
        </>
    );
}

export default function OrdersPage() {
    const [tab, setTab] = useState<'buying' | 'selling'>('buying');

    return (
        <div className="max-w-3xl mx-auto px-4 py-8">
            <h1 className="text-2xl font-heading font-bold text-gray-900 mb-6">My Orders</h1>

            <div className="flex gap-1 bg-gray-100 p-1 rounded-xl mb-6 w-fit">
                {(['buying', 'selling'] as const).map((t) => (
                    <button
                        key={t}
                        onClick={() => setTab(t)}
                        className={[
                            'px-5 py-2 text-sm font-medium rounded-lg transition-colors capitalize',
                            tab === t ? 'bg-white text-[#1a6b3c] shadow-sm' : 'text-gray-600 hover:text-gray-800',
                        ].join(' ')}
                    >
                        {t}
                    </button>
                ))}
            </div>

            <OrderList key={tab} type={tab} />
        </div>
    );
}
