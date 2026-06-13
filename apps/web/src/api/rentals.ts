import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query';
import { api } from '../lib/api';

export interface RentalBooking {
    id: string;
    status: string;
    startDate: string;
    endDate: string;
    totalFare: number;
    deposit: number;
    serviceFee: number;
    totalAmount: number;
    evidencePhotos: string[];
    returnRequestedAt?: string | null;
    returnedAt?: string | null;
    product: {
        id: string;
        title: string;
        images: string[];
        seller: { id: string; name: string };
    };
    renter: { id: string; name: string; avatarUrl?: string | null };
    createdAt: string;
    payment?: { id: string; status: string; gateway: string } | null;
    deposit_record?: { id: string; status: string; refundedAt?: string | null } | null;
    reviewExists?: boolean;
}

interface PaginatedRentals {
    data: RentalBooking[];
    total: number;
    page: number;
    limit: number;
}

interface BookRentalPayload {
    productId: string;
    startDate: string;
    endDate: string;
    message?: string;
}

export function useBookRental() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (payload: BookRentalPayload) =>
            api.post<RentalBooking>('/rentals/book', payload).then((r) => r.data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['rentals'] });
        },
    });
}

export function useRentals(page = 1) {
    return useQuery({
        queryKey: ['rentals', 'renting', page],
        queryFn: () =>
            api.get<PaginatedRentals>('/rentals', { params: { page } }).then((r) => r.data),
        placeholderData: keepPreviousData,
    });
}

export function useLendingRentals(page = 1) {
    return useQuery({
        queryKey: ['rentals', 'lending', page],
        queryFn: () =>
            api.get<PaginatedRentals>('/rentals/lending', { params: { page } }).then((r) => r.data),
        placeholderData: keepPreviousData,
    });
}

export function useRental(id: string) {
    return useQuery({
        queryKey: ['rental', id],
        queryFn: () => api.get<RentalBooking>(`/rentals/${id}`).then((r) => r.data),
        enabled: !!id,
    });
}

export function useCancelRental() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (id: string) =>
            api.post<RentalBooking>(`/rentals/${id}/cancel`).then((r) => r.data),
        onSuccess: (_, id) => {
            queryClient.invalidateQueries({ queryKey: ['rental', id] });
            queryClient.invalidateQueries({ queryKey: ['rentals'] });
        },
    });
}

export function useRequestReturn() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: ({ id, formData }: { id: string; formData: FormData }) =>
            api
                .post<RentalBooking>(`/rentals/${id}/request-return`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                })
                .then((r) => r.data),
        onSuccess: (_, { id }) => {
            queryClient.invalidateQueries({ queryKey: ['rental', id] });
        },
    });
}

export function useConfirmReturn() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (id: string) =>
            api.post<RentalBooking>(`/rentals/${id}/confirm-return`).then((r) => r.data),
        onSuccess: (_, id) => {
            queryClient.invalidateQueries({ queryKey: ['rental', id] });
        },
    });
}

export function useInitiateRentalPayment() {
    return useMutation({
        mutationFn: ({ rentalId, gateway = 'khalti' }: { rentalId: string; gateway?: string }) =>
            api
                .post<{ paymentUrl: string }>(`/payments/initiate/rental/${rentalId}`, {}, { params: { gateway } })
                .then((r) => r.data),
    });
}
