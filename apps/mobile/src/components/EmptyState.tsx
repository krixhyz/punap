import { View, Text, TouchableOpacity } from 'react-native';

interface Props {
    icon?: string;
    title: string;
    message?: string;
    actionLabel?: string;
    onAction?: () => void;
}

export function EmptyState({ icon = '📭', title, message, actionLabel, onAction }: Props) {
    return (
        <View className="flex-1 justify-center items-center py-16 px-6">
            <Text className="text-5xl mb-4">{icon}</Text>
            <Text className="text-lg font-semibold text-text-primary text-center mb-2" style={{ fontFamily: 'Outfit_600SemiBold' }}>
                {title}
            </Text>
            {message ? (
                <Text className="text-text-secondary text-center text-sm mb-6">{message}</Text>
            ) : null}
            {actionLabel && onAction ? (
                <TouchableOpacity className="bg-primary rounded-xl py-3 px-6" onPress={onAction}>
                    <Text className="text-white font-semibold">{actionLabel}</Text>
                </TouchableOpacity>
            ) : null}
        </View>
    );
}
