import { useState } from 'react';
import toast from 'react-hot-toast';
import { useAdminProducts, useApproveProduct, useRejectProduct } from '../../api/admin';
import { Badge } from '../../components/Badge';
import { Button } from '../../components/Button';
import { Pagination } from '../../components/Pagination';
import { Skeleton } from '../../components/Skeleton';
import { usePagination } from '../../hooks/usePagination';

const STATUS_VARIANT: Record<string, 'success' | 'warning' | 'danger' | 'neutral'> = {
    APPROVED: 'success',
    PENDING: 'warning',
    REJECTED: 'danger',
};

export default function AdminProductsPage() {
    const { page, goToPage } = usePagination();
    const [filter, setFilter] = useState('PENDING');
    const { data, isLoading } = useAdminProducts(page, filter);
    const approve = useApproveProduct();
    const reject = useRejectProduct();

    async function handleApprove(id: string) {
        try {
            await approve.mutateAsync(id);
            toast.success('Product approved');
        } catch { toast.error('Failed to approve'); }
    }

    async function handleReject(id: string) {
        const reason = prompt('Reason for rejection:');
        if (!reason) return;
        try {
            await reject.mutateAsync({ id, reason });
            toast.success('Product rejected');
        } catch { toast.error('Failed to reject'); }
    }

    return (
        <div className="p-8">
            <h1 className="text-2xl font-heading font-bold text-gray-900 mb-6">Products</h1>

            <div className="flex gap-1 bg-gray-100 p-1 rounded-lg w-fit mb-6">
                {['PENDING', 'APPROVED', 'REJECTED', ''].map((s) => (
                    <button
                        key={s}
                        onClick={() => { setFilter(s); goToPage(1); }}
                        className={[
                            'px-4 py-1.5 text-sm font-medium rounded-md transition-colors',
                            filter === s ? 'bg-white text-[#1a6b3c] shadow-sm' : 'text-gray-600',
                        ].join(' ')}
                    >
                        {s || 'All'}
                    </button>
                ))}
            </div>

            {isLoading ? (
                <div className="space-y-2">{Array.from({ length: 6 }).map((_, i) => <Skeleton key={i} className="h-16 rounded-lg" />)}</div>
            ) : (
                <>
                    <div className="bg-white border border-gray-200 rounded-xl overflow-hidden">
                        <table className="w-full text-sm">
                            <thead className="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th className="text-left px-4 py-3 text-xs text-gray-500 uppercase">Product</th>
                                    <th className="text-left px-4 py-3 text-xs text-gray-500 uppercase">Seller</th>
                                    <th className="text-left px-4 py-3 text-xs text-gray-500 uppercase">Price</th>
                                    <th className="text-left px-4 py-3 text-xs text-gray-500 uppercase">Status</th>
                                    <th className="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {data?.data.map((product) => (
                                    <tr key={product.id} className="hover:bg-gray-50">
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-3">
                                                <div className="w-10 h-10 bg-gray-100 rounded-lg overflow-hidden shrink-0">
                                                    {product.images?.[0] ? (
                                                        <img src={product.images[0]} alt="" className="w-full h-full object-cover" />
                                                    ) : null}
                                                </div>
                                                <p className="font-medium text-gray-900 truncate max-w-48">{product.title}</p>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-gray-600">{product.seller.name}</td>
                                        <td className="px-4 py-3 text-gray-700">Rs {product.price.toLocaleString()}</td>
                                        <td className="px-4 py-3">
                                            <Badge variant={STATUS_VARIANT[product.approvalStatus] ?? 'neutral'} size="sm">
                                                {product.approvalStatus}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3">
                                            {product.approvalStatus === 'PENDING' && (
                                                <div className="flex gap-1 justify-end">
                                                    <Button size="sm" loading={approve.isPending} onClick={() => handleApprove(product.id)}>
                                                        Approve
                                                    </Button>
                                                    <Button size="sm" variant="danger" loading={reject.isPending} onClick={() => handleReject(product.id)}>
                                                        Reject
                                                    </Button>
                                                </div>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    {(data?.total ?? 0) > 10 && (
                        <div className="mt-4 flex justify-center">
                            <Pagination page={page} totalPages={Math.ceil((data?.total ?? 0) / 10)} onPageChange={goToPage} />
                        </div>
                    )}
                </>
            )}
        </div>
    );
}
