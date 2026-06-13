import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query';
import { api } from '../lib/api';
import type { Dispute } from './disputes';

export interface AdminStats {
    totalUsers: number;
    totalProducts: number;
    activeRentals: number;
    openDisputes: number;
    pendingPayouts: number;
    platformBalance: number;
    monthlyRevenue: number;
}

export interface AdminUser {
    id: string;
    name: string;
    email: string;
    role: string;
    accountStatus: string;
    totalEcoScore: number;
    ecoLevel: string;
    createdAt: string;
    _count?: { listings: number };
    wallet?: { availableBalance: number } | null;
}

export interface AdminProduct {
    id: string;
    title: string;
    price: number;
    approvalStatus: string;
    transactionTypes: string[];
    images: string[];
    condition: string;
    seller: { id: string; name: string };
    createdAt: string;
}

export interface AdminPayout {
    id: string;
    amount: number;
    status: string;
    note?: string | null;
    adminNote?: string | null;
    payoutReference?: string | null;
    user: { id: string; name: string; email: string };
    createdAt: string;
}

export interface PlatformSetting {
    key: string;
    value: string;
    description?: string | null;
    updatedAt: string;
}

interface Paginated<T> {
    data: T[];
    total: number;
    page: number;
    limit: number;
}

export function useAdminStats() {
    return useQuery({
        queryKey: ['admin-stats'],
        queryFn: () => api.get<AdminStats>('/admin/stats').then((r) => r.data),
    });
}

export function useAdminUsers(page = 1, search = '') {
    return useQuery({
        queryKey: ['admin-users', page, search],
        queryFn: () =>
            api.get<Paginated<AdminUser>>('/admin/users', { params: { page, search } }).then((r) => r.data),
        placeholderData: keepPreviousData,
    });
}

export function useAdminProducts(page = 1, approvalStatus = '') {
    return useQuery({
        queryKey: ['admin-products', page, approvalStatus],
        queryFn: () =>
            api
                .get<Paginated<AdminProduct>>('/admin/products', { params: { page, approvalStatus: approvalStatus || undefined } })
                .then((r) => r.data),
        placeholderData: keepPreviousData,
    });
}

export function useAdminDisputes(page = 1) {
    return useQuery({
        queryKey: ['admin-disputes', page],
        queryFn: () =>
            api.get<Paginated<Dispute>>('/admin/disputes', { params: { page } }).then((r) => r.data),
        placeholderData: keepPreviousData,
    });
}

export function useAdminPayouts(page = 1) {
    return useQuery({
        queryKey: ['admin-payouts', page],
        queryFn: () =>
            api.get<Paginated<AdminPayout>>('/admin/payouts', { params: { page } }).then((r) => r.data),
        placeholderData: keepPreviousData,
    });
}

export function useAdminSettings() {
    return useQuery({
        queryKey: ['admin-settings'],
        queryFn: () => api.get<PlatformSetting[]>('/admin/settings').then((r) => r.data),
    });
}

export function useApproveProduct() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (id: string) =>
            api.patch(`/admin/products/${id}/approve`).then((r) => r.data),
        onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-products'] }),
    });
}

export function useRejectProduct() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: ({ id, reason }: { id: string; reason: string }) =>
            api.patch(`/admin/products/${id}/reject`, { reason }).then((r) => r.data),
        onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-products'] }),
    });
}

export function useSuspendUser() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: ({ id, reason }: { id: string; reason: string }) =>
            api.patch(`/admin/users/${id}/suspend`, { reason }).then((r) => r.data),
        onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-users'] }),
    });
}

export function useBanUser() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: ({ id, reason }: { id: string; reason: string }) =>
            api.patch(`/admin/users/${id}/ban`, { reason }).then((r) => r.data),
        onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-users'] }),
    });
}

export function useActivateUser() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (id: string) =>
            api.patch(`/admin/users/${id}/activate`).then((r) => r.data),
        onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-users'] }),
    });
}

export function useResolveDispute() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: ({
            id,
            resolution,
            favoredUserId,
            rentalClaimAmount,
        }: {
            id: string;
            resolution: string;
            favoredUserId?: string;
            rentalClaimAmount?: number;
        }) =>
            api
                .patch<Dispute>(`/admin/disputes/${id}/resolve`, { resolution, favoredUserId, rentalClaimAmount })
                .then((r) => r.data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['admin-disputes'] });
            queryClient.invalidateQueries({ queryKey: ['disputes'] });
        },
    });
}

export function useApproveAdminPayout() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (id: string) => api.post(`/admin/payouts/${id}/approve`).then((r) => r.data),
        onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-payouts'] }),
    });
}

export function useRejectAdminPayout() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: ({ id, reason }: { id: string; reason: string }) =>
            api.post(`/admin/payouts/${id}/reject`, { reason }).then((r) => r.data),
        onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-payouts'] }),
    });
}

export function useMarkPayoutPaid() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: ({
            id,
            payoutReference,
            note,
        }: {
            id: string;
            payoutReference: string;
            note?: string;
        }) =>
            api.post(`/admin/payouts/${id}/mark-paid`, { payoutReference, note }).then((r) => r.data),
        onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-payouts'] }),
    });
}

export function useUpdateSetting() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: ({ key, value }: { key: string; value: string }) =>
            api.patch<PlatformSetting>(`/admin/settings/${key}`, { value }).then((r) => r.data),
        onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-settings'] }),
    });
}
