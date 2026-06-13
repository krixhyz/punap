import { useState, useCallback } from 'react';

type Rules<T> = {
    [K in keyof T]?: Array<(value: string, values: T) => string | null>;
};

export function useFormValidation<T extends Record<string, string>>(rules: Rules<T>) {
    const [errors, setErrors] = useState<Partial<Record<keyof T, string>>>({});

    const validate = useCallback(
        (values: T): boolean => {
            const newErrors: Partial<Record<keyof T, string>> = {};
            let valid = true;

            for (const key in rules) {
                const fieldRules = rules[key];
                if (!fieldRules) continue;
                for (const rule of fieldRules) {
                    const error = rule(values[key] ?? '', values);
                    if (error) {
                        newErrors[key] = error;
                        valid = false;
                        break;
                    }
                }
            }

            setErrors(newErrors);
            return valid;
        },
        [rules],
    );

    const clearError = useCallback((field: keyof T) => {
        setErrors((prev) => {
            const next = { ...prev };
            delete next[field];
            return next;
        });
    }, []);

    return { errors, validate, clearError, setErrors };
}

export const required = (msg = 'This field is required') =>
    (value: string) => (!value.trim() ? msg : null);

export const minLength = (min: number, msg?: string) =>
    (value: string) => value.length < min ? (msg ?? `Must be at least ${min} characters`) : null;

export const isEmail = (msg = 'Enter a valid email address') =>
    (value: string) => (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) ? null : msg);

export const matchField =
    <T extends Record<string, string>>(field: keyof T, msg = 'Fields do not match') =>
    (value: string, values: T) =>
        value !== values[field] ? msg : null;
