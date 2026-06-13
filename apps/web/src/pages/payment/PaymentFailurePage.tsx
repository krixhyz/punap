import { useSearchParams, Link, useNavigate } from 'react-router-dom';

export default function PaymentFailurePage() {
    const [params] = useSearchParams();
    const navigate = useNavigate();
    const productId = params.get('productId');

    return (
        <div className="min-h-screen flex items-center justify-center bg-gray-50 px-4">
            <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-10 max-w-sm w-full text-center">
                <div className="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-5">
                    <svg className="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <h1 className="text-2xl font-heading font-bold text-gray-900 mb-2">Payment failed</h1>
                <p className="text-gray-500 text-sm mb-6">
                    Your payment could not be completed. No charges have been made.
                </p>
                <div className="flex flex-col gap-3">
                    {productId ? (
                        <button
                            onClick={() => navigate(`/products/${productId}`)}
                            className="w-full px-4 py-2.5 bg-[#1a6b3c] text-white rounded-xl text-sm font-medium hover:bg-[#124d2b] transition-colors"
                        >
                            Try again
                        </button>
                    ) : (
                        <button
                            onClick={() => navigate(-1)}
                            className="w-full px-4 py-2.5 bg-[#1a6b3c] text-white rounded-xl text-sm font-medium hover:bg-[#124d2b] transition-colors"
                        >
                            Go back
                        </button>
                    )}
                    <Link to="/" className="text-sm text-[#1a6b3c] hover:underline">
                        Return to home
                    </Link>
                </div>
            </div>
        </div>
    );
}
