import { useParams, useNavigate } from 'react-router-dom';
import { ChevronLeft, AlertTriangle, CheckCircle } from 'lucide-react';
import { useDispute } from '../../api/disputes';
import { Avatar } from '../../components/Avatar';
import { Badge } from '../../components/Badge';
import { Button } from '../../components/Button';
import { Skeleton } from '../../components/Skeleton';

const STATUS_VARIANT: Record<string, 'warning' | 'success' | 'danger' | 'neutral'> = {
    OPEN: 'warning',
    IN_REVIEW: 'warning',
    RESOLVED: 'success',
    DISMISSED: 'neutral',
};

export default function DisputeDetailPage() {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const { data: dispute, isLoading } = useDispute(id ?? '');

    if (isLoading) {
        return (
            <div className="max-w-2xl mx-auto px-4 py-8 space-y-4">
                <Skeleton className="h-8 w-48" />
                <Skeleton className="h-48 rounded-xl" />
            </div>
        );
    }

    if (!dispute) {
        return (
            <div className="max-w-2xl mx-auto px-4 py-16 text-center text-gray-500">
                <p>Dispute not found.</p>
                <button className="mt-4 text-[#1a6b3c] hover:underline" onClick={() => navigate(-1)}>Go back</button>
            </div>
        );
    }

    return (
        <div className="max-w-2xl mx-auto px-4 py-8">
            <button
                onClick={() => navigate(-1)}
                className="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-6"
            >
                <ChevronLeft className="w-4 h-4" />
                Back to disputes
            </button>

            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-heading font-bold text-gray-900">Dispute Details</h1>
                <Badge variant={STATUS_VARIANT[dispute.status] ?? 'neutral'}>
                    {dispute.status.replace('_', ' ')}
                </Badge>
            </div>

            {/* Subject & description */}
            <div className="bg-white border border-gray-200 rounded-xl p-5 mb-4">
                <div className="flex items-start gap-3 mb-3">
                    <AlertTriangle className="w-5 h-5 text-amber-500 shrink-0 mt-0.5" />
                    <div>
                        <h2 className="font-semibold text-gray-900">{dispute.subject}</h2>
                        <p className="text-xs text-gray-500 mt-0.5">{dispute.transactionType} dispute</p>
                    </div>
                </div>
                <p className="text-sm text-gray-700 leading-relaxed">{dispute.description}</p>
            </div>

            {/* Participants */}
            <div className="bg-white border border-gray-200 rounded-xl p-5 mb-4">
                <h3 className="text-sm font-semibold text-gray-900 mb-3">Parties involved</h3>
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Avatar src={dispute.claimant.avatarUrl} name={dispute.claimant.name} size="sm" />
                        <div>
                            <p className="text-sm font-medium">{dispute.claimant.name}</p>
                            <p className="text-xs text-gray-400">Claimant</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <div className="text-right">
                            <p className="text-sm font-medium">{dispute.respondent.name}</p>
                            <p className="text-xs text-gray-400">Respondent</p>
                        </div>
                        <Avatar src={dispute.respondent.avatarUrl} name={dispute.respondent.name} size="sm" />
                    </div>
                </div>
            </div>

            {/* Evidence photos */}
            {dispute.evidencePhotos?.length > 0 && (
                <div className="bg-white border border-gray-200 rounded-xl p-5 mb-4">
                    <h3 className="text-sm font-semibold text-gray-900 mb-3">Evidence photos</h3>
                    <div className="flex flex-wrap gap-2">
                        {dispute.evidencePhotos.map((url, i) => (
                            <img
                                key={i}
                                src={url}
                                alt="Evidence"
                                className="w-20 h-20 object-cover rounded-lg border border-gray-200 cursor-pointer hover:opacity-90"
                                onClick={() => window.open(url, '_blank')}
                            />
                        ))}
                    </div>
                </div>
            )}

            {/* Resolution */}
            {dispute.status === 'RESOLVED' && dispute.resolution && (
                <div className="bg-[#e8f5ee] border border-[#1a6b3c]/20 rounded-xl p-5 mb-4">
                    <div className="flex items-center gap-2 mb-2">
                        <CheckCircle className="w-4 h-4 text-[#1a6b3c]" />
                        <h3 className="text-sm font-semibold text-[#1a6b3c]">Resolution</h3>
                    </div>
                    <p className="text-sm text-gray-700">{dispute.resolution}</p>
                    {dispute.favoredUser && (
                        <p className="text-sm text-gray-600 mt-2">
                            Favored: <strong>{dispute.favoredUser.name}</strong>
                        </p>
                    )}
                    {dispute.resolvedAt && (
                        <p className="text-xs text-gray-400 mt-1">
                            Resolved on {new Date(dispute.resolvedAt).toLocaleDateString()}
                        </p>
                    )}
                </div>
            )}

            <div className="flex justify-end">
                <Button variant="ghost" onClick={() => navigate('/disputes')}>
                    Back to disputes
                </Button>
            </div>
        </div>
    );
}
