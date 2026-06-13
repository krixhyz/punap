import { View, Text, TouchableOpacity, ScrollView, Alert, ActivityIndicator } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useRouter } from 'expo-router';
import { Image } from 'expo-image';
import Svg, { Circle } from 'react-native-svg';
import Animated, { useAnimatedProps, useSharedValue, withTiming, Easing } from 'react-native-reanimated';
import { useEffect } from 'react';
import { useAuthStore } from '../../src/store/authSlice';
import { useProfile, useMyEcoScore } from '../../src/api/profile';
import { useLogout } from '../../src/api/auth';
import { useMyProducts } from '../../src/api/products';
import { ProductCard } from '../../src/components/ProductCard';
import { ErrorBoundary } from '../../src/components/ErrorBoundary';

const AnimatedCircle = Animated.createAnimatedComponent(Circle);

const ECO_LEVELS: Record<string, string> = {
    NONE: '🌱', BRONZE: '🥉', SILVER: '🥈', GOLD: '🥇', PLATINUM: '💎',
};

const CIRCUMFERENCE = 2 * Math.PI * 52;

function EcoRing({ score, maxScore = 5000 }: { score: number; maxScore?: number }) {
    const progress = useSharedValue(0);

    useEffect(() => {
        progress.value = withTiming(Math.min(score / maxScore, 1), { duration: 600, easing: Easing.out(Easing.ease) });
    }, [score, maxScore, progress]);

    const animProps = useAnimatedProps(() => ({
        strokeDashoffset: CIRCUMFERENCE * (1 - progress.value),
    }));

    return (
        <Svg width={120} height={120} viewBox="0 0 120 120">
            <Circle cx={60} cy={60} r={52} stroke="#E8F5EE" strokeWidth={10} fill="none" />
            <AnimatedCircle
                cx={60}
                cy={60}
                r={52}
                stroke="#1A6B3C"
                strokeWidth={10}
                fill="none"
                strokeDasharray={CIRCUMFERENCE}
                strokeDashoffset={CIRCUMFERENCE}
                animatedProps={animProps}
                strokeLinecap="round"
                rotation="-90"
                origin="60, 60"
            />
        </Svg>
    );
}

function ProfileContent() {
    const router = useRouter();
    const { user, clearAuth } = useAuthStore();
    const { data: profile, isLoading } = useProfile(user?.id ?? '');
    const { data: ecoData } = useMyEcoScore();
    const { data: myProducts } = useMyProducts();
    const { mutateAsync: logout } = useLogout();

    async function handleLogout() {
        Alert.alert('Logout', 'Are you sure you want to logout?', [
            { text: 'Cancel', style: 'cancel' },
            {
                text: 'Logout',
                style: 'destructive',
                onPress: async () => {
                    try { await logout(); } finally { clearAuth(); }
                },
            },
        ]);
    }

    if (isLoading) {
        return (
            <SafeAreaView className="flex-1 bg-surface justify-center items-center">
                <ActivityIndicator size="large" color="#1A6B3C" />
            </SafeAreaView>
        );
    }

    const ecoScore = ecoData?.totalEcoScore ?? profile?.totalEcoScore ?? 0;
    const ecoLevel = ecoData?.ecoLevel ?? profile?.ecoLevel ?? 'NONE';

    const menuItems = [
        { label: '✏️  Edit Profile', onPress: () => router.push('/profile/edit') },
        { label: '💰  Wallet', onPress: () => router.push('/wallet') },
        { label: '📋  My Listings', onPress: () => {} },
        { label: '🔔  Notifications', onPress: () => router.push('/(tabs)/notifications') },
        { label: '🚪  Logout', onPress: handleLogout, danger: true },
    ];

    return (
        <SafeAreaView className="flex-1 bg-surface" edges={['top']}>
            <ScrollView showsVerticalScrollIndicator={false}>
                {/* Header */}
                <View className="items-center px-6 pt-6 pb-4">
                    <View className="relative mb-2">
                        <EcoRing score={ecoScore} />
                        <View className="absolute inset-0 justify-center items-center">
                            {profile?.avatarUrl ? (
                                <Image source={{ uri: profile.avatarUrl }} style={{ width: 88, height: 88, borderRadius: 44 }} contentFit="cover" />
                            ) : (
                                <View className="w-[88px] h-[88px] rounded-full bg-primary-light items-center justify-center">
                                    <Text className="text-primary text-3xl font-bold">{profile?.name?.[0]?.toUpperCase()}</Text>
                                </View>
                            )}
                        </View>
                    </View>

                    <Text className="text-xl font-bold text-text-primary mb-0.5" style={{ fontFamily: 'Outfit_700Bold' }}>
                        {profile?.name}
                    </Text>
                    <Text className="text-text-secondary text-sm mb-2">{profile?.email}</Text>

                    <View className="flex-row items-center gap-2">
                        <View className="bg-eco-gold/10 px-3 py-1 rounded-full flex-row items-center">
                            <Text>{ECO_LEVELS[ecoLevel]}</Text>
                            <Text className="text-eco-gold text-sm font-semibold ml-1">{ecoLevel}</Text>
                        </View>
                        <View className="bg-primary-light px-3 py-1 rounded-full">
                            <Text className="text-primary text-sm font-semibold">🌿 {ecoScore} pts</Text>
                        </View>
                    </View>
                </View>

                {/* Quick stats */}
                <View className="flex-row mx-4 bg-card rounded-xl mb-4 overflow-hidden" style={{ shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 4, elevation: 1 }}>
                    {[
                        { label: 'Listings', value: myProducts?.length ?? 0 },
                        { label: 'Eco Points', value: ecoScore },
                        { label: 'Member Since', value: profile?.createdAt ? new Date(profile.createdAt).getFullYear() : '—' },
                    ].map((stat, i) => (
                        <View key={stat.label} className={`flex-1 py-4 items-center ${i < 2 ? 'border-r border-gray-100' : ''}`}>
                            <Text className="text-lg font-bold text-text-primary">{stat.value}</Text>
                            <Text className="text-text-muted text-xs">{stat.label}</Text>
                        </View>
                    ))}
                </View>

                {/* My listings preview */}
                {myProducts && myProducts.length > 0 ? (
                    <View className="mb-4">
                        <Text className="px-4 text-base font-semibold text-text-primary mb-2" style={{ fontFamily: 'Outfit_600SemiBold' }}>
                            My Listings
                        </Text>
                        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ paddingHorizontal: 16, gap: 12 }}>
                            {myProducts.slice(0, 6).map((p) => (
                                <View key={p.id} style={{ width: 140 }}>
                                    <ProductCard product={p} />
                                </View>
                            ))}
                        </ScrollView>
                    </View>
                ) : null}

                {/* Menu */}
                <View className="mx-4 bg-card rounded-2xl overflow-hidden mb-8" style={{ shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 4, elevation: 1 }}>
                    {menuItems.map((item, i) => (
                        <TouchableOpacity
                            key={item.label}
                            onPress={item.onPress}
                            className={`px-6 py-4 flex-row items-center justify-between ${i < menuItems.length - 1 ? 'border-b border-gray-100' : ''}`}
                        >
                            <Text style={{ color: (item as { danger?: boolean }).danger ? '#EF4444' : '#111827', fontSize: 15 }}>
                                {item.label}
                            </Text>
                            <Text className="text-text-muted">›</Text>
                        </TouchableOpacity>
                    ))}
                </View>
            </ScrollView>
        </SafeAreaView>
    );
}

export default function ProfileTab() {
    return <ErrorBoundary><ProfileContent /></ErrorBoundary>;
}
