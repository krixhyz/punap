import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query';
import { api } from '../lib/api';

export interface Order {
    id: string;
    status: string;
    quantity: number;
    subtotal: number;
    serviceFee: number;
    totalAmount: number;
    sellerAmount: number;
    platformAmount: number;
    productSnapshot?: { title: string; images: string[] };
    product: {
        id: string;
        title: string;
        images: string[];
        seller: { id: string; name: string };
    };
    buyer: { id: string; name: string; avatarUrl?: string | null };
    createdAt: string;
    updatedAt: string;
    payment?: { id: string; status: string; gateway: string } | null;
    reviewExists?: boolean;
}

interface CreateOrderPayload {
    productId: string;
    quantity: number;
}

interface PaginatedOrders {
    data: Order[];
    total: number;
    page: number;
    limit: number;
}

export function useCreateOrder() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (payload: CreateOrderPayload) =>
            api.post<Order>('/orders', payload).then((r) => r.data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['orders'] });
        },
    });
}

export function useOrders(page = 1) {
    return useQuery({
        queryKey: ['orders', 'buying', page],
        queryFn: () =>
            api.get<PaginatedOrders>('/orders', { params: { page } }).then((r) => r.data),
        placeholderData: keepPreviousData,
    });
}

export function useSellingOrders(page = 1) {
    return useQuery({
        queryKey: ['orders', 'selling', page],
        queryFn: () =>
            api.get<PaginatedOrders>('/orders/selling', { params: { page } }).then((r) => r.data),
        placeholderData: keepPreviousData,
    });
}

export function useOrder(id: string) {
    return useQuery({
        queryKey: ['order', id],
        queryFn: () => api.get<Order>(`/orders/${id}`).then((r) => r.data),
        enabled: !!id,
    });
}

export function useCancelOrder() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (id: string) =>
            api.post<Order>(`/orders/${id}/cancel`).then((r) => r.data),
        onSuccess: (_, id) => {
            queryClient.invalidateQueries({ queryKey: ['order', id] });
            queryClient.invalidateQueries({ queryKey: ['orders'] });
        },
    });
}

export function useCompleteOrder() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (id: string) =>
            api.patch<Order>(`/orders/${id}/complete`).then((r) => r.data),
        onSuccess: (_, id) => {
            queryClient.invalidateQueries({ queryKey: ['order', id] });
            queryClient.invalidateQueries({ queryKey: ['orders'] });
        },
    });
}

export function useInitiateOrderPayment() {
    return useMutation({
        mutationFn: ({ orderId, gateway = 'khalti' }: { orderId: string; gateway?: string }) =>
            api
                .post<{ paymentUrl: string }>(`/payments/initiate/order/${orderId}`, {}, { params: { gateway } })
                .then((r) => r.data),
    });
}
