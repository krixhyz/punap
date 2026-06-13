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
                <p>By using Punap, users agree to use the platform responsibly for buying, renting, and swapping items.</p>
            </section>

            <section>
                <h2 class="font-space text-xs font-bold uppercase tracking-widest text-[#006a38] mb-1">2. User Responsibility</h2>
                <p>Users must provide accurate information. Each user is responsible for their account and activities.</p>
            </section>

            <section>
                <h2 class="font-space text-xs font-bold uppercase tracking-widest text-[#006a38] mb-1">3. Listings</h2>
                <p>All product details must be honest and accurate. Punap can remove or approve listings if needed.</p>
            </section>

            <section>
                <h2 class="font-space text-xs font-bold uppercase tracking-widest text-[#006a38] mb-1">4. Transactions</h2>
                <p>All transactions are between users. Punap only provides the platform and does not own listed items.</p>
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
                <p>Punap may suspend or delete accounts that violate rules.</p>
            </section>

            <section>
                <h2 class="font-space text-xs font-bold uppercase tracking-widest text-[#006a38] mb-1">9. Limitation of Liability</h2>
                <p>Punap is not responsible for product quality, delivery issues, or disputes between users.</p>
            </section>

            <section class="border border-[#fde68a] bg-[#fffbeb] rounded-sm px-5 py-4">
                <h2 class="font-space text-xs font-bold uppercase tracking-widest text-[#b45309] mb-3">10. User Transactions and Disclaimer of Responsibility</h2>

                <p class="mb-3">
                    Punap operates solely as a digital marketplace platform that connects independent buyers, sellers, renters, and swappers ("Users"). Punap is <strong>not a party to any transaction</strong> conducted between Users and does not own, hold, inspect, or handle any items listed on the platform.
                </p>

                <ul class="space-y-2.5 mb-3">
                    <li class="flex items-start gap-2.5">
                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#b45309]"></span>
                        <span><strong>Platform role only.</strong> Punap provides the technology infrastructure to facilitate listings, discovery, and payment processing. We do not act as an agent, broker, or guarantor for any User or transaction.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#b45309]"></span>
                        <span><strong>Dispute moderation.</strong> Platform administrators may assist in reviewing and moderating disputes between Users as a courtesy service. Such assistance does not constitute legal liability or an obligation to resolve disputes on behalf of any party.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#b45309]"></span>
                        <span><strong>User risk.</strong> All transactions are conducted at the sole risk of the participating Users. By completing a transaction on Punap, you acknowledge that you have independently assessed the counterparty and the item involved.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#b45309]"></span>
                        <span><strong>No guarantee of authenticity or quality.</strong> Punap does not verify, warrant, or guarantee the authenticity, condition, quality, safety, legality, or fitness for purpose of any item listed on the platform, nor the identity, trustworthiness, or reliability of any User.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#b45309]"></span>
                        <span><strong>No liability for user conduct.</strong> Punap expressly disclaims all liability for any loss, damage, injury, claim, or expense of any kind — including but not limited to financial loss, loss of data, or consequential damages — arising out of or in connection with: scams or fraudulent activity by other Users; counterfeit, misrepresented, or defective products; payment disputes or failed transactions; non-delivery or damage of items; or any other interaction between Users on or off the platform.</span>
                    </li>
                </ul>

                <p class="text-xs text-[#92400e] leading-relaxed">
                    By using Punap, you agree to transact responsibly and acknowledge that the platform's liability is limited to the maximum extent permitted by applicable law. If you believe a User has engaged in fraudulent or illegal conduct, you are encouraged to report it through our dispute system and, where appropriate, to the relevant authorities.
                </p>
            </section>

            <section>
                <h2 class="font-space text-xs font-bold uppercase tracking-widest text-[#006a38] mb-1">11. Agreement</h2>
                <p>By using Punap, you accept these terms.</p>
            </section>
        </div>

        <div>
            <a href="{{ route('register') }}" class="inline-flex items-center justify-center bg-gradient-to-br from-[#006a38] to-[#09864a] text-white px-5 py-2.5 font-space font-bold text-xs uppercase tracking-wider hover:brightness-110">
                Back to Registration
            </a>
        </div>
    </div>
@endsection
