import { useState, useEffect } from 'react';
import { View, Text, TextInput, TouchableOpacity, ScrollView, ActivityIndicator, Alert, KeyboardAvoidingView, Platform } from 'react-native';
import { Image } from 'expo-image';
import * as ImagePicker from 'expo-image-picker';
import { useRouter } from 'expo-router';
import { useAuthStore } from '../../src/store/authSlice';
import { useProfile, useUpdateProfile, useUploadAvatar } from '../../src/api/profile';
import { useProvinces, useCities } from '../../src/api/products';
import { ErrorBoundary } from '../../src/components/ErrorBoundary';

function EditProfileContent() {
    const router = useRouter();
    const { user } = useAuthStore();
    const { data: profile, isLoading } = useProfile(user?.id ?? '');
    const { mutateAsync: updateProfile, isPending: saving } = useUpdateProfile();
    const { mutateAsync: uploadAvatar, isPending: uploading } = useUploadAvatar();

    const [name, setName] = useState('');
    const [phone, setPhone] = useState('');
    const [provinceId, setProvinceId] = useState<number | undefined>();
    const [cityId, setCityId] = useState<number | undefined>();

    const { data: provinces } = useProvinces();
    const { data: cities } = useCities(provinceId);

    useEffect(() => {
        if (profile) {
            setName(profile.name ?? '');
            setPhone(profile.phone ?? '');
            setProvinceId(profile.province?.id);
            setCityId(profile.city?.id);
        }
    }, [profile]);

    async function handleSave() {
        try {
            await updateProfile({ name, phone, provinceId, cityId });
            Alert.alert('Success', 'Profile updated');
            router.back();
        } catch {
            Alert.alert('Error', 'Failed to update profile');
        }
    }

    async function handleAvatarChange() {
        const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
        if (!permission.granted) return;
        const result = await ImagePicker.launchImageLibraryAsync({ mediaTypes: ImagePicker.MediaTypeOptions.Images, quality: 0.8 });
        if (result.canceled) return;
        const asset = result.assets[0];
        await uploadAvatar({ uri: asset.uri, name: asset.fileName ?? 'avatar.jpg', type: asset.mimeType ?? 'image/jpeg' });
    }

    if (isLoading) return <View className="flex-1 justify-center items-center"><ActivityIndicator size="large" color="#1A6B3C" /></View>;

    return (
        <KeyboardAvoidingView className="flex-1" behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
            <ScrollView className="flex-1 bg-surface" contentContainerStyle={{ paddingBottom: 40 }}>
                {/* Avatar */}
                <View className="items-center py-6">
                    <TouchableOpacity onPress={handleAvatarChange} className="relative">
                        {profile?.avatarUrl ? (
                            <Image source={{ uri: profile.avatarUrl }} style={{ width: 88, height: 88, borderRadius: 44 }} contentFit="cover" />
                        ) : (
                            <View className="w-[88px] h-[88px] rounded-full bg-primary-light items-center justify-center">
                                <Text className="text-primary text-3xl font-bold">{profile?.name?.[0]?.toUpperCase()}</Text>
                            </View>
                        )}
                        <View className="absolute bottom-0 right-0 w-7 h-7 bg-primary rounded-full items-center justify-center">
                            <Text style={{ color: '#fff', fontSize: 14 }}>✏️</Text>
                        </View>
                    </TouchableOpacity>
                    {uploading ? <Text className="text-text-muted text-xs mt-2">Uploading...</Text> : null}
                </View>

                <View className="px-4">
                    {/* Name */}
                    <View className="mb-4">
                        <Text className="text-text-secondary text-sm mb-1 font-medium">Full Name</Text>
                        <TextInput
                            className="bg-card border border-gray-200 rounded-xl px-4 py-3 text-text-primary"
                            value={name}
                            onChangeText={setName}
                            placeholder="Your name"
                        />
                    </View>

                    {/* Phone */}
                    <View className="mb-4">
                        <Text className="text-text-secondary text-sm mb-1 font-medium">Phone</Text>
                        <TextInput
                            className="bg-card border border-gray-200 rounded-xl px-4 py-3 text-text-primary"
                            value={phone}
                            onChangeText={setPhone}
                            placeholder="+977 98xxxxxxxx"
                            keyboardType="phone-pad"
                        />
                    </View>

                    {/* Province */}
                    <View className="mb-4">
                        <Text className="text-text-secondary text-sm mb-1 font-medium">Province</Text>
                        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: 8 }}>
                            {provinces?.map((p) => (
                                <TouchableOpacity
                                    key={p.id}
                                    onPress={() => { setProvinceId(p.id); setCityId(undefined); }}
                                    className="px-3 py-2 rounded-xl border"
                                    style={{ borderColor: provinceId === p.id ? '#1A6B3C' : '#E5E7EB', backgroundColor: provinceId === p.id ? '#E8F5EE' : '#fff' }}
                                >
                                    <Text style={{ color: provinceId === p.id ? '#1A6B3C' : '#374151', fontSize: 13 }}>{p.name}</Text>
                                </TouchableOpacity>
                            ))}
                        </ScrollView>
                    </View>

                    {/* City */}
                    {provinceId && cities && cities.length > 0 ? (
                        <View className="mb-4">
                            <Text className="text-text-secondary text-sm mb-1 font-medium">City</Text>
                            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: 8 }}>
                                {cities.map((c) => (
                                    <TouchableOpacity
                                        key={c.id}
                                        onPress={() => setCityId(c.id)}
                                        className="px-3 py-2 rounded-xl border"
                                        style={{ borderColor: cityId === c.id ? '#1A6B3C' : '#E5E7EB', backgroundColor: cityId === c.id ? '#E8F5EE' : '#fff' }}
                                    >
                                        <Text style={{ color: cityId === c.id ? '#1A6B3C' : '#374151', fontSize: 13 }}>{c.name}</Text>
                                    </TouchableOpacity>
                                ))}
                            </ScrollView>
                        </View>
                    ) : null}

                    <TouchableOpacity
                        className="bg-primary rounded-xl py-4 items-center mt-4"
                        onPress={handleSave}
                        disabled={saving}
                    >
                        {saving ? <ActivityIndicator color="#fff" /> : <Text className="text-white font-semibold text-base">Save Changes</Text>}
                    </TouchableOpacity>
                </View>
            </ScrollView>
        </KeyboardAvoidingView>
    );
}

export default function EditProfileScreen() {
    return <ErrorBoundary><EditProfileContent /></ErrorBoundary>;
}
