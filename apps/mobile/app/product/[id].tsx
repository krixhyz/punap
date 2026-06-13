import { useState, useRef } from 'react';
import {
    View, Text, ScrollView, TouchableOpacity, FlatList, ActivityIndicator,
    Modal, TextInput, Alert, Dimensions,
} from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { Image } from 'expo-image';
import * as WebBrowser from 'expo-web-browser';
import * as Haptics from 'expo-haptics';
import { useProduct, useToggleWishlist } from '../../src/api/products';
import { useProductReviews } from '../../src/api/reviews';
import { useAuthStore } from '../../src/store/authSlice';
import { useCreateOrder, useInitiateOrderPayment } from '../../src/api/orders';
import { useBookRental, useInitiateRentalPayment } from '../../src/api/rentals';
import { useMyProducts } from '../../src/api/products';
import { useCreateSwapRequest } from '../../src/api/swaps';
import { StatusBadge } from '../../src/components/StatusBadge';
import { ErrorBoundary } from '../../src/components/ErrorBoundary';
import { Skeleton } from '../../src/components/Skeleton';

const { width: SCREEN_WIDTH } = Dimensions.get('window');

const DEEP_LINK_CALLBACK = 'punap://payment/callback';

function ImageGallery({ images }: { images: string[] }) {
    const [activeIndex, setActiveIndex] = useState(0);
    const listRef = useRef<FlatList>(null);

    return (
        <View>
            <FlatList
                ref={listRef}
                data={images.length > 0 ? images : ['placeholder']}
                horizontal
                pagingEnabled
                showsHorizontalScrollIndicator={false}
                keyExtractor={(_, i) => String(i)}
                onMomentumScrollEnd={(e) => {
                    setActiveIndex(Math.round(e.nativeEvent.contentOffset.x / SCREEN_WIDTH));
                }}
                renderItem={({ item }) => (
                    <Image
                        source={{ uri: item !== 'placeholder' ? item : 'https://via.placeholder.com/400x300' }}
                        style={{ width: SCREEN_WIDTH, height: 280 }}
                        contentFit="cover"
                    />
                )}
            />
            {images.length > 1 ? (
                <View className="absolute bottom-3 left-0 right-0 flex-row justify-center gap-1.5">
                    {images.map((_, i) => (
                        <View key={i} style={{ width: i === activeIndex ? 20 : 6, height: 6, borderRadius: 3, backgroundColor: i === activeIndex ? '#fff' : 'rgba(255,255,255,0.5)' }} />
                    ))}
                </View>
            ) : null}
        </View>
    );
}

function BuyCTA({ productId, price }: { productId: string; price: number }) {
    const router = useRouter();
    const { mutateAsync: createOrder, isPending: creatingOrder } = useCreateOrder();
    const { mutateAsync: initiatePayment, isPending: initiatingPayment } = useInitiateOrderPayment();
    const loading = creatingOrder || initiatingPayment;

    async function handleBuy() {
        try {
            const order = await createOrder({ productId, quantity: 1 });
            const { paymentUrl } = await initiatePayment({ orderId: order.id });
            const result = await WebBrowser.openAuthSessionAsync(paymentUrl, DEEP_LINK_CALLBACK);
            if (result.type === 'success' || result.type === 'dismiss') {
                router.push(`/order/${order.id}`);
            }
        } catch (err: unknown) {
            const msg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message;
            Alert.alert('Error', msg ?? 'Failed to initiate purchase');
        }
    }

    return (
        <View className="p-4 bg-card border-t border-gray-100">
            <View className="flex-row items-center justify-between mb-3">
                <Text className="text-text-secondary text-sm">Price</Text>
                <Text className="text-2xl font-bold text-primary" style={{ fontFamily: 'Outfit_700Bold' }}>
                    Rs. {price.toLocaleString()}
                </Text>
            </View>
            <TouchableOpacity
                className="bg-primary rounded-xl py-4 items-center"
                onPress={handleBuy}
                disabled={loading}
            >
                {loading ? <ActivityIndicator color="#fff" /> : (
                    <Text className="text-white font-semibold text-base">Buy Now</Text>
                )}
            </TouchableOpacity>
        </View>
    );
}

function RentCTA({ productId, rentFare, rentType }: { productId: string; rentFare: number; rentType: string }) {
    const router = useRouter();
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');
    const [showForm, setShowForm] = useState(false);
    const { mutateAsync: bookRental, isPending: booking } = useBookRental();
    const { mutateAsync: initiatePayment, isPending: initiating } = useInitiateRentalPayment();

    async function handleBook() {
        if (!startDate || !endDate) { Alert.alert('Error', 'Please enter start and end dates'); return; }
        try {
            const rental = await bookRental({ productId, startDate, endDate });
            const { paymentUrl } = await initiatePayment({ rentalId: rental.id });
            const result = await WebBrowser.openAuthSessionAsync(paymentUrl, DEEP_LINK_CALLBACK);
            if (result.type === 'success' || result.type === 'dismiss') {
                router.push(`/rental/${rental.id}`);
            }
        } catch (err: unknown) {
            const msg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message;
            Alert.alert('Error', msg ?? 'Booking failed');
        }
    }

    if (!showForm) {
        return (
            <View className="p-4 bg-card border-t border-gray-100">
                <View className="flex-row items-center justify-between mb-3">
                    <Text className="text-text-secondary text-sm">Rent</Text>
                    <Text className="text-xl font-bold text-accent-rent" style={{ fontFamily: 'Outfit_700Bold' }}>
                        Rs. {rentFare.toLocaleString()}/{rentType?.toLowerCase() ?? 'day'}
                    </Text>
                </View>
                <TouchableOpacity className="bg-accent-rent rounded-xl py-4 items-center" onPress={() => setShowForm(true)}>
                    <Text className="text-white font-semibold text-base">Book Now</Text>
                </TouchableOpacity>
            </View>
        );
    }

    return (
        <View className="p-4 bg-card border-t border-gray-100">
            <Text className="font-semibold text-text-primary mb-3">Select Dates</Text>
            <View className="flex-row gap-2 mb-4">
                <View className="flex-1">
                    <Text className="text-text-secondary text-xs mb-1">Start Date</Text>
                    <TextInput
                        className="border border-gray-200 rounded-xl px-3 py-2.5 text-text-primary text-sm"
                        placeholder="YYYY-MM-DD"
                        value={startDate}
                        onChangeText={setStartDate}
                    />
                </View>
                <View className="flex-1">
                    <Text className="text-text-secondary text-xs mb-1">End Date</Text>
                    <TextInput
                        className="border border-gray-200 rounded-xl px-3 py-2.5 text-text-primary text-sm"
                        placeholder="YYYY-MM-DD"
                        value={endDate}
                        onChangeText={setEndDate}
                    />
                </View>
            </View>
            <TouchableOpacity
                className="bg-accent-rent rounded-xl py-4 items-center"
                onPress={handleBook}
                disabled={booking || initiating}
            >
                {booking || initiating ? <ActivityIndicator color="#fff" /> : (
                    <Text className="text-white font-semibold text-base">Confirm Booking</Text>
                )}
            </TouchableOpacity>
        </View>
    );
}

function SwapCTA({ targetProductId, targetProduct }: { targetProductId: string; targetProduct: { title: string } }) {
    const { user } = useAuthStore();
    const { data: myProducts } = useMyProducts();
    const { mutateAsync: createSwap, isPending } = useCreateSwapRequest();
    const [showModal, setShowModal] = useState(false);
    const [offeredProductId, setOfferedProductId] = useState('');
    const [message, setMessage] = useState('');
    const router = useRouter();

    async function handlePropose() {
        if (!offeredProductId) { Alert.alert('Error', 'Please select a product to offer'); return; }
        try {
            const swap = await createSwap({ productId: targetProductId, offeredProductId, message: message || undefined });
            Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
            setShowModal(false);
            router.push(`/swap/${swap.id}`);
        } catch (err: unknown) {
            const msg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message;
            Alert.alert('Error', msg ?? 'Failed to create swap request');
        }
    }

    const eligibleProducts = myProducts?.filter((p) => p.id !== targetProductId && p.approvalStatus === 'APPROVED') ?? [];

    return (
        <>
            <View className="p-4 bg-card border-t border-gray-100">
                <TouchableOpacity className="bg-accent-swap rounded-xl py-4 items-center" onPress={() => setShowModal(true)}>
                    <Text className="text-white font-semibold text-base">Propose Swap</Text>
                </TouchableOpacity>
            </View>

            <Modal visible={showModal} animationType="slide" presentationStyle="pageSheet" onRequestClose={() => setShowModal(false)}>
                <View className="flex-1 bg-surface">
                    <View className="px-4 pt-6 pb-4 flex-row items-center justify-between border-b border-gray-100">
                        <Text className="text-lg font-bold text-text-primary" style={{ fontFamily: 'Outfit_700Bold' }}>
                            Propose Swap
                        </Text>
                        <TouchableOpacity onPress={() => setShowModal(false)}>
                            <Text className="text-text-muted text-2xl">✕</Text>
                        </TouchableOpacity>
                    </View>

                    <ScrollView className="flex-1 px-4 pt-4">
                        <Text className="text-text-secondary text-sm mb-1">Swapping for</Text>
                        <Text className="font-semibold text-text-primary mb-4">{targetProduct.title}</Text>

                        <Text className="text-text-secondary text-sm mb-2">Offer your product</Text>
                        {eligibleProducts.length === 0 ? (
                            <Text className="text-text-muted text-sm mb-4">You have no approved products to offer.</Text>
                        ) : eligibleProducts.map((p) => (
                            <TouchableOpacity
                                key={p.id}
                                onPress={() => setOfferedProductId(p.id)}
                                className="flex-row items-center p-3 mb-2 rounded-xl border"
                                style={{ borderColor: offeredProductId === p.id ? '#1A6B3C' : '#E5E7EB', backgroundColor: offeredProductId === p.id ? '#E8F5EE' : '#fff' }}
                            >
                                <Image source={{ uri: p.images?.[0] }} style={{ width: 48, height: 48, borderRadius: 8, marginRight: 12 }} contentFit="cover" />
                                <View className="flex-1">
                                    <Text className="font-medium text-text-primary text-sm">{p.title}</Text>
                                    <Text className="text-text-secondary text-xs">Rs. {p.price.toLocaleString()}</Text>
                                </View>
                                {offeredProductId === p.id ? <Text className="text-primary">✓</Text> : null}
                            </TouchableOpacity>
                        ))}

                        <Text className="text-text-secondary text-sm mb-2 mt-2">Message (optional)</Text>
                        <TextInput
                            className="border border-gray-200 rounded-xl px-4 py-3 text-text-primary text-sm mb-6"
                            placeholder="Add a message..."
                            value={message}
                            onChangeText={setMessage}
                            multiline
                            numberOfLines={3}
                        />
                    </ScrollView>

                    <View className="px-4 pb-8 pt-2 border-t border-gray-100">
                        <TouchableOpacity
                            className="bg-accent-swap rounded-xl py-4 items-center"
                            onPress={handlePropose}
                            disabled={isPending}
                        >
                            {isPending ? <ActivityIndicator color="#fff" /> : (
                                <Text className="text-white font-semibold text-base">Send Swap Request</Text>
                            )}
                        </TouchableOpacity>
                    </View>
                </View>
            </Modal>
        </>
    );
}

function ProductDetailContent() {
    const { id } = useLocalSearchParams<{ id: string }>();
    const { data: product, isLoading } = useProduct(id);
    const { data: reviewsData } = useProductReviews(id);
    const { mutate: toggleWishlist } = useToggleWishlist();
    const [activeTab, setActiveTab] = useState(0);

    if (isLoading) {
        return (
            <View className="flex-1 bg-surface">
                <Skeleton height={280} style={{ borderRadius: 0 }} />
                <View className="p-4">
                    <Skeleton height={22} style={{ marginBottom: 8 }} />
                    <Skeleton width="60%" height={16} style={{ marginBottom: 16 }} />
                    <Skeleton height={60} />
                </View>
            </View>
        );
    }

    if (!product) return null;

    const reviews = reviewsData?.data ?? [];
    const avgRating = reviews.length > 0 ? reviews.reduce((sum, r) => sum + r.rating, 0) / reviews.length : 0;

    const tabs = product.transactionTypes.map((type, i) => ({ type, i }));

    function renderCTA() {
        const currentType = tabs[activeTab]?.type;
        if (currentType === 'BUY') return <BuyCTA productId={product!.id} price={product!.price} />;
        if (currentType === 'RENT') return (
            <RentCTA
                productId={product!.id}
                rentFare={product!.rentFare ?? 0}
                rentType={product!.rentType ?? 'DAY'}
            />
        );
        if (currentType === 'SWAP') return <SwapCTA targetProductId={product!.id} targetProduct={product!} />;
        return null;
    }

    return (
        <View className="flex-1 bg-surface">
            <ScrollView showsVerticalScrollIndicator={false}>
                {/* Images */}
                <ImageGallery images={product.images} />

                {/* Wishlist */}
                <TouchableOpacity
                    className="absolute top-4 right-4 w-10 h-10 bg-white rounded-full items-center justify-center"
                    style={{ shadowColor: '#000', shadowOpacity: 0.15, shadowRadius: 4, elevation: 3 }}
                    onPress={() => { Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light); toggleWishlist(product.id); }}
                >
                    <Text>{product.wishlisted ? '❤️' : '🤍'}</Text>
                </TouchableOpacity>

                <View className="p-4">
                    {/* Title + condition */}
                    <View className="flex-row items-start justify-between mb-2">
                        <Text className="text-xl font-bold text-text-primary flex-1 mr-3" style={{ fontFamily: 'Outfit_700Bold' }}>
                            {product.title}
                        </Text>
                        <StatusBadge status={product.condition} size="md" />
                    </View>

                    {/* Location */}
                    {(product.province || product.city) ? (
                        <Text className="text-text-secondary text-sm mb-3">
                            📍 {[product.city?.name, product.province?.name].filter(Boolean).join(', ')}
                        </Text>
                    ) : null}

                    {/* Transaction type tabs */}
                    {tabs.length > 1 ? (
                        <View className="flex-row bg-gray-100 rounded-xl p-1 mb-4">
                            {tabs.map(({ type }, i) => (
                                <TouchableOpacity
                                    key={type}
                                    className="flex-1 py-2 rounded-lg items-center"
                                    style={{ backgroundColor: activeTab === i ? '#fff' : 'transparent' }}
                                    onPress={() => setActiveTab(i)}
                                >
                                    <Text style={{ color: activeTab === i ? '#1A6B3C' : '#6B7280', fontSize: 13, fontWeight: activeTab === i ? '600' : '400' }}>
                                        {type}
                                    </Text>
                                </TouchableOpacity>
                            ))}
                        </View>
                    ) : null}

                    {/* Description */}
                    <Text className="text-text-secondary text-sm leading-5 mb-4">{product.description}</Text>

                    {/* Seller card */}
                    <View className="bg-card rounded-xl p-4 mb-4" style={{ shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 4, elevation: 1 }}>
                        <Text className="font-semibold text-text-primary mb-2">Seller</Text>
                        <View className="flex-row items-center">
                            <View className="w-10 h-10 bg-primary-light rounded-full items-center justify-center mr-3">
                                <Text className="text-primary font-bold">{product.seller.name[0]}</Text>
                            </View>
                            <View className="flex-1">
                                <Text className="font-medium text-text-primary">{product.seller.name}</Text>
                                <Text className="text-eco-gold text-xs">🌿 {product.seller.ecoLevel ?? 'NONE'}</Text>
                            </View>
                        </View>
                    </View>

                    {/* Reviews */}
                    {reviews.length > 0 ? (
                        <View className="mb-4">
                            <Text className="font-semibold text-text-primary mb-2">Reviews ({reviews.length})</Text>
                            {avgRating > 0 ? (
                                <Text className="text-eco-gold mb-3">{'★'.repeat(Math.round(avgRating))}{'☆'.repeat(5 - Math.round(avgRating))} ({avgRating.toFixed(1)})</Text>
                            ) : null}
                            {reviews.slice(0, 3).map((r) => (
                                <View key={r.id} className="bg-card rounded-xl p-3 mb-2">
                                    <Text className="font-medium text-text-primary text-sm">{r.reviewer.name}</Text>
                                    <Text className="text-eco-gold text-xs">{'★'.repeat(r.rating)}</Text>
                                    {r.body ? <Text className="text-text-secondary text-sm mt-1">{r.body}</Text> : null}
                                </View>
                            ))}
                        </View>
                    ) : null}
                </View>
            </ScrollView>

            {/* Sticky CTA */}
            {renderCTA()}
        </View>
    );
}

export default function ProductDetailScreen() {
    return <ErrorBoundary><ProductDetailContent /></ErrorBoundary>;
}
