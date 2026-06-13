import { Link } from 'react-router-dom';
import { Heart, Leaf, Star } from 'lucide-react';
import { Badge } from './Badge';
import { useToggleWishlist, type Product } from '../api/products';
import { useAuth } from '../hooks/useAuth';

interface ProductCardProps {
    product: Product;
}

const TYPE_LABELS: Record<string, string> = {
    BUY: 'Buy',
    RENT: 'Rent',
    SWAP: 'Swap',
};

const TYPE_VARIANTS: Record<string, 'buy' | 'rent' | 'swap'> = {
    BUY: 'buy',
    RENT: 'rent',
    SWAP: 'swap',
};

export function ProductCard({ product }: ProductCardProps) {
    const { isAuthenticated } = useAuth();
    const toggle = useToggleWishlist();
    const image = product.images?.[0];
    const rating = product._avg?.rating;
    const reviewCount = product._count?.reviews ?? 0;

    function handleWishlist(e: React.MouseEvent) {
        e.preventDefault();
        if (!isAuthenticated) return;
        toggle.mutate(product.id);
    }

    return (
        <Link
            to={`/products/${product.id}`}
            className="group block bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-md transition-shadow"
        >
            <div className="relative aspect-[4/3] bg-gray-100 overflow-hidden">
                {image ? (
                    <img
                        src={image}
                        alt={product.title}
                        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                    />
                ) : (
                    <div className="w-full h-full flex items-center justify-center text-gray-300">
                        <svg className="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                )}

                {isAuthenticated && (
                    <button
                        onClick={handleWishlist}
                        className="absolute top-2 right-2 w-8 h-8 bg-white/90 rounded-full flex items-center justify-center shadow-sm hover:bg-white transition-colors"
                        title={product.wishlisted ? 'Remove from wishlist' : 'Add to wishlist'}
                    >
                        <Heart
                            className={`w-4 h-4 ${product.wishlisted ? 'fill-red-500 text-red-500' : 'text-gray-400'}`}
                        />
                    </button>
                )}

                <div className="absolute top-2 left-2 flex flex-wrap gap-1">
                    {product.transactionTypes.map((t) => (
                        <Badge key={t} variant={TYPE_VARIANTS[t] ?? 'success'} size="sm">
                            {TYPE_LABELS[t] ?? t}
                        </Badge>
                    ))}
                </div>
            </div>

            <div className="p-3">
                <p className="text-xs text-gray-500 mb-0.5 truncate">{product.category?.name}</p>
                <h3 className="font-medium text-gray-900 text-sm leading-snug line-clamp-2 mb-1">
                    {product.title}
                </h3>

                <div className="flex items-center justify-between mt-2">
                    <span className="font-semibold text-gray-900">
                        Rs {product.price.toLocaleString()}
                    </span>
                    <span className="text-xs text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded">
                        {product.condition}
                    </span>
                </div>

                <div className="flex items-center gap-3 mt-2">
                    {rating !== null && rating !== undefined && (
                        <span className="flex items-center gap-0.5 text-xs text-gray-600">
                            <Star className="w-3 h-3 fill-amber-400 text-amber-400" />
                            {rating.toFixed(1)}
                            {reviewCount > 0 && (
                                <span className="text-gray-400">({reviewCount})</span>
                            )}
                        </span>
                    )}
                    {product.ecoScore !== undefined && product.ecoScore > 0 && (
                        <span className="flex items-center gap-0.5 text-xs text-[#1a6b3c]">
                            <Leaf className="w-3 h-3" />
                            {product.ecoScore}
                        </span>
                    )}
                </div>
            </div>
        </Link>
    );
}
