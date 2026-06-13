import { useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { ChevronLeft, ArrowLeftRight, CheckCircle, XCircle } from 'lucide-react';
import toast from 'react-hot-toast';
import {
    useSwap,
    useSwapEvents,
    useAcceptSwap,
    useRejectSwap,
    useCancelSwap,
    useConfirmReceived,
    useCounterOffer,
    useInitiateSwapPayment,
    type MoneyDirection,
} from '../../api/swaps';
import { Avatar } from '../../components/Avatar';
import { Badge } from '../../components/Badge';
import { Button } from '../../components/Button';
import { Skeleton } from '../../components/Skeleton';
import { Input } from '../../components/Input';
import { Select } from '../../components/Select';
import { Textarea } from '../../components/Textarea';
import { WriteReviewModal } from '../../components/WriteReviewModal';
import { useAuth } from '../../hooks/useAuth';

const STATUS_VARIANT: Record<string, 'success' | 'warning' | 'danger' | 'neutral'> = {
    PENDING: 'warning',
    COUNTERED: 'warning',
    ACCEPTED: 'success',
    AWAITING_PAYMENT: 'warning',
    CONFIRMATION_PENDING: 'neutral',
    COMPLETED: 'success',
    REJECTED: 'danger',
    CANCELLED: 'danger',
};

const EVENT_TYPE_LABEL: Record<string, string> = {
    INITIAL_OFFER: 'Initial offer',
    COUNTER_OFFER: 'Counter offer',
    ACCEPT: 'Accepted',
    REJECT: 'Rejected',
    CANCEL: 'Cancelled',
};

function CounterForm({ swapId, onDone }: { swapId: string; onDone: () => void }) {
    const counter = useCounterOffer();
    const [moneyDirection, setMoneyDirection] = useState<MoneyDirection>('NONE');
    const [amount, setAmount] = useState('');
    const [message, setMessage] = useState('');

    async function handleSubmit() {
        try {
            const payload: {
                id: string;
                moneyDirection: MoneyDirection;
                message?: string;
                offeredAmount?: number;
                askedAmount?: number;
            } = { id: swapId, moneyDirection };
            if (message) payload.message = message;
            if (moneyDirection === 'REQUESTER_OFFERS_CASH' && amount) payload.offeredAmount = parseFloat(amount);
            if (moneyDirection === 'OWNER_ASKS_CASH' && amount) payload.askedAmount = parseFloat(amount);
            await counter.mutateAsync(payload);
            toast.success('Counter offer sent');
            onDone();
        } catch {
            toast.error('Failed to send counter offer');
        }
    }

    return (
        <div className="bg-purple-50 border border-purple-200 rounded-xl p-4 space-y-3">
            <h3 className="text-sm font-semibold text-purple-900">Send Counter Offer</h3>
            <Select
                label="Cash adjustment"
                value={moneyDirection}
                onChange={(e) => setMoneyDirection(e.target.value as MoneyDirection)}
            >
                <option value="NONE">No cash top-up</option>
                <option value="REQUESTER_OFFERS_CASH">I'll add cash</option>
                <option value="OWNER_ASKS_CASH">I'm requesting cash</option>
            </Select>
            {moneyDirection !== 'NONE' && (
                <Input
                    label="Amount (Rs)"
                    type="number"
                    min="0"
                    value={amount}
                    onChange={(e) => setAmount(e.target.value)}
                />
            )}
            <Textarea
                label="Message"
                value={message}
                onChange={(e) => setMessage(e.target.value)}
                rows={2}
            />
            <div className="flex gap-2 justify-end">
                <Button variant="ghost" onClick={onDone}>Cancel</Button>
                <Button loading={counter.isPending} onClick={handleSubmit} className="!bg-purple-600 hover:!bg-purple-700">
                    Send counter
                </Button>
            </div>
        </div>
    );
}

export default function SwapDetailPage() {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const { user } = useAuth();
    const { data: swap, isLoading } = useSwap(id ?? '');
    const { data: events } = useSwapEvents(id ?? '');
    const accept = useAcceptSwap();
    const reject = useRejectSwap();
    const cancel = useCancelSwap();
    const confirm = useConfirmReceived();
    const initiatePayment = useInitiateSwapPayment();
    const [showCounter, setShowCounter] = useState(false);
    const [showReview, setShowReview] = useState(false);

    if (isLoading) {
        return (
            <div className="max-w-3xl mx-auto px-4 py-8 space-y-4">
                <Skeleton className="h-8 w-48" />
                <Skeleton className="h-48 rounded-xl" />
                <Skeleton className="h-32 rounded-xl" />
            </div>
        );
    }

    if (!swap) {
        return (
            <div className="max-w-3xl mx-auto px-4 py-16 text-center text-gray-500">
                <p>Swap not found.</p>
                <button className="mt-4 text-[#1a6b3c] hover:underline" onClick={() => navigate(-1)}>Go back</button>
            </div>
        );
    }

    const isRequester = user?.id === swap.requesterId;
    const isOwner = user?.id === swap.ownerId;
    const canAct = ['PENDING', 'COUNTERED'].includes(swap.status);
    const ownerHasConfirmed = swap.confirmation?.ownerConfirmedAt != null;
    const requesterHasConfirmed = swap.confirmation?.requesterConfirmedAt != null;
    const myConfirmed = isOwner ? ownerHasConfirmed : requesterHasConfirmed;

    async function handleAccept() {
        try {
            await accept.mutateAsync(swap!.id);
            toast.success('Swap accepted');
        } catch { toast.error('Failed to accept'); }
    }

    async function handleReject() {
        try {
            await reject.mutateAsync(swap!.id);
            toast.success('Swap rejected');
        } catch { toast.error('Failed to reject'); }
    }

    async function handleCancel() {
        try {
            await cancel.mutateAsync(swap!.id);
            toast.success('Swap cancelled');
        } catch { toast.error('Failed to cancel'); }
    }

    async function handleConfirm() {
        try {
            await confirm.mutateAsync(swap!.id);
            toast.success('Receipt confirmed!');
        } catch { toast.error('Failed to confirm receipt'); }
    }

    async function handlePayment() {
        try {
            const { paymentUrl } = await initiatePayment.mutateAsync({ swapId: swap!.id });
            window.location.href = paymentUrl;
        } catch { toast.error('Failed to initiate payment'); }
    }

    return (
        <div className="max-w-3xl mx-auto px-4 py-8">
            <button
                onClick={() => navigate(-1)}
                className="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-6"
            >
                <ChevronLeft className="w-4 h-4" />
                Back to swaps
            </button>

            {/* Header */}
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-heading font-bold text-gray-900">Swap Details</h1>
                <Badge variant={STATUS_VARIANT[swap.status] ?? 'neutral'}>
                    {swap.status.replace('_', ' ')}
                </Badge>
            </div>

            {/* Products side by side */}
            <div className="grid grid-cols-2 gap-4 mb-6">
                {[
                    { label: 'They have', product: swap.product, person: swap.owner },
                    { label: 'You offer', product: swap.offeredProduct, person: swap.requester },
                ].map(({ label, product, person }) => (
                    <div key={product.id} className="bg-white border border-gray-200 rounded-xl p-4">
                        <p className="text-xs text-gray-500 mb-2 uppercase tracking-wide">{label}</p>
                        <div className="aspect-video bg-gray-100 rounded-lg overflow-hidden mb-3">
                            {product.images?.[0] ? (
                                <img src={product.images[0]} alt={product.title} className="w-full h-full object-cover" />
                            ) : (
                                <div className="w-full h-full flex items-center justify-center text-gray-300 text-sm">No image</div>
                            )}
                        </div>
                        <p className="text-sm font-medium text-gray-900 truncate">{product.title}</p>
                        <p className="text-xs text-gray-500 mt-0.5">Rs {product.price?.toLocaleString()}</p>
                        <div className="flex items-center gap-2 mt-2">
                            <Avatar src={undefined} name={person.name} size="sm" />
                            <span className="text-xs text-gray-600">{person.name}</span>
                        </div>
                    </div>
                ))}
            </div>

            {/* Cash info */}
            {swap.moneyDirection !== 'NONE' && (
                <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 flex items-center gap-3">
                    <ArrowLeftRight className="w-5 h-5 text-amber-600 shrink-0" />
                    <p className="text-sm text-amber-800">
                        {swap.moneyDirection === 'REQUESTER_OFFERS_CASH'
                            ? `Requester offers additional Rs ${swap.offeredAmount?.toLocaleString() ?? 0} cash`
                            : `Owner requests Rs ${swap.askedAmount?.toLocaleString() ?? 0} cash`}
                    </p>
                </div>
            )}

            {/* Action bar */}
            {swap.status === 'AWAITING_PAYMENT' && (
                <div className="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
                    <p className="text-sm text-blue-800 mb-3">Cash payment required to complete this swap.</p>
                    <Button
                        loading={initiatePayment.isPending}
                        onClick={handlePayment}
                        className="!bg-blue-600 hover:!bg-blue-700"
                    >
                        Pay cash difference
                    </Button>
                </div>
            )}

            {swap.status === 'CONFIRMATION_PENDING' && !myConfirmed && (
                <div className="bg-[#e8f5ee] border border-[#1a6b3c]/20 rounded-xl p-4 mb-6">
                    <p className="text-sm text-[#1a6b3c] mb-1 font-medium">Physical swap — confirm receipt</p>
                    <p className="text-xs text-gray-600 mb-3">
                        {isOwner ? (ownerHasConfirmed ? 'You already confirmed. Waiting for requester.' : 'Confirm you received the other item.') :
                            (requesterHasConfirmed ? 'You already confirmed. Waiting for owner.' : 'Confirm you received the other item.')}
                    </p>
                    <div className="flex items-center gap-2 text-xs text-gray-500 mb-3">
                        <span className={ownerHasConfirmed ? 'text-green-600 flex items-center gap-1' : 'flex items-center gap-1'}>
                            {ownerHasConfirmed ? <CheckCircle className="w-3.5 h-3.5" /> : <XCircle className="w-3.5 h-3.5" />}
                            Owner confirmed
                        </span>
                        <span className="text-gray-300">·</span>
                        <span className={requesterHasConfirmed ? 'text-green-600 flex items-center gap-1' : 'flex items-center gap-1'}>
                            {requesterHasConfirmed ? <CheckCircle className="w-3.5 h-3.5" /> : <XCircle className="w-3.5 h-3.5" />}
                            Requester confirmed
                        </span>
                    </div>
                    <Button loading={confirm.isPending} onClick={handleConfirm}>
                        Confirm I received the item
                    </Button>
                </div>
            )}

            {swap.status === 'COMPLETED' && (
                <div className="bg-[#e8f5ee] border border-[#1a6b3c]/20 rounded-xl p-4 mb-6 flex items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <CheckCircle className="w-5 h-5 text-[#1a6b3c] shrink-0" />
                        <p className="text-sm text-[#1a6b3c] font-medium">Swap completed! Both parties confirmed receipt.</p>
                    </div>
                    <Button variant="ghost" size="sm" onClick={() => setShowReview(true)} className="border border-amber-300 text-amber-700 hover:bg-amber-50 shrink-0">
                        Write a review
                    </Button>
                </div>
            )}

            {canAct && !showCounter && (
                <div className="flex flex-wrap gap-3 mb-6">
                    {isOwner && (
                        <>
                            <Button loading={accept.isPending} onClick={handleAccept}>
                                Accept swap
                            </Button>
                            <Button variant="ghost" onClick={() => setShowCounter(true)} className="border border-purple-300 text-purple-700 hover:bg-purple-50">
                                Counter offer
                            </Button>
                            <Button variant="danger" loading={reject.isPending} onClick={handleReject}>
                                Reject
                            </Button>
                        </>
                    )}
                    {isRequester && swap.status === 'COUNTERED' && (
                        <>
                            <Button loading={accept.isPending} onClick={handleAccept}>
                                Accept counter
                            </Button>
                            <Button variant="ghost" onClick={() => setShowCounter(true)} className="border border-purple-300 text-purple-700 hover:bg-purple-50">
                                Counter back
                            </Button>
                            <Button variant="danger" loading={cancel.isPending} onClick={handleCancel}>
                                Cancel swap
                            </Button>
                        </>
                    )}
                    {isRequester && swap.status === 'PENDING' && (
                        <Button variant="danger" loading={cancel.isPending} onClick={handleCancel}>
                            Cancel swap
                        </Button>
                    )}
                </div>
            )}

            {showCounter && id && (
                <div className="mb-6">
                    <CounterForm swapId={id} onDone={() => setShowCounter(false)} />
                </div>
            )}

            {showReview && (
                <div className="mb-6">
                    <WriteReviewModal
                        subjectId={isRequester ? swap.ownerId : swap.requesterId}
                        productId={swap.product.id}
                        transactionType="SWAP"
                        swapId={swap.id}
                        onClose={() => setShowReview(false)}
                    />
                </div>
            )}

            {/* Negotiation timeline */}
            {events && events.length > 0 && (
                <div>
                    <h2 className="text-lg font-heading font-semibold text-gray-900 mb-4">Negotiation history</h2>
                    <div className="space-y-3">
                        {events.map((event) => (
                            <div key={event.id} className="bg-white border border-gray-200 rounded-xl p-4">
                                <div className="flex items-center gap-3 mb-2">
                                    <Avatar src={undefined} name={event.actor.name} size="sm" />
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">{event.actor.name}</p>
                                        <p className="text-xs text-gray-500">{EVENT_TYPE_LABEL[event.type] ?? event.type}</p>
                                    </div>
                                    <span className="ml-auto text-xs text-gray-400">
                                        {new Date(event.createdAt).toLocaleDateString()}
                                    </span>
                                </div>
                                {event.message && (
                                    <p className="text-sm text-gray-700 mt-1">"{event.message}"</p>
                                )}
                                {event.moneyDirection && event.moneyDirection !== 'NONE' && (
                                    <p className="text-xs text-purple-600 mt-1">
                                        {event.moneyDirection === 'REQUESTER_OFFERS_CASH'
                                            ? `+ Rs ${event.offeredAmount?.toLocaleString() ?? 0} cash offered`
                                            : `Rs ${event.askedAmount?.toLocaleString() ?? 0} cash requested`}
                                    </p>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}
