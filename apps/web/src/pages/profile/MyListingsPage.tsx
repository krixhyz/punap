import { Link } from 'react-router-dom';
import { Plus, Pencil, Trash2 } from 'lucide-react';
import toast from 'react-hot-toast';
import { useMyProducts } from '../../api/products';
import { Badge } from '../../components/Badge';
import { Button } from '../../components/Button';
import { Skeleton } from '../../components/Skeleton';

const STATUS_VARIANT: Record<string, 'success' | 'warning' | 'danger' | 'neutral'> = {
    APPROVED: 'success',
    PENDING: 'warning',
    REJECTED: 'danger',
};

export default function MyListingsPage() {
    const { data: products, isLoading } = useMyProducts();

    if (isLoading) {
        return (
            <div className="max-w-4xl mx-auto px-4 py-8 space-y-3">
                <Skeleton className="h-8 w-48" />
                {Array.from({ length: 5 }).map((_, i) => (
                    <Skeleton key={i} className="h-16 rounded-xl" />
                ))}
            </div>
        );
    }

    return (
        <div className="max-w-4xl mx-auto px-4 py-8">
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-heading font-bold text-gray-900">My Listings</h1>
                <Button size="sm" onClick={() => toast('Create listing coming soon')}>
                    <Plus className="w-4 h-4" />
                    New listing
                </Button>
            </div>

            {(!products || products.length === 0) ? (
                <div className="text-center py-16 text-gray-400">
                    <p className="text-sm">You have no listings yet.</p>
                    <p className="text-xs mt-1">Create your first listing to start selling or renting.</p>
                </div>
            ) : (
                <div className="bg-white border border-gray-200 rounded-2xl overflow-hidden">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th className="text-left px-4 py-3 text-xs text-gray-500 uppercase tracking-wide">Product</th>
                                <th className="text-left px-4 py-3 text-xs text-gray-500 uppercase tracking-wide">Price</th>
                                <th className="text-left px-4 py-3 text-xs text-gray-500 uppercase tracking-wide">Types</th>
                                <th className="text-left px-4 py-3 text-xs text-gray-500 uppercase tracking-wide">Status</th>
                                <th className="px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {products.map((product) => (
                                <tr key={product.id} className="hover:bg-gray-50 transition-colors">
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 rounded-lg bg-gray-100 overflow-hidden shrink-0">
                                                {product.images?.[0] ? (
                                                    <img src={product.images[0]} alt={product.title} className="w-full h-full object-cover" />
                                                ) : (
                                                    <div className="w-full h-full flex items-center justify-center text-gray-300 text-xs">—</div>
                                                )}
                                            </div>
                                            <div>
                                                <Link
                                                    to={`/products/${product.id}`}
                                                    className="font-medium text-gray-900 hover:text-[#1a6b3c] truncate block max-w-48"
                                                >
                                                    {product.title}
                                                </Link>
                                                <p className="text-xs text-gray-500">{product.condition}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3 text-gray-700">Rs {product.price.toLocaleString()}</td>
                                    <td className="px-4 py-3">
                                        <div className="flex gap-1 flex-wrap">
                                            {product.transactionTypes.map((t) => (
                                                <Badge key={t} variant={t === 'BUY' ? 'buy' : t === 'RENT' ? 'rent' : 'swap'} size="sm">
                                                    {t}
                                                </Badge>
                                            ))}
                                        </div>
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge variant={STATUS_VARIANT[product.approvalStatus] ?? 'neutral'} size="sm">
                                            {product.approvalStatus}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-1 justify-end">
                                            <button
                                                onClick={() => toast('Edit listing coming soon')}
                                                className="p-1.5 text-gray-400 hover:text-gray-700 rounded"
                                                title="Edit"
                                            >
                                                <Pencil className="w-3.5 h-3.5" />
                                            </button>
                                            <button
                                                onClick={() => toast('Delete listing coming soon')}
                                                className="p-1.5 text-gray-400 hover:text-red-500 rounded"
                                                title="Delete"
                                            >
                                                <Trash2 className="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}
