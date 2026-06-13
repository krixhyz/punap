import { useState } from 'react';
import {
    View, Text, ScrollView, TouchableOpacity, ActivityIndicator, Alert,
    Modal, TextInput,
} from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { Image } from 'expo-image';
import * as WebBrowser from 'expo-web-browser';
import * as Haptics from 'expo-haptics';
import {
    useSwap, useSwapEvents, useAcceptSwap, useRejectSwap, useCancelSwap,
    useConfirmReceived, useCounterOffer, useInitiateSwapPayment,
    type MoneyDirection,
} from '../../src/api/swaps';
import { useAuthStore } from '../../src/store/authSlice';
import { StatusBadge } from '../../src/components/StatusBadge';
import { ErrorBoundary } from '../../src/components/ErrorBoundary';

const DEEP_LINK_CALLBACK = 'punap://payment/callback';

function CounterOfferSheet({
    swapId,
    onClose,
}: { swapId: string; onClose: () => void }) {
    const [offeredAmount, setOfferedAmount] = useState('');
    const [askedAmount, setAskedAmount] = useState('');
    const [moneyDirection, setMoneyDirection] = useState<MoneyDirection>('NONE');
    const [message, setMessage] = useState('');
    const { mutateAsync: counterOffer, isPending } = useCounterOffer();

    async function submit() {
        try {
            await counterOffer({
                id: swapId,
                payload: {
                    offeredAmount: offeredAmount ? Number(offeredAmount) : undefined,
                    askedAmount: askedAmount ? Number(askedAmount) : undefined,
                    moneyDirection,
                    message: message || undefined,
                },
            });
            Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
            onClose();
        } catch {
            Alert.alert('Error', 'Failed to submit counter offer');
        }
    }

    const DIRECTIONS: { value: MoneyDirection; label: string }[] = [
        { value: 'NONE', label: 'No cash difference' },
        { value: 'OWNER_ASKS_CASH', label: 'I (owner) want cash' },
        { value: 'REQUESTER_OFFERS_CASH', label: 'Requester adds cash' },
    ];

    return (
        <Modal visible animationType="slide" presentationStyle="pageSheet" onRequestClose={onClose}>
            <View className="flex-1 bg-surface">
                <View className="px-4 pt-6 pb-4 flex-row items-center justify-between border-b border-gray-100">
                    <Text className="text-lg font-bold text-text-primary" style={{ fontFamily: 'Outfit_700Bold' }}>Counter Offer</Text>
                    <TouchableOpacity onPress={onClose}><Text className="text-text-muted text-2xl">✕</Text></TouchableOpacity>
                </View>
                <ScrollView className="flex-1 px-4 pt-4">
                    <Text className="font-medium text-text-primary mb-2">Cash Direction</Text>
                    {DIRECTIONS.map((d) => (
                        <TouchableOpacity
                            key={d.value}
                            onPress={() => setMoneyDirection(d.value)}
                            className="flex-row items-center p-3 mb-2 rounded-xl border"
                            style={{ borderColor: moneyDirection === d.value ? '#1A6B3C' : '#E5E7EB', backgroundColor: moneyDirection === d.value ? '#E8F5EE' : '#fff' }}
                        >
                            <View className="w-5 h-5 rounded-full border-2 items-center justify-center mr-3" style={{ borderColor: moneyDirection === d.value ? '#1A6B3C' : '#9CA3AF' }}>
                                {moneyDirection === d.value ? <View className="w-2.5 h-2.5 bg-primary rounded-full" /> : null}
                            </View>
                            <Text style={{ color: moneyDirection === d.value ? '#1A6B3C' : '#374151' }}>{d.label}</Text>
                        </TouchableOpacity>
                    ))}

                    {moneyDirection !== 'NONE' ? (
                        <View className="flex-row gap-3 mb-4">
                            <View className="flex-1">
                                <Text className="text-text-secondary text-xs mb-1">Offered Amount (Rs.)</Text>
                                <TextInput
                                    className="border border-gray-200 rounded-xl px-3 py-2.5 text-text-primary"
                                    placeholder="0"
                                    value={offeredAmount}
                                    onChangeText={setOfferedAmount}
                                    keyboardType="numeric"
                                />
                            </View>
                            <View className="flex-1">
                                <Text className="text-text-secondary text-xs mb-1">Asked Amount (Rs.)</Text>
                                <TextInput
                                    className="border border-gray-200 rounded-xl px-3 py-2.5 text-text-primary"
                                    placeholder="0"
                                    value={askedAmount}
                                    onChangeText={setAskedAmount}
                                    keyboardType="numeric"
                                />
                            </View>
                        </View>
                    ) : null}

                    <Text className="text-text-secondary text-xs mb-1">Message</Text>
                    <TextInput
                        className="border border-gray-200 rounded-xl px-4 py-3 text-text-primary mb-6"
                        placeholder="Add a message..."
                        value={message}
                        onChangeText={setMessage}
                        multiline
                        numberOfLines={3}
                    />
                </ScrollView>
                <View className="px-4 pb-8 pt-2 border-t border-gray-100">
                    <TouchableOpacity className="bg-accent-swap rounded-xl py-4 items-center" onPress={submit} disabled={isPending}>
                        {isPending ? <ActivityIndicator color="#fff" /> : <Text className="text-white font-semibold">Send Counter Offer</Text>}
                    </TouchableOpacity>
                </View>
            </View>
        </Modal>
    );
}

function SwapDetailContent() {
    const { id } = useLocalSearchParams<{ id: string }>();
    const router = useRouter();
    const { data: swap, isLoading, refetch } = useSwap(id);
    const { data: events } = useSwapEvents(id);
    const { mutateAsync: accept, isPending: accepting } = useAcceptSwap();
    const { mutateAsync: reject, isPending: rejecting } = useRejectSwap();
    const { mutateAsync: cancel, isPending: cancelling } = useCancelSwap();
    const { mutateAsync: confirmReceived, isPending: confirming } = useConfirmReceived();
    const { mutateAsync: initiatePayment } = useInitiateSwapPayment();
    const { user } = useAuthStore();
    const [showCounter, setShowCounter] = useState(false);

    if (isLoading) return <View className="flex-1 justify-center items-center"><ActivityIndicator size="large" color="#1A6B3C" /></View>;
    if (!swap) return null;

    const isOwner = user?.id === swap.ownerId;
    const isRequester = user?.id === swap.requesterId;
    const canAct = ['PENDING', 'COUNTERED'].includes(swap.status);

    async function handleAccept() {
        await accept(id);
        Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
        refetch();
    }

    async function handleReject() {
        Alert.alert('Reject Swap', 'Reject this swap request?', [
            { text: 'Cancel', style: 'cancel' },
            { text: 'Reject', style: 'destructive', onPress: async () => { await reject(id); refetch(); } },
        ]);
    }

    async function handleCancel() {
        Alert.alert('Cancel Swap', 'Cancel this swap request?', [
            { text: 'No', style: 'cancel' },
            { text: 'Yes', style: 'destructive', onPress: async () => { await cancel(id); refetch(); } },
        ]);
    }

    async function handleConfirm() {
        await confirmReceived(id);
        Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
        refetch();
    }

    async function handlePayment() {
        try {
            const { paymentUrl } = await initiatePayment({ swapId: id });
            const result = await WebBrowser.openAuthSessionAsync(paymentUrl, DEEP_LINK_CALLBACK);
            if (result.type === 'success' || result.type === 'dismiss') refetch();
        } catch {
            Alert.alert('Error', 'Payment initiation failed');
        }
    }

    function formatEventType(type: string): string {
        return type.replace(/_/g, ' ').toLowerCase().replace(/\b\w/g, (c) => c.toUpperCase());
    }

    return (
        <ScrollView className="flex-1 bg-surface" showsVerticalScrollIndicator={false}>
            {/* Products side-by-side */}
            <View className="bg-card mx-4 mt-4 rounded-2xl p-4" style={{ shadowColor: '#000', shadowOpacity: 0.06, shadowRadius: 8, elevation: 2 }}>
                <Text className="font-semibold text-text-primary mb-3">Swap Details</Text>
                <View className="flex-row items-center">
                    <View className="flex-1 items-center">
                        <Image source={{ uri: swap.product.images?.[0] }} style={{ width: 80, height: 80, borderRadius: 12, marginBottom: 8 }} contentFit="cover" />
                        <Text className="text-center text-sm text-text-primary font-medium" numberOfLines={2}>{swap.product.title}</Text>
                        <Text className="text-center text-xs text-text-secondary">{swap.owner.name}</Text>
                    </View>
                    <Text className="text-2xl mx-4 text-text-muted">⇄</Text>
                    <View className="flex-1 items-center">
                        <Image source={{ uri: swap.offeredProduct.images?.[0] }} style={{ width: 80, height: 80, borderRadius: 12, marginBottom: 8 }} contentFit="cover" />
                        <Text className="text-center text-sm text-text-primary font-medium" numberOfLines={2}>{swap.offeredProduct.title}</Text>
                        <Text className="text-center text-xs text-text-secondary">{swap.requester.name}</Text>
                    </View>
                </View>
                <View className="mt-3 items-center">
                    <StatusBadge status={swap.status} size="md" />
                </View>

                {/* Cash difference */}
                {swap.moneyDirection !== 'NONE' && (swap.offeredAmount || swap.askedAmount) ? (
                    <View className="mt-3 bg-warning/10 rounded-xl p-3">
                        <Text className="text-warning text-sm text-center">
                            {swap.moneyDirection === 'OWNER_ASKS_CASH'
                                ? `Owner asks Rs. ${swap.askedAmount?.toLocaleString()}`
                                : `Requester offers Rs. ${swap.offeredAmount?.toLocaleString()}`}
                        </Text>
                    </View>
                ) : null}
            </View>

            {/* Negotiation timeline */}
            {events && events.length > 0 ? (
                <View className="bg-card mx-4 mt-3 rounded-2xl p-4" style={{ shadowColor: '#000', shadowOpacity: 0.06, shadowRadius: 8, elevation: 2 }}>
                    <Text className="font-semibold text-text-primary mb-3">Negotiation History</Text>
                    {events.map((event) => (
                        <View key={event.id} className="flex-row mb-4">
                            <View className="w-8 h-8 bg-primary-light rounded-full items-center justify-center mr-3 mt-0.5">
                                <Text style={{ fontSize: 14 }}>
                                    {event.type === 'INITIAL_OFFER' ? '📝' : event.type === 'COUNTER_OFFER' ? '🔄' : event.type === 'ACCEPT' ? '✅' : '❌'}
                                </Text>
                            </View>
                            <View className="flex-1">
                                <Text className="text-text-primary font-medium text-sm">{event.actor.name}</Text>
                                <Text className="text-text-secondary text-xs mb-0.5">{formatEventType(event.type)}</Text>
                                {event.message ? <Text className="text-text-secondary text-xs italic">"{event.message}"</Text> : null}
                                <Text className="text-text-muted text-xs mt-1">{new Date(event.createdAt).toLocaleString()}</Text>
                            </View>
                        </View>
                    ))}
                </View>
            ) : null}

            {/* Confirmation status */}
            {swap.status === 'CONFIRMATION_PENDING' && swap.confirmation ? (
                <View className="bg-card mx-4 mt-3 rounded-2xl p-4" style={{ shadowColor: '#000', shadowOpacity: 0.06, shadowRadius: 8, elevation: 2 }}>
                    <Text className="font-semibold text-text-primary mb-2">Confirm Receipt</Text>
                    <View className="flex-row justify-between mb-2">
                        <Text className="text-text-secondary text-sm">Owner confirmed</Text>
                        <Text>{swap.confirmation.ownerConfirmedAt ? '✅' : '⏳'}</Text>
                    </View>
                    <View className="flex-row justify-between">
                        <Text className="text-text-secondary text-sm">Requester confirmed</Text>
                        <Text>{swap.confirmation.requesterConfirmedAt ? '✅' : '⏳'}</Text>
                    </View>
                </View>
            ) : null}

            {/* Actions */}
            <View className="mx-4 mt-3 mb-8 gap-3">
                {swap.status === 'AWAITING_PAYMENT' ? (
                    <TouchableOpacity className="bg-warning rounded-xl py-4 items-center" onPress={handlePayment}>
                        <Text className="text-white font-semibold">Pay Cash Difference</Text>
                    </TouchableOpacity>
                ) : null}

                {swap.status === 'CONFIRMATION_PENDING' ? (
                    <TouchableOpacity className="bg-primary rounded-xl py-4 items-center" onPress={handleConfirm} disabled={confirming}>
                        {confirming ? <ActivityIndicator color="#fff" /> : <Text className="text-white font-semibold">Confirm I Received Item</Text>}
                    </TouchableOpacity>
                ) : null}

                {isOwner && canAct ? (
                    <>
                        <TouchableOpacity className="bg-primary rounded-xl py-4 items-center" onPress={handleAccept} disabled={accepting}>
                            {accepting ? <ActivityIndicator color="#fff" /> : <Text className="text-white font-semibold">Accept Swap</Text>}
                        </TouchableOpacity>
                        <TouchableOpacity className="bg-accent-swap rounded-xl py-4 items-center" onPress={() => setShowCounter(true)}>
                            <Text className="text-white font-semibold">Counter Offer</Text>
                        </TouchableOpacity>
                        <TouchableOpacity className="border border-danger rounded-xl py-4 items-center" onPress={handleReject} disabled={rejecting}>
                            {rejecting ? <ActivityIndicator color="#EF4444" /> : <Text className="text-danger font-semibold">Reject</Text>}
                        </TouchableOpacity>
                    </>
                ) : null}

                {isRequester && canAct ? (
                    <>
                        {swap.status === 'COUNTERED' ? (
                            <>
                                <TouchableOpacity className="bg-primary rounded-xl py-4 items-center" onPress={handleAccept} disabled={accepting}>
                                    {accepting ? <ActivityIndicator color="#fff" /> : <Text className="text-white font-semibold">Accept Counter</Text>}
                                </TouchableOpacity>
                                <TouchableOpacity className="bg-accent-swap rounded-xl py-4 items-center" onPress={() => setShowCounter(true)}>
                                    <Text className="text-white font-semibold">Counter Again</Text>
                                </TouchableOpacity>
                            </>
                        ) : null}
                        <TouchableOpacity className="border border-danger rounded-xl py-4 items-center" onPress={handleCancel} disabled={cancelling}>
                            {cancelling ? <ActivityIndicator color="#EF4444" /> : <Text className="text-danger font-semibold">Cancel Request</Text>}
                        </TouchableOpacity>
                    </>
                ) : null}
            </View>

            {showCounter ? (
                <CounterOfferSheet swapId={id} onClose={() => { setShowCounter(false); refetch(); }} />
            ) : null}
        </ScrollView>
    );
}

export default function SwapDetailScreen() {
    return <ErrorBoundary><SwapDetailContent /></ErrorBoundary>;
}
