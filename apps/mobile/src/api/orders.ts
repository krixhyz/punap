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
    buyerId: string;
    buyer: { id: string; name: string };
    productId: string;
    product: { id: string; title: string; images: string[]; price: number };
    sellerId: string;
    seller: { id: string; name: string };
    createdAt: string;
    updatedAt: string;
}

interface PaginatedOrders { data: Order[]; total: number; page: number; limit: number; }
interface CreateOrderPayload { productId: string; quantity: number; }
interface InitiatePaymentResponse { paymentUrl: string; pidx?: string; }

export function useOrders(page = 1) {
    return useQuery({
        queryKey: ['orders', page],
        queryFn: () => api.get<PaginatedOrders>('/orders', { params: { page } }).then((r) => r.data),
        placeholderData: keepPreviousData,
    });
}

export function useSellingOrders(page = 1) {
    return useQuery({
        queryKey: ['orders', 'selling', page],
        queryFn: () => api.get<PaginatedOrders>('/orders/selling', { params: { page } }).then((r) => r.data),
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

export function useCreateOrder() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (payload: CreateOrderPayload) =>
            api.post<Order>('/orders', payload).then((r) => r.data),
        onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['orders'] }); },
    });
}

export function useCancelOrder() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (id: string) => api.post(`/orders/${id}/cancel`).then((r) => r.data),
        onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['orders'] }); },
    });
}

export function useInitiateOrderPayment() {
    return useMutation({
        mutationFn: ({ orderId, gateway = 'khalti' }: { orderId: string; gateway?: string }) =>
            api.post<InitiatePaymentResponse>(`/payments/initiate/order/${orderId}`, {}, { params: { gateway } }).then((r) => r.data),
    });
}
