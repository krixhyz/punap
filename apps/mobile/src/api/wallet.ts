import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query';
import { api } from '../lib/api';

export interface Wallet { id: string; availableBalance: number; pendingPayoutBalance: number; }

export interface LedgerEntry {
    id: string;
    direction: 'CREDIT' | 'DEBIT';
    entryType: string;
    amount: number;
    balanceAfter: number;
    referenceType?: string | null;
    referenceId?: string | null;
    note?: string | null;
    createdAt: string;
}

export interface PayoutRequest {
    id: string;
    amount: number;
    status: string;
    note?: string | null;
    adminNote?: string | null;
    payoutReference?: string | null;
    createdAt: string;
    processedAt?: string | null;
}

interface PaginatedLedger { data: LedgerEntry[]; total: number; page: number; limit: number; }
interface PaginatedPayouts { data: PayoutRequest[]; total: number; page: number; limit: number; }

export function useWallet() {
    return useQuery({
        queryKey: ['wallet'],
        queryFn: () => api.get<Wallet>('/wallet').then((r) => r.data),
    });
}

export function useWalletLedger(page = 1) {
    return useQuery({
        queryKey: ['wallet-ledger', page],
        queryFn: () => api.get<PaginatedLedger>('/wallet/ledger', { params: { page } }).then((r) => r.data),
        placeholderData: keepPreviousData,
    });
}

export function usePayoutRequests(page = 1) {
    return useQuery({
        queryKey: ['payout-requests', page],
        queryFn: () => api.get<PaginatedPayouts>('/wallet/payouts', { params: { page } }).then((r) => r.data),
        placeholderData: keepPreviousData,
    });
}

export function useCreatePayoutRequest() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (payload: { amount: number; note?: string }) =>
            api.post<PayoutRequest>('/wallet/payout', payload).then((r) => r.data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['wallet'] });
            queryClient.invalidateQueries({ queryKey: ['payout-requests'] });
        },
    });
}
