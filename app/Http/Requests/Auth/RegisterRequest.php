<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $email = trim((string) $this->input('email', ''));
        $phone = preg_replace('/\D+/', '', (string) $this->input('phone_number', ''));

        $this->merge([
            'name' => trim((string) $this->input('name', '')),
            'email' => strtolower($email),
            'phone_number' => $phone !== '' ? substr($phone, -10) : null,
        ]);
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter your full name.',
            'name.min' => 'Your full name must be at least 2 characters.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'phone_number.digits' => 'Phone number must be exactly 10 digits.',
            'province_id.required' => 'Please select your province.',
            'province_id.exists' => 'Selected province is invalid.',
            'city_id.required' => 'Please select your city.',
            'city_id.exists' => 'Selected city is invalid for the chosen province.',
            'password.required' => 'Please set a password.',
            'password.confirmed' => 'Password confirmation does not match.',
            'terms_accepted.accepted' => 'You must accept the terms and conditions to register.',
            'terms_accepted.required' => 'Please accept the terms and conditions to continue.',
        ];
    }

    public function attributes(): array
    {
        return [
            'province_id' => 'province',
            'city_id' => 'city',
            'phone_number' => 'phone number',
            'terms_accepted' => 'terms and conditions',
        ];
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'phone_number' => ['nullable', 'digits:10'],
            'address' => ['nullable', 'string', 'max:1000'],
            'province_id' => ['required', 'integer', 'exists:provinces,id'],
            'city_id' => [
                'required',
                Rule::exists('cities', 'id')->where(function ($query) {
                    $query->where('province_id', $this->input('province_id'));
                }),
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms_accepted' => ['required', 'accepted'],
        ];
    }
}
