import { create } from 'zustand';
import { persist } from 'zustand/middleware';

export interface AuthUser {
    id: string;
    name: string;
    email: string;
    role: 'USER' | 'ADMIN' | 'SUPER_ADMIN';
    avatarUrl?: string | null;
    ecoLevel?: string | null;
    totalEcoScore?: number | null;
}

interface AuthState {
    user: AuthUser | null;
    accessToken: string | null;
    setAuth: (user: AuthUser, accessToken: string) => void;
    clearAuth: () => void;
}

export const useAuthStore = create<AuthState>()(
    persist(
        (set) => ({
            user: null,
            accessToken: null,
            setAuth: (user, accessToken) => set({ user, accessToken }),
            clearAuth: () => set({ user: null, accessToken: null }),
        }),
        { name: 'punap-auth' },
    ),
);
