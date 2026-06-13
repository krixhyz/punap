import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Card } from '../../components/Card';
import { Input } from '../../components/Input';
import { Button } from '../../components/Button';
import { useResendVerification } from '../../api/auth';
import { useFormValidation, required, isEmail } from '../../hooks/useFormValidation';
import { getApiError } from '../../lib/apiError';

type Fields = { email: string };

export default function ResendVerificationPage() {
    const resend = useResendVerification();
    const [fields, setFields] = useState<Fields>({ email: '' });
    const { errors, validate } = useFormValidation<Fields>({
        email: [required(), isEmail()],
    });

    async function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (!validate(fields)) return;
        resend.mutate(fields);
    }

    const apiError = getApiError(resend.error);

    if (resend.isSuccess) {
        return (
            <Card className="p-8 text-center">
                <h2 className="text-xl font-heading font-bold text-gray-900 mb-2">Email sent</h2>
                <p className="text-gray-500 text-sm mb-6">
                    If <strong>{fields.email}</strong> has an unverified account, a new link is on its way.
                </p>
                <Link to="/login" className="text-[#1a6b3c] font-medium hover:underline text-sm">
                    Back to sign in
                </Link>
            </Card>
        );
    }

    return (
        <Card className="p-8">
            <h2 className="text-2xl font-heading font-bold text-gray-900 mb-1">Resend verification</h2>
            <p className="text-gray-500 text-sm mb-6">
                Enter your email to receive a new verification link.
            </p>

            <form onSubmit={handleSubmit} noValidate className="flex flex-col gap-4">
                <Input
                    label="Email"
                    type="email"
                    autoComplete="email"
                    value={fields.email}
                    onChange={(e) => setFields({ email: e.target.value })}
                    error={errors.email}
                />
                {apiError && (
                    <p className="text-sm text-red-600 rounded-lg bg-red-50 px-3 py-2">{apiError}</p>
                )}
                <Button type="submit" size="lg" loading={resend.isPending} className="w-full">
                    Send verification email
                </Button>
            </form>

            <p className="mt-6 text-sm text-gray-500 text-center">
                <Link to="/login" className="text-[#1a6b3c] font-medium hover:underline">
                    Back to sign in
                </Link>
            </p>
        </Card>
    );
}
