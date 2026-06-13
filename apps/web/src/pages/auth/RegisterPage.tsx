import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Card } from '../../components/Card';
import { Input } from '../../components/Input';
import { Button } from '../../components/Button';
import { Checkbox } from '../../components/Checkbox';
import { useRegister } from '../../api/auth';
import { useFormValidation, required, isEmail, minLength, matchField } from '../../hooks/useFormValidation';
import { getApiError } from '../../lib/apiError';

type Fields = {
    name: string;
    email: string;
    phone: string;
    password: string;
    confirmPassword: string;
    terms: string;
};

export default function RegisterPage() {
    const navigate = useNavigate();
    const register = useRegister();
    const [fields, setFields] = useState<Fields>({
        name: '',
        email: '',
        phone: '',
        password: '',
        confirmPassword: '',
        terms: '',
    });
    const [termsChecked, setTermsChecked] = useState(false);
    const [success, setSuccess] = useState(false);

    const { errors, validate } = useFormValidation<Fields>({
        name: [required()],
        email: [required(), isEmail()],
        phone: [required()],
        password: [required(), minLength(8)],
        confirmPassword: [required(), matchField('password', 'Passwords do not match')],
        terms: [(_, v) => (v.terms !== 'on' ? 'You must accept the terms' : null)],
    });

    function set(key: keyof Fields, value: string) {
        setFields((prev) => ({ ...prev, [key]: value }));
    }

    async function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        const combined = { ...fields, terms: termsChecked ? 'on' : '' };
        if (!validate(combined)) return;
        try {
            await register.mutateAsync({
                name: fields.name,
                email: fields.email,
                phone: fields.phone,
                password: fields.password,
            });
            setSuccess(true);
        } catch {
            // shown via register.error
        }
    }

    const apiError = getApiError(register.error);

    if (success) {
        return (
            <Card className="p-8 text-center">
                <div className="w-16 h-16 bg-[#e8f5ee] rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg className="w-8 h-8 text-[#1a6b3c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h2 className="text-xl font-heading font-bold text-gray-900 mb-2">Check your email</h2>
                <p className="text-gray-500 text-sm mb-6">
                    We sent a verification link to <strong>{fields.email}</strong>. Click it to activate your account.
                </p>
                <Link to="/login" className="text-[#1a6b3c] font-medium hover:underline text-sm">
                    Back to sign in
                </Link>
            </Card>
        );
    }

    return (
        <Card className="p-8">
            <h2 className="text-2xl font-heading font-bold text-gray-900 mb-1">Create account</h2>
            <p className="text-gray-500 text-sm mb-6">Join the PUNAP community</p>

            <form onSubmit={handleSubmit} noValidate className="flex flex-col gap-4">
                <Input
                    label="Full name"
                    autoComplete="name"
                    value={fields.name}
                    onChange={(e) => set('name', e.target.value)}
                    error={errors.name}
                />
                <Input
                    label="Email"
                    type="email"
                    autoComplete="email"
                    value={fields.email}
                    onChange={(e) => set('email', e.target.value)}
                    error={errors.email}
                />
                <Input
                    label="Phone number"
                    type="tel"
                    autoComplete="tel"
                    value={fields.phone}
                    onChange={(e) => set('phone', e.target.value)}
                    error={errors.phone}
                />
                <Input
                    label="Password"
                    type="password"
                    autoComplete="new-password"
                    value={fields.password}
                    onChange={(e) => set('password', e.target.value)}
                    error={errors.password}
                />
                <Input
                    label="Confirm password"
                    type="password"
                    autoComplete="new-password"
                    value={fields.confirmPassword}
                    onChange={(e) => set('confirmPassword', e.target.value)}
                    error={errors.confirmPassword}
                />

                <div>
                    <Checkbox
                        label="I agree to the Terms of Service and Privacy Policy"
                        checked={termsChecked}
                        onChange={(e) => setTermsChecked(e.target.checked)}
                    />
                    {errors.terms && <p className="text-xs text-red-600 mt-1">{errors.terms}</p>}
                </div>

                {apiError && (
                    <p className="text-sm text-red-600 rounded-lg bg-red-50 px-3 py-2">{apiError}</p>
                )}

                <Button type="submit" size="lg" loading={register.isPending} className="w-full">
                    Create account
                </Button>
            </form>

            <p className="mt-6 text-sm text-gray-500 text-center">
                Already have an account?{' '}
                <Link to="/login" className="text-[#1a6b3c] font-medium hover:underline">
                    Sign in
                </Link>
            </p>
        </Card>
    );
}
