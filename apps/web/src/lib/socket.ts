import { io, Socket } from 'socket.io-client';

const API_URL = import.meta.env.VITE_API_URL ?? 'http://localhost:3001';

let socket: Socket | null = null;

export function connectSocket(token: string) {
    if (socket?.connected) return socket;

    socket = io(API_URL, {
        auth: { token },
        transports: ['websocket'],
        autoConnect: true,
    });

    socket.on('connect_error', (err) => {
        console.warn('[socket] connect error:', err.message);
    });

    return socket;
}

export function disconnectSocket() {
    socket?.disconnect();
    socket = null;
}

export function getSocket() {
    return socket;
}

export function onEvent<T = unknown>(event: string, handler: (data: T) => void) {
    socket?.on(event, handler);
}

export function offEvent(event: string, handler: (...args: unknown[]) => void) {
    socket?.off(event, handler);
}
