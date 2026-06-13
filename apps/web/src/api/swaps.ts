import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query';
import { api } from '../lib/api';

export type MoneyDirection = 'NONE' | 'OWNER_ASKS_CASH' | 'REQUESTER_OFFERS_CASH';

export interface SwapProduct {
    id: string;
    title: string;
    images: string[];
    price: number;
    condition: string;
}

export interface SwapParticipant {
    id: string;
    name: string;
    avatarUrl?: string | null;
}

export interface SwapNegotiationEvent {
    id: string;
    type: string;
    actorId: string;
    actor: SwapParticipant;
    offeredAmount?: number | null;
    askedAmount?: number | null;
    moneyDirection?: MoneyDirection | null;
    message?: string | null;
    createdAt: string;
}

export interface SwapRequest {
    id: string;
    status: string;
    moneyDirection: MoneyDirection;
    offeredAmount?: number | null;
    askedAmount?: number | null;
    message?: string | null;
    requesterId: string;
    requester: SwapParticipant;
    ownerId: string;
    owner: SwapParticipant;
    product: SwapProduct;
    offeredProduct: SwapProduct;
    confirmation?: {
        requesterConfirmedAt?: string | null;
        ownerConfirmedAt?: string | null;
    } | null;
    createdAt: string;
    updatedAt: string;
}

interface PaginatedSwaps {
    data: SwapRequest[];
    total: number;
    page: number;
    limit: number;
}

interface CreateSwapPayload {
    productId: string;
    offeredProductId: string;
    message?: string;
    offeredAmount?: number;
    askedAmount?: number;
    moneyDirection?: MoneyDirection;
}

interface CounterOfferPayload {
    offeredAmount?: number;
    askedAmount?: number;
    moneyDirection?: MoneyDirection;
    message?: string;
}

export function useCreateSwapRequest() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (payload: CreateSwapPayload) =>
            api.post<SwapRequest>('/swaps', payload).then((r) => r.data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['swaps'] });
        },
    });
}

export function useSwaps(type: 'sent' | 'received' | 'all' = 'all', page = 1) {
    return useQuery({
        queryKey: ['swaps', type, page],
        queryFn: () =>
            api
                .get<PaginatedSwaps>('/swaps', { params: { type, page } })
                .then((r) => r.data),
        placeholderData: keepPreviousData,
    });
}

export function useSwap(id: string) {
    return useQuery({
        queryKey: ['swap', id],
        queryFn: () => api.get<SwapRequest>(`/swaps/${id}`).then((r) => r.data),
        enabled: !!id,
    });
}

export function useSwapEvents(id: string) {
    return useQuery({
        queryKey: ['swap-events', id],
        queryFn: () =>
            api.get<SwapNegotiationEvent[]>(`/swaps/${id}/events`).then((r) => r.data),
        enabled: !!id,
    });
}

export function useCounterOffer() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: ({ id, ...payload }: CounterOfferPayload & { id: string }) =>
            api.post<SwapRequest>(`/swaps/${id}/counter`, payload).then((r) => r.data),
        onSuccess: (_, { id }) => {
            queryClient.invalidateQueries({ queryKey: ['swap', id] });
            queryClient.invalidateQueries({ queryKey: ['swap-events', id] });
            queryClient.invalidateQueries({ queryKey: ['swaps'] });
        },
    });
}

export function useAcceptSwap() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (id: string) =>
            api.post<SwapRequest>(`/swaps/${id}/accept`).then((r) => r.data),
        onSuccess: (_, id) => {
            queryClient.invalidateQueries({ queryKey: ['swap', id] });
            queryClient.invalidateQueries({ queryKey: ['swap-events', id] });
            queryClient.invalidateQueries({ queryKey: ['swaps'] });
        },
    });
}

export function useRejectSwap() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (id: string) =>
            api.post<SwapRequest>(`/swaps/${id}/reject`).then((r) => r.data),
        onSuccess: (_, id) => {
            queryClient.invalidateQueries({ queryKey: ['swap', id] });
            queryClient.invalidateQueries({ queryKey: ['swaps'] });
        },
    });
}

export function useCancelSwap() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (id: string) =>
            api.post<SwapRequest>(`/swaps/${id}/cancel`).then((r) => r.data),
        onSuccess: (_, id) => {
            queryClient.invalidateQueries({ queryKey: ['swap', id] });
            queryClient.invalidateQueries({ queryKey: ['swaps'] });
        },
    });
}

export function useConfirmReceived() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (id: string) =>
            api.post<SwapRequest>(`/swaps/${id}/confirm-received`).then((r) => r.data),
        onSuccess: (_, id) => {
            queryClient.invalidateQueries({ queryKey: ['swap', id] });
            queryClient.invalidateQueries({ queryKey: ['swaps'] });
        },
    });
}

export function useInitiateSwapPayment() {
    return useMutation({
        mutationFn: ({ swapId, gateway = 'khalti' }: { swapId: string; gateway?: string }) =>
            api
                .post<{ paymentUrl: string }>(`/payments/initiate/swap/${swapId}`, {}, { params: { gateway } })
                .then((r) => r.data),
    });
}
