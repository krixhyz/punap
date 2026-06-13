import { useEffect } from 'react';
import { View, Text, FlatList, TouchableOpacity, ActivityIndicator } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useRouter } from 'expo-router';
import { useNotifications, useMarkAllRead } from '../../src/api/notifications';
import { useNotificationStore } from '../../src/store/notificationSlice';
import { EmptyState } from '../../src/components/EmptyState';
import { ErrorBoundary } from '../../src/components/ErrorBoundary';

function NotificationsContent() {
    const { data, isLoading } = useNotifications();
    const { mutate: markAllRead } = useMarkAllRead();
    const { reset } = useNotificationStore();

    useEffect(() => {
        markAllRead();
        reset();
    }, []);

    const notifications = data?.data ?? [];

    function getIcon(type: string): string {
        if (type.includes('order')) return '🛒';
        if (type.includes('rental')) return '📅';
        if (type.includes('swap')) return '🔄';
        if (type.includes('payout')) return '💰';
        if (type.includes('dispute')) return '⚠️';
        if (type.includes('payment')) return '💳';
        return '🔔';
    }

    function formatTime(iso: string) {
        const d = new Date(iso);
        const diff = Date.now() - d.getTime();
        const mins = Math.floor(diff / 60000);
        if (mins < 60) return `${mins}m ago`;
        const hrs = Math.floor(mins / 60);
        if (hrs < 24) return `${hrs}h ago`;
        return d.toLocaleDateString();
    }

    return (
        <SafeAreaView className="flex-1 bg-surface" edges={['top']}>
            <View className="px-4 pt-3 pb-2 flex-row items-center justify-between">
                <Text className="text-2xl font-bold text-text-primary" style={{ fontFamily: 'Outfit_700Bold' }}>
                    Notifications
                </Text>
                {notifications.length > 0 ? (
                    <TouchableOpacity onPress={() => { markAllRead(); reset(); }}>
                        <Text className="text-primary text-sm">Mark all read</Text>
                    </TouchableOpacity>
                ) : null}
            </View>

            {isLoading ? (
                <View className="flex-1 justify-center items-center">
                    <ActivityIndicator size="large" color="#1A6B3C" />
                </View>
            ) : notifications.length === 0 ? (
                <EmptyState icon="🔕" title="No notifications" message="You're all caught up!" />
            ) : (
                <FlatList
                    data={notifications}
                    keyExtractor={(item) => item.id}
                    contentContainerStyle={{ paddingHorizontal: 16, paddingBottom: 20 }}
                    showsVerticalScrollIndicator={false}
                    renderItem={({ item }) => (
                        <View
                            className="bg-card rounded-xl p-4 mb-3 flex-row"
                            style={{
                                shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 4, elevation: 1,
                                opacity: item.isRead ? 0.7 : 1,
                            }}
                        >
                            <View className="w-10 h-10 bg-primary-light rounded-full items-center justify-center mr-3">
                                <Text style={{ fontSize: 18 }}>{getIcon(item.type)}</Text>
                            </View>
                            <View className="flex-1">
                                <Text className="text-text-primary font-semibold text-sm mb-0.5">
                                    {item.title}
                                </Text>
                                <Text className="text-text-secondary text-xs mb-1" numberOfLines={2}>
                                    {item.body}
                                </Text>
                                <Text className="text-text-muted text-xs">{formatTime(item.createdAt)}</Text>
                            </View>
                            {!item.isRead ? (
                                <View className="w-2 h-2 bg-primary rounded-full mt-1 ml-2" />
                            ) : null}
                        </View>
                    )}
                />
            )}
        </SafeAreaView>
    );
}

export default function NotificationsScreen() {
    return <ErrorBoundary><NotificationsContent /></ErrorBoundary>;
}
