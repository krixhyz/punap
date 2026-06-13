import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query';
import { api } from '../lib/api';

export interface RentalBooking {
    id: string;
    status: string;
    startDate: string;
    endDate: string;
    rentFare: number;
    rentDeposit: number;
    totalRentAmount: number;
    depositAmount: number;
    serviceFee: number;
    totalAmount: number;
    evidencePhotos?: string[];
    renterId: string;
    renter: { id: string; name: string };
    ownerId: string;
    owner: { id: string; name: string };
    productId: string;
    product: { id: string; title: string; images: string[]; rentType?: string | null };
    createdAt: string;
    updatedAt: string;
}

interface PaginatedRentals { data: RentalBooking[]; total: number; page: number; limit: number; }

interface BookRentalPayload {
    productId: string;
    startDate: string;
    endDate: string;
    message?: string;
}

interface ReturnRequestPayload {
    rentalId: string;
    photos: { uri: string; name: string; type: string }[];
}

interface InitiatePaymentResponse { paymentUrl: string; }

export function useRentals(page = 1) {
    return useQuery({
        queryKey: ['rentals', page],
        queryFn: () => api.get<PaginatedRentals>('/rentals', { params: { page } }).then((r) => r.data),
        placeholderData: keepPreviousData,
    });
}

export function useLendingRentals(page = 1) {
    return useQuery({
        queryKey: ['rentals', 'lending', page],
        queryFn: () => api.get<PaginatedRentals>('/rentals/lending', { params: { page } }).then((r) => r.data),
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

export function useBookRental() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (payload: BookRentalPayload) =>
            api.post<RentalBooking>('/rentals/book', payload).then((r) => r.data),
        onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['rentals'] }); },
    });
}

export function useInitiateRentalPayment() {
    return useMutation({
        mutationFn: ({ rentalId, gateway = 'khalti' }: { rentalId: string; gateway?: string }) =>
            api.post<InitiatePaymentResponse>(`/payments/initiate/rental/${rentalId}`, {}, { params: { gateway } }).then((r) => r.data),
    });
}

export function useRequestReturn() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async ({ rentalId, photos }: ReturnRequestPayload) => {
            const form = new FormData();
            photos.forEach((p) => form.append('photos', { uri: p.uri, name: p.name, type: p.type } as unknown as Blob));
            return api.post(`/rentals/${rentalId}/request-return`, form, { headers: { 'Content-Type': 'multipart/form-data' } }).then((r) => r.data);
        },
        onSuccess: (_, { rentalId }) => { queryClient.invalidateQueries({ queryKey: ['rental', rentalId] }); },
    });
}

export function useConfirmReturn() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (rentalId: string) => api.post(`/rentals/${rentalId}/confirm-return`).then((r) => r.data),
        onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['rentals'] }); },
    });
}
