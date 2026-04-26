@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-8 md:px-16 py-12 space-y-8">
    <!-- Hero Section -->
    <section class="bg-[#f3f3f3] px-6 md:px-8 py-8 border-b border-[rgba(189,202,189,0.3)]">
        <p class="font-space text-[11px] font-bold uppercase tracking-widest text-[#444746] mb-2">Buy Workflow</p>
        <h1 class="font-space font-bold text-3xl text-[#1a1c1c] mb-1">Checkout Summary</h1>
        <p class="font-manrope text-base text-[#444746]">Adjust item quantities before you proceed to payment.</p>
    </section>

    <div class="bg-white shadow-[0_20px_40px_rgba(26,28,28,0.06)] p-6 space-y-3">
        @php $total = 0; @endphp
        @foreach($cartItems as $item)
            @php
                $unit = $item->product->price ?? 0;
                $qty = $item->quantity ?? 1;
                $line = $unit * $qty;
                $total += $line;
            @endphp
            <div class="grid grid-cols-1 gap-3 bg-[#f3f3f3] p-4 sm:grid-cols-[1fr_auto] sm:items-center" data-checkout-item="{{ $item->id }}" data-unit-price="{{ $unit }}" data-line-total="{{ $line }}">
                <div>
                    <p class="font-manrope font-medium text-sm text-[#1a1c1c]">{{ $item->product->title }}</p>
                    <p class="font-manrope text-xs text-[#888888]" data-line-summary>Unit: Rs. {{ number_format($unit,2) }} | Line Total: Rs. {{ number_format($line,2) }}</p>
                </div>
                <form action="{{ route('cart.update', $item->id) }}" method="POST" class="grid grid-cols-[40px_64px_40px_auto] items-center gap-2 justify-self-start sm:justify-self-end" data-cart-action="checkout-update">
                    @csrf
                    @method('PATCH')
                    <button type="button" class="cart-qty-decrement w-10 h-10 border-2 border-gray-300 flex items-center justify-center hover:bg-[#f9f9f9]" data-target="qty-{{ $item->id }}">−</button>
                    <input
                        id="qty-{{ $item->id }}"
                        type="number"
                        name="quantity"
                        min="1"
                        max="{{ max(1, (int) ($item->product->quantity ?? 1)) }}"
                        value="{{ $qty }}"
                        class="h-10 bg-white border-0 border-b-2 border-gray-400 text-center font-manrope text-sm"
                    >
                    <button type="button" class="cart-qty-increment w-10 h-10 border-2 border-gray-300 flex items-center justify-center hover:bg-[#f9f9f9]" data-target="qty-{{ $item->id }}">+</button>
                    <button type="submit" class="bg-gradient-to-br from-[#006a38] to-[#09864a] text-white py-2 px-3 font-space font-bold text-xs uppercase tracking-wider hover:brightness-110">Update</button>
                </form>
            </div>
        @endforeach

        @php
            $serviceFee = $total * 0.03;
            $totalPayable = $total + $serviceFee;
        @endphp

        <div class="space-y-2 bg-[#f3f3f3] px-4 py-3">
            <div class="flex justify-between">
                <span class="font-space font-bold text-[#1a1c1c]">Item Price</span>
                <span class="font-space font-bold text-[#1a1c1c]" data-checkout-subtotal>Rs. {{ number_format($total,2) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="font-space font-bold text-[#1a1c1c]">Service Charge (3%)</span>
                <span class="font-space font-bold text-[#1a1c1c]" data-checkout-fee>Rs. {{ number_format($serviceFee,2) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="font-space font-bold text-[#1a1c1c]">Total Payable</span>
                <span class="font-space font-bold text-[#1a1c1c]" data-checkout-total>Rs. {{ number_format($totalPayable,2) }}</span>
            </div>
        </div>

        <form action="{{ route('orders.placeFromCart') }}" method="POST">
            @csrf
            <!-- Buyer Details Section -->
            <div class="mb-6 p-6 bg-[#f3f3f3]">
                <p class="font-space text-[11px] font-bold uppercase tracking-widest text-[#444746] mb-4">Delivery Details</p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="buyer_name" class="block font-manrope text-sm font-medium text-[#1a1c1c] mb-2">Full Name *</label>
                        <input type="text" id="buyer_name" name="buyer_name" value="{{ old('buyer_name', Auth::user()->name) }}" required class="w-full px-4 py-2 border-2 border-gray-300 font-manrope text-sm focus:border-[#006a38] focus:outline-none">
                        @error('buyer_name')
                            <p class="font-manrope text-xs text-[#ba1a1a] mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="buyer_phone" class="block font-manrope text-sm font-medium text-[#1a1c1c] mb-2">Phone Number</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 font-manrope text-sm text-[#666666] pointer-events-none">+977</span>
                            <input type="tel" id="buyer_phone" name="buyer_phone" value="{{ old('buyer_phone') ? substr(preg_replace('/[^0-9]+/', '', old('buyer_phone', Auth::user()->phone_number ?? '')), -10) : substr(preg_replace('/[^0-9]+/', '', Auth::user()->phone_number ?? ''), -10) }}" placeholder="10 digits" maxlength="10" pattern="[0-9]{10}" class="w-full px-4 py-2 pl-14 border-2 border-gray-300 font-manrope text-sm focus:border-[#006a38] focus:outline-none" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)">
                        </div>
                        @error('buyer_phone')
                            <p class="font-manrope text-xs text-[#ba1a1a] mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="buyer_email" class="block font-manrope text-sm font-medium text-[#1a1c1c] mb-2">Email Address *</label>
                        <input type="email" id="buyer_email" name="buyer_email" value="{{ old('buyer_email', Auth::user()->email) }}" required class="w-full px-4 py-2 border-2 border-gray-300 font-manrope text-sm focus:border-[#006a38] focus:outline-none">
                        @error('buyer_email')
                            <p class="font-manrope text-xs text-[#ba1a1a] mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="buyer_address" class="block font-manrope text-sm font-medium text-[#1a1c1c] mb-2">Delivery Address (Auto-filled)</label>
                        <input type="hidden" id="buyer_address" name="buyer_address" value="{{ Auth::user()->province?->name }}, {{ Auth::user()->city?->name }}">
                        <p class="w-full px-4 py-2 border-2 border-gray-300 font-manrope text-sm bg-gray-100 text-gray-700 rounded">{{ Auth::user()->province?->name }}, {{ Auth::user()->city?->name }}</p>
                    </div>
                </div>
            </div>

            <!-- Review Delivery Details Section -->
            <div class="mb-6 p-6 bg-white border-2 border-[#006a38]">
                <p class="font-space text-[11px] font-bold uppercase tracking-widest text-[#006a38] mb-4">Review Your Delivery Details</p>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 text-sm">
                    <div>
                        <p class="font-space text-[10px] uppercase tracking-widest text-[#888888]">Full Name</p>
                        <p class="font-manrope text-[#1a1c1c] font-medium" id="review_buyer_name">—</p>
                    </div>
                    <div>
                        <p class="font-space text-[10px] uppercase tracking-widest text-[#888888]">Phone Number</p>
                        <p class="font-manrope text-[#1a1c1c] font-medium" id="review_buyer_phone">—</p>
                    </div>
                    <div>
                        <p class="font-space text-[10px] uppercase tracking-widest text-[#888888]">Email Address</p>
                        <p class="font-manrope text-[#1a1c1c] font-medium" id="review_buyer_email">—</p>
                    </div>
                    <div>
                        <p class="font-space text-[10px] uppercase tracking-widest text-[#888888]">Delivery Address</p>
                        <p class="font-manrope text-[#1a1c1c] font-medium" id="review_buyer_address" style="word-break: break-word;">—</p>
                    </div>
                </div>
            </div>

            <!-- Payment Method Section -->
            <div class="mb-4 grid gap-2">
                <p class="font-space text-[11px] font-bold uppercase tracking-widest text-[#444746] mb-2">Choose Payment Method</p>
                <label class="flex items-center gap-3 bg-[#f3f3f3] px-4 py-3 cursor-pointer hover:bg-[#e8e8e8]">
                    <input type="radio" name="payment_gateway" value="esewa" {{ old('payment_gateway', 'esewa') === 'esewa' ? 'checked' : '' }} class="w-4 h-4">
                    <span class="font-manrope text-sm text-[#1a1c1c]">eSewa</span>
                </label>
                <label class="flex items-center gap-3 bg-[#f3f3f3] px-4 py-3 cursor-pointer hover:bg-[#e8e8e8]">
                    <input type="radio" name="payment_gateway" value="khalti" {{ old('payment_gateway', 'esewa') === 'khalti' ? 'checked' : '' }} class="w-4 h-4">
                    <span class="font-manrope text-sm text-[#1a1c1c]">Khalti</span>
                </label>
                @error('payment_gateway')
                    <p class="font-manrope text-xs text-[#ba1a1a]">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="w-full bg-gradient-to-br from-[#006a38] to-[#09864a] text-white px-6 py-3 font-space font-bold text-sm uppercase tracking-wider hover:brightness-110">
                Proceed to Payment
            </button>
        </form>
    </div>
</div>

<script>
    (function () {
        const clamp = (v, min, max) => {
            const n = parseInt(v, 10);
            if (Number.isNaN(n)) return min;
            return Math.min(max, Math.max(min, n));
        };

        document.querySelectorAll('.cart-qty-decrement').forEach((button) => {
            button.addEventListener('click', () => {
                const targetId = button.dataset.target;
                const input = document.getElementById(targetId);
                if (!input) return;
                const min = parseInt(input.min || '1', 10);
                const max = parseInt(input.max || '1', 10);
                input.value = clamp(input.value, min, max) - 1;
                input.dispatchEvent(new Event('blur'));
            });
        });

        document.querySelectorAll('.cart-qty-increment').forEach((button) => {
            button.addEventListener('click', () => {
                const targetId = button.dataset.target;
                const input = document.getElementById(targetId);
                if (!input) return;
                const min = parseInt(input.min || '1', 10);
                const max = parseInt(input.max || '1', 10);
                input.value = clamp(input.value, min, max) + 1;
                input.dispatchEvent(new Event('blur'));
            });
        });

        document.querySelectorAll('input[id^="qty-"]').forEach((input) => {
            const normalize = () => {
                const min = parseInt(input.min || '1', 10);
                const max = parseInt(input.max || '1', 10);
                input.value = clamp(input.value, min, max);
            };

            input.addEventListener('blur', () => {
                normalize();
            });

            input.addEventListener('input', () => {
                normalize();
            });

            const form = input.closest('form');
            if (form) {
                form.addEventListener('submit', normalize);
            }

            normalize();
        });

        // Update review section as user fills form
        function updateReviewSection() {
            document.getElementById('review_buyer_name').textContent = document.getElementById('buyer_name').value || '—';
            document.getElementById('review_buyer_phone').textContent = document.getElementById('buyer_phone').value || '—';
            document.getElementById('review_buyer_email').textContent = document.getElementById('buyer_email').value || '—';
            document.getElementById('review_buyer_address').textContent = document.getElementById('buyer_address').value || '—';
        }

        // Listen to form field changes
        document.getElementById('buyer_name').addEventListener('input', updateReviewSection);
        document.getElementById('buyer_phone').addEventListener('input', updateReviewSection);
        document.getElementById('buyer_email').addEventListener('input', updateReviewSection);

        // Initialize review on page load
        updateReviewSection();
    })();
</script>
@endsection
