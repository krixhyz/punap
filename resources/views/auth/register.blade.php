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
                    <input id="password" name="password" type="password" required
                           class="bg-white border border-[rgba(68,71,70,0.2)] px-3 py-2.5 font-manrope text-sm focus:border-[#006a38] focus:outline-none focus:ring-0 w-full rounded-md">
                    @error('password')<p class="font-manrope text-sm text-[#ba1a1a] mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password_confirmation" class="font-space text-[11px] font-bold uppercase tracking-widest text-[#444746] block mb-1.5">Confirm Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required
                           class="bg-white border border-[rgba(68,71,70,0.2)] px-3 py-2.5 font-manrope text-sm focus:border-[#006a38] focus:outline-none focus:ring-0 w-full rounded-md">
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

        <button type="submit" class="w-full bg-gradient-to-br from-[#006a38] to-[#09864a] text-white py-3 font-space font-bold text-sm uppercase tracking-wider hover:brightness-110 active:brightness-95 transition-all rounded-md">
            Create Account
        </button>

        <p class="text-center font-manrope text-sm text-[#444746]">
            Already registered?
            <a href="{{ route('login') }}" class="font-space font-bold uppercase text-[#006a38] hover:text-[#004a29] tracking-wider">Sign in here</a>
        </p>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const provinceSelect = document.getElementById('province_id');
            const citySelect = document.getElementById('city_id');
            const loadingLabel = document.querySelector('[x-show="loadingCities"]');
            const oldProvince = '{{ old('province_id') }}';
            const oldCity = '{{ old('city_id') }}';

            const setLoading = (loading) => {
                citySelect.disabled = loading || !provinceSelect.value;
                if (loadingLabel) {
                    loadingLabel.style.display = loading ? 'inline' : 'none';
                }
            };

            const resetCityOptions = (placeholder = 'Choose province first') => {
                citySelect.innerHTML = '';
                const option = document.createElement('option');
                option.value = '';
                option.textContent = placeholder;
                citySelect.appendChild(option);
            };

            const populateCities = (cities, selectedCity = '') => {
                resetCityOptions('Select city');

                cities.forEach((city) => {
                    const option = document.createElement('option');
                    option.value = String(city.id);
                    option.textContent = city.name;

                    if (selectedCity && String(city.id) === String(selectedCity)) {
                        option.selected = true;
                    }

                    citySelect.appendChild(option);
                });
            };

            const fetchCities = async (provinceId, selectedCity = '') => {
                if (!provinceId) {
                    resetCityOptions();
                    setLoading(false);
                    return;
                }

                setLoading(true);

                try {
                    const response = await fetch(`/api/cities/${provinceId}`, {
                        headers: { 'Accept': 'application/json' },
                    });

                    if (!response.ok) {
                        throw new Error('Failed to load cities');
                    }

                    const payload = await response.json();
                    const cities = Array.isArray(payload) ? payload : [];
                    populateCities(cities, selectedCity);
                    citySelect.disabled = false;
                } catch (error) {
                    resetCityOptions('No cities available');
                    citySelect.disabled = true;
                } finally {
                    setLoading(false);
                }
            };

            provinceSelect.addEventListener('change', () => {
                fetchCities(provinceSelect.value, '');
            });

            if (oldProvince) {
                provinceSelect.value = oldProvince;
                fetchCities(oldProvince, oldCity);
            } else {
                resetCityOptions();
                citySelect.disabled = true;
                setLoading(false);
            }
        });
    </script>
@endsection
