@extends('layouts.guest')

@section('guest-card-class', 'bg-white shadow-[0_20px_40px_rgba(26,28,28,0.06)] p-6 md:p-8 w-full max-w-4xl')

@section('content')
    <div class="space-y-6">
        <div>
            <p class="font-space text-[11px] font-bold uppercase tracking-widest text-[#444746] mb-2">Legal</p>
            <h1 class="font-space font-bold text-3xl md:text-4xl text-[#1a1c1c] mb-2">Terms and Conditions</h1>
            <p class="font-manrope text-sm text-[#444746]">Effective date: April 26, 2026</p>
        </div>

        <div class="space-y-5 font-manrope text-sm text-[#1a1c1c] leading-7">
            <section>
                <h2 class="font-space text-xs font-bold uppercase tracking-widest text-[#006a38] mb-1">1. Platform Use</h2>
                <p>By using ReLoop, users agree to use the platform responsibly for buying, renting, and swapping items.</p>
            </section>

            <section>
                <h2 class="font-space text-xs font-bold uppercase tracking-widest text-[#006a38] mb-1">2. User Responsibility</h2>
                <p>Users must provide accurate information. Each user is responsible for their account and activities.</p>
            </section>

            <section>
                <h2 class="font-space text-xs font-bold uppercase tracking-widest text-[#006a38] mb-1">3. Listings</h2>
                <p>All product details must be honest and accurate. ReLoop can remove or approve listings if needed.</p>
            </section>

            <section>
                <h2 class="font-space text-xs font-bold uppercase tracking-widest text-[#006a38] mb-1">4. Transactions</h2>
                <p>All transactions are between users. ReLoop only provides the platform and does not own listed items.</p>
            </section>

            <section>
                <h2 class="font-space text-xs font-bold uppercase tracking-widest text-[#006a38] mb-1">5. Payments</h2>
                <p>Payments are processed through services like eSewa and Khalti. A small platform fee (for example, 3%) may be applied.</p>
            </section>

            <section>
                <h2 class="font-space text-xs font-bold uppercase tracking-widest text-[#006a38] mb-1">6. Renting and Swapping</h2>
                <p>Renters must return items on time and in proper condition. Swap agreements must be mutually accepted by both users.</p>
            </section>

            <section>
                <h2 class="font-space text-xs font-bold uppercase tracking-widest text-[#006a38] mb-1">7. Prohibited Activities</h2>
                <p>Users must not:</p>
                <ul class="mt-1 space-y-1.5">
                    <li class="flex items-start gap-2">
                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#006a38]"></span>
                        <span>Post fake or illegal items</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#006a38]"></span>
                        <span>Attempt fraud or scams</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#006a38]"></span>
                        <span>Misuse the platform</span>
                    </li>
                </ul>
            </section>

            <section>
                <h2 class="font-space text-xs font-bold uppercase tracking-widest text-[#006a38] mb-1">8. Account Actions</h2>
                <p>ReLoop may suspend or delete accounts that violate rules.</p>
            </section>

            <section>
                <h2 class="font-space text-xs font-bold uppercase tracking-widest text-[#006a38] mb-1">9. Limitation of Liability</h2>
                <p>ReLoop is not responsible for product quality, delivery issues, or disputes between users.</p>
            </section>

            <section>
                <h2 class="font-space text-xs font-bold uppercase tracking-widest text-[#006a38] mb-1">10. Agreement</h2>
                <p>By using ReLoop, you accept these terms.</p>
            </section>
        </div>

        <div>
            <a href="{{ route('register') }}" class="inline-flex items-center justify-center bg-gradient-to-br from-[#006a38] to-[#09864a] text-white px-5 py-2.5 font-space font-bold text-xs uppercase tracking-wider hover:brightness-110">
                Back to Registration
            </a>
        </div>
    </div>
@endsection
