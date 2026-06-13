import { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, ActivityIndicator, KeyboardAvoidingView, Platform } from 'react-native';
import { Link } from 'expo-router';
import { useForgotPassword } from '../../src/api/auth';

export default function ForgotPasswordScreen() {
    const [email, setEmail] = useState('');
    const [submitted, setSubmitted] = useState(false);
    const [error, setError] = useState('');
    const { mutateAsync: forgotPassword, isPending } = useForgotPassword();

    async function handleSubmit() {
        if (!email) { setError('Email is required'); return; }
        try {
            await forgotPassword({ email });
            setSubmitted(true);
        } catch {
            setSubmitted(true); // Always show success message (API never reveals if email exists)
        }
    }

    if (submitted) {
        return (
            <View className="flex-1 justify-center items-center px-6 bg-surface">
                <View className="bg-card rounded-2xl p-8 items-center shadow-sm w-full">
                    <Text className="text-4xl mb-4">📬</Text>
                    <Text className="text-xl font-bold text-text-primary mb-2 text-center" style={{ fontFamily: 'Outfit_700Bold' }}>
                        Check your inbox
                    </Text>
                    <Text className="text-text-secondary text-center">
                        If an account with that email exists, we've sent a password reset link.
                    </Text>
                </View>
            </View>
        );
    }

    return (
        <KeyboardAvoidingView className="flex-1" behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
            <View className="flex-1 justify-center px-6 bg-surface">
                <View className="bg-card rounded-2xl p-6 shadow-sm">
                    <Text className="text-2xl font-bold text-text-primary mb-2" style={{ fontFamily: 'Outfit_700Bold' }}>
                        Reset Password
                    </Text>
                    <Text className="text-text-secondary mb-6">Enter your email to receive a reset link.</Text>

                    {error ? <Text className="text-danger text-sm mb-3">{error}</Text> : null}

                    <TextInput
                        className="border border-gray-200 rounded-xl px-4 py-3 text-text-primary bg-white mb-4"
                        placeholder="you@example.com"
                        value={email}
                        onChangeText={setEmail}
                        keyboardType="email-address"
                        autoCapitalize="none"
                    />

                    <TouchableOpacity
                        className="bg-primary rounded-xl py-4 items-center mb-4"
                        onPress={handleSubmit}
                        disabled={isPending}
                    >
                        {isPending ? <ActivityIndicator color="#fff" /> : (
                            <Text className="text-white font-semibold text-base">Send Reset Link</Text>
                        )}
                    </TouchableOpacity>

                    <Link href="/(auth)/login" asChild>
                        <TouchableOpacity className="items-center">
                            <Text className="text-primary text-sm">Back to Login</Text>
                        </TouchableOpacity>
                    </Link>
                </View>
            </View>
        </KeyboardAvoidingView>
    );
}
