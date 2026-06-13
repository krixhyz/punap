import { create } from 'zustand';

interface NotificationState {
    unreadCount: number;
    increment: () => void;
    reset: () => void;
    setCount: (count: number) => void;
}

export const useNotificationStore = create<NotificationState>()((set) => ({
    unreadCount: 0,
    increment: () => set((s) => ({ unreadCount: s.unreadCount + 1 })),
    reset: () => set({ unreadCount: 0 }),
    setCount: (count) => set({ unreadCount: count }),
}));
