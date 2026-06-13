import { useState } from 'react';
import { View, Text, ScrollView, TouchableOpacity, ActivityIndicator, Alert } from 'react-native';
import { useLocalSearchParams } from 'expo-router';
import { Image } from 'expo-image';
import * as ImagePicker from 'expo-image-picker';
import * as Haptics from 'expo-haptics';
import { useRental, useRequestReturn, useConfirmReturn } from '../../src/api/rentals';
import { useAuthStore } from '../../src/store/authSlice';
import { StatusBadge } from '../../src/components/StatusBadge';
import { ErrorBoundary } from '../../src/components/ErrorBoundary';

const RENTAL_STEPS = ['PENDING_PAYMENT', 'ACTIVE', 'RETURN_REQUESTED', 'COMPLETED'];

function RentalDetailContent() {
    const { id } = useLocalSearchParams<{ id: string }>();
    const { data: rental, isLoading, refetch } = useRental(id);
    const { mutateAsync: requestReturn, isPending: returning } = useRequestReturn();
    const { mutateAsync: confirmReturn, isPending: confirming } = useConfirmReturn();
    const { user } = useAuthStore();

    if (isLoading) return <View className="flex-1 justify-center items-center"><ActivityIndicator size="large" color="#1A6B3C" /></View>;
    if (!rental) return null;

    const isRenter = user?.id === rental.renterId;
    const isOwner = user?.id === rental.ownerId;
    const stepIndex = RENTAL_STEPS.indexOf(rental.status);

    async function handleRequestReturn() {
        const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
        if (!permission.granted) { Alert.alert('Permission required', 'Allow access to your photos'); return; }

        const result = await ImagePicker.launchImageLibraryAsync({
            mediaTypes: ImagePicker.MediaTypeOptions.Images,
            allowsMultipleSelection: true,
            quality: 0.8,
        });

        if (result.canceled) return;
        const photos = result.assets.map((a) => ({ uri: a.uri, name: a.fileName ?? 'photo.jpg', type: a.mimeType ?? 'image/jpeg' }));

        try {
            await requestReturn({ rentalId: id, photos });
            Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
            refetch();
        } catch (err: unknown) {
            const msg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message;
            Alert.alert('Error', msg ?? 'Return request failed');
        }
    }

    async function handleConfirmReturn() {
        Alert.alert('Confirm Return', 'Confirm you have received the item back?', [
            { text: 'Cancel', style: 'cancel' },
            {
                text: 'Confirm',
                onPress: async () => {
                    await confirmReturn(id);
                    Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
                    refetch();
                },
            },
        ]);
    }

    return (
        <ScrollView className="flex-1 bg-surface" showsVerticalScrollIndicator={false}>
            {/* Product snapshot */}
            <View className="bg-card mx-4 mt-4 rounded-2xl p-4 flex-row" style={{ shadowColor: '#000', shadowOpacity: 0.06, shadowRadius: 8, elevation: 2 }}>
                <Image
                    source={{ uri: rental.product.images?.[0] }}
                    style={{ width: 72, height: 72, borderRadius: 12, marginRight: 16 }}
                    contentFit="cover"
                />
                <View className="flex-1">
                    <Text className="font-semibold text-text-primary mb-1" numberOfLines={2}>{rental.product.title}</Text>
                    <Text className="text-text-secondary text-sm">
                        {new Date(rental.startDate).toLocaleDateString()} – {new Date(rental.endDate).toLocaleDateString()}
                    </Text>
                    <StatusBadge status={rental.status} size="md" />
                </View>
            </View>

            {/* Status stepper */}
            <View className="bg-card mx-4 mt-3 rounded-2xl p-4" style={{ shadowColor: '#000', shadowOpacity: 0.06, shadowRadius: 8, elevation: 2 }}>
                <Text className="font-semibold text-text-primary mb-3">Rental Status</Text>
                {RENTAL_STEPS.map((step, i) => (
                    <View key={step} className="flex-row items-center mb-3">
                        <View
                            className="w-8 h-8 rounded-full items-center justify-center mr-3"
                            style={{ backgroundColor: i <= stepIndex ? '#1A6B3C' : '#F3F4F6' }}
                        >
                            {i < stepIndex ? (
                                <Text style={{ color: '#fff', fontSize: 14 }}>✓</Text>
                            ) : (
                                <View className={`w-3 h-3 rounded-full ${i === stepIndex ? 'bg-white' : 'bg-gray-300'}`} />
                            )}
                        </View>
                        <Text style={{ color: i <= stepIndex ? '#111827' : '#9CA3AF', fontWeight: i === stepIndex ? '600' : '400' }}>
                            {step.replace(/_/g, ' ')}
                        </Text>
                    </View>
                ))}
            </View>

            {/* Pricing */}
            <View className="bg-card mx-4 mt-3 rounded-2xl p-4" style={{ shadowColor: '#000', shadowOpacity: 0.06, shadowRadius: 8, elevation: 2 }}>
                <Text className="font-semibold text-text-primary mb-3">Payment Summary</Text>
                {[
                    { label: 'Rent Amount', value: rental.totalRentAmount },
                    { label: 'Security Deposit', value: rental.depositAmount },
                    { label: 'Service Fee', value: rental.serviceFee },
                ].map(({ label, value }) => (
                    <View key={label} className="flex-row justify-between mb-2">
                        <Text className="text-text-secondary text-sm">{label}</Text>
                        <Text className="text-text-primary text-sm">Rs. {(value ?? 0).toLocaleString()}</Text>
                    </View>
                ))}
                <View className="border-t border-gray-100 mt-2 pt-2 flex-row justify-between">
                    <Text className="font-semibold text-text-primary">Total Paid</Text>
                    <Text className="font-bold text-primary">Rs. {rental.totalAmount.toLocaleString()}</Text>
                </View>
            </View>

            {/* Evidence photos */}
            {rental.evidencePhotos && rental.evidencePhotos.length > 0 ? (
                <View className="bg-card mx-4 mt-3 rounded-2xl p-4" style={{ shadowColor: '#000', shadowOpacity: 0.06, shadowRadius: 8, elevation: 2 }}>
                    <Text className="font-semibold text-text-primary mb-3">Return Evidence</Text>
                    <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: 8 }}>
                        {rental.evidencePhotos.map((url, i) => (
                            <Image key={i} source={{ uri: url }} style={{ width: 80, height: 80, borderRadius: 10 }} contentFit="cover" />
                        ))}
                    </ScrollView>
                </View>
            ) : null}

            {/* Actions */}
            <View className="mx-4 mt-3 mb-8">
                {isRenter && rental.status === 'ACTIVE' ? (
                    <TouchableOpacity
                        className="bg-accent-rent rounded-xl py-4 items-center"
                        onPress={handleRequestReturn}
                        disabled={returning}
                    >
                        {returning ? <ActivityIndicator color="#fff" /> : <Text className="text-white font-semibold">Request Return</Text>}
                    </TouchableOpacity>
                ) : null}

                {isOwner && rental.status === 'RETURN_REQUESTED' ? (
                    <TouchableOpacity
                        className="bg-primary rounded-xl py-4 items-center"
                        onPress={handleConfirmReturn}
                        disabled={confirming}
                    >
                        {confirming ? <ActivityIndicator color="#fff" /> : <Text className="text-white font-semibold">Confirm Return Received</Text>}
                    </TouchableOpacity>
                ) : null}
            </View>
        </ScrollView>
    );
}

export default function RentalDetailScreen() {
    return <ErrorBoundary><RentalDetailContent /></ErrorBoundary>;
}
