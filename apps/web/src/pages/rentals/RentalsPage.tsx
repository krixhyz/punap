import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Calendar } from 'lucide-react';
import { useRentals, useLendingRentals } from '../../api/rentals';
import { Badge } from '../../components/Badge';
import { Skeleton } from '../../components/Skeleton';
import { Pagination } from '../../components/Pagination';
import { usePagination } from '../../hooks/usePagination';

const STATUS_VARIANTS: Record<string, 'success' | 'warning' | 'danger' | 'neutral'> = {
    PENDING_PAYMENT: 'warning',
    ACTIVE: 'success',
    RETURN_REQUESTED: 'warning',
    COMPLETED: 'success',
    CANCELLED: 'danger',
    DISPUTED: 'danger',
};

function RentalRow({ rental }: { rental: { id: string; status: string; totalAmount: number; startDate: string; endDate: string; product: { title: string; images: string[] }; createdAt: string } }) {
    const image = rental.product.images?.[0];
    const start = new Date(rental.startDate).toLocaleDateString();
    const end = new Date(rental.endDate).toLocaleDateString();

    return (
        <Link
            to={`/rentals/${rental.id}`}
            className="flex items-center gap-4 p-4 bg-white border border-gray-200 rounded-xl hover:shadow-sm transition-shadow"
        >
            <div className="w-14 h-14 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                {image ? (
                    <img src={image} alt={rental.product.title} className="w-full h-full object-cover" />
                ) : (
                    <div className="w-full h-full bg-gray-200" />
                )}
            </div>
            <div className="flex-1 min-w-0">
                <p className="font-medium text-gray-900 text-sm truncate">{rental.product.title}</p>
                <p className="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                    <Calendar className="w-3 h-3" />
                    {start} – {end}
                </p>
            </div>
            <div className="text-right flex-shrink-0">
                <p className="font-semibold text-gray-900 text-sm">Rs {rental.totalAmount.toLocaleString()}</p>
                <Badge variant={STATUS_VARIANTS[rental.status] ?? 'neutral'} className="mt-1">
                    {rental.status.replace('_', ' ')}
                </Badge>
            </div>
        </Link>
    );
}

function RentalList({ type }: { type: 'renting' | 'lending' }) {
    const { page, setPage } = usePagination();
    const renting = useRentals(page);
    const lending = useLendingRentals(page);
    const { data, isLoading } = type === 'renting' ? renting : lending;

    if (isLoading) {
        return (
            <div className="space-y-3">
                {Array.from({ length: 4 }).map((_, i) => (
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
                <p className="font-medium">No rentals yet</p>
                <Link to="/?transactionType=RENT" className="text-sm text-[#1a6b3c] hover:underline mt-2 block">
                    Browse items for rent
                </Link>
            </div>
        );
    }

    return (
        <>
            <div className="space-y-3">
                {data.data.map((rental) => (
                    <RentalRow key={rental.id} rental={rental} />
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

export default function RentalsPage() {
    const [tab, setTab] = useState<'renting' | 'lending'>('renting');

    return (
        <div className="max-w-3xl mx-auto px-4 py-8">
            <h1 className="text-2xl font-heading font-bold text-gray-900 mb-6">My Rentals</h1>

            <div className="flex gap-1 bg-gray-100 p-1 rounded-xl mb-6 w-fit">
                {(['renting', 'lending'] as const).map((t) => (
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

            <RentalList key={tab} type={tab} />
        </div>
    );
}
