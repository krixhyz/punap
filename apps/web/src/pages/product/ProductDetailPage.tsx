import { useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { Heart, Star, Leaf, MapPin, ChevronLeft, ShoppingCart, Calendar, ArrowLeftRight } from 'lucide-react';
import toast from 'react-hot-toast';
import { useProduct, useProductReviews, useToggleWishlist } from '../../api/products';
import { useCreateOrder, useInitiateOrderPayment } from '../../api/orders';
import { useBookRental, useInitiateRentalPayment } from '../../api/rentals';
import { Avatar } from '../../components/Avatar';
import { Button } from '../../components/Button';
import { Badge } from '../../components/Badge';
import { Skeleton } from '../../components/Skeleton';
import { Spinner } from '../../components/Spinner';
import { Input } from '../../components/Input';
import { SwapRequestModal } from '../../components/SwapRequestModal';
import { useAuth } from '../../hooks/useAuth';

const TYPE_LABELS: Record<string, string> = { BUY: 'Buy', RENT: 'Rent', SWAP: 'Swap' };
const TYPE_VARIANTS: Record<string, 'buy' | 'rent' | 'swap'> = { BUY: 'buy', RENT: 'rent', SWAP: 'swap' };

function BuyCTA({ productId, price }: { productId: string; price: number }) {
    const navigate = useNavigate();
    const createOrder = useCreateOrder();
    const initiatePayment = useInitiateOrderPayment();

    async function handleBuy() {
        try {
            const order = await createOrder.mutateAsync({ productId, quantity: 1 });
            const { paymentUrl } = await initiatePayment.mutateAsync({ orderId: order.id });
            window.location.href = paymentUrl;
        } catch (err: unknown) {
            const msg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message;
            toast.error(msg ?? 'Could not initiate payment');
        }
    }

    return (
        <div className="space-y-3">
            <div className="flex items-baseline gap-2">
                <span className="text-2xl font-bold text-gray-900">Rs {price.toLocaleString()}</span>
            </div>
            <Button
                size="lg"
                className="w-full"
                loading={createOrder.isPending || initiatePayment.isPending}
                onClick={handleBuy}
            >
                <ShoppingCart className="w-4 h-4" />
                Buy now
            </Button>
        </div>
    );
}

function RentCTA({ productId, rentFare, rentDeposit, rentType }: {
    productId: string;
    rentFare?: number | null;
    rentDeposit?: number | null;
    rentType?: string | null;
}) {
    const navigate = useNavigate();
    const today = new Date().toISOString().split('T')[0];
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');
    const bookRental = useBookRental();
    const initiatePayment = useInitiateRentalPayment();

    function calcDays() {
        if (!startDate || !endDate) return 0;
        const diff = new Date(endDate).getTime() - new Date(startDate).getTime();
        return Math.max(1, Math.ceil(diff / (1000 * 60 * 60 * 24)));
    }

    const days = calcDays();
    const multiplier = rentType === 'WEEKLY' ? 7 : rentType === 'MONTHLY' ? 30 : 1;
    const estimated = rentFare ? Math.round(rentFare * days * (1 / multiplier) * 1.03 * 100) / 100 : 0;

    async function handleBook() {
        if (!startDate || !endDate) {
            toast.error('Please select rental dates');
            return;
        }
        try {
            const booking = await bookRental.mutateAsync({ productId, startDate, endDate });
            const { paymentUrl } = await initiatePayment.mutateAsync({ rentalId: booking.id });
            window.location.href = paymentUrl;
        } catch (err: unknown) {
            const msg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message;
            toast.error(msg ?? 'Could not book rental');
        }
    }

    return (
        <div className="space-y-3">
            {rentFare && (
                <div className="flex items-baseline gap-2">
                    <span className="text-2xl font-bold text-gray-900">
                        Rs {rentFare.toLocaleString()}
                    </span>
                    <span className="text-sm text-gray-500">/ {rentType?.toLowerCase() ?? 'day'}</span>
                </div>
            )}
            {rentDeposit && (
                <p className="text-sm text-gray-500">
                    Deposit: <strong>Rs {rentDeposit.toLocaleString()}</strong>
                </p>
            )}
            <div className="grid grid-cols-2 gap-2">
                <Input
                    label="Start date"
                    type="date"
                    min={today}
                    value={startDate}
                    onChange={(e) => setStartDate(e.target.value)}
                />
                <Input
                    label="End date"
                    type="date"
                    min={startDate || today}
                    value={endDate}
                    onChange={(e) => setEndDate(e.target.value)}
                />
            </div>
            {days > 0 && estimated > 0 && (
                <p className="text-sm text-gray-600 bg-[#e8f5ee] px-3 py-2 rounded-lg">
                    Estimated total: <strong>Rs {estimated.toLocaleString()}</strong> for {days} day(s)
                </p>
            )}
            <Button
                size="lg"
                variant="secondary"
                className="w-full !bg-[#2563eb] !text-white hover:!bg-[#1d4ed8]"
                loading={bookRental.isPending || initiatePayment.isPending}
                onClick={handleBook}
            >
                <Calendar className="w-4 h-4" />
                Book rental
            </Button>
        </div>
    );
}

function SwapCTA({ productId, productTitle }: { productId: string; productTitle: string }) {
    const [open, setOpen] = useState(false);
    return (
        <div className="space-y-3">
            <p className="text-sm text-gray-500">
                Propose a swap — offer one of your listed items in exchange.
            </p>
            <Button
                size="lg"
                variant="ghost"
                className="w-full border border-purple-300 text-purple-700 hover:bg-purple-50"
                onClick={() => setOpen(true)}
            >
                <ArrowLeftRight className="w-4 h-4" />
                Propose swap
            </Button>
            {open && (
                <SwapRequestModal
                    targetProductId={productId}
                    targetProductTitle={productTitle}
                    onClose={() => setOpen(false)}
                />
            )}
        </div>
    );
}

export default function ProductDetailPage() {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const { isAuthenticated } = useAuth();
    const { data: product, isLoading } = useProduct(id ?? '');
    const { data: reviewsData } = useProductReviews(id ?? '');
    const toggle = useToggleWishlist();
    const [activeImage, setActiveImage] = useState(0);
    const [activeTab, setActiveTab] = useState<string | null>(null);

    if (isLoading) {
        return (
            <div className="max-w-7xl mx-auto px-4 py-8">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div className="space-y-3">
                        <Skeleton className="aspect-square rounded-2xl" />
                        <div className="flex gap-2">
                            {Array.from({ length: 4 }).map((_, i) => <Skeleton key={i} className="w-16 h-16 rounded-lg" />)}
                        </div>
                    </div>
                    <div className="space-y-4">
                        <Skeleton variant="text" className="w-2/3 h-7" />
                        <Skeleton variant="text" className="w-1/3 h-5" />
                        <Skeleton className="h-32 rounded-xl" />
                    </div>
                </div>
            </div>
        );
    }

    if (!product) {
        return (
            <div className="max-w-7xl mx-auto px-4 py-16 text-center text-gray-500">
                <p className="text-lg font-medium">Product not found</p>
                <button className="mt-4 text-[#1a6b3c] hover:underline" onClick={() => navigate(-1)}>Go back</button>
            </div>
        );
    }

    const tabs = product.transactionTypes;
    const currentTab = activeTab ?? tabs[0];
    const images = product.images?.length > 0 ? product.images : [];
    const avg = product._avg?.rating;
    const reviewCount = product._count?.reviews ?? 0;

    return (
        <div className="max-w-7xl mx-auto px-4 py-8">
            {/* Back */}
            <button
                onClick={() => navigate(-1)}
                className="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-6"
            >
                <ChevronLeft className="w-4 h-4" />
                Back
            </button>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-10 mb-12">
                {/* Image gallery */}
                <div>
                    <div className="aspect-square bg-gray-100 rounded-2xl overflow-hidden mb-3">
                        {images.length > 0 ? (
                            <img
                                src={images[activeImage]}
                                alt={product.title}
                                className="w-full h-full object-cover"
                            />
                        ) : (
                            <div className="w-full h-full flex items-center justify-center text-gray-300">
                                <svg className="w-24 h-24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        )}
                    </div>
                    {images.length > 1 && (
                        <div className="flex gap-2 overflow-x-auto">
                            {images.map((img, i) => (
                                <button
                                    key={i}
                                    onClick={() => setActiveImage(i)}
                                    className={[
                                        'flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden border-2 transition-colors',
                                        i === activeImage ? 'border-[#1a6b3c]' : 'border-transparent hover:border-gray-300',
                                    ].join(' ')}
                                >
                                    <img src={img} alt="" className="w-full h-full object-cover" />
                                </button>
                            ))}
                        </div>
                    )}
                </div>

                {/* Product info */}
                <div>
                    {/* Transaction type badges */}
                    <div className="flex flex-wrap gap-1.5 mb-3">
                        {product.transactionTypes.map((t) => (
                            <Badge key={t} variant={TYPE_VARIANTS[t] ?? 'neutral'}>{TYPE_LABELS[t] ?? t}</Badge>
                        ))}
                        <Badge variant="neutral">{product.condition.replace('_', ' ')}</Badge>
                    </div>

                    <h1 className="text-2xl font-heading font-bold text-gray-900 mb-2">{product.title}</h1>

                    {/* Rating */}
                    {avg !== null && avg !== undefined && (
                        <div className="flex items-center gap-1.5 mb-3">
                            {Array.from({ length: 5 }).map((_, i) => (
                                <Star key={i} className={`w-4 h-4 ${i < Math.round(avg) ? 'fill-amber-400 text-amber-400' : 'text-gray-200'}`} />
                            ))}
                            <span className="text-sm text-gray-600">{avg.toFixed(1)} ({reviewCount} review{reviewCount !== 1 ? 's' : ''})</span>
                        </div>
                    )}

                    {/* Location */}
                    {(product.city || product.province) && (
                        <div className="flex items-center gap-1 text-sm text-gray-500 mb-4">
                            <MapPin className="w-3.5 h-3.5" />
                            {[product.city?.name, product.province?.name].filter(Boolean).join(', ')}
                        </div>
                    )}

                    {/* Description */}
                    <p className="text-gray-700 text-sm leading-relaxed mb-6">{product.description}</p>

                    {/* Eco score */}
                    {product.ecoScore !== undefined && product.ecoScore > 0 && (
                        <div className="flex items-center gap-1.5 text-sm text-[#1a6b3c] bg-[#e8f5ee] px-3 py-2 rounded-lg mb-6 w-fit">
                            <Leaf className="w-4 h-4" />
                            Eco score: <strong>{product.ecoScore}</strong>
                        </div>
                    )}

                    {/* Wishlist */}
                    {isAuthenticated && (
                        <button
                            onClick={() => toggle.mutate(product.id)}
                            className="flex items-center gap-2 text-sm text-gray-600 hover:text-red-500 transition-colors mb-6"
                        >
                            <Heart className={`w-4 h-4 ${product.wishlisted ? 'fill-red-500 text-red-500' : ''}`} />
                            {product.wishlisted ? 'Remove from wishlist' : 'Add to wishlist'}
                        </button>
                    )}

                    {/* CTA area */}
                    <div className="border border-gray-200 rounded-xl p-5">
                        {/* Tabs if multiple transaction types */}
                        {tabs.length > 1 && (
                            <div className="flex gap-1 mb-5 bg-gray-100 p-1 rounded-lg">
                                {tabs.map((t) => (
                                    <button
                                        key={t}
                                        onClick={() => setActiveTab(t)}
                                        className={[
                                            'flex-1 py-1.5 text-sm font-medium rounded-md transition-colors',
                                            currentTab === t
                                                ? 'bg-white text-[#1a6b3c] shadow-sm'
                                                : 'text-gray-600 hover:text-gray-800',
                                        ].join(' ')}
                                    >
                                        {TYPE_LABELS[t] ?? t}
                                    </button>
                                ))}
                            </div>
                        )}

                        {!isAuthenticated ? (
                            <div className="text-center py-2">
                                <p className="text-sm text-gray-500 mb-3">Sign in to proceed</p>
                                <Link to="/login">
                                    <Button size="lg" className="w-full">Sign in to buy / rent / swap</Button>
                                </Link>
                            </div>
                        ) : (
                            <>
                                {currentTab === 'BUY' && (
                                    <BuyCTA productId={product.id} price={product.price} />
                                )}
                                {currentTab === 'RENT' && (
                                    <RentCTA
                                        productId={product.id}
                                        rentFare={product.rentFare}
                                        rentDeposit={product.rentDeposit}
                                        rentType={product.rentType}
                                    />
                                )}
                                {currentTab === 'SWAP' && (
                                    <SwapCTA productId={product.id} productTitle={product.title} />
                                )}
                            </>
                        )}
                    </div>

                    {/* Seller card */}
                    <div className="flex items-center gap-3 mt-5 p-4 bg-gray-50 rounded-xl">
                        <Avatar src={product.seller.avatarUrl} name={product.seller.name} />
                        <div className="flex-1 min-w-0">
                            <p className="text-sm font-medium text-gray-900 truncate">{product.seller.name}</p>
                            {product.seller.ecoLevel && (
                                <p className="text-xs text-[#1a6b3c]">{product.seller.ecoLevel}</p>
                            )}
                        </div>
                        <Link
                            to={`/profile/${product.seller.id}`}
                            className="text-xs text-[#1a6b3c] hover:underline font-medium"
                        >
                            View profile
                        </Link>
                    </div>
                </div>
            </div>

            {/* Reviews section */}
            {reviewsData && reviewsData.data.length > 0 && (
                <div>
                    <h2 className="text-xl font-heading font-semibold text-gray-900 mb-4">
                        Reviews ({reviewsData.total})
                    </h2>
                    <div className="space-y-4">
                        {reviewsData.data.map((review) => (
                            <div key={review.id} className="bg-white border border-gray-200 rounded-xl p-4">
                                <div className="flex items-center gap-3 mb-2">
                                    <Avatar src={review.reviewer.avatarUrl} name={review.reviewer.name} size="sm" />
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">{review.reviewer.name}</p>
                                        <div className="flex items-center gap-0.5">
                                            {Array.from({ length: 5 }).map((_, i) => (
                                                <Star key={i} className={`w-3 h-3 ${i < review.rating ? 'fill-amber-400 text-amber-400' : 'text-gray-200'}`} />
                                            ))}
                                        </div>
                                    </div>
                                    <span className="ml-auto text-xs text-gray-400">
                                        {new Date(review.createdAt).toLocaleDateString()}
                                    </span>
                                </div>
                                {review.body && <p className="text-sm text-gray-700">{review.body}</p>}
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}
