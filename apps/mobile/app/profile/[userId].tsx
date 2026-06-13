import { View, Text, ScrollView, ActivityIndicator, FlatList } from 'react-native';
import { useLocalSearchParams } from 'expo-router';
import { Image } from 'expo-image';
import { useProfile } from '../../src/api/profile';
import { useUserReviews } from '../../src/api/reviews';
import { useProducts } from '../../src/api/products';
import { ProductCard } from '../../src/components/ProductCard';
import { ErrorBoundary } from '../../src/components/ErrorBoundary';

const ECO_LEVELS: Record<string, string> = {
    NONE: '🌱', BRONZE: '🥉', SILVER: '🥈', GOLD: '🥇', PLATINUM: '💎',
};

function PublicProfileContent() {
    const { userId } = useLocalSearchParams<{ userId: string }>();
    const { data: profile, isLoading } = useProfile(userId);
    const { data: reviewsData } = useUserReviews(userId);
    const { data: listings } = useProducts({ sellerId: userId, limit: 10 });

    if (isLoading) return <View className="flex-1 justify-center items-center"><ActivityIndicator size="large" color="#1A6B3C" /></View>;
    if (!profile) return null;

    const reviews = reviewsData?.data ?? [];
    const avgRating = reviews.length > 0 ? reviews.reduce((s, r) => s + r.rating, 0) / reviews.length : 0;

    return (
        <ScrollView className="flex-1 bg-surface" showsVerticalScrollIndicator={false}>
            {/* Header */}
            <View className="items-center px-6 pt-6 pb-4 bg-card">
                {profile.avatarUrl ? (
                    <Image source={{ uri: profile.avatarUrl }} style={{ width: 80, height: 80, borderRadius: 40, marginBottom: 12 }} contentFit="cover" />
                ) : (
                    <View className="w-20 h-20 rounded-full bg-primary-light items-center justify-center mb-3">
                        <Text className="text-primary text-3xl font-bold">{profile.name[0]?.toUpperCase()}</Text>
                    </View>
                )}
                <Text className="text-xl font-bold text-text-primary mb-1" style={{ fontFamily: 'Outfit_700Bold' }}>{profile.name}</Text>
                <View className="flex-row items-center gap-2">
                    <Text>{ECO_LEVELS[profile.ecoLevel] ?? '🌱'}</Text>
                    <Text className="text-text-secondary text-sm">{profile.ecoLevel} · {profile.totalEcoScore} eco pts</Text>
                </View>
                {avgRating > 0 ? (
                    <Text className="text-eco-gold mt-1">{'★'.repeat(Math.round(avgRating))} ({avgRating.toFixed(1)})</Text>
                ) : null}
            </View>

            {/* Stats */}
            <View className="flex-row mx-4 mt-4 bg-card rounded-xl overflow-hidden" style={{ shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 4, elevation: 1 }}>
                {[
                    { label: 'Listings', value: listings?.total ?? 0 },
                    { label: 'Reviews', value: reviews.length },
                    { label: 'Member Since', value: new Date(profile.createdAt).getFullYear() },
                ].map((stat, i) => (
                    <View key={stat.label} className={`flex-1 py-4 items-center ${i < 2 ? 'border-r border-gray-100' : ''}`}>
                        <Text className="text-lg font-bold text-text-primary">{stat.value}</Text>
                        <Text className="text-text-muted text-xs">{stat.label}</Text>
                    </View>
                ))}
            </View>

            {/* Active listings */}
            {listings && listings.data.length > 0 ? (
                <View className="mt-4 mb-2">
                    <Text className="px-4 text-base font-semibold text-text-primary mb-2" style={{ fontFamily: 'Outfit_600SemiBold' }}>
                        Active Listings
                    </Text>
                    <FlatList
                        data={listings.data}
                        keyExtractor={(p) => p.id}
                        horizontal
                        showsHorizontalScrollIndicator={false}
                        contentContainerStyle={{ paddingHorizontal: 16, gap: 12 }}
                        renderItem={({ item }) => <View style={{ width: 150 }}><ProductCard product={item} /></View>}
                    />
                </View>
            ) : null}

            {/* Reviews */}
            {reviews.length > 0 ? (
                <View className="mx-4 mt-4 mb-8">
                    <Text className="text-base font-semibold text-text-primary mb-3" style={{ fontFamily: 'Outfit_600SemiBold' }}>
                        Reviews ({reviews.length})
                    </Text>
                    {reviews.slice(0, 5).map((r) => (
                        <View key={r.id} className="bg-card rounded-xl p-4 mb-3" style={{ shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 4, elevation: 1 }}>
                            <View className="flex-row items-center mb-1">
                                <View className="w-8 h-8 bg-primary-light rounded-full items-center justify-center mr-2">
                                    <Text className="text-primary text-xs font-bold">{r.reviewer.name[0]}</Text>
                                </View>
                                <Text className="font-medium text-text-primary text-sm">{r.reviewer.name}</Text>
                                <Text className="ml-auto text-eco-gold text-xs">{'★'.repeat(r.rating)}</Text>
                            </View>
                            {r.body ? <Text className="text-text-secondary text-sm">{r.body}</Text> : null}
                        </View>
                    ))}
                </View>
            ) : null}
        </ScrollView>
    );
}

export default function PublicProfileScreen() {
    return <ErrorBoundary><PublicProfileContent /></ErrorBoundary>;
}
