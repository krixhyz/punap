import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '../lib/api';

export interface Review {
    id: string;
    rating: number;
    body?: string | null;
    transactionType: string;
    reviewer: { id: string; name: string; avatarUrl?: string | null };
    subject: { id: string; name: string };
    createdAt: string;
}

interface PaginatedReviews {
    data: Review[];
    total: number;
    page: number;
    limit: number;
}

interface CreateReviewPayload {
    subjectId: string;
    productId: string;
    transactionType: string;
    orderId?: string;
    rentalBookingId?: string;
    swapId?: string;
    rating: number;
    body?: string;
}

export function useProductReviews(productId: string, page = 1) {
    return useQuery({
        queryKey: ['reviews', 'product', productId, page],
        queryFn: () =>
            api.get<PaginatedReviews>(`/reviews/product/${productId}`, { params: { page } })
                .then((r) => r.data),
        enabled: !!productId,
    });
}

export function useUserReviews(userId: string, page = 1) {
    return useQuery({
        queryKey: ['reviews', 'user', userId, page],
        queryFn: () =>
            api.get<PaginatedReviews>(`/reviews/user/${userId}`, { params: { page } })
                .then((r) => r.data),
        enabled: !!userId,
    });
}

export function useCreateReview() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (payload: CreateReviewPayload) =>
            api.post<Review>('/reviews', payload).then((r) => r.data),
        onSuccess: (_, variables) => {
            queryClient.invalidateQueries({ queryKey: ['reviews'] });
            if (variables.orderId) queryClient.invalidateQueries({ queryKey: ['order', variables.orderId] });
            if (variables.rentalBookingId) queryClient.invalidateQueries({ queryKey: ['rental', variables.rentalBookingId] });
            if (variables.swapId) queryClient.invalidateQueries({ queryKey: ['swap', variables.swapId] });
        },
    });
}
