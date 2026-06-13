import { useState } from 'react';

export function usePagination(initialLimit = 20) {
    const [page, setPage] = useState(1);
    const [limit] = useState(initialLimit);

    const goToPage = (p: number) => setPage(p);
    const nextPage = () => setPage((p) => p + 1);
    const prevPage = () => setPage((p) => Math.max(1, p - 1));

    return { page, limit, setPage, goToPage, nextPage, prevPage };
}
