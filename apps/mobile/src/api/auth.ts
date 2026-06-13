import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '../lib/api';
import { useAuthStore, type AuthUser } from '../store/authSlice';

interface LoginPayload { email: string; password: string; }
interface LoginResponse { accessToken: string; user: AuthUser; }
interface RegisterPayload { name: string; email: string; phone: string; password: string; }
interface VerifyEmailPayload { token: string; }
interface ForgotPasswordPayload { email: string; }
interface ResetPasswordPayload { token: string; newPassword: string; }

export function useLogin() {
    return useMutation({
        mutationFn: (payload: LoginPayload) =>
            api.post<LoginResponse>('/auth/login', payload).then((r) => r.data),
        onSuccess: (data) => { useAuthStore.getState().setAuth(data.user, data.accessToken); },
    });
}

export function useRegister() {
    return useMutation({
        mutationFn: (payload: RegisterPayload) =>
            api.post('/auth/register', payload).then((r) => r.data),
    });
}

export function useLogout() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: () => api.post('/auth/logout').then((r) => r.data),
        onSettled: () => { useAuthStore.getState().clearAuth(); queryClient.clear(); },
    });
}

export function useVerifyEmail() {
    return useMutation({
        mutationFn: (payload: VerifyEmailPayload) =>
            api.post('/auth/verify-email', payload).then((r) => r.data),
    });
}

export function useForgotPassword() {
    return useMutation({
        mutationFn: (payload: ForgotPasswordPayload) =>
            api.post('/auth/forgot-password', payload).then((r) => r.data),
    });
}

export function useResetPassword() {
    return useMutation({
        mutationFn: (payload: ResetPasswordPayload) =>
            api.post('/auth/reset-password', payload).then((r) => r.data),
    });
}

export function useResendVerification() {
    return useMutation({
        mutationFn: (payload: { email: string }) =>
            api.post('/auth/resend-verification', payload).then((r) => r.data),
    });
}
