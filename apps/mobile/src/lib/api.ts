import axios, { AxiosError } from 'axios';
import { useAuthStore } from '../store/authSlice';

const BASE_URL = process.env.EXPO_PUBLIC_API_URL ?? 'http://localhost:3001';

export const api = axios.create({
    baseURL: BASE_URL,
    withCredentials: true,
});

api.interceptors.request.use((config) => {
    const token = useAuthStore.getState().accessToken;
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

let isRefreshing = false;
let refreshQueue: Array<(token: string) => void> = [];

function processQueue(token: string) {
    refreshQueue.forEach((cb) => cb(token));
    refreshQueue = [];
}

api.interceptors.response.use(
    (res) => res,
    async (error: AxiosError) => {
        const original = error.config as typeof error.config & { _retry?: boolean };
        if (error.response?.status !== 401 || original?._retry) {
            return Promise.reject(error);
        }
        original._retry = true;

        if (isRefreshing) {
            return new Promise((resolve) => {
                refreshQueue.push((token: string) => {
                    original.headers!.Authorization = `Bearer ${token}`;
                    resolve(api(original));
                });
            });
        }

        isRefreshing = true;
        try {
            const { data } = await axios.post<{ accessToken: string }>(
                `${BASE_URL}/auth/refresh`,
                {},
                { withCredentials: true },
            );
            const newToken = data.accessToken;
            const { user } = useAuthStore.getState();
            if (user) {
                useAuthStore.getState().setAuth(user, newToken);
            }
            processQueue(newToken);
            original.headers!.Authorization = `Bearer ${newToken}`;
            isRefreshing = false;
            return api(original);
        } catch {
            isRefreshing = false;
            processQueue('');
            useAuthStore.getState().clearAuth();
            return Promise.reject(error);
        }
    },
);
