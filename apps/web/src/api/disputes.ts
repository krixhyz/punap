import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '../lib/api';

export interface Dispute {
    id: string;
    subject: string;
    description: string;
    status: string;
    transactionType: string;
    evidencePhotos: string[];
    resolution?: string | null;
    favoredUser?: { id: string; name: string } | null;
    orderId?: string | null;
    rentalBookingId?: string | null;
    swapId?: string | null;
    claimant: { id: string; name: string; avatarUrl?: string | null };
    respondent: { id: string; name: string; avatarUrl?: string | null };
    createdAt: string;
    resolvedAt?: string | null;
}

interface PaginatedDisputes {
    data: Dispute[];
    total: number;
    page: number;
    limit: number;
}

interface OpenDisputePayload {
    respondentId: string;
    transactionType: string;
    orderId?: string;
    rentalBookingId?: string;
    swapId?: string;
    subject: string;
    description: string;
    evidencePhotos?: string[];
}

export function useMyDisputes(page = 1) {
    return useQuery({
        queryKey: ['disputes', 'my', page],
        queryFn: () =>
            api.get<PaginatedDisputes>('/disputes/my', { params: { page } }).then((r) => r.data),
    });
}

export function useDispute(id: string) {
    return useQuery({
        queryKey: ['dispute', id],
        queryFn: () => api.get<Dispute>(`/disputes/${id}`).then((r) => r.data),
        enabled: !!id,
    });
}

export function useOpenDispute() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (payload: OpenDisputePayload) =>
            api.post<Dispute>('/disputes', payload).then((r) => r.data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['disputes'] });
        },
    });
}
