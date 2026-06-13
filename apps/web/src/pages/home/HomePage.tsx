import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { ProductCard } from '../../components/ProductCard';
import { Skeleton } from '../../components/Skeleton';
import { Pagination } from '../../components/Pagination';
import { useProducts, useCategories } from '../../api/products';
import { usePagination } from '../../hooks/usePagination';

const TRANSACTION_TABS = [
    { label: 'All', value: '' },
    { label: 'Buy', value: 'BUY' },
    { label: 'Rent', value: 'RENT' },
    { label: 'Swap', value: 'SWAP' },
];

export default function HomePage() {
    const navigate = useNavigate();
    const [transactionType, setTransactionType] = useState('');
    const [categoryId, setCategoryId] = useState<number | undefined>();
    const { page, setPage } = usePagination();

    const { data: categories } = useCategories();
    const { data, isLoading } = useProducts({
        transactionType: transactionType || undefined,
        categoryId,
        page,
        limit: 12,
    });

    function handleTypeChange(value: string) {
        setTransactionType(value);
        setPage(1);
    }

    function handleCategoryChange(id: number | undefined) {
        setCategoryId(id);
        setPage(1);
    }

    return (
        <div className="max-w-7xl mx-auto px-4 py-8">
            {/* Hero */}
            <div className="mb-8">
                <h1 className="text-3xl font-heading font-bold text-gray-900 mb-2">
                    Discover sustainable products
                </h1>
                <p className="text-gray-500">Buy, rent, or swap second-hand items in your community.</p>
            </div>

            {/* Transaction type tabs */}
            <div className="flex gap-2 mb-4 border-b border-gray-200">
                {TRANSACTION_TABS.map((tab) => (
                    <button
                        key={tab.value}
                        onClick={() => handleTypeChange(tab.value)}
                        className={[
                            'px-4 py-2 text-sm font-medium border-b-2 transition-colors -mb-px',
                            transactionType === tab.value
                                ? 'border-[#1a6b3c] text-[#1a6b3c]'
                                : 'border-transparent text-gray-500 hover:text-gray-700',
                        ].join(' ')}
                    >
                        {tab.label}
                    </button>
                ))}
            </div>

            {/* Category pills */}
            {categories && categories.length > 0 && (
                <div className="flex gap-2 overflow-x-auto pb-2 mb-6 scrollbar-hide">
                    <button
                        onClick={() => handleCategoryChange(undefined)}
                        className={[
                            'flex-shrink-0 px-3 py-1.5 rounded-full text-sm font-medium border transition-colors',
                            categoryId === undefined
                                ? 'bg-[#1a6b3c] text-white border-[#1a6b3c]'
                                : 'bg-white text-gray-700 border-gray-300 hover:border-[#1a6b3c]',
                        ].join(' ')}
                    >
                        All categories
                    </button>
                    {categories.map((cat) => (
                        <button
                            key={cat.id}
                            onClick={() => handleCategoryChange(cat.id)}
                            className={[
                                'flex-shrink-0 px-3 py-1.5 rounded-full text-sm font-medium border transition-colors',
                                categoryId === cat.id
                                    ? 'bg-[#1a6b3c] text-white border-[#1a6b3c]'
                                    : 'bg-white text-gray-700 border-gray-300 hover:border-[#1a6b3c]',
                            ].join(' ')}
                        >
                            {cat.name}
                        </button>
                    ))}
                </div>
            )}

            {/* Product grid */}
            {isLoading ? (
                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    {Array.from({ length: 12 }).map((_, i) => (
                        <div key={i} className="rounded-xl overflow-hidden">
                            <Skeleton className="aspect-[4/3]" />
                            <div className="p-3 flex flex-col gap-2">
                                <Skeleton variant="text" className="w-3/4" />
                                <Skeleton variant="text" className="w-1/2" />
                            </div>
                        </div>
                    ))}
                </div>
            ) : data && data.data.length > 0 ? (
                <>
                    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                        {data.data.map((product) => (
                            <ProductCard key={product.id} product={product} />
                        ))}
                    </div>
                    <div className="mt-8 flex justify-center">
                        <Pagination
                            page={page}
                            totalPages={Math.ceil(data.total / 12)}
                            onPageChange={setPage}
                        />
                    </div>
                </>
            ) : (
                <div className="text-center py-16 text-gray-500">
                    <p className="text-lg font-medium mb-2">No products found</p>
                    <p className="text-sm">
                        Try a different category or{' '}
                        <button
                            className="text-[#1a6b3c] hover:underline"
                            onClick={() => navigate('/search')}
                        >
                            search
                        </button>{' '}
                        for something specific.
                    </p>
                </div>
            )}
        </div>
    );
}
