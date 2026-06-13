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
    seller: { id: string; name: string; avatarUrl?: string | null; totalEcoScore?: number | null; ecoLevel?: string | null };
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

interface PaginatedProducts { data: Product[]; total: number; page: number; limit: number; }

export function useProducts(filters: ProductFilters = {}) {
    return useQuery({
        queryKey: ['products', filters],
        queryFn: () => api.get<PaginatedProducts>('/products', { params: filters }).then((r) => r.data),
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
        queryFn: () => api.get<string[]>('/search/suggestions', { params: { q } }).then((r) => r.data),
        enabled: q.length >= 2,
    });
}

export function useToggleWishlist() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (productId: string) =>
            api.post<{ wishlisted: boolean }>(`/wishlist/${productId}`).then((r) => r.data),
        onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['products'] }); },
    });
}

export function useCategories() {
    return useQuery({
        queryKey: ['categories'],
        queryFn: () => api.get<{ id: number; name: string; children?: { id: number; name: string }[] }[]>('/categories').then((r) => r.data),
        staleTime: 300_000,
    });
}

export function useProvinces() {
    return useQuery({
        queryKey: ['provinces'],
        queryFn: () => api.get<{ id: number; name: string }[]>('/location/provinces').then((r) => r.data),
        staleTime: 600_000,
    });
}

export function useCities(provinceId?: number) {
    return useQuery({
        queryKey: ['cities', provinceId],
        queryFn: () => api.get<{ id: number; name: string }[]>(`/location/cities/${provinceId}`).then((r) => r.data),
        enabled: !!provinceId,
        staleTime: 600_000,
    });
}
