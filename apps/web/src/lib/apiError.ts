import { AxiosError } from 'axios';

export function getApiError(error: unknown): string | null {
    if (!error) return null;
    if (error instanceof AxiosError) {
        const data = error.response?.data;
        if (typeof data?.message === 'string') return data.message;
        if (Array.isArray(data?.message)) return data.message[0];
        if (typeof data?.error === 'string') return data.error;
    }
    return 'Something went wrong. Please try again.';
}
