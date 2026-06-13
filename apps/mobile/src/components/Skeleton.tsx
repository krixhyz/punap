import { View } from 'react-native';
import Animated, { useAnimatedStyle, useSharedValue, withRepeat, withTiming, Easing } from 'react-native-reanimated';
import { useEffect } from 'react';

interface Props {
    width?: number | string;
    height?: number;
    rounded?: boolean;
    style?: object;
}

export function Skeleton({ width = '100%', height = 16, rounded = false, style }: Props) {
    const opacity = useSharedValue(0.4);

    useEffect(() => {
        opacity.value = withRepeat(
            withTiming(1, { duration: 800, easing: Easing.inOut(Easing.ease) }),
            -1,
            true,
        );
    }, [opacity]);

    const animStyle = useAnimatedStyle(() => ({ opacity: opacity.value }));

    return (
        <Animated.View
            style={[
                {
                    width: width as number,
                    height,
                    backgroundColor: '#E5E7EB',
                    borderRadius: rounded ? 999 : 8,
                },
                animStyle,
                style,
            ]}
        />
    );
}

export function ProductCardSkeleton() {
    return (
        <View className="bg-card rounded-xl overflow-hidden mb-3 mx-1" style={{ width: '48%', shadowColor: '#000', shadowOpacity: 0.07, shadowRadius: 8, elevation: 2 }}>
            <Skeleton height={160} style={{ borderRadius: 0 }} />
            <View className="p-3">
                <Skeleton height={14} style={{ marginBottom: 6 }} />
                <Skeleton width="60%" height={12} style={{ marginBottom: 8 }} />
                <Skeleton width="40%" height={18} />
            </View>
        </View>
    );
}
