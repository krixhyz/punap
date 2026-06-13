import { useEffect, useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, ActivityIndicator } from 'react-native';
import { useLocalSearchParams, useRouter, Link } from 'expo-router';
import * as Linking from 'expo-linking';
import { useVerifyEmail } from '../../src/api/auth';

export default function VerifyEmailScreen() {
    const { token: paramToken } = useLocalSearchParams<{ token?: string }>();
    const [token, setToken] = useState(paramToken ?? '');
    const [result, setResult] = useState<'idle' | 'success' | 'error'>('idle');
    const [errorMsg, setErrorMsg] = useState('');
    const router = useRouter();
    const { mutateAsync: verify, isPending } = useVerifyEmail();

    // Handle deep link punap://verify?token=...
    useEffect(() => {
        const sub = Linking.addEventListener('url', ({ url }) => {
            const parsed = Linking.parse(url);
            if (parsed.queryParams?.token) {
                setToken(String(parsed.queryParams.token));
            }
        });
        return () => sub.remove();
    }, []);

    useEffect(() => {
        if (paramToken && result === 'idle') {
            handleVerify(paramToken);
        }
    }, [paramToken]);

    async function handleVerify(t = token) {
        if (!t) return;
        try {
            await verify({ token: t });
            setResult('success');
        } catch {
            setResult('error');
            setErrorMsg('Invalid or expired token. Please try again.');
        }
    }

    if (result === 'success') {
        return (
            <View className="flex-1 justify-center items-center px-6 bg-surface">
                <View className="bg-card rounded-2xl p-8 items-center shadow-sm w-full">
                    <Text className="text-5xl mb-4">✅</Text>
                    <Text className="text-xl font-bold text-text-primary mb-2 text-center" style={{ fontFamily: 'Outfit_700Bold' }}>
                        Email Verified!
                    </Text>
                    <Text className="text-text-secondary text-center mb-6">
                        Your account is now active. You can sign in.
                    </Text>
                    <TouchableOpacity className="bg-primary rounded-xl py-3 px-8" onPress={() => router.replace('/(auth)/login')}>
                        <Text className="text-white font-semibold">Go to Login</Text>
                    </TouchableOpacity>
                </View>
            </View>
        );
    }

    return (
        <View className="flex-1 justify-center px-6 bg-surface">
            <View className="bg-card rounded-2xl p-6 shadow-sm">
                <Text className="text-2xl font-bold text-text-primary mb-2" style={{ fontFamily: 'Outfit_700Bold' }}>
                    Verify Email
                </Text>
                <Text className="text-text-secondary mb-6">Enter the 6-character token from your email.</Text>

                {result === 'error' ? (
                    <View className="bg-red-50 border border-danger rounded-xl p-3 mb-4">
                        <Text className="text-danger text-sm">{errorMsg}</Text>
                    </View>
                ) : null}

                <TextInput
                    className="border border-gray-200 rounded-xl px-4 py-3 text-text-primary bg-white mb-4 text-center text-lg tracking-widest"
                    placeholder="ABC123"
                    value={token}
                    onChangeText={setToken}
                    autoCapitalize="characters"
                    maxLength={6}
                />

                <TouchableOpacity
                    className="bg-primary rounded-xl py-4 items-center"
                    onPress={() => handleVerify()}
                    disabled={isPending || !token}
                >
                    {isPending ? <ActivityIndicator color="#fff" /> : (
                        <Text className="text-white font-semibold text-base">Verify</Text>
                    )}
                </TouchableOpacity>
            </View>
        </View>
    );
}
