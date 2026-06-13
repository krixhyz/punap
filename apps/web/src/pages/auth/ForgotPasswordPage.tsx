import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Card } from '../../components/Card';
import { Input } from '../../components/Input';
import { Button } from '../../components/Button';
import { useForgotPassword } from '../../api/auth';
import { useFormValidation, required, isEmail } from '../../hooks/useFormValidation';

type Fields = { email: string };

export default function ForgotPasswordPage() {
    const forgotPassword = useForgotPassword();
    const [fields, setFields] = useState<Fields>({ email: '' });
    const [submitted, setSubmitted] = useState(false);
    const { errors, validate } = useFormValidation<Fields>({
        email: [required(), isEmail()],
    });

    async function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (!validate(fields)) return;
        await forgotPassword.mutateAsync(fields).catch(() => {});
        setSubmitted(true);
    }

    if (submitted) {
        return (
            <Card className="p-8 text-center">
                <div className="w-16 h-16 bg-[#e8f5ee] rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg className="w-8 h-8 text-[#1a6b3c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <h2 className="text-xl font-heading font-bold text-gray-900 mb-2">Check your email</h2>
                <p className="text-gray-500 text-sm mb-6">
                    If an account with <strong>{fields.email}</strong> exists, we've sent a password reset link.
                </p>
                <Link to="/login" className="text-[#1a6b3c] font-medium hover:underline text-sm">
                    Back to sign in
                </Link>
            </Card>
        );
    }

    return (
        <Card className="p-8">
            <h2 className="text-2xl font-heading font-bold text-gray-900 mb-1">Forgot password?</h2>
            <p className="text-gray-500 text-sm mb-6">
                Enter your email and we'll send you a reset link.
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
                <Button type="submit" size="lg" loading={forgotPassword.isPending} className="w-full">
                    Send reset link
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
