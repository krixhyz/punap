@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto py-10 px-6">
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-8">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Send Swap Request</h2>

        <form action="{{ route('swap.request.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Hidden field for the product being requested -->
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <input type="hidden" id="target_price" value="{{ $product->price }}">

            {{-- Product being requested --}}
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                <h3 class="text-lg font-medium text-gray-800 mb-1">{{ $product->title }}</h3>
                <p class="text-sm text-gray-500 mb-2">{{ Str::limit($product->description, 100) }}</p>
                <p class="text-sm text-gray-600"><strong>Category:</strong> {{ $product->category ?? 'General' }}</p>
                <p class="text-sm text-gray-600"><strong>Price:</strong> Rs. {{ $product->price }}</p>
            </div>

            {{-- User's offered product --}}
            <div>
                <label for="offered_product_id" class="block text-sm font-medium text-gray-700 mb-1">
                    Select a product to swap
                </label>
                <select name="offered_product_id" id="offered_product_id" required
                        class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Choose your product --</option>
                    @foreach ($userProducts as $userProduct)
                        <option value="{{ $userProduct->id }}" data-price="{{ $userProduct->price }}">
                            {{ $userProduct->title }} (Rs. {{ $userProduct->price }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Cash offer --}}
            <div>
                <label for="offered_amount" class="block text-sm font-medium text-gray-700 mb-1">
                    Add extra cash (if needed)
                </label>
                <input type="number" name="offered_amount" id="offered_amount" value="0" min="0"
                       class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" />

                <p id="cash_suggestion" class="text-sm text-gray-500 mt-1">
                    💡 Tip: If your product’s value is lower than theirs, consider adding a small cash offer.
                </p>
            </div>

            {{-- Message --}}
            <div>
                <label for="message" class="block text-sm font-medium text-gray-700 mb-1">
                    Message (optional)
                </label>
                <textarea name="message" id="message" rows="3"
                          class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                          placeholder="You can mention product condition or meeting details..."></textarea>
            </div>

            {{-- Submit --}}
            <div class="pt-4">
                <button type="submit"
                        class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition">
                    Send Swap Request
                </button>
            </div>
        </form>
    </div>
</div>

{{-- JavaScript Logic --}}
<script>
    const targetPrice = parseFloat(document.getElementById('target_price').value);
    const offeredProductSelect = document.getElementById('offered_product_id');
    const offeredAmountInput = document.getElementById('offered_amount');
    const cashSuggestion = document.getElementById('cash_suggestion');

    offeredProductSelect.addEventListener('change', () => {
        const selectedOption = offeredProductSelect.options[offeredProductSelect.selectedIndex];
        const yourPrice = parseFloat(selectedOption.getAttribute('data-price')) || 0;

        if (yourPrice < targetPrice) {
            const difference = targetPrice - yourPrice;
            offeredAmountInput.value = difference;
            cashSuggestion.innerHTML = `💡 Your product is cheaper by Rs. <strong>${difference}</strong>. You should consider adding Rs. <strong>${difference}</strong> as extra cash.`;
        } else if (yourPrice > targetPrice) {
            const surplus = yourPrice - targetPrice;
            offeredAmountInput.value = 0;
            cashSuggestion.innerHTML = `✅ Your product is more valuable by Rs. <strong>${surplus}</strong>. You don't need to add extra cash.`;
        } else {
            offeredAmountInput.value = 0;
            cashSuggestion.innerHTML = `⚖️ Both products have equal value. No extra cash needed.`;
        }

        // Prevent negative input
        offeredAmountInput.addEventListener('input', () => {
            if (offeredAmountInput.value < 0) offeredAmountInput.value = 0;
        });
    });
</script>
@endsection
