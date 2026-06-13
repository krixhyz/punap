import { useState, useEffect, useRef } from 'react';
import { useSearchParams } from 'react-router-dom';
import { Search, SlidersHorizontal, X } from 'lucide-react';
import { ProductCard } from '../../components/ProductCard';
import { Skeleton } from '../../components/Skeleton';
import { Pagination } from '../../components/Pagination';
import { Select } from '../../components/Select';
import { useSearch, useSearchSuggestions, useCategories, useProvinces, useCities } from '../../api/products';
import { usePagination } from '../../hooks/usePagination';

const CONDITIONS = ['NEW', 'LIKE_NEW', 'GOOD', 'FAIR', 'POOR'];
const TRANSACTION_TYPES = ['BUY', 'RENT', 'SWAP'];
const SORT_OPTIONS = [
    { value: 'newest', label: 'Newest first' },
    { value: 'price_asc', label: 'Price: low to high' },
    { value: 'price_desc', label: 'Price: high to low' },
    { value: 'eco_score', label: 'Eco score' },
];

const LIMIT = 12;

export default function SearchPage() {
    const [searchParams, setSearchParams] = useSearchParams();
    const { page, setPage } = usePagination();
    const [showFilters, setShowFilters] = useState(false);
    const [inputValue, setInputValue] = useState(searchParams.get('q') ?? '');
    const [showSuggestions, setShowSuggestions] = useState(false);
    const suggestionRef = useRef<HTMLDivElement>(null);

    // Derive filter state from URL
    const q = searchParams.get('q') ?? '';
    const categoryId = searchParams.get('categoryId') ? Number(searchParams.get('categoryId')) : undefined;
    const transactionType = searchParams.get('transactionType') ?? '';
    const condition = searchParams.get('condition') ?? '';
    const minPrice = searchParams.get('minPrice') ? Number(searchParams.get('minPrice')) : undefined;
    const maxPrice = searchParams.get('maxPrice') ? Number(searchParams.get('maxPrice')) : undefined;
    const provinceId = searchParams.get('provinceId') ? Number(searchParams.get('provinceId')) : undefined;
    const cityId = searchParams.get('cityId') ? Number(searchParams.get('cityId')) : undefined;
    const sortBy = searchParams.get('sortBy') ?? 'newest';

    const { data: categories } = useCategories();
    const { data: provinces } = useProvinces();
    const { data: cities } = useCities(provinceId);
    const { data: suggestions } = useSearchSuggestions(inputValue);
    const { data, isLoading } = useSearch({
        q: q || undefined,
        categoryId,
        transactionType: transactionType || undefined,
        condition: condition || undefined,
        minPrice,
        maxPrice,
        provinceId,
        cityId,
        sortBy,
        page,
        limit: LIMIT,
    });

    useEffect(() => {
        setInputValue(q);
    }, [q]);

    useEffect(() => {
        function handleClick(e: MouseEvent) {
            if (suggestionRef.current && !suggestionRef.current.contains(e.target as Node)) {
                setShowSuggestions(false);
            }
        }
        document.addEventListener('mousedown', handleClick);
        return () => document.removeEventListener('mousedown', handleClick);
    }, []);

    function updateParam(key: string, value: string | undefined) {
        const next = new URLSearchParams(searchParams);
        if (value) {
            next.set(key, value);
        } else {
            next.delete(key);
        }
        next.delete('page');
        setSearchParams(next);
        setPage(1);
    }

    function handleSearch(value: string) {
        updateParam('q', value || undefined);
        setShowSuggestions(false);
    }

    function handleKeyDown(e: React.KeyboardEvent) {
        if (e.key === 'Enter') handleSearch(inputValue);
    }

    function clearFilters() {
        setSearchParams({});
        setInputValue('');
        setPage(1);
    }

    const hasFilters = q || categoryId || transactionType || condition || minPrice || maxPrice || provinceId || cityId;

    return (
        <div className="max-w-7xl mx-auto px-4 py-8">
            {/* Search bar */}
            <div className="relative mb-6" ref={suggestionRef}>
                <div className="flex gap-2">
                    <div className="relative flex-1">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input
                            className="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-[#1a6b3c] focus:border-transparent"
                            placeholder="Search products…"
                            value={inputValue}
                            onChange={(e) => { setInputValue(e.target.value); setShowSuggestions(true); }}
                            onKeyDown={handleKeyDown}
                            onFocus={() => setShowSuggestions(true)}
                        />
                    </div>
                    <button
                        onClick={() => handleSearch(inputValue)}
                        className="px-5 py-2.5 bg-[#1a6b3c] text-white rounded-xl text-sm font-medium hover:bg-[#124d2b] transition-colors"
                    >
                        Search
                    </button>
                    <button
                        onClick={() => setShowFilters((v) => !v)}
                        className={[
                            'px-4 py-2.5 rounded-xl border text-sm font-medium flex items-center gap-2 transition-colors',
                            showFilters ? 'bg-[#1a6b3c] text-white border-[#1a6b3c]' : 'bg-white text-gray-700 border-gray-300 hover:border-[#1a6b3c]',
                        ].join(' ')}
                    >
                        <SlidersHorizontal className="w-4 h-4" />
                        Filters
                    </button>
                </div>

                {/* Typeahead suggestions */}
                {showSuggestions && suggestions && suggestions.length > 0 && (
                    <div className="absolute top-full mt-1 left-0 right-0 bg-white border border-gray-200 rounded-xl shadow-lg z-20 overflow-hidden">
                        {suggestions.map((s) => (
                            <button
                                key={s}
                                className="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 flex items-center gap-2"
                                onMouseDown={() => { setInputValue(s); handleSearch(s); }}
                            >
                                <Search className="w-3.5 h-3.5 text-gray-400 flex-shrink-0" />
                                {s}
                            </button>
                        ))}
                    </div>
                )}
            </div>

            <div className="flex gap-6">
                {/* Filter sidebar */}
                {showFilters && (
                    <aside className="w-64 flex-shrink-0 space-y-5">
                        <div className="flex items-center justify-between">
                            <h3 className="font-medium text-gray-900 text-sm">Filters</h3>
                            {hasFilters && (
                                <button onClick={clearFilters} className="text-xs text-[#1a6b3c] hover:underline flex items-center gap-1">
                                    <X className="w-3 h-3" /> Clear all
                                </button>
                            )}
                        </div>

                        {/* Category */}
                        <div>
                            <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Category</p>
                            <Select
                                value={String(categoryId ?? '')}
                                onChange={(e) => updateParam('categoryId', e.target.value || undefined)}
                            >
                                <option value="">All categories</option>
                                {categories?.map((c) => (
                                    <option key={c.id} value={c.id}>{c.name}</option>
                                ))}
                            </Select>
                        </div>

                        {/* Transaction type */}
                        <div>
                            <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Transaction type</p>
                            <div className="space-y-1.5">
                                {TRANSACTION_TYPES.map((t) => (
                                    <label key={t} className="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={transactionType === t}
                                            onChange={() => updateParam('transactionType', transactionType === t ? undefined : t)}
                                            className="w-4 h-4 text-[#1a6b3c] rounded border-gray-300"
                                        />
                                        <span className="text-sm text-gray-700">{t}</span>
                                    </label>
                                ))}
                            </div>
                        </div>

                        {/* Condition */}
                        <div>
                            <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Condition</p>
                            <div className="space-y-1.5">
                                {CONDITIONS.map((c) => (
                                    <label key={c} className="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={condition === c}
                                            onChange={() => updateParam('condition', condition === c ? undefined : c)}
                                            className="w-4 h-4 text-[#1a6b3c] rounded border-gray-300"
                                        />
                                        <span className="text-sm text-gray-700">{c.replace('_', ' ')}</span>
                                    </label>
                                ))}
                            </div>
                        </div>

                        {/* Price range */}
                        <div>
                            <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Price (Rs)</p>
                            <div className="flex gap-2 items-center">
                                <input
                                    type="number"
                                    placeholder="Min"
                                    value={minPrice ?? ''}
                                    onChange={(e) => updateParam('minPrice', e.target.value || undefined)}
                                    className="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#1a6b3c]"
                                />
                                <span className="text-gray-400 text-sm">–</span>
                                <input
                                    type="number"
                                    placeholder="Max"
                                    value={maxPrice ?? ''}
                                    onChange={(e) => updateParam('maxPrice', e.target.value || undefined)}
                                    className="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#1a6b3c]"
                                />
                            </div>
                        </div>

                        {/* Province / City */}
                        <div>
                            <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Location</p>
                            <div className="space-y-2">
                                <Select
                                    value={String(provinceId ?? '')}
                                    onChange={(e) => { updateParam('provinceId', e.target.value || undefined); updateParam('cityId', undefined); }}
                                >
                                    <option value="">All provinces</option>
                                    {provinces?.map((p) => (
                                        <option key={p.id} value={p.id}>{p.name}</option>
                                    ))}
                                </Select>
                                {provinceId && (
                                    <Select
                                        value={String(cityId ?? '')}
                                        onChange={(e) => updateParam('cityId', e.target.value || undefined)}
                                    >
                                        <option value="">All cities</option>
                                        {cities?.map((c) => (
                                            <option key={c.id} value={c.id}>{c.name}</option>
                                        ))}
                                    </Select>
                                )}
                            </div>
                        </div>
                    </aside>
                )}

                {/* Results */}
                <div className="flex-1 min-w-0">
                    <div className="flex items-center justify-between mb-4">
                        <p className="text-sm text-gray-500">
                            {data ? `${data.total.toLocaleString()} results` : ''}
                            {q ? ` for "${q}"` : ''}
                        </p>
                        <div className="flex items-center gap-2">
                            <span className="text-sm text-gray-500">Sort:</span>
                            <Select
                                value={sortBy}
                                onChange={(e) => updateParam('sortBy', e.target.value)}
                                className="text-sm py-1.5"
                            >
                                {SORT_OPTIONS.map((o) => (
                                    <option key={o.value} value={o.value}>{o.label}</option>
                                ))}
                            </Select>
                        </div>
                    </div>

                    {isLoading ? (
                        <div className="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            {Array.from({ length: LIMIT }).map((_, i) => (
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
                            <div className="grid grid-cols-2 sm:grid-cols-3 gap-4">
                                {data.data.map((product) => (
                                    <ProductCard key={product.id} product={product} />
                                ))}
                            </div>
                            <div className="mt-8 flex justify-center">
                                <Pagination
                                    page={page}
                                    totalPages={Math.ceil(data.total / LIMIT)}
                                    onPageChange={setPage}
                                />
                            </div>
                        </>
                    ) : (
                        <div className="text-center py-16 text-gray-500">
                            <Search className="w-12 h-12 mx-auto mb-4 text-gray-300" />
                            <p className="font-medium">No products found</p>
                            <p className="text-sm mt-1">Try adjusting your filters.</p>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
