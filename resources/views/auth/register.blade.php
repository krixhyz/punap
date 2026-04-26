@extends('layouts.guest')

@section('guest-card-class', 'bg-white shadow-[0_20px_40px_rgba(26,28,28,0.06)] p-6 md:p-8 w-full max-w-4xl')

@section('content')
    <div class="text-center mb-6 md:mb-8">
        <p class="font-space text-[11px] font-bold uppercase tracking-widest text-[#444746] mb-2">Create Account</p>
        <h1 class="font-space font-bold text-3xl md:text-4xl text-[#1a1c1c] mb-1">Join Reloop</h1>
        <p class="font-manrope text-sm text-[#444746]">Set up your account to buy, rent, swap, and manage your activity.</p>
    </div>

    @if ($errors->any())
        <div class="bg-[#ba1a1a] text-white p-4 mb-6 text-sm rounded-md">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="space-y-6">
        @csrf

        <div class="grid gap-6 md:grid-cols-2">
            <section class="bg-[#f8faf8] border border-[rgba(189,202,189,0.35)] rounded-xl p-5 md:p-6 space-y-5">
                <div>
                    <h2 class="font-space text-xs font-bold uppercase tracking-widest text-[#006a38]">Account Details</h2>
                    <p class="font-manrope text-xs text-[#666] mt-1">Basic information and login credentials.</p>
                </div>

                <div>
                    <label for="name" class="font-space text-[11px] font-bold uppercase tracking-widest text-[#444746] block mb-1.5">Full Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required
                           class="bg-white border border-[rgba(68,71,70,0.2)] px-3 py-2.5 font-manrope text-sm focus:border-[#006a38] focus:outline-none focus:ring-0 w-full rounded-md">
                    @error('name')<p class="font-manrope text-sm text-[#ba1a1a] mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="email" class="font-space text-[11px] font-bold uppercase tracking-widest text-[#444746] block mb-1.5">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required
                           class="bg-white border border-[rgba(68,71,70,0.2)] px-3 py-2.5 font-manrope text-sm focus:border-[#006a38] focus:outline-none focus:ring-0 w-full rounded-md">
                    @error('email')<p class="font-manrope text-sm text-[#ba1a1a] mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password" class="font-space text-[11px] font-bold uppercase tracking-widest text-[#444746] block mb-1.5">Password</label>
                    <div class="relative">
                        <input id="password" name="password" type="password" required
                               class="bg-white border border-[rgba(68,71,70,0.2)] px-3 py-2.5 pr-16 font-manrope text-sm focus:border-[#006a38] focus:outline-none focus:ring-0 w-full rounded-md"
                               data-password-input>
                        <button type="button"
                                class="absolute right-2 top-1/2 -translate-y-1/2 rounded px-2 py-1 font-space text-[10px] font-bold uppercase tracking-widest text-[#006a38] hover:text-[#004a29] focus:outline-none focus:ring-2 focus:ring-[#006a38]/25"
                                data-password-toggle
                                data-target="password"
                                aria-controls="password"
                                aria-label="Show password">
                            Show
                        </button>
                    </div>
                    @error('password')<p class="font-manrope text-sm text-[#ba1a1a] mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password_confirmation" class="font-space text-[11px] font-bold uppercase tracking-widest text-[#444746] block mb-1.5">Confirm Password</label>
                    <div class="relative">
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                               class="bg-white border border-[rgba(68,71,70,0.2)] px-3 py-2.5 pr-16 font-manrope text-sm focus:border-[#006a38] focus:outline-none focus:ring-0 w-full rounded-md"
                               data-password-input>
                        <button type="button"
                                class="absolute right-2 top-1/2 -translate-y-1/2 rounded px-2 py-1 font-space text-[10px] font-bold uppercase tracking-widest text-[#006a38] hover:text-[#004a29] focus:outline-none focus:ring-2 focus:ring-[#006a38]/25"
                                data-password-toggle
                                data-target="password_confirmation"
                                aria-controls="password_confirmation"
                                aria-label="Show password confirmation">
                            Show
                        </button>
                    </div>
                </div>
            </section>

            <section class="bg-[#f8faf8] border border-[rgba(189,202,189,0.35)] rounded-xl p-5 md:p-6 space-y-5">
                <div>
                    <h2 class="font-space text-xs font-bold uppercase tracking-widest text-[#006a38]">Location and Contact</h2>
                    <p class="font-manrope text-xs text-[#666] mt-1">Tell us where you are and how to reach you.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="province_id" class="font-space text-[11px] font-bold uppercase tracking-widest text-[#444746] block mb-1.5">Province</label>
                        <select id="province_id"
                                name="province_id"
                            data-old-province="{{ old('province_id') }}"
                                required
                                class="bg-white border border-[rgba(68,71,70,0.2)] px-3 py-2.5 font-manrope text-sm focus:border-[#006a38] focus:outline-none focus:ring-0 w-full rounded-md">
                            <option value="">Select province</option>
                            @foreach ($provinces as $province)
                                <option value="{{ $province->id }}">{{ $province->name }}</option>
                            @endforeach
                        </select>
                        @error('province_id')<p class="font-manrope text-sm text-[#ba1a1a] mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="city_id" class="font-space text-[11px] font-bold uppercase tracking-widest text-[#444746]">City</label>
                            <span x-show="loadingCities" class="font-space text-[10px] font-bold uppercase tracking-widest text-[#006a38]">Loading...</span>
                        </div>
                        <select id="city_id"
                                name="city_id"
                            data-old-city="{{ old('city_id') }}"
                                required
                                class="bg-white border border-[rgba(68,71,70,0.2)] px-3 py-2.5 font-manrope text-sm focus:border-[#006a38] focus:outline-none focus:ring-0 w-full rounded-md disabled:opacity-50 disabled:cursor-not-allowed">
                            <option value="">Choose province first</option>
                        </select>
                        @error('city_id')<p class="font-manrope text-sm text-[#ba1a1a] mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="phone_number" class="font-space text-[11px] font-bold uppercase tracking-widest text-[#444746] block mb-1.5">Phone Number (optional)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-3 font-manrope text-sm text-[#666666] pointer-events-none">+977</span>
                        <input id="phone_number" name="phone_number" type="tel" value="{{ old('phone_number') ? substr(preg_replace('/[^0-9]+/', '', old('phone_number')), -10) : '' }}" placeholder="10 digits"
                               maxlength="10" pattern="[0-9]{10}"
                               class="bg-white border border-[rgba(68,71,70,0.2)] px-3 py-2.5 pl-14 font-manrope text-sm focus:border-[#006a38] focus:outline-none focus:ring-0 w-full rounded-md"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)">
                    </div>
                    @error('phone_number')<p class="font-manrope text-sm text-[#ba1a1a] mt-1">{{ $message }}</p>@enderror
                </div>
            </section>
        </div>

        <div class="bg-[#f8faf8] border border-[rgba(189,202,189,0.35)] rounded-xl p-4">
            <label for="terms_accepted" class="flex items-start gap-3">
                <input id="terms_accepted"
                       name="terms_accepted"
                       type="checkbox"
                       value="1"
                       {{ old('terms_accepted') ? 'checked' : '' }}
                       class="mt-1 w-4 h-4 border-2 border-[rgba(68,71,70,0.4)] text-[#006a38] focus:outline-none">
                <span class="font-manrope text-sm text-[#444746]">
                    I accept the
                    <a href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer" class="font-space font-bold uppercase tracking-wider text-[#006a38] hover:text-[#004a29]">Terms and Conditions</a>
                    and agree to follow marketplace rules.
                </span>
            </label>
            @error('terms_accepted')<p class="font-manrope text-sm text-[#ba1a1a] mt-2">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="w-full bg-gradient-to-br from-[#006a38] to-[#09864a] text-white py-3 font-space font-bold text-sm uppercase tracking-wider hover:brightness-110 active:brightness-95 transition-all rounded-md">
            Create Account
        </button>

        <p class="text-center font-manrope text-sm text-[#444746]">
            Already registered?
            <a href="{{ route('login') }}" class="font-space font-bold uppercase text-[#006a38] hover:text-[#004a29] tracking-wider">Sign in here</a>
        </p>
    </form>

@endsection
