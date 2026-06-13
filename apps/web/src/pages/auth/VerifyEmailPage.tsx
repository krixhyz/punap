import { useEffect } from 'react';
import { useSearchParams, Link } from 'react-router-dom';
import { Card } from '../../components/Card';
import { Button } from '../../components/Button';
import { Spinner } from '../../components/Spinner';
import { useVerifyEmail, useResendVerification } from '../../api/auth';
import { getApiError } from '../../lib/apiError';

export default function VerifyEmailPage() {
    const [params] = useSearchParams();
    const token = params.get('token') ?? '';

    const verify = useVerifyEmail();
    const resend = useResendVerification();

    useEffect(() => {
        if (token) {
            verify.mutate({ token });
        }
    }, [token]); // eslint-disable-line react-hooks/exhaustive-deps

    if (!token) {
        return (
            <Card className="p-8 text-center">
                <h2 className="text-xl font-heading font-bold text-gray-900 mb-2">No token found</h2>
                <p className="text-gray-500 text-sm mb-6">
                    The verification link is missing or invalid. Check your email for the correct link.
                </p>
                <Link to="/login" className="text-[#1a6b3c] font-medium hover:underline text-sm">
                    Back to sign in
                </Link>
            </Card>
        );
    }

    if (verify.isPending) {
        return (
            <Card className="p-8 text-center">
                <Spinner size="lg" className="text-[#1a6b3c] mx-auto mb-4" />
                <p className="text-gray-500 text-sm">Verifying your email…</p>
            </Card>
        );
    }

    if (verify.isSuccess) {
        return (
            <Card className="p-8 text-center">
                <div className="w-16 h-16 bg-[#e8f5ee] rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg className="w-8 h-8 text-[#1a6b3c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h2 className="text-xl font-heading font-bold text-gray-900 mb-2">Email verified!</h2>
                <p className="text-gray-500 text-sm mb-6">Your account is now active. You can sign in.</p>
                <Link to="/login">
                    <Button size="lg" className="w-full">Sign in</Button>
                </Link>
            </Card>
        );
    }

    const apiError = getApiError(verify.error);

    return (
        <Card className="p-8 text-center">
            <div className="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg className="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
            <h2 className="text-xl font-heading font-bold text-gray-900 mb-2">Verification failed</h2>
            <p className="text-gray-500 text-sm mb-2">{apiError ?? 'This link has expired or already been used.'}</p>
            <p className="text-gray-500 text-sm mb-6">Request a new verification email below.</p>

            <Button
                variant="secondary"
                size="lg"
                className="w-full"
                loading={resend.isPending}
                onClick={() => resend.mutate({ email: '' })}
            >
                Resend verification email
            </Button>
            {resend.isSuccess && (
                <p className="text-sm text-[#1a6b3c] mt-3">
                    Check your inbox for a new verification link.
                </p>
            )}
            <p className="mt-4 text-sm">
                <Link to="/login" className="text-[#1a6b3c] hover:underline">Back to sign in</Link>
            </p>
        </Card>
    );
}
