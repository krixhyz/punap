import { useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import { useOrder, useCancelOrder, useCompleteOrder } from '../../api/orders';
import { Badge } from '../../components/Badge';
import { Button } from '../../components/Button';
import { Skeleton } from '../../components/Skeleton';
import { Avatar } from '../../components/Avatar';
import { WriteReviewModal } from '../../components/WriteReviewModal';
import { OpenDisputeModal } from '../../components/OpenDisputeModal';
import { useAuth } from '../../hooks/useAuth';

const STATUS_VARIANTS: Record<string, 'success' | 'warning' | 'danger' | 'neutral'> = {
    PENDING: 'warning',
    PAID: 'success',
    COMPLETED: 'success',
    CANCELLED: 'danger',
    DISPUTED: 'danger',
};

const STATUS_STEPS = ['PENDING', 'PAID', 'COMPLETED'];

function StatusTimeline({ status }: { status: string }) {
    const currentIndex = STATUS_STEPS.indexOf(status);
    return (
        <div className="flex items-center gap-2">
            {STATUS_STEPS.map((step, i) => (
                <div key={step} className="flex items-center gap-2">
                    <div className={[
                        'flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold',
                        i < currentIndex ? 'bg-[#1a6b3c] text-white' :
                        i === currentIndex ? 'bg-[#1a6b3c] text-white ring-2 ring-[#1a6b3c] ring-offset-2' :
                        'bg-gray-200 text-gray-400',
                    ].join(' ')}>
                        {i < currentIndex ? '✓' : i + 1}
                    </div>
                    <span className={`text-xs ${i <= currentIndex ? 'text-gray-800 font-medium' : 'text-gray-400'}`}>
                        {step}
                    </span>
                    {i < STATUS_STEPS.length - 1 && (
                        <div className={`w-8 h-0.5 ${i < currentIndex ? 'bg-[#1a6b3c]' : 'bg-gray-200'}`} />
                    )}
                </div>
            ))}
        </div>
    );
}

export default function OrderDetailPage() {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const { user } = useAuth();
    const { data: order, isLoading } = useOrder(id ?? '');
    const cancelOrder = useCancelOrder();
    const completeOrder = useCompleteOrder();
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

    if (!order) {
        return (
            <div className="max-w-2xl mx-auto px-4 py-16 text-center text-gray-500">
                <p>Order not found.</p>
                <button className="text-[#1a6b3c] text-sm hover:underline mt-2" onClick={() => navigate('/orders')}>Back to orders</button>
            </div>
        );
    }

    const isBuyer = user?.id === order.buyer.id;
    const isSeller = user?.id === order.product.seller.id;
    const image = order.product.images?.[0];

    async function handleCancel() {
        try {
            await cancelOrder.mutateAsync(order!.id);
            toast.success('Order cancelled');
        } catch {
            toast.error('Could not cancel order');
        }
    }

    async function handleComplete() {
        try {
            await completeOrder.mutateAsync(order!.id);
            toast.success('Order marked as completed');
        } catch {
            toast.error('Could not complete order');
        }
    }

    return (
        <div className="max-w-2xl mx-auto px-4 py-8">
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-heading font-bold text-gray-900">Order details</h1>
                <Badge variant={STATUS_VARIANTS[order.status] ?? 'neutral'}>{order.status}</Badge>
            </div>

            {/* Status timeline */}
            {order.status !== 'CANCELLED' && order.status !== 'DISPUTED' && (
                <div className="bg-white border border-gray-200 rounded-xl p-4 mb-4 overflow-x-auto">
                    <StatusTimeline status={order.status} />
                </div>
            )}

            {/* Product */}
            <div className="bg-white border border-gray-200 rounded-xl p-4 mb-4 flex items-center gap-4">
                <div className="w-16 h-16 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                    {image ? (
                        <img src={image} alt={order.product.title} className="w-full h-full object-cover" />
                    ) : (
                        <div className="w-full h-full bg-gray-200" />
                    )}
                </div>
                <div className="flex-1 min-w-0">
                    <Link to={`/products/${order.product.id}`} className="font-medium text-gray-900 hover:text-[#1a6b3c] truncate block">
                        {order.product.title}
                    </Link>
                    <p className="text-sm text-gray-500 mt-0.5">Qty: {order.quantity}</p>
                </div>
            </div>

            {/* Pricing breakdown */}
            <div className="bg-white border border-gray-200 rounded-xl p-4 mb-4 space-y-2">
                <h3 className="font-medium text-gray-900 mb-3">Payment summary</h3>
                <div className="flex justify-between text-sm">
                    <span className="text-gray-500">Subtotal</span>
                    <span>Rs {order.subtotal.toLocaleString()}</span>
                </div>
                <div className="flex justify-between text-sm">
                    <span className="text-gray-500">Service fee</span>
                    <span>Rs {order.serviceFee.toLocaleString()}</span>
                </div>
                <div className="border-t border-gray-100 pt-2 flex justify-between font-semibold">
                    <span>Total</span>
                    <span>Rs {order.totalAmount.toLocaleString()}</span>
                </div>
                {order.payment && (
                    <p className="text-xs text-gray-400 mt-2">
                        Payment status: <span className="font-medium">{order.payment.status}</span> via {order.payment.gateway}
                    </p>
                )}
            </div>

            {/* Participants */}
            <div className="bg-white border border-gray-200 rounded-xl p-4 mb-6 flex items-center gap-4">
                <Avatar src={order.buyer.avatarUrl} name={order.buyer.name} size="sm" />
                <div>
                    <p className="text-sm font-medium">{order.buyer.name}</p>
                    <p className="text-xs text-gray-400">Buyer</p>
                </div>
                <div className="ml-auto text-right">
                    <p className="text-sm font-medium">{order.product.seller.name}</p>
                    <p className="text-xs text-gray-400">Seller</p>
                </div>
            </div>

            {/* Actions */}
            <div className="flex flex-wrap gap-3">
                {isBuyer && order.status === 'PENDING' && (
                    <Button variant="danger" loading={cancelOrder.isPending} onClick={handleCancel}>
                        Cancel order
                    </Button>
                )}
                {isSeller && order.status === 'PAID' && (
                    <Button loading={completeOrder.isPending} onClick={handleComplete}>
                        Mark as completed
                    </Button>
                )}
                {order.status === 'COMPLETED' && !order.reviewExists && (
                    <Button variant="ghost" onClick={() => setShowReview(true)} className="border border-amber-300 text-amber-700 hover:bg-amber-50">
                        Write a review
                    </Button>
                )}
                {['PAID', 'COMPLETED'].includes(order.status) && (
                    <Button variant="ghost" onClick={() => setShowDispute(true)} className="border border-red-300 text-red-600 hover:bg-red-50">
                        Open dispute
                    </Button>
                )}
                <Link to="/orders" className="ml-auto">
                    <Button variant="ghost">Back to orders</Button>
                </Link>
            </div>

            {showReview && (
                <WriteReviewModal
                    subjectId={isBuyer ? order.product.seller.id : order.buyer.id}
                    productId={order.product.id}
                    transactionType="ORDER"
                    orderId={order.id}
                    onClose={() => setShowReview(false)}
                />
            )}
            {showDispute && (
                <OpenDisputeModal
                    respondentId={isBuyer ? order.product.seller.id : order.buyer.id}
                    respondentName={isBuyer ? order.product.seller.name : order.buyer.name}
                    transactionType="ORDER"
                    orderId={order.id}
                    onClose={() => setShowDispute(false)}
                />
            )}
        </div>
    );
}
