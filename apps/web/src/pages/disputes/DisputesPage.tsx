import { Link } from 'react-router-dom';
import { AlertTriangle } from 'lucide-react';
import { useMyDisputes } from '../../api/disputes';
import { Badge } from '../../components/Badge';
import { Skeleton } from '../../components/Skeleton';
import { Pagination } from '../../components/Pagination';
import { usePagination } from '../../hooks/usePagination';

const STATUS_VARIANT: Record<string, 'warning' | 'success' | 'danger' | 'neutral'> = {
    OPEN: 'warning',
    IN_REVIEW: 'warning',
    RESOLVED: 'success',
    DISMISSED: 'neutral',
};

export default function DisputesPage() {
    const { page, goToPage } = usePagination();
    const { data, isLoading } = useMyDisputes(page);

    if (isLoading) {
        return (
            <div className="max-w-3xl mx-auto px-4 py-8 space-y-3">
                <Skeleton className="h-8 w-48" />
                {Array.from({ length: 4 }).map((_, i) => <Skeleton key={i} className="h-20 rounded-xl" />)}
            </div>
        );
    }

    return (
        <div className="max-w-3xl mx-auto px-4 py-8">
            <h1 className="text-2xl font-heading font-bold text-gray-900 mb-6">My Disputes</h1>

            {!data || data.data.length === 0 ? (
                <div className="text-center py-16 text-gray-400">
                    <AlertTriangle className="w-10 h-10 mx-auto mb-3 opacity-30" />
                    <p className="text-sm">No disputes found.</p>
                </div>
            ) : (
                <div className="space-y-3">
                    {data.data.map((dispute) => (
                        <Link
                            key={dispute.id}
                            to={`/disputes/${dispute.id}`}
                            className="flex items-center gap-4 bg-white border border-gray-200 rounded-xl p-4 hover:shadow-md transition-shadow"
                        >
                            <AlertTriangle className="w-5 h-5 text-amber-500 shrink-0" />
                            <div className="flex-1 min-w-0">
                                <p className="text-sm font-medium text-gray-900 truncate">{dispute.subject}</p>
                                <p className="text-xs text-gray-500 mt-0.5">
                                    {dispute.transactionType} · Against: {dispute.respondent.name}
                                </p>
                            </div>
                            <div className="flex flex-col items-end gap-1 shrink-0">
                                <Badge variant={STATUS_VARIANT[dispute.status] ?? 'neutral'} size="sm">
                                    {dispute.status.replace('_', ' ')}
                                </Badge>
                                <span className="text-xs text-gray-400">
                                    {new Date(dispute.createdAt).toLocaleDateString()}
                                </span>
                            </div>
                        </Link>
                    ))}
                    {data.total > 10 && (
                        <div className="mt-4 flex justify-center">
                            <Pagination
                                page={page}
                                totalPages={Math.ceil(data.total / 10)}
                                onPageChange={goToPage}
                            />
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
