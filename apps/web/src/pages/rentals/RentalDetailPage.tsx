import { useRef, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { Calendar, Upload, X } from 'lucide-react';
import toast from 'react-hot-toast';
import { useRental, useCancelRental, useRequestReturn, useConfirmReturn } from '../../api/rentals';
import { Badge } from '../../components/Badge';
import { Button } from '../../components/Button';
import { Skeleton } from '../../components/Skeleton';
import { Avatar } from '../../components/Avatar';
import { Modal } from '../../components/Modal';
import { WriteReviewModal } from '../../components/WriteReviewModal';
import { OpenDisputeModal } from '../../components/OpenDisputeModal';
import { useAuth } from '../../hooks/useAuth';

const STATUS_VARIANTS: Record<string, 'success' | 'warning' | 'danger' | 'neutral'> = {
    PENDING_PAYMENT: 'warning',
    ACTIVE: 'success',
    RETURN_REQUESTED: 'warning',
    COMPLETED: 'success',
    CANCELLED: 'danger',
    DISPUTED: 'danger',
};

const STATUS_STEPS = ['PENDING_PAYMENT', 'ACTIVE', 'RETURN_REQUESTED', 'COMPLETED'];

function StatusTimeline({ status }: { status: string }) {
    const currentIndex = STATUS_STEPS.indexOf(status);
    const labels: Record<string, string> = {
        PENDING_PAYMENT: 'Pending',
        ACTIVE: 'Active',
        RETURN_REQUESTED: 'Returning',
        COMPLETED: 'Done',
    };
    return (
        <div className="flex items-center gap-1 overflow-x-auto">
            {STATUS_STEPS.map((step, i) => (
                <div key={step} className="flex items-center gap-1 flex-shrink-0">
                    <div className={[
                        'flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold',
                        i < currentIndex ? 'bg-[#1a6b3c] text-white' :
                        i === currentIndex ? 'bg-[#1a6b3c] text-white ring-2 ring-[#1a6b3c] ring-offset-2' :
                        'bg-gray-200 text-gray-400',
                    ].join(' ')}>
                        {i < currentIndex ? '✓' : i + 1}
                    </div>
                    <span className={`text-xs whitespace-nowrap ${i <= currentIndex ? 'text-gray-800 font-medium' : 'text-gray-400'}`}>
                        {labels[step]}
                    </span>
                    {i < STATUS_STEPS.length - 1 && (
                        <div className={`w-6 h-0.5 ${i < currentIndex ? 'bg-[#1a6b3c]' : 'bg-gray-200'}`} />
                    )}
                </div>
            ))}
        </div>
    );
}

function ReturnRequestModal({ rentalId, onClose }: { rentalId: string; onClose: () => void }) {
    const requestReturn = useRequestReturn();
    const [files, setFiles] = useState<File[]>([]);
    const fileRef = useRef<HTMLInputElement>(null);

    async function handleSubmit() {
        const formData = new FormData();
        files.forEach((f) => formData.append('photos', f));
        try {
            await requestReturn.mutateAsync({ id: rentalId, formData });
            toast.success('Return request submitted');
            onClose();
        } catch {
            toast.error('Could not submit return request');
        }
    }

    return (
        <Modal open onClose={onClose} title="Request return">
            <div className="space-y-4">
                <p className="text-sm text-gray-600">
                    Upload photos of the item before returning it. This protects both parties.
                </p>
                <input
                    ref={fileRef}
                    type="file"
                    accept="image/*"
                    multiple
                    className="hidden"
                    onChange={(e) => setFiles(Array.from(e.target.files ?? []))}
                />
                <button
                    type="button"
                    onClick={() => fileRef.current?.click()}
                    className="w-full border-2 border-dashed border-gray-300 rounded-xl py-8 flex flex-col items-center gap-2 text-sm text-gray-500 hover:border-[#1a6b3c] transition-colors"
                >
                    <Upload className="w-6 h-6" />
                    Click to upload evidence photos
                </button>
                {files.length > 0 && (
                    <div className="flex flex-wrap gap-2">
                        {files.map((f, i) => (
                            <div key={i} className="flex items-center gap-1 bg-gray-100 px-2.5 py-1 rounded-lg text-xs">
                                {f.name}
                                <button onClick={() => setFiles((prev) => prev.filter((_, j) => j !== i))}>
                                    <X className="w-3 h-3" />
                                </button>
                            </div>
                        ))}
                    </div>
                )}
                <div className="flex gap-3 justify-end">
                    <Button variant="ghost" onClick={onClose}>Cancel</Button>
                    <Button loading={requestReturn.isPending} onClick={handleSubmit}>
                        Submit return request
                    </Button>
                </div>
            </div>
        </Modal>
    );
}

export default function RentalDetailPage() {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const { user } = useAuth();
    const { data: rental, isLoading } = useRental(id ?? '');
    const cancelRental = useCancelRental();
    const confirmReturn = useConfirmReturn();
    const [showReturnModal, setShowReturnModal] = useState(false);
    const [showReview, setShowReview] = useState(false);
    const [showDispute, setShowDispute] = useState(false);

    if (isLoading) {
        return (
            <div className="max-w-2xl mx-auto px-4 py-8 space-y-4">
                <Skeleton className="h-8 w-48" />
                <Skeleton className="h-32 rounded-xl" />
                <Skeleton className="h-48 rounded-xl" />
            </div>
        );
    }

    if (!rental) {
        return (
            <div className="max-w-2xl mx-auto px-4 py-16 text-center text-gray-500">
                <p>Rental not found.</p>
                <button className="text-[#1a6b3c] text-sm hover:underline mt-2" onClick={() => navigate('/rentals')}>
                    Back to rentals
                </button>
            </div>
        );
    }

    const isRenter = user?.id === rental.renter.id;
    const isOwner = user?.id === rental.product.seller.id;
    const image = rental.product.images?.[0];
    const start = new Date(rental.startDate).toLocaleDateString();
    const end = new Date(rental.endDate).toLocaleDateString();

    async function handleCancel() {
        try {
            await cancelRental.mutateAsync(rental!.id);
            toast.success('Rental cancelled');
        } catch {
            toast.error('Could not cancel rental');
        }
    }

    async function handleConfirmReturn() {
        try {
            await confirmReturn.mutateAsync(rental!.id);
            toast.success('Return confirmed — rental completed');
        } catch {
            toast.error('Could not confirm return');
        }
    }

    return (
        <div className="max-w-2xl mx-auto px-4 py-8">
            {showReturnModal && (
                <ReturnRequestModal rentalId={rental.id} onClose={() => setShowReturnModal(false)} />
            )}

            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-heading font-bold text-gray-900">Rental details</h1>
                <Badge variant={STATUS_VARIANTS[rental.status] ?? 'neutral'}>
                    {rental.status.replace('_', ' ')}
                </Badge>
            </div>

            {/* Timeline */}
            {!['CANCELLED', 'DISPUTED'].includes(rental.status) && (
                <div className="bg-white border border-gray-200 rounded-xl p-4 mb-4 overflow-x-auto">
                    <StatusTimeline status={rental.status} />
                </div>
            )}

            {/* Product + dates */}
            <div className="bg-white border border-gray-200 rounded-xl p-4 mb-4 flex items-start gap-4">
                <div className="w-16 h-16 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                    {image ? (
                        <img src={image} alt={rental.product.title} className="w-full h-full object-cover" />
                    ) : (
                        <div className="w-full h-full bg-gray-200" />
                    )}
                </div>
                <div className="flex-1 min-w-0">
                    <Link to={`/products/${rental.product.id}`} className="font-medium text-gray-900 hover:text-[#1a6b3c] block truncate">
                        {rental.product.title}
                    </Link>
                    <p className="text-sm text-gray-500 mt-1 flex items-center gap-1">
                        <Calendar className="w-3.5 h-3.5" />
                        {start} – {end}
                    </p>
                </div>
            </div>

            {/* Pricing */}
            <div className="bg-white border border-gray-200 rounded-xl p-4 mb-4 space-y-2">
                <h3 className="font-medium text-gray-900 mb-3">Payment breakdown</h3>
                <div className="flex justify-between text-sm">
                    <span className="text-gray-500">Rental fare</span>
                    <span>Rs {rental.totalFare.toLocaleString()}</span>
                </div>
                <div className="flex justify-between text-sm">
                    <span className="text-gray-500">Deposit</span>
                    <span>Rs {rental.deposit.toLocaleString()}</span>
                </div>
                <div className="flex justify-between text-sm">
                    <span className="text-gray-500">Service fee</span>
                    <span>Rs {rental.serviceFee.toLocaleString()}</span>
                </div>
                <div className="border-t border-gray-100 pt-2 flex justify-between font-semibold">
                    <span>Total paid</span>
                    <span>Rs {rental.totalAmount.toLocaleString()}</span>
                </div>
                {rental.deposit_record && (
                    <p className="text-xs text-gray-400 mt-1">
                        Deposit: <span className={rental.deposit_record.status === 'REFUNDED' ? 'text-green-600' : ''}>
                            {rental.deposit_record.status}
                        </span>
                    </p>
                )}
            </div>

            {/* Evidence photos (if return requested) */}
            {rental.evidencePhotos?.length > 0 && (
                <div className="bg-white border border-gray-200 rounded-xl p-4 mb-4">
                    <h3 className="font-medium text-gray-900 mb-3">Evidence photos</h3>
                    <div className="flex flex-wrap gap-2">
                        {rental.evidencePhotos.map((url, i) => (
                            <img key={i} src={url} alt="Evidence" className="w-20 h-20 object-cover rounded-lg border border-gray-200" />
                        ))}
                    </div>
                </div>
            )}

            {/* Participants */}
            <div className="bg-white border border-gray-200 rounded-xl p-4 mb-6 flex items-center gap-4">
                <Avatar src={rental.renter.avatarUrl} name={rental.renter.name} size="sm" />
                <div>
                    <p className="text-sm font-medium">{rental.renter.name}</p>
                    <p className="text-xs text-gray-400">Renter</p>
                </div>
                <div className="ml-auto text-right">
                    <p className="text-sm font-medium">{rental.product.seller.name}</p>
                    <p className="text-xs text-gray-400">Owner</p>
                </div>
            </div>

            {/* Actions */}
            <div className="flex flex-wrap gap-3">
                {isRenter && rental.status === 'PENDING_PAYMENT' && (
                    <Button variant="danger" loading={cancelRental.isPending} onClick={handleCancel}>
                        Cancel booking
                    </Button>
                )}
                {isRenter && rental.status === 'ACTIVE' && (
                    <Button variant="secondary" onClick={() => setShowReturnModal(true)}>
                        Request return
                    </Button>
                )}
                {isOwner && rental.status === 'RETURN_REQUESTED' && (
                    <Button loading={confirmReturn.isPending} onClick={handleConfirmReturn}>
                        Confirm return
                    </Button>
                )}
                {rental.status === 'COMPLETED' && !rental.reviewExists && (
                    <Button variant="ghost" onClick={() => setShowReview(true)} className="border border-amber-300 text-amber-700 hover:bg-amber-50">
                        Write a review
                    </Button>
                )}
                {['ACTIVE', 'RETURN_REQUESTED', 'COMPLETED'].includes(rental.status) && (
                    <Button variant="ghost" onClick={() => setShowDispute(true)} className="border border-red-300 text-red-600 hover:bg-red-50">
                        Open dispute
                    </Button>
                )}
                <Link to="/rentals" className="ml-auto">
                    <Button variant="ghost">Back to rentals</Button>
                </Link>
            </div>

            {showReview && (
                <WriteReviewModal
                    subjectId={isRenter ? rental.product.seller.id : rental.renter.id}
                    productId={rental.product.id}
                    transactionType="RENTAL"
                    rentalBookingId={rental.id}
                    onClose={() => setShowReview(false)}
                />
            )}
            {showDispute && (
                <OpenDisputeModal
                    respondentId={isRenter ? rental.product.seller.id : rental.renter.id}
                    respondentName={isRenter ? rental.product.seller.name : rental.renter.name}
                    transactionType="RENTAL"
                    rentalBookingId={rental.id}
                    onClose={() => setShowDispute(false)}
                />
            )}
        </div>
    );
}
