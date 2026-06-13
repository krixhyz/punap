import { QueryClientProvider } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import { Toaster } from 'react-hot-toast';
import { queryClient } from './lib/queryClient';
import { AppRouter } from './router/AppRouter';

export default function App() {
    return (
        <QueryClientProvider client={queryClient}>
            <AppRouter />
            <Toaster
                position="top-right"
                toastOptions={{
                    duration: 4000,
                    style: { fontFamily: 'Inter, sans-serif' },
                }}
            />
            {import.meta.env.DEV && <ReactQueryDevtools initialIsOpen={false} />}
        </QueryClientProvider>
    );
}
