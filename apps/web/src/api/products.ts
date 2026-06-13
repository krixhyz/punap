import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query';
import { api } from '../lib/api';

export interface Product {
    id: string;
    title: string;
    description: string;
    price: number;
    condition: string;
    transactionTypes: string[];
    images: string[];
    approvalStatus: string;
    ecoScore?: number;
    quantity: number;
    rentFare?: number | null;
    rentDeposit?: number | null;
    rentType?: string | null;
    availableFrom?: string | null;
    categoryId: number;
    category?: { id: number; name: string };
    seller: {
        id: string;
        name: string;
        avatarUrl?: string | null;
        totalEcoScore?: number | null;
        ecoLevel?: string | null;
    };
    province?: { id: number; name: string } | null;
    city?: { id: number; name: string } | null;
    _avg?: { rating: number | null };
    _count?: { reviews: number };
    createdAt: string;
    wishlisted?: boolean;
}

export interface ProductFilters {
    q?: string;
    categoryId?: number;
    transactionType?: string;
    condition?: string;
    minPrice?: number;
    maxPrice?: number;
    provinceId?: number;
    cityId?: number;
    sortBy?: string;
    page?: number;
    limit?: number;
    sellerId?: string;
}

interface PaginatedProducts {
    data: Product[];
    total: number;
    page: number;
    limit: number;
}

export function useProducts(filters: ProductFilters = {}) {
    return useQuery({
        queryKey: ['products', filters],
        queryFn: () =>
            api
                .get<PaginatedProducts>('/products', { params: filters })
                .then((r) => r.data),
        placeholderData: keepPreviousData,
    });
}

export function useProduct(id: string) {
    return useQuery({
        queryKey: ['product', id],
        queryFn: () => api.get<Product>(`/products/${id}`).then((r) => r.data),
        enabled: !!id,
    });
}

export function useMyProducts() {
    return useQuery({
        queryKey: ['products', 'my'],
        queryFn: () => api.get<Product[]>('/products/my').then((r) => r.data),
    });
}

export function useSearchSuggestions(q: string) {
    return useQuery({
        queryKey: ['search-suggestions', q],
        queryFn: () =>
            api
                .get<string[]>('/search/suggestions', { params: { q } })
                .then((r) => r.data),
        enabled: q.length >= 2,
        staleTime: 30_000,
    });
}

export function useSearch(filters: ProductFilters = {}) {
    return useQuery({
        queryKey: ['search', filters],
        queryFn: () =>
            api
                .get<PaginatedProducts>('/search', { params: filters })
                .then((r) => r.data),
        placeholderData: keepPreviousData,
    });
}

export function useToggleWishlist() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (productId: string) =>
            api.post<{ wishlisted: boolean }>(`/wishlist/${productId}`).then((r) => r.data),
        onSuccess: (_, productId) => {
            queryClient.invalidateQueries({ queryKey: ['product', productId] });
            queryClient.invalidateQueries({ queryKey: ['wishlist'] });
        },
    });
}

export function useWishlist(page = 1) {
    return useQuery({
        queryKey: ['wishlist', page],
        queryFn: () =>
            api.get<PaginatedProducts>('/wishlist', { params: { page } }).then((r) => r.data),
    });
}

export interface Category {
    id: number;
    name: string;
    ecoPoints: number;
    children?: Category[];
}

export function useCategories() {
    return useQuery({
        queryKey: ['categories'],
        queryFn: () => api.get<Category[]>('/categories').then((r) => r.data),
        staleTime: 5 * 60_000,
    });
}

export interface Province {
    id: number;
    name: string;
}

export interface City {
    id: number;
    name: string;
    provinceId: number;
}

export function useProvinces() {
    return useQuery({
        queryKey: ['provinces'],
        queryFn: () => api.get<Province[]>('/location/provinces').then((r) => r.data),
        staleTime: 24 * 60 * 60_000,
    });
}

export function useCities(provinceId?: number) {
    return useQuery({
        queryKey: ['cities', provinceId],
        queryFn: () =>
            api.get<City[]>(`/location/cities/${provinceId}`).then((r) => r.data),
        enabled: !!provinceId,
        staleTime: 24 * 60 * 60_000,
    });
}

export interface Review {
    id: string;
    rating: number;
    body?: string | null;
    reviewer: { id: string; name: string; avatarUrl?: string | null };
    createdAt: string;
}

export function useProductReviews(productId: string, page = 1) {
    return useQuery({
        queryKey: ['reviews', 'product', productId, page],
        queryFn: () =>
            api
                .get<{ data: Review[]; total: number }>(`/reviews/product/${productId}`, {
                    params: { page },
                })
                .then((r) => r.data),
        enabled: !!productId,
    });
}

export function useRecentlyViewed() {
    return useQuery({
        queryKey: ['recently-viewed'],
        queryFn: () => api.get<Product[]>('/recently-viewed').then((r) => r.data),
    });
}
