import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '../lib/api';

export interface UserProfile {
    id: string;
    name: string;
    email: string;
    phone?: string | null;
    avatarUrl?: string | null;
    role: string;
    accountStatus: string;
    totalEcoScore: number;
    ecoLevel: string;
    createdAt: string;
    city?: { id: number; name: string } | null;
    province?: { id: number; name: string } | null;
    _count?: { listings: number };
}

export interface EcoScoreData {
    totalEcoScore: number;
    ecoLevel: string;
    history: Array<{
        id: string;
        ecoPoints: number;
        transactionType: string;
        createdAt: string;
    }>;
}

export interface UpdateProfilePayload {
    name?: string;
    phone?: string;
    cityId?: number;
    provinceId?: number;
    avatarUrl?: string;
}

export function useProfile(userId: string) {
    return useQuery({
        queryKey: ['profile', userId],
        queryFn: () => api.get<UserProfile>(`/users/${userId}`).then((r) => r.data),
        enabled: !!userId,
    });
}

export function useUpdateProfile() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (payload: UpdateProfilePayload) =>
            api.patch<UserProfile>('/users/me', payload).then((r) => r.data),
        onSuccess: (data) => {
            queryClient.invalidateQueries({ queryKey: ['profile', data.id] });
            queryClient.invalidateQueries({ queryKey: ['me'] });
        },
    });
}

export function useMyEcoScore() {
    return useQuery({
        queryKey: ['eco-score', 'my'],
        queryFn: () => api.get<EcoScoreData>('/eco-score/my').then((r) => r.data),
    });
}

export function useUploadAvatar() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (file: File) => {
            const form = new FormData();
            form.append('avatar', file);
            return api.post<{ avatarUrl: string }>('/users/me/avatar', form, {
                headers: { 'Content-Type': 'multipart/form-data' },
            }).then((r) => r.data);
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['me'] });
        },
    });
}
