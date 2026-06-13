import { useState } from 'react';
import { View, Text, FlatList, TouchableOpacity, ActivityIndicator } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useRouter } from 'expo-router';
import { useOrders, useSellingOrders } from '../../src/api/orders';
import { useRentals, useLendingRentals } from '../../src/api/rentals';
import { useSwaps } from '../../src/api/swaps';
import { StatusBadge } from '../../src/components/StatusBadge';
import { EmptyState } from '../../src/components/EmptyState';
import { ErrorBoundary } from '../../src/components/ErrorBoundary';
import { Image } from 'expo-image';

type MainTab = 'Orders' | 'Rentals' | 'Swaps';
type SubTab = 'Buying' | 'Selling' | 'Renting' | 'Lending' | 'All';

function ActivityContent() {
    const router = useRouter();
    const [mainTab, setMainTab] = useState<MainTab>('Orders');
    const [subTab, setSubTab] = useState<SubTab>('Buying');

    const { data: buyingOrders, isLoading: loadingOrders } = useOrders();
    const { data: sellingOrders, isLoading: loadingSelling } = useSellingOrders();
    const { data: rentingData, isLoading: loadingRenting } = useRentals();
    const { data: lendingData, isLoading: loadingLending } = useLendingRentals();
    const { data: swapsData, isLoading: loadingSwaps } = useSwaps();

    const mainTabs: MainTab[] = ['Orders', 'Rentals', 'Swaps'];

    function getSubTabs(): SubTab[] {
        if (mainTab === 'Orders') return ['Buying', 'Selling'];
        if (mainTab === 'Rentals') return ['Renting', 'Lending'];
        return ['All'];
    }

    function getItems() {
        if (mainTab === 'Orders') return subTab === 'Buying' ? (buyingOrders?.data ?? []) : (sellingOrders?.data ?? []);
        if (mainTab === 'Rentals') return subTab === 'Renting' ? (rentingData?.data ?? []) : (lendingData?.data ?? []);
        return swapsData?.data ?? [];
    }

    const isLoading = loadingOrders || loadingSelling || loadingRenting || loadingLending || loadingSwaps;
    const items = getItems();

    function handleTabChange(tab: MainTab) {
        setMainTab(tab);
        if (tab === 'Orders') setSubTab('Buying');
        else if (tab === 'Rentals') setSubTab('Renting');
        else setSubTab('All');
    }

    function navigateTo(item: { id: string }) {
        if (mainTab === 'Orders') router.push(`/order/${item.id}`);
        else if (mainTab === 'Rentals') router.push(`/rental/${item.id}`);
        else router.push(`/swap/${item.id}`);
    }

    function renderItem({ item }: { item: Record<string, unknown> }) {
        if (mainTab === 'Orders') {
            const order = item as { id: string; status: string; totalAmount: number; product: { title: string; images: string[] }; createdAt: string };
            return (
                <TouchableOpacity
                    className="bg-card rounded-xl p-4 mb-3 flex-row items-center"
                    style={{ shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 4, elevation: 1 }}
                    onPress={() => navigateTo(order)}
                >
                    <Image
                        source={{ uri: order.product?.images?.[0] ?? '' }}
                        style={{ width: 56, height: 56, borderRadius: 10, marginRight: 12 }}
                        contentFit="cover"
                    />
                    <View className="flex-1">
                        <Text className="text-text-primary font-medium text-sm mb-1" numberOfLines={1}>
                            {order.product?.title}
                        </Text>
                        <Text className="text-text-secondary text-xs mb-1">
                            Rs. {(order.totalAmount as number).toLocaleString()}
                        </Text>
                        <StatusBadge status={order.status} />
                    </View>
                    <Text className="text-text-muted text-xs">›</Text>
                </TouchableOpacity>
            );
        }

        if (mainTab === 'Rentals') {
            const rental = item as { id: string; status: string; totalAmount: number; product: { title: string; images: string[] }; startDate: string; endDate: string };
            return (
                <TouchableOpacity
                    className="bg-card rounded-xl p-4 mb-3 flex-row items-center"
                    style={{ shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 4, elevation: 1 }}
                    onPress={() => navigateTo(rental)}
                >
                    <Image
                        source={{ uri: rental.product?.images?.[0] ?? '' }}
                        style={{ width: 56, height: 56, borderRadius: 10, marginRight: 12 }}
                        contentFit="cover"
                    />
                    <View className="flex-1">
                        <Text className="text-text-primary font-medium text-sm mb-1" numberOfLines={1}>
                            {rental.product?.title}
                        </Text>
                        <Text className="text-text-secondary text-xs mb-1">
                            {new Date(rental.startDate).toLocaleDateString()} – {new Date(rental.endDate).toLocaleDateString()}
                        </Text>
                        <StatusBadge status={rental.status} />
                    </View>
                    <Text className="text-text-muted text-xs">›</Text>
                </TouchableOpacity>
            );
        }

        // Swaps
        const swap = item as { id: string; status: string; product: { title: string; images: string[] }; offeredProduct: { title: string; images: string[] } };
        return (
            <TouchableOpacity
                className="bg-card rounded-xl p-4 mb-3"
                style={{ shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 4, elevation: 1 }}
                onPress={() => navigateTo(swap)}
            >
                <View className="flex-row items-center mb-2">
                    <Image source={{ uri: swap.product?.images?.[0] ?? '' }} style={{ width: 48, height: 48, borderRadius: 8 }} contentFit="cover" />
                    <Text className="mx-3 text-lg">⇄</Text>
                    <Image source={{ uri: swap.offeredProduct?.images?.[0] ?? '' }} style={{ width: 48, height: 48, borderRadius: 8 }} contentFit="cover" />
                    <View className="flex-1 ml-3">
                        <Text className="text-text-primary text-xs" numberOfLines={1}>{swap.product?.title}</Text>
                        <Text className="text-text-muted text-xs" numberOfLines={1}>{swap.offeredProduct?.title}</Text>
                    </View>
                </View>
                <StatusBadge status={swap.status} />
            </TouchableOpacity>
        );
    }

    return (
        <SafeAreaView className="flex-1 bg-surface" edges={['top']}>
            <View className="px-4 pt-3 pb-2">
                <Text className="text-2xl font-bold text-text-primary" style={{ fontFamily: 'Outfit_700Bold' }}>
                    Activity
                </Text>
            </View>

            {/* Main tabs */}
            <View className="flex-row px-4 mb-3 bg-gray-100 mx-4 rounded-xl p-1">
                {mainTabs.map((tab) => (
                    <TouchableOpacity
                        key={tab}
                        className="flex-1 py-2 rounded-lg items-center"
                        style={{ backgroundColor: mainTab === tab ? '#fff' : 'transparent' }}
                        onPress={() => handleTabChange(tab)}
                    >
                        <Text style={{ color: mainTab === tab ? '#1A6B3C' : '#6B7280', fontSize: 13, fontWeight: mainTab === tab ? '600' : '400' }}>
                            {tab}
                        </Text>
                    </TouchableOpacity>
                ))}
            </View>

            {/* Sub tabs */}
            {getSubTabs().length > 1 ? (
                <View className="flex-row px-4 mb-3 gap-2">
                    {getSubTabs().map((tab) => (
                        <TouchableOpacity
                            key={tab}
                            onPress={() => setSubTab(tab)}
                            className="rounded-full px-4 py-1.5"
                            style={{ backgroundColor: subTab === tab ? '#E8F5EE' : '#F3F4F6' }}
                        >
                            <Text style={{ color: subTab === tab ? '#1A6B3C' : '#6B7280', fontSize: 13, fontWeight: subTab === tab ? '600' : '400' }}>
                                {tab}
                            </Text>
                        </TouchableOpacity>
                    ))}
                </View>
            ) : null}

            {isLoading ? (
                <View className="flex-1 justify-center items-center">
                    <ActivityIndicator size="large" color="#1A6B3C" />
                </View>
            ) : items.length === 0 ? (
                <EmptyState icon="📦" title={`No ${mainTab.toLowerCase()} yet`} message="Your transactions will appear here" />
            ) : (
                // eslint-disable-next-line @typescript-eslint/no-explicit-any
                <FlatList<any>
                    data={items as any[]}
                    keyExtractor={(item) => item.id}
                    contentContainerStyle={{ paddingHorizontal: 16, paddingBottom: 20 }}
                    renderItem={renderItem as any}
                    showsVerticalScrollIndicator={false}
                />
            )}
        </SafeAreaView>
    );
}

export default function ActivityScreen() {
    return <ErrorBoundary><ActivityContent /></ErrorBoundary>;
}
