import { Link, useParams } from 'react-router-dom';
import { Leaf, Package, Star } from 'lucide-react';
import { useProfile, useMyEcoScore } from '../../api/profile';
import { useUserReviews } from '../../api/reviews';
import { useProducts } from '../../api/products';
import { Avatar } from '../../components/Avatar';
import { Badge } from '../../components/Badge';
import { Skeleton } from '../../components/Skeleton';
import { ProductCard } from '../../components/ProductCard';
import { useAuth } from '../../hooks/useAuth';

const ECO_LEVEL_COLOR: Record<string, string> = {
    NONE: '#9ca3af',
    BRONZE: '#cd7f32',
    SILVER: '#9ca3af',
    GOLD: '#f59e0b',
    PLATINUM: '#a855f7',
};

const ECO_SCORE_MAX = 5000;

function EcoScoreRing({ score, level }: { score: number; level: string }) {
    const radius = 54;
    const circumference = 2 * Math.PI * radius;
    const pct = Math.min(score / ECO_SCORE_MAX, 1);
    const dash = pct * circumference;
    const color = ECO_LEVEL_COLOR[level] ?? '#9ca3af';

    return (
        <div className="relative w-32 h-32 mx-auto">
            <svg
                viewBox="0 0 120 120"
                className="w-full h-full -rotate-90"
                aria-hidden
            >
                <circle cx="60" cy="60" r={radius} fill="none" stroke="#e5e7eb" strokeWidth="10" />
                <circle
                    cx="60"
                    cy="60"
                    r={radius}
                    fill="none"
                    stroke={color}
                    strokeWidth="10"
                    strokeDasharray={`${dash} ${circumference}`}
                    strokeLinecap="round"
                    style={{
                        transition: 'stroke-dasharray 0.6s ease',
                    }}
                />
            </svg>
            <div className="absolute inset-0 flex flex-col items-center justify-center">
                <Leaf className="w-4 h-4" style={{ color }} />
                <span className="text-lg font-bold text-gray-800 leading-none mt-0.5">{score.toLocaleString()}</span>
                <span className="text-xs text-gray-500">{level}</span>
            </div>
        </div>
    );
}

export default function ProfilePage() {
    const { userId } = useParams<{ userId: string }>();
    const { user } = useAuth();

    const resolvedId = userId ?? user?.id ?? '';
    const { data: profile, isLoading } = useProfile(resolvedId);
    const { data: ecoData } = useMyEcoScore();
    const { data: reviews } = useUserReviews(resolvedId);
    const { data: listings } = useProducts({ sellerId: resolvedId, limit: 8 });

    if (isLoading) {
        return (
            <div className="max-w-4xl mx-auto px-4 py-8 space-y-6">
                <div className="bg-white border border-gray-200 rounded-2xl p-6 flex gap-6">
                    <Skeleton className="w-24 h-24 rounded-full shrink-0" />
                    <div className="flex-1 space-y-3">
                        <Skeleton variant="text" className="w-40 h-6" />
                        <Skeleton variant="text" className="w-24 h-4" />
                        <Skeleton variant="text" className="w-32 h-4" />
                    </div>
                </div>
            </div>
        );
    }

    if (!profile) {
        return (
            <div className="max-w-4xl mx-auto px-4 py-16 text-center text-gray-500">
                <p>User not found.</p>
            </div>
        );
    }

    const avgRating =
        reviews && reviews.data.length > 0
            ? reviews.data.reduce((sum, r) => sum + r.rating, 0) / reviews.data.length
            : null;

    const isOwnProfile = user?.id === profile.id;

    return (
        <div className="max-w-4xl mx-auto px-4 py-8 space-y-8">
            {/* Profile card */}
            <div className="bg-white border border-gray-200 rounded-2xl p-6 flex flex-col sm:flex-row gap-6 items-center sm:items-start">
                <Avatar src={profile.avatarUrl} name={profile.name} size="xl" />

                <div className="flex-1 text-center sm:text-left">
                    <h1 className="text-2xl font-heading font-bold text-gray-900">{profile.name}</h1>
                    {profile.city && (
                        <p className="text-sm text-gray-500 mt-1">
                            {[profile.city.name, profile.province?.name].filter(Boolean).join(', ')}
                        </p>
                    )}
                    <p className="text-xs text-gray-400 mt-1">
                        Member since {new Date(profile.createdAt).toLocaleDateString('en-US', { year: 'numeric', month: 'long' })}
                    </p>
                    {avgRating !== null && (
                        <div className="flex items-center justify-center sm:justify-start gap-1 mt-2">
                            {Array.from({ length: 5 }).map((_, i) => (
                                <Star
                                    key={i}
                                    className={`w-3.5 h-3.5 ${i < Math.round(avgRating) ? 'fill-amber-400 text-amber-400' : 'text-gray-200'}`}
                                />
                            ))}
                            <span className="text-xs text-gray-600 ml-1">{avgRating.toFixed(1)}</span>
                        </div>
                    )}
                    {isOwnProfile && (
                        <div className="mt-4 flex gap-2 justify-center sm:justify-start">
                            <Link
                                to="/settings/profile"
                                className="text-sm text-[#1a6b3c] border border-[#1a6b3c] px-4 py-1.5 rounded-lg hover:bg-[#e8f5ee] transition-colors"
                            >
                                Edit profile
                            </Link>
                            <Link
                                to="/settings/listings"
                                className="text-sm text-gray-600 border border-gray-300 px-4 py-1.5 rounded-lg hover:bg-gray-50 transition-colors"
                            >
                                My listings
                            </Link>
                        </div>
                    )}
                </div>

                {/* Eco score ring */}
                <div className="shrink-0 text-center">
                    <EcoScoreRing
                        score={profile.totalEcoScore ?? 0}
                        level={profile.ecoLevel ?? 'NONE'}
                    />
                    <p className="text-xs text-gray-500 mt-1">Eco Score</p>
                    {ecoData && isOwnProfile && ecoData.history.length > 0 && (
                        <p className="text-xs text-[#1a6b3c] mt-0.5">
                            +{ecoData.history[0]?.ecoPoints} recently
                        </p>
                    )}
                </div>
            </div>

            {/* Active listings */}
            {listings && listings.data.length > 0 && (
                <section>
                    <div className="flex items-center gap-2 mb-4">
                        <Package className="w-5 h-5 text-gray-600" />
                        <h2 className="text-lg font-heading font-semibold text-gray-900">
                            Active listings ({listings.total})
                        </h2>
                    </div>
                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                        {listings.data.map((product) => (
                            <ProductCard key={product.id} product={product} />
                        ))}
                    </div>
                </section>
            )}

            {/* Reviews */}
            {reviews && reviews.data.length > 0 && (
                <section>
                    <h2 className="text-lg font-heading font-semibold text-gray-900 mb-4">
                        Reviews received ({reviews.total})
                    </h2>
                    <div className="space-y-3">
                        {reviews.data.map((review) => (
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
                                    <div className="ml-auto flex items-center gap-2">
                                        <Badge variant="neutral" size="sm">{review.transactionType}</Badge>
                                        <span className="text-xs text-gray-400">
                                            {new Date(review.createdAt).toLocaleDateString()}
                                        </span>
                                    </div>
                                </div>
                                {review.body && <p className="text-sm text-gray-700">{review.body}</p>}
                            </div>
                        ))}
                    </div>
                </section>
            )}
        </div>
    );
}
