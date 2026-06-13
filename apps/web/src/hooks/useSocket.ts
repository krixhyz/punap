import { useEffect } from 'react';
import { connectSocket, disconnectSocket, onEvent, offEvent } from '../lib/socket';
import { useAuth } from './useAuth';
import { useNotificationStore } from '../store/notificationSlice';

export function useSocket() {
    const { accessToken, isAuthenticated } = useAuth();
    const increment = useNotificationStore((s) => s.increment);

    useEffect(() => {
        if (!isAuthenticated || !accessToken) return;

        const socket = connectSocket(accessToken);

        const handleNotification = () => {
            increment();
        };

        // Any event starting with "notification." increments badge
        socket.onAny((event: string) => {
            if (event.startsWith('notification.')) {
                handleNotification();
            }
        });

        return () => {
            socket.offAny();
        };
    }, [isAuthenticated, accessToken, increment]);
}

export { onEvent, offEvent };
