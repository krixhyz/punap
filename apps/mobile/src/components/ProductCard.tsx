import { View, Text, TouchableOpacity } from 'react-native';
import { Image } from 'expo-image';
import { useRouter } from 'expo-router';
import Animated, { useAnimatedStyle, useSharedValue, withTiming } from 'react-native-reanimated';
import * as Haptics from 'expo-haptics';
import type { Product } from '../api/products';
import { useToggleWishlist } from '../api/products';

const TYPE_COLORS: Record<string, string> = {
    BUY: '#1A6B3C',
    RENT: '#2563EB',
    SWAP: '#7C3AED',
};

interface Props {
    product: Product;
    onWishlistToggle?: () => void;
}

export function ProductCard({ product, onWishlistToggle }: Props) {
    const router = useRouter();
    const scale = useSharedValue(1);
    const { mutate: toggleWishlist } = useToggleWishlist();
    const imageUrl = product.images?.[0];

    const animStyle = useAnimatedStyle(() => ({
        transform: [{ scale: scale.value }],
    }));

    function handlePressIn() {
        scale.value = withTiming(0.97, { duration: 100 });
    }

    function handlePressOut() {
        scale.value = withTiming(1, { duration: 100 });
    }

    function handleWishlist() {
        Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
        toggleWishlist(product.id);
        onWishlistToggle?.();
    }

    return (
        <Animated.View style={[animStyle, { width: '48%', marginHorizontal: '1%', marginBottom: 12 }]}>
            <TouchableOpacity
                activeOpacity={1}
                onPressIn={handlePressIn}
                onPressOut={handlePressOut}
                onPress={() => router.push(`/product/${product.id}`)}
                className="bg-card rounded-xl overflow-hidden"
                style={{ shadowColor: '#000', shadowOpacity: 0.07, shadowRadius: 8, elevation: 2 }}
            >
                <View className="relative">
                    <Image
                        source={{ uri: imageUrl ?? 'https://via.placeholder.com/200x160' }}
                        style={{ width: '100%', height: 140 }}
                        contentFit="cover"
                        transition={200}
                    />
                    <TouchableOpacity
                        className="absolute top-2 right-2 w-8 h-8 bg-white rounded-full items-center justify-center"
                        style={{ shadowColor: '#000', shadowOpacity: 0.1, shadowRadius: 4, elevation: 2 }}
                        onPress={handleWishlist}
                    >
                        <Text>{product.wishlisted ? '❤️' : '🤍'}</Text>
                    </TouchableOpacity>
                </View>

                <View className="p-3">
                    <Text className="text-text-primary font-medium text-sm mb-1" numberOfLines={2} style={{ fontFamily: 'Inter_400Regular' }}>
                        {product.title}
                    </Text>

                    <View className="flex-row flex-wrap gap-1 mb-2">
                        {product.transactionTypes.map((type) => (
                            <View
                                key={type}
                                style={{ backgroundColor: TYPE_COLORS[type] ?? '#6B7280', borderRadius: 999, paddingHorizontal: 6, paddingVertical: 2 }}
                            >
                                <Text style={{ color: '#fff', fontSize: 9, fontWeight: '500' }}>{type}</Text>
                            </View>
                        ))}
                    </View>

                    <Text className="text-primary font-bold text-base" style={{ fontFamily: 'Outfit_700Bold' }}>
                        Rs. {product.price.toLocaleString()}
                    </Text>

                    {product.ecoScore ? (
                        <View className="flex-row items-center mt-1">
                            <Text style={{ fontSize: 10 }}>🌿</Text>
                            <Text className="text-eco-gold text-xs ml-1">{product.ecoScore} eco pts</Text>
                        </View>
                    ) : null}
                </View>
            </TouchableOpacity>
        </Animated.View>
    );
}
