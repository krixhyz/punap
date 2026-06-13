import { useState } from 'react';
import {
    View,
    Text,
    FlatList,
    TouchableOpacity,
    ScrollView,
    RefreshControl,
    TextInput,
} from 'react-native';
import { useRouter } from 'expo-router';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useProducts, useCategories, type ProductFilters } from '../../src/api/products';
import { ProductCard } from '../../src/components/ProductCard';
import { ProductCardSkeleton } from '../../src/components/Skeleton';
import { EmptyState } from '../../src/components/EmptyState';
import { ErrorBoundary } from '../../src/components/ErrorBoundary';

const TRANSACTION_TYPES = ['All', 'BUY', 'RENT', 'SWAP'] as const;

function HomeContent() {
    const router = useRouter();
    const [activeType, setActiveType] = useState<string>('All');
    const [activeCategoryId, setActiveCategoryId] = useState<number | undefined>(undefined);

    const filters: ProductFilters = {
        transactionType: activeType !== 'All' ? activeType : undefined,
        categoryId: activeCategoryId,
        limit: 20,
    };

    const { data, isLoading, refetch, isRefetching } = useProducts(filters);
    const { data: categories } = useCategories();

    const products = data?.data ?? [];

    return (
        <SafeAreaView className="flex-1 bg-surface" edges={['top']}>
            {/* Header */}
            <View className="px-4 pt-2 pb-3">
                <Text className="text-2xl font-bold text-primary" style={{ fontFamily: 'Outfit_700Bold' }}>
                    PUNAP
                </Text>
                <Text className="text-text-secondary text-sm">Circular economy marketplace</Text>
            </View>

            {/* Search bar */}
            <TouchableOpacity
                className="mx-4 mb-3 bg-card rounded-xl px-4 py-3 flex-row items-center border border-gray-100"
                onPress={() => router.push('/(tabs)/search')}
            >
                <Text className="text-text-muted text-sm">🔍  Search products...</Text>
            </TouchableOpacity>

            {/* Transaction type tabs */}
            <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                className="px-4 mb-3"
                contentContainerStyle={{ gap: 8 }}
            >
                {TRANSACTION_TYPES.map((type) => (
                    <TouchableOpacity
                        key={type}
                        onPress={() => setActiveType(type)}
                        className="rounded-full px-4 py-2"
                        style={{
                            backgroundColor: activeType === type ? '#1A6B3C' : '#F3F4F6',
                        }}
                    >
                        <Text style={{ color: activeType === type ? '#fff' : '#6B7280', fontSize: 13, fontWeight: '500' }}>
                            {type === 'All' ? '🏷️ All' : type === 'BUY' ? '🛒 Buy' : type === 'RENT' ? '📅 Rent' : '🔄 Swap'}
                        </Text>
                    </TouchableOpacity>
                ))}
            </ScrollView>

            {/* Category pills */}
            {categories && categories.length > 0 ? (
                <ScrollView
                    horizontal
                    showsHorizontalScrollIndicator={false}
                    className="px-4 mb-3"
                    contentContainerStyle={{ gap: 8 }}
                >
                    <TouchableOpacity
                        onPress={() => setActiveCategoryId(undefined)}
                        className="rounded-full px-3 py-1.5"
                        style={{ backgroundColor: !activeCategoryId ? '#E8F5EE' : '#F3F4F6' }}
                    >
                        <Text style={{ color: !activeCategoryId ? '#1A6B3C' : '#6B7280', fontSize: 12 }}>All</Text>
                    </TouchableOpacity>
                    {categories.map((cat) => (
                        <TouchableOpacity
                            key={cat.id}
                            onPress={() => setActiveCategoryId(cat.id === activeCategoryId ? undefined : cat.id)}
                            className="rounded-full px-3 py-1.5"
                            style={{ backgroundColor: activeCategoryId === cat.id ? '#E8F5EE' : '#F3F4F6' }}
                        >
                            <Text style={{ color: activeCategoryId === cat.id ? '#1A6B3C' : '#6B7280', fontSize: 12 }}>
                                {cat.name}
                            </Text>
                        </TouchableOpacity>
                    ))}
                </ScrollView>
            ) : null}

            {/* Products grid */}
            {isLoading ? (
                <View className="flex-row flex-wrap px-3">
                    {[...Array(6)].map((_, i) => <ProductCardSkeleton key={i} />)}
                </View>
            ) : products.length === 0 ? (
                <EmptyState
                    icon="🛍️"
                    title="No products yet"
                    message="Be the first to list something!"
                />
            ) : (
                <FlatList
                    data={products}
                    keyExtractor={(item) => item.id}
                    numColumns={2}
                    contentContainerStyle={{ paddingHorizontal: 8, paddingBottom: 20 }}
                    renderItem={({ item }) => <ProductCard product={item} />}
                    refreshControl={
                        <RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor="#1A6B3C" />
                    }
                    showsVerticalScrollIndicator={false}
                />
            )}
        </SafeAreaView>
    );
}

export default function HomeScreen() {
    return (
        <ErrorBoundary>
            <HomeContent />
        </ErrorBoundary>
    );
}
