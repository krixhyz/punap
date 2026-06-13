import { useState } from 'react';
import {
    View, Text, TextInput, TouchableOpacity, ScrollView,
    ActivityIndicator, KeyboardAvoidingView, Platform,
} from 'react-native';
import { Link, useRouter } from 'expo-router';
import { useRegister } from '../../src/api/auth';

export default function RegisterScreen() {
    const [form, setForm] = useState({ name: '', email: '', phone: '', password: '', confirm: '' });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [success, setSuccess] = useState(false);
    const router = useRouter();
    const { mutateAsync: register, isPending } = useRegister();

    function set(field: string, value: string) {
        setForm((prev) => ({ ...prev, [field]: value }));
    }

    function validate() {
        const e: Record<string, string> = {};
        if (!form.name.trim()) e.name = 'Name is required';
        if (!form.email) e.email = 'Email is required';
        else if (!/\S+@\S+\.\S+/.test(form.email)) e.email = 'Invalid email';
        if (!form.phone) e.phone = 'Phone is required';
        if (!form.password || form.password.length < 8) e.password = 'Password must be at least 8 characters';
        if (form.password !== form.confirm) e.confirm = 'Passwords do not match';
        setErrors(e);
        return Object.keys(e).length === 0;
    }

    async function handleSubmit() {
        if (!validate()) return;
        try {
            await register({ name: form.name, email: form.email, phone: form.phone, password: form.password });
            setSuccess(true);
        } catch (err: unknown) {
            const msg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message;
            setErrors({ general: msg ?? 'Registration failed. Please try again.' });
        }
    }

    if (success) {
        return (
            <View className="flex-1 justify-center items-center px-6 bg-surface">
                <View className="bg-card rounded-2xl p-8 items-center shadow-sm w-full">
                    <Text className="text-4xl mb-4">📧</Text>
                    <Text className="text-xl font-bold text-text-primary mb-2 text-center" style={{ fontFamily: 'Outfit_700Bold' }}>
                        Check your email
                    </Text>
                    <Text className="text-text-secondary text-center mb-6">
                        We've sent a verification link to {form.email}. Please verify to activate your account.
                    </Text>
                    <TouchableOpacity className="bg-primary rounded-xl py-3 px-8" onPress={() => router.replace('/(auth)/login')}>
                        <Text className="text-white font-semibold">Back to Login</Text>
                    </TouchableOpacity>
                </View>
            </View>
        );
    }

    const fields: { label: string; key: keyof typeof form; placeholder: string; secure?: boolean; keyboard?: 'email-address' | 'phone-pad' | 'default' }[] = [
        { label: 'Full Name', key: 'name', placeholder: 'Your name' },
        { label: 'Email', key: 'email', placeholder: 'you@example.com', keyboard: 'email-address' },
        { label: 'Phone', key: 'phone', placeholder: '+977 98xxxxxxxx', keyboard: 'phone-pad' },
        { label: 'Password', key: 'password', placeholder: '••••••••', secure: true },
        { label: 'Confirm Password', key: 'confirm', placeholder: '••••••••', secure: true },
    ];

    return (
        <KeyboardAvoidingView className="flex-1" behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
            <ScrollView contentContainerStyle={{ flexGrow: 1 }} className="bg-surface">
                <View className="px-6 py-12">
                    <View className="mb-6">
                        <Text className="text-3xl font-bold text-primary mb-1" style={{ fontFamily: 'Outfit_700Bold' }}>
                            Create account
                        </Text>
                        <Text className="text-text-secondary" style={{ fontFamily: 'Inter_400Regular' }}>
                            Join PUNAP's circular economy
                        </Text>
                    </View>

                    {errors.general ? (
                        <View className="bg-red-50 border border-danger rounded-xl p-3 mb-4">
                            <Text className="text-danger text-sm">{errors.general}</Text>
                        </View>
                    ) : null}

                    <View className="bg-card rounded-2xl p-6 shadow-sm mb-4">
                        {fields.map(({ label, key, placeholder, secure, keyboard }) => (
                            <View key={key} className="mb-4">
                                <Text className="text-text-secondary text-sm mb-1 font-medium">{label}</Text>
                                <TextInput
                                    className="border border-gray-200 rounded-xl px-4 py-3 text-text-primary bg-white"
                                    placeholder={placeholder}
                                    value={form[key]}
                                    onChangeText={(v) => set(key, v)}
                                    secureTextEntry={secure}
                                    keyboardType={keyboard ?? 'default'}
                                    autoCapitalize={key === 'email' || key === 'password' || key === 'confirm' ? 'none' : 'words'}
                                />
                                {errors[key] ? <Text className="text-danger text-xs mt-1">{errors[key]}</Text> : null}
                            </View>
                        ))}

                        <TouchableOpacity
                            className="bg-primary rounded-xl py-4 items-center mt-2"
                            onPress={handleSubmit}
                            disabled={isPending}
                        >
                            {isPending ? (
                                <ActivityIndicator color="#fff" />
                            ) : (
                                <Text className="text-white font-semibold text-base" style={{ fontFamily: 'Outfit_600SemiBold' }}>
                                    Create Account
                                </Text>
                            )}
                        </TouchableOpacity>
                    </View>

                    <View className="flex-row justify-center">
                        <Text className="text-text-secondary text-sm">Already have an account? </Text>
                        <Link href="/(auth)/login" asChild>
                            <TouchableOpacity>
                                <Text className="text-primary text-sm font-semibold">Sign In</Text>
                            </TouchableOpacity>
                        </Link>
                    </View>
                </View>
            </ScrollView>
        </KeyboardAvoidingView>
    );
}
