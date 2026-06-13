import { useEffect } from 'react';
import { Stack, useRouter, useSegments } from 'expo-router';
import { QueryClientProvider } from '@tanstack/react-query';
import {
    useFonts,
    Outfit_400Regular,
    Outfit_600SemiBold,
    Outfit_700Bold,
} from '@expo-google-fonts/outfit';
import { Inter_400Regular } from '@expo-google-fonts/inter';
import * as SplashScreen from 'expo-splash-screen';
import { StatusBar } from 'expo-status-bar';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { queryClient } from '../src/lib/queryClient';
import { useAuthStore } from '../src/store/authSlice';
import { useNotificationStore } from '../src/store/notificationSlice';
import { socketClient } from '../src/lib/socket';
import '../global.css';

SplashScreen.preventAutoHideAsync();

function AuthGuard() {
    const { user, accessToken } = useAuthStore();
    const segments = useSegments();
    const router = useRouter();

    useEffect(() => {
        const inAuth = segments[0] === '(auth)';
        if (!user || !accessToken) {
            if (!inAuth) router.replace('/(auth)/login');
        } else {
            if (inAuth) router.replace('/(tabs)');
        }
    }, [user, accessToken, segments, router]);

    return null;
}

export default function RootLayout() {
    const { accessToken } = useAuthStore();
    const { increment } = useNotificationStore();

    const [fontsLoaded] = useFonts({
        Outfit_400Regular,
        Outfit_600SemiBold,
        Outfit_700Bold,
        Inter_400Regular,
    });

    useEffect(() => {
        if (fontsLoaded) {
            SplashScreen.hideAsync();
        }
    }, [fontsLoaded]);

    useEffect(() => {
        if (accessToken) {
            socketClient.connect(accessToken);
            socketClient.on('notification.new', () => increment());
        } else {
            socketClient.disconnect();
        }
        return () => {
            if (!accessToken) socketClient.disconnect();
        };
    }, [accessToken, increment]);

    if (!fontsLoaded) return null;

    return (
        <GestureHandlerRootView style={{ flex: 1 }}>
            <QueryClientProvider client={queryClient}>
                <AuthGuard />
                <Stack screenOptions={{ headerShown: false }}>
                    <Stack.Screen name="(auth)" />
                    <Stack.Screen name="(tabs)" />
                    <Stack.Screen name="product/[id]" options={{ headerShown: true, title: 'Product' }} />
                    <Stack.Screen name="order/[id]" options={{ headerShown: true, title: 'Order' }} />
                    <Stack.Screen name="rental/[id]" options={{ headerShown: true, title: 'Rental' }} />
                    <Stack.Screen name="swap/[id]" options={{ headerShown: true, title: 'Swap' }} />
                    <Stack.Screen name="profile/[userId]" options={{ headerShown: true, title: 'Profile' }} />
                    <Stack.Screen name="profile/edit" options={{ headerShown: true, title: 'Edit Profile' }} />
                    <Stack.Screen name="wallet/index" options={{ headerShown: true, title: 'Wallet' }} />
                </Stack>
                <StatusBar style="auto" />
            </QueryClientProvider>
        </GestureHandlerRootView>
    );
}
