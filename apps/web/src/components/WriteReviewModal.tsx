import { useState } from 'react';
import { Star } from 'lucide-react';
import toast from 'react-hot-toast';
import { Modal } from './Modal';
import { Button } from './Button';
import { Textarea } from './Textarea';
import { useCreateReview } from '../api/reviews';

interface Props {
    subjectId: string;
    productId: string;
    transactionType: 'ORDER' | 'RENTAL' | 'SWAP';
    orderId?: string;
    rentalBookingId?: string;
    swapId?: string;
    onClose: () => void;
}

export function WriteReviewModal({
    subjectId,
    productId,
    transactionType,
    orderId,
    rentalBookingId,
    swapId,
    onClose,
}: Props) {
    const [rating, setRating] = useState(0);
    const [hovered, setHovered] = useState(0);
    const [body, setBody] = useState('');
    const createReview = useCreateReview();

    async function handleSubmit() {
        if (rating === 0) {
            toast.error('Please select a star rating');
            return;
        }
        try {
            await createReview.mutateAsync({
                subjectId,
                productId,
                transactionType,
                orderId,
                rentalBookingId,
                swapId,
                rating,
                body: body || undefined,
            });
            toast.success('Review submitted!');
            onClose();
        } catch (err: unknown) {
            const msg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message;
            toast.error(msg ?? 'Failed to submit review');
        }
    }

    const display = hovered || rating;

    return (
        <Modal open title="Write a Review" onClose={onClose}>
            <div className="space-y-5">
                <div className="text-center">
                    <p className="text-sm text-gray-600 mb-3">How was your experience?</p>
                    <div className="flex items-center justify-center gap-1">
                        {Array.from({ length: 5 }).map((_, i) => (
                            <button
                                key={i}
                                onMouseEnter={() => setHovered(i + 1)}
                                onMouseLeave={() => setHovered(0)}
                                onClick={() => setRating(i + 1)}
                                className="transition-transform hover:scale-110"
                            >
                                <Star
                                    className={`w-8 h-8 transition-colors ${
                                        i < display
                                            ? 'fill-amber-400 text-amber-400'
                                            : 'text-gray-300 hover:text-amber-300'
                                    }`}
                                />
                            </button>
                        ))}
                    </div>
                    {rating > 0 && (
                        <p className="text-sm text-gray-500 mt-2">
                            {['', 'Poor', 'Fair', 'Good', 'Very good', 'Excellent'][rating]}
                        </p>
                    )}
                </div>

                <Textarea
                    label="Comment (optional)"
                    value={body}
                    onChange={(e) => setBody(e.target.value)}
                    placeholder="Share details about your experience..."
                    rows={3}
                />

                <div className="flex gap-3 justify-end">
                    <Button variant="ghost" onClick={onClose}>Cancel</Button>
                    <Button loading={createReview.isPending} onClick={handleSubmit} disabled={rating === 0}>
                        Submit review
                    </Button>
                </div>
            </div>
        </Modal>
    );
}
