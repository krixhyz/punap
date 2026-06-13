import { useState } from 'react';
import { View, Text, ScrollView, TouchableOpacity, ActivityIndicator, Alert, TextInput, Modal } from 'react-native';
import * as Haptics from 'expo-haptics';
import { useWallet, useWalletLedger, usePayoutRequests, useCreatePayoutRequest } from '../../src/api/wallet';
import { EmptyState } from '../../src/components/EmptyState';
import { ErrorBoundary } from '../../src/components/ErrorBoundary';

function PayoutSheet({ available, onClose }: { available: number; onClose: () => void }) {
    const [amount, setAmount] = useState('');
    const [note, setNote] = useState('');
    const { mutateAsync: createPayout, isPending } = useCreatePayoutRequest();

    async function submit() {
        const num = Number(amount);
        if (!num || num <= 0) { Alert.alert('Error', 'Enter a valid amount'); return; }
        if (num > available) { Alert.alert('Error', `Amount exceeds available balance (Rs. ${available.toLocaleString()})`); return; }
        try {
            await createPayout({ amount: num, note: note || undefined });
            Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
            Alert.alert('Success', 'Payout request submitted');
            onClose();
        } catch (err: unknown) {
            const msg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message;
            Alert.alert('Error', msg ?? 'Failed to request payout');
        }
    }

    return (
        <Modal visible animationType="slide" presentationStyle="pageSheet" onRequestClose={onClose}>
            <View className="flex-1 bg-surface">
                <View className="px-4 pt-6 pb-4 flex-row items-center justify-between border-b border-gray-100">
                    <Text className="text-lg font-bold text-text-primary" style={{ fontFamily: 'Outfit_700Bold' }}>Request Payout</Text>
                    <TouchableOpacity onPress={onClose}><Text className="text-text-muted text-2xl">✕</Text></TouchableOpacity>
                </View>
                <View className="p-4">
                    <Text className="text-text-secondary text-sm mb-1">Available: Rs. {available.toLocaleString()}</Text>
                    <View className="mb-4">
                        <Text className="text-text-secondary text-sm mb-1 font-medium">Amount (Rs.)</Text>
                        <TextInput
                            className="bg-card border border-gray-200 rounded-xl px-4 py-3 text-text-primary"
                            value={amount}
                            onChangeText={setAmount}
                            placeholder="0"
                            keyboardType="numeric"
                        />
                    </View>
                    <View className="mb-6">
                        <Text className="text-text-secondary text-sm mb-1 font-medium">Note (optional)</Text>
                        <TextInput
                            className="bg-card border border-gray-200 rounded-xl px-4 py-3 text-text-primary"
                            value={note}
                            onChangeText={setNote}
                            placeholder="Payout note..."
                        />
                    </View>
                    <TouchableOpacity className="bg-primary rounded-xl py-4 items-center" onPress={submit} disabled={isPending}>
                        {isPending ? <ActivityIndicator color="#fff" /> : <Text className="text-white font-semibold">Submit Request</Text>}
                    </TouchableOpacity>
                </View>
            </View>
        </Modal>
    );
}

function WalletContent() {
    const { data: wallet, isLoading: loadingWallet } = useWallet();
    const { data: ledger, isLoading: loadingLedger } = useWalletLedger();
    const { data: payouts } = usePayoutRequests();
    const [showPayout, setShowPayout] = useState(false);

    if (loadingWallet) return <View className="flex-1 justify-center items-center"><ActivityIndicator size="large" color="#1A6B3C" /></View>;

    const ledgerEntries = ledger?.data ?? [];
    const payoutList = payouts?.data ?? [];

    return (
        <ScrollView className="flex-1 bg-surface" showsVerticalScrollIndicator={false}>
            {/* Balance cards */}
            <View className="px-4 pt-4 gap-3 mb-4">
                <View className="bg-primary rounded-2xl p-5" style={{ shadowColor: '#1A6B3C', shadowOpacity: 0.3, shadowRadius: 12, elevation: 4 }}>
                    <Text className="text-primary-light text-sm mb-1">Available Balance</Text>
                    <Text className="text-white text-3xl font-bold" style={{ fontFamily: 'Outfit_700Bold' }}>
                        Rs. {(wallet?.availableBalance ?? 0).toLocaleString()}
                    </Text>
                    <TouchableOpacity
                        className="mt-4 bg-white/20 rounded-xl py-3 items-center"
                        onPress={() => setShowPayout(true)}
                        disabled={!wallet || wallet.availableBalance <= 0}
                    >
                        <Text className="text-white font-semibold">Request Payout</Text>
                    </TouchableOpacity>
                </View>

                {(wallet?.pendingPayoutBalance ?? 0) > 0 ? (
                    <View className="bg-card rounded-2xl p-4 border border-warning/30">
                        <Text className="text-text-secondary text-sm mb-1">Pending Payout</Text>
                        <Text className="text-warning text-xl font-bold">
                            Rs. {wallet!.pendingPayoutBalance.toLocaleString()}
                        </Text>
                    </View>
                ) : null}
            </View>

            {/* Payout history */}
            {payoutList.length > 0 ? (
                <View className="mx-4 mb-4">
                    <Text className="font-semibold text-text-primary mb-2" style={{ fontFamily: 'Outfit_600SemiBold' }}>Payout Requests</Text>
                    {payoutList.map((p) => (
                        <View key={p.id} className="bg-card rounded-xl p-4 mb-2 flex-row items-center" style={{ shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 4, elevation: 1 }}>
                            <View className="flex-1">
                                <Text className="font-medium text-text-primary">Rs. {p.amount.toLocaleString()}</Text>
                                <Text className="text-text-secondary text-xs">{new Date(p.createdAt).toLocaleDateString()}</Text>
                            </View>
                            <View className="px-3 py-1 rounded-full" style={{ backgroundColor: p.status === 'PAID' ? '#D1FAE5' : p.status === 'REJECTED' ? '#FEE2E2' : '#FEF3C7' }}>
                                <Text style={{ fontSize: 11, color: p.status === 'PAID' ? '#065F46' : p.status === 'REJECTED' ? '#991B1B' : '#92400E' }}>
                                    {p.status}
                                </Text>
                            </View>
                        </View>
                    ))}
                </View>
            ) : null}

            {/* Ledger */}
            <View className="mx-4 mb-8">
                <Text className="font-semibold text-text-primary mb-2" style={{ fontFamily: 'Outfit_600SemiBold' }}>Transaction History</Text>
                {ledgerEntries.length === 0 ? (
                    <EmptyState icon="📒" title="No transactions yet" />
                ) : ledgerEntries.map((entry) => (
                    <View key={entry.id} className="bg-card rounded-xl p-4 mb-2 flex-row items-center" style={{ shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 4, elevation: 1 }}>
                        <View
                            className="w-10 h-10 rounded-full items-center justify-center mr-3"
                            style={{ backgroundColor: entry.direction === 'CREDIT' ? '#D1FAE5' : '#FEE2E2' }}
                        >
                            <Text style={{ fontSize: 16 }}>{entry.direction === 'CREDIT' ? '↑' : '↓'}</Text>
                        </View>
                        <View className="flex-1">
                            <Text className="font-medium text-text-primary text-sm">{entry.entryType.replace(/_/g, ' ')}</Text>
                            <Text className="text-text-muted text-xs">{new Date(entry.createdAt).toLocaleDateString()}</Text>
                        </View>
                        <Text
                            className="font-semibold"
                            style={{ color: entry.direction === 'CREDIT' ? '#10B981' : '#EF4444' }}
                        >
                            {entry.direction === 'CREDIT' ? '+' : '-'}Rs. {entry.amount.toLocaleString()}
                        </Text>
                    </View>
                ))}
            </View>

            {showPayout && wallet ? (
                <PayoutSheet available={wallet.availableBalance} onClose={() => setShowPayout(false)} />
            ) : null}
        </ScrollView>
    );
}

export default function WalletScreen() {
    return <ErrorBoundary><WalletContent /></ErrorBoundary>;
}
