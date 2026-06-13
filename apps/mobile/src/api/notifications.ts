import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query';
import { api } from '../lib/api';

export interface Notification {
    id: string;
    type: string;
    title: string;
    body: string;
    data?: Record<string, unknown> | null;
    isRead: boolean;
    createdAt: string;
}

interface PaginatedNotifications { data: Notification[]; total: number; page: number; limit: number; }

export function useNotifications(page = 1) {
    return useQuery({
        queryKey: ['notifications', page],
        queryFn: () =>
            api.get<PaginatedNotifications>('/notifications', { params: { page } }).then((r) => r.data),
        placeholderData: keepPreviousData,
    });
}

export function useUnreadCount() {
    return useQuery({
        queryKey: ['notifications', 'count'],
        queryFn: () => api.get<{ count: number }>('/notifications/count').then((r) => r.data),
        refetchInterval: 60_000,
    });
}

export function useMarkAllRead() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: () => api.patch('/notifications/read-all').then((r) => r.data),
        onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['notifications'] }); },
    });
}

export function useMarkRead() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (id: string) => api.patch(`/notifications/${id}/read`).then((r) => r.data),
        onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['notifications'] }); },
    });
}
