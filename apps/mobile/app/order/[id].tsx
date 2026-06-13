import { useState } from 'react';
import { View, Text, ScrollView, TouchableOpacity, ActivityIndicator, Alert, Modal, TextInput } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { Image } from 'expo-image';
import * as Haptics from 'expo-haptics';
import { useOrder, useCancelOrder } from '../../src/api/orders';
import { useCreateReview } from '../../src/api/reviews';
import { useAuthStore } from '../../src/store/authSlice';
import { StatusBadge } from '../../src/components/StatusBadge';
import { ErrorBoundary } from '../../src/components/ErrorBoundary';

const ORDER_STEPS = ['PENDING', 'PAID', 'COMPLETED'];

function ReviewModal({ orderId, subjectId, productId, onClose, onSuccess }: { orderId: string; subjectId: string; productId: string; onClose: () => void; onSuccess: () => void }) {
    const [rating, setRating] = useState(5);
    const [body, setBody] = useState('');
    const { mutateAsync: createReview, isPending } = useCreateReview();

    async function submit() {
        try {
            await createReview({ subjectId, productId, transactionType: 'ORDER', orderId, rating, body });
            Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
            onSuccess();
            onClose();
        } catch {
            Alert.alert('Error', 'Failed to submit review');
        }
    }

    return (
        <Modal visible animationType="slide" presentationStyle="pageSheet" onRequestClose={onClose}>
            <View className="flex-1 bg-surface">
                <View className="px-4 pt-6 pb-4 flex-row items-center justify-between border-b border-gray-100">
                    <Text className="text-lg font-bold text-text-primary" style={{ fontFamily: 'Outfit_700Bold' }}>Write Review</Text>
                    <TouchableOpacity onPress={onClose}><Text className="text-text-muted text-2xl">✕</Text></TouchableOpacity>
                </View>
                <View className="p-4">
                    <Text className="font-medium text-text-primary mb-3">Rating</Text>
                    <View className="flex-row mb-4">
                        {[1, 2, 3, 4, 5].map((star) => (
                            <TouchableOpacity key={star} onPress={() => setRating(star)}>
                                <Text style={{ fontSize: 32, marginRight: 4, color: star <= rating ? '#F59E0B' : '#D1D5DB' }}>★</Text>
                            </TouchableOpacity>
                        ))}
                    </View>
                    <Text className="font-medium text-text-primary mb-2">Comment (optional)</Text>
                    <TextInput
                        className="border border-gray-200 rounded-xl px-4 py-3 text-text-primary mb-6"
                        placeholder="Share your experience..."
                        value={body}
                        onChangeText={setBody}
                        multiline
                        numberOfLines={4}
                    />
                    <TouchableOpacity className="bg-primary rounded-xl py-4 items-center" onPress={submit} disabled={isPending}>
                        {isPending ? <ActivityIndicator color="#fff" /> : <Text className="text-white font-semibold">Submit Review</Text>}
                    </TouchableOpacity>
                </View>
            </View>
        </Modal>
    );
}

function OrderDetailContent() {
    const { id } = useLocalSearchParams<{ id: string }>();
    const { data: order, isLoading, refetch } = useOrder(id);
    const { mutateAsync: cancelOrder, isPending: cancelling } = useCancelOrder();
    const { user } = useAuthStore();
    const [showReview, setShowReview] = useState(false);
    const [reviewed, setReviewed] = useState(false);

    if (isLoading) return <View className="flex-1 justify-center items-center"><ActivityIndicator size="large" color="#1A6B3C" /></View>;
    if (!order) return null;

    const isBuyer = user?.id === order.buyerId;
    const isSeller = user?.id === order.sellerId;
    const stepIndex = ORDER_STEPS.indexOf(order.status);

    async function handleCancel() {
        Alert.alert('Cancel Order', 'Are you sure?', [
            { text: 'No', style: 'cancel' },
            { text: 'Yes, Cancel', style: 'destructive', onPress: async () => { await cancelOrder(id); refetch(); } },
        ]);
    }

    return (
        <ScrollView className="flex-1 bg-surface" showsVerticalScrollIndicator={false}>
            {/* Product snapshot */}
            <View className="bg-card mx-4 mt-4 rounded-2xl p-4 flex-row" style={{ shadowColor: '#000', shadowOpacity: 0.06, shadowRadius: 8, elevation: 2 }}>
                <Image
                    source={{ uri: order.product.images?.[0] }}
                    style={{ width: 72, height: 72, borderRadius: 12, marginRight: 16 }}
                    contentFit="cover"
                />
                <View className="flex-1">
                    <Text className="font-semibold text-text-primary mb-1" numberOfLines={2}>{order.product.title}</Text>
                    <Text className="text-text-secondary text-sm">Qty: {order.quantity}</Text>
                    <Text className="text-primary font-bold mt-1">Rs. {order.totalAmount.toLocaleString()}</Text>
                </View>
            </View>

            {/* Status stepper */}
            <View className="bg-card mx-4 mt-3 rounded-2xl p-4" style={{ shadowColor: '#000', shadowOpacity: 0.06, shadowRadius: 8, elevation: 2 }}>
                <Text className="font-semibold text-text-primary mb-3">Order Status</Text>
                {ORDER_STEPS.map((step, i) => (
                    <View key={step} className="flex-row items-center mb-3">
                        <View
                            className="w-8 h-8 rounded-full items-center justify-center mr-3"
                            style={{ backgroundColor: i <= stepIndex ? '#1A6B3C' : '#F3F4F6' }}
                        >
                            {i < stepIndex ? (
                                <Text style={{ color: '#fff', fontSize: 14 }}>✓</Text>
                            ) : i === stepIndex ? (
                                <View className="w-3 h-3 bg-white rounded-full" />
                            ) : (
                                <View className="w-3 h-3 bg-gray-300 rounded-full" />
                            )}
                        </View>
                        <Text style={{ color: i <= stepIndex ? '#111827' : '#9CA3AF', fontWeight: i === stepIndex ? '600' : '400' }}>
                            {step}
                        </Text>
                    </View>
                ))}
            </View>

            {/* Pricing breakdown */}
            <View className="bg-card mx-4 mt-3 rounded-2xl p-4" style={{ shadowColor: '#000', shadowOpacity: 0.06, shadowRadius: 8, elevation: 2 }}>
                <Text className="font-semibold text-text-primary mb-3">Payment Summary</Text>
                {[
                    { label: 'Subtotal', value: order.subtotal },
                    { label: 'Service Fee', value: order.serviceFee },
                ].map(({ label, value }) => (
                    <View key={label} className="flex-row justify-between mb-2">
                        <Text className="text-text-secondary text-sm">{label}</Text>
                        <Text className="text-text-primary text-sm">Rs. {value.toLocaleString()}</Text>
                    </View>
                ))}
                <View className="border-t border-gray-100 mt-2 pt-2 flex-row justify-between">
                    <Text className="font-semibold text-text-primary">Total</Text>
                    <Text className="font-bold text-primary">Rs. {order.totalAmount.toLocaleString()}</Text>
                </View>
            </View>

            {/* Actions */}
            <View className="mx-4 mt-3 mb-8">
                {order.status === 'PENDING' ? (
                    <TouchableOpacity
                        className="border border-danger rounded-xl py-4 items-center mb-3"
                        onPress={handleCancel}
                        disabled={cancelling}
                    >
                        {cancelling ? <ActivityIndicator color="#EF4444" /> : <Text className="text-danger font-semibold">Cancel Order</Text>}
                    </TouchableOpacity>
                ) : null}

                {order.status === 'COMPLETED' && isBuyer && !reviewed ? (
                    <TouchableOpacity className="bg-eco-gold rounded-xl py-4 items-center" onPress={() => setShowReview(true)}>
                        <Text className="text-white font-semibold">Write a Review</Text>
                    </TouchableOpacity>
                ) : null}
            </View>

            {showReview ? (
                <ReviewModal
                    orderId={id}
                    subjectId={order.sellerId}
                    productId={order.productId}
                    onClose={() => setShowReview(false)}
                    onSuccess={() => setReviewed(true)}
                />
            ) : null}
        </ScrollView>
    );
}

export default function OrderDetailScreen() {
    return <ErrorBoundary><OrderDetailContent /></ErrorBoundary>;
}
