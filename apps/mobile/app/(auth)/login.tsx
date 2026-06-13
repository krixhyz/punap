import { useState } from 'react';
import {
    View,
    Text,
    TextInput,
    TouchableOpacity,
    ScrollView,
    ActivityIndicator,
    KeyboardAvoidingView,
    Platform,
    Alert,
} from 'react-native';
import { Link, useRouter } from 'expo-router';
import { useLogin } from '../../src/api/auth';

export default function LoginScreen() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [errors, setErrors] = useState<{ email?: string; password?: string; general?: string }>({});
    const router = useRouter();
    const { mutateAsync: login, isPending } = useLogin();

    function validate() {
        const e: typeof errors = {};
        if (!email) e.email = 'Email is required';
        else if (!/\S+@\S+\.\S+/.test(email)) e.email = 'Invalid email';
        if (!password) e.password = 'Password is required';
        setErrors(e);
        return Object.keys(e).length === 0;
    }

    async function handleSubmit() {
        if (!validate()) return;
        try {
            await login({ email, password });
            router.replace('/(tabs)');
        } catch (err: unknown) {
            const msg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message;
            setErrors({ general: msg ?? 'Login failed. Please try again.' });
        }
    }

    return (
        <KeyboardAvoidingView className="flex-1" behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
            <ScrollView contentContainerStyle={{ flexGrow: 1 }} className="bg-surface">
                <View className="flex-1 justify-center px-6 py-12">
                    <View className="mb-8">
                        <Text className="text-3xl font-bold text-primary mb-1" style={{ fontFamily: 'Outfit_700Bold' }}>
                            Welcome back
                        </Text>
                        <Text className="text-text-secondary" style={{ fontFamily: 'Inter_400Regular' }}>
                            Sign in to your PUNAP account
                        </Text>
                    </View>

                    {errors.general ? (
                        <View className="bg-red-50 border border-danger rounded-xl p-3 mb-4">
                            <Text className="text-danger text-sm">{errors.general}</Text>
                        </View>
                    ) : null}

                    <View className="bg-card rounded-2xl p-6 shadow-sm mb-4">
                        <View className="mb-4">
                            <Text className="text-text-secondary text-sm mb-1 font-medium">Email</Text>
                            <TextInput
                                className="border border-gray-200 rounded-xl px-4 py-3 text-text-primary bg-white"
                                placeholder="you@example.com"
                                value={email}
                                onChangeText={setEmail}
                                keyboardType="email-address"
                                autoCapitalize="none"
                                autoComplete="email"
                            />
                            {errors.email ? <Text className="text-danger text-xs mt-1">{errors.email}</Text> : null}
                        </View>

                        <View className="mb-6">
                            <Text className="text-text-secondary text-sm mb-1 font-medium">Password</Text>
                            <TextInput
                                className="border border-gray-200 rounded-xl px-4 py-3 text-text-primary bg-white"
                                placeholder="••••••••"
                                value={password}
                                onChangeText={setPassword}
                                secureTextEntry
                                autoComplete="password"
                            />
                            {errors.password ? <Text className="text-danger text-xs mt-1">{errors.password}</Text> : null}
                        </View>

                        <TouchableOpacity
                            className="bg-primary rounded-xl py-4 items-center"
                            onPress={handleSubmit}
                            disabled={isPending}
                        >
                            {isPending ? (
                                <ActivityIndicator color="#fff" />
                            ) : (
                                <Text className="text-white font-semibold text-base" style={{ fontFamily: 'Outfit_600SemiBold' }}>
                                    Sign In
                                </Text>
                            )}
                        </TouchableOpacity>
                    </View>

                    <Link href="/(auth)/forgot-password" asChild>
                        <TouchableOpacity className="items-center mb-4">
                            <Text className="text-primary text-sm">Forgot your password?</Text>
                        </TouchableOpacity>
                    </Link>

                    <View className="flex-row justify-center">
                        <Text className="text-text-secondary text-sm">Don't have an account? </Text>
                        <Link href="/(auth)/register" asChild>
                            <TouchableOpacity>
                                <Text className="text-primary text-sm font-semibold">Sign Up</Text>
                            </TouchableOpacity>
                        </Link>
                    </View>
                </View>
            </ScrollView>
        </KeyboardAvoidingView>
    );
}
