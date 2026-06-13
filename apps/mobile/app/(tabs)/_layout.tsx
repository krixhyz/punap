import { Tabs } from 'expo-router';
import { View, Text, TouchableOpacity } from 'react-native';
import Animated, { useAnimatedStyle, useSharedValue, withSpring } from 'react-native-reanimated';
import { useNotificationStore } from '../../src/store/notificationSlice';

function TabIcon({ name, focused }: { name: string; focused: boolean }) {
    const scale = useSharedValue(1);

    const animStyle = useAnimatedStyle(() => ({
        transform: [{ scale: scale.value }],
    }));

    const icons: Record<string, { active: string; inactive: string }> = {
        Home: { active: '🏠', inactive: '🏠' },
        Search: { active: '🔍', inactive: '🔍' },
        Activity: { active: '📦', inactive: '📦' },
        Notifications: { active: '🔔', inactive: '🔔' },
        Profile: { active: '👤', inactive: '👤' },
    };

    const icon = icons[name] ?? { active: '●', inactive: '○' };

    return (
        <Animated.View style={[animStyle, { alignItems: 'center' }]}>
            <Text style={{ fontSize: 20 }}>{focused ? icon.active : icon.inactive}</Text>
        </Animated.View>
    );
}

export default function TabsLayout() {
    const { unreadCount } = useNotificationStore();

    return (
        <Tabs
            screenOptions={{
                headerShown: false,
                tabBarStyle: {
                    borderTopWidth: 1,
                    borderTopColor: '#E5E7EB',
                    backgroundColor: '#FFFFFF',
                    height: 60,
                    paddingBottom: 8,
                },
                tabBarActiveTintColor: '#1A6B3C',
                tabBarInactiveTintColor: '#9CA3AF',
                tabBarLabelStyle: { fontSize: 11, fontFamily: 'Inter_400Regular' },
            }}
        >
            <Tabs.Screen
                name="index"
                options={{
                    title: 'Home',
                    tabBarIcon: ({ focused }) => <TabIcon name="Home" focused={focused} />,
                }}
            />
            <Tabs.Screen
                name="search"
                options={{
                    title: 'Search',
                    tabBarIcon: ({ focused }) => <TabIcon name="Search" focused={focused} />,
                }}
            />
            <Tabs.Screen
                name="activity"
                options={{
                    title: 'Activity',
                    tabBarIcon: ({ focused }) => <TabIcon name="Activity" focused={focused} />,
                }}
            />
            <Tabs.Screen
                name="notifications"
                options={{
                    title: 'Alerts',
                    tabBarIcon: ({ focused }) => <TabIcon name="Notifications" focused={focused} />,
                    tabBarBadge: unreadCount > 0 ? unreadCount : undefined,
                }}
            />
            <Tabs.Screen
                name="profile"
                options={{
                    title: 'Profile',
                    tabBarIcon: ({ focused }) => <TabIcon name="Profile" focused={focused} />,
                }}
            />
        </Tabs>
    );
}
