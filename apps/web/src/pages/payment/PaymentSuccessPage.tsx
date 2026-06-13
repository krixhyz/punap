import { useSearchParams, Link } from 'react-router-dom';

export default function PaymentSuccessPage() {
    const [params] = useSearchParams();
    const pidx = params.get('pidx');
    const transactionUuid = params.get('transaction_uuid');
    const ref = pidx ?? transactionUuid;

    return (
        <div className="min-h-screen flex items-center justify-center bg-gray-50 px-4">
            <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-10 max-w-sm w-full text-center">
                <div className="w-20 h-20 bg-[#e8f5ee] rounded-full flex items-center justify-center mx-auto mb-5">
                    <svg className="w-10 h-10 text-[#1a6b3c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h1 className="text-2xl font-heading font-bold text-gray-900 mb-2">Payment successful!</h1>
                <p className="text-gray-500 text-sm mb-1">Your transaction has been processed.</p>
                {ref && (
                    <p className="text-xs text-gray-400 mb-6 font-mono">Ref: {ref}</p>
                )}
                <div className="flex flex-col gap-3">
                    <Link
                        to="/orders"
                        className="w-full px-4 py-2.5 bg-[#1a6b3c] text-white rounded-xl text-sm font-medium hover:bg-[#124d2b] transition-colors"
                    >
                        View my orders
                    </Link>
                    <Link
                        to="/rentals"
                        className="w-full px-4 py-2.5 border border-gray-300 text-gray-700 rounded-xl text-sm font-medium hover:border-[#1a6b3c] transition-colors"
                    >
                        View my rentals
                    </Link>
                    <Link to="/" className="text-sm text-[#1a6b3c] hover:underline">
                        Continue shopping
                    </Link>
                </div>
            </div>
        </div>
    );
}
