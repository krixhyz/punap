import { io, Socket } from 'socket.io-client';

const BASE_URL = process.env.EXPO_PUBLIC_API_URL ?? 'http://localhost:3001';

let socket: Socket | null = null;

export const socketClient = {
    connect(token: string) {
        if (socket?.connected) return;
        socket = io(BASE_URL, {
            auth: { token },
            transports: ['websocket'],
            reconnection: true,
            reconnectionAttempts: 5,
            reconnectionDelay: 1000,
        });
    },
    disconnect() {
        socket?.disconnect();
        socket = null;
    },
    on(event: string, handler: (...args: unknown[]) => void) {
        socket?.on(event, handler);
    },
    off(event: string, handler: (...args: unknown[]) => void) {
        socket?.off(event, handler);
    },
    isConnected() {
        return socket?.connected ?? false;
    },
};
