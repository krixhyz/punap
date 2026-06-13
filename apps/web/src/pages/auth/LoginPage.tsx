import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Card } from '../../components/Card';
import { Input } from '../../components/Input';
import { Button } from '../../components/Button';
import { useLogin } from '../../api/auth';
import { useFormValidation, required, isEmail } from '../../hooks/useFormValidation';
import { getApiError } from '../../lib/apiError';

type Fields = { email: string; password: string };

export default function LoginPage() {
    const navigate = useNavigate();
    const login = useLogin();
    const [fields, setFields] = useState<Fields>({ email: '', password: '' });
    const { errors, validate } = useFormValidation<Fields>({
        email: [required(), isEmail()],
        password: [required()],
    });

    function set(key: keyof Fields, value: string) {
        setFields((prev) => ({ ...prev, [key]: value }));
    }

    async function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (!validate(fields)) return;
        try {
            await login.mutateAsync(fields);
            navigate('/');
        } catch (err) {
            // error shown via login.error
        }
    }

    const apiError = getApiError(login.error);

    return (
        <Card className="p-8">
            <h2 className="text-2xl font-heading font-bold text-gray-900 mb-1">Welcome back</h2>
            <p className="text-gray-500 text-sm mb-6">Sign in to your PUNAP account</p>

            <form onSubmit={handleSubmit} noValidate className="flex flex-col gap-4">
                <Input
                    label="Email"
                    type="email"
                    autoComplete="email"
                    value={fields.email}
                    onChange={(e) => set('email', e.target.value)}
                    error={errors.email}
                />
                <Input
                    label="Password"
                    type="password"
                    autoComplete="current-password"
                    value={fields.password}
                    onChange={(e) => set('password', e.target.value)}
                    error={errors.password}
                />

                {apiError && (
                    <p className="text-sm text-red-600 rounded-lg bg-red-50 px-3 py-2">{apiError}</p>
                )}

                <div className="flex items-center justify-between text-sm">
                    <Link to="/forgot-password" className="text-[#1a6b3c] hover:underline">
                        Forgot password?
                    </Link>
                </div>

                <Button type="submit" size="lg" loading={login.isPending} className="w-full">
                    Sign in
                </Button>
            </form>

            <p className="mt-6 text-sm text-gray-500 text-center">
                No account?{' '}
                <Link to="/register" className="text-[#1a6b3c] font-medium hover:underline">
                    Create one
                </Link>
            </p>
        </Card>
    );
}
