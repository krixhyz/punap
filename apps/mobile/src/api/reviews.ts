import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query';
import { api } from '../lib/api';

export interface Review {
    id: string;
    rating: number;
    body?: string | null;
    transactionType: string;
    reviewer: { id: string; name: string; avatarUrl?: string | null };
    subject: { id: string; name: string };
    product?: { id: string; title: string } | null;
    createdAt: string;
}

interface PaginatedReviews { data: Review[]; total: number; page: number; limit: number; }

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
        queryFn: () => api.get<PaginatedReviews>(`/reviews/product/${productId}`, { params: { page } }).then((r) => r.data),
        enabled: !!productId,
        placeholderData: keepPreviousData,
    });
}

export function useUserReviews(userId: string, page = 1) {
    return useQuery({
        queryKey: ['reviews', 'user', userId, page],
        queryFn: () => api.get<PaginatedReviews>(`/reviews/user/${userId}`, { params: { page } }).then((r) => r.data),
        enabled: !!userId,
        placeholderData: keepPreviousData,
    });
}

export function useCreateReview() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (payload: CreateReviewPayload) =>
            api.post<Review>('/reviews', payload).then((r) => r.data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['reviews'] });
            queryClient.invalidateQueries({ queryKey: ['orders'] });
            queryClient.invalidateQueries({ queryKey: ['rentals'] });
            queryClient.invalidateQueries({ queryKey: ['swaps'] });
        },
    });
}
