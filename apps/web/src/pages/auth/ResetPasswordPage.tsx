import { useState } from 'react';
import { useSearchParams, Link, useNavigate } from 'react-router-dom';
import { Card } from '../../components/Card';
import { Input } from '../../components/Input';
import { Button } from '../../components/Button';
import { useResetPassword } from '../../api/auth';
import { useFormValidation, required, minLength, matchField } from '../../hooks/useFormValidation';
import { getApiError } from '../../lib/apiError';

type Fields = { newPassword: string; confirmPassword: string };

export default function ResetPasswordPage() {
    const [params] = useSearchParams();
    const token = params.get('token') ?? '';
    const navigate = useNavigate();
    const resetPassword = useResetPassword();
    const [fields, setFields] = useState<Fields>({ newPassword: '', confirmPassword: '' });
    const { errors, validate } = useFormValidation<Fields>({
        newPassword: [required(), minLength(8)],
        confirmPassword: [required(), matchField('newPassword', 'Passwords do not match')],
    });

    function set(key: keyof Fields, value: string) {
        setFields((prev) => ({ ...prev, [key]: value }));
    }

    async function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (!validate(fields)) return;
        try {
            await resetPassword.mutateAsync({ token, newPassword: fields.newPassword });
            navigate('/login?reset=1');
        } catch {
            // shown via resetPassword.error
        }
    }

    const apiError = getApiError(resetPassword.error);

    if (!token) {
        return (
            <Card className="p-8 text-center">
                <h2 className="text-xl font-heading font-bold text-gray-900 mb-2">Invalid link</h2>
                <p className="text-gray-500 text-sm mb-4">
                    This reset link is missing a token. Please request a new one.
                </p>
                <Link to="/forgot-password" className="text-[#1a6b3c] hover:underline text-sm">
                    Request new link
                </Link>
            </Card>
        );
    }

    return (
        <Card className="p-8">
            <h2 className="text-2xl font-heading font-bold text-gray-900 mb-1">Set new password</h2>
            <p className="text-gray-500 text-sm mb-6">Choose a strong password for your account.</p>

            <form onSubmit={handleSubmit} noValidate className="flex flex-col gap-4">
                <Input
                    label="New password"
                    type="password"
                    autoComplete="new-password"
                    value={fields.newPassword}
                    onChange={(e) => set('newPassword', e.target.value)}
                    error={errors.newPassword}
                />
                <Input
                    label="Confirm new password"
                    type="password"
                    autoComplete="new-password"
                    value={fields.confirmPassword}
                    onChange={(e) => set('confirmPassword', e.target.value)}
                    error={errors.confirmPassword}
                />

                {apiError && (
                    <p className="text-sm text-red-600 rounded-lg bg-red-50 px-3 py-2">{apiError}</p>
                )}

                <Button type="submit" size="lg" loading={resetPassword.isPending} className="w-full">
                    Reset password
                </Button>
            </form>
        </Card>
    );
}
