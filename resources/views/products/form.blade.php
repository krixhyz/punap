<div class="bg-white shadow-lg rounded-2xl p-6 border border-gray-100">
    <form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @if ($method === 'PUT')
            @method('PUT')
        @endif

        {{-- Title --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Title</label>
            <input type="text" name="title"
                   value="{{ old('title', $product->title ?? '') }}"
                   class="w-full border-gray-300 rounded-lg text-sm p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
        </div>

        {{-- Description --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
            <textarea name="description" rows="3"
                      class="w-full border-gray-300 rounded-lg text-sm p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" required>{{ old('description', $product->description ?? '') }}</textarea>
        </div>

        {{-- Category --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Category</label>
            <select name="category" required
                    class="w-full border-gray-300 rounded-lg text-sm p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <option value="">Select Category</option>
                @foreach(['electronics', 'clothing', 'furniture', 'general'] as $cat)
                    <option value="{{ $cat }}" {{ old('category', $product->category ?? '') == $cat ? 'selected' : '' }}>
                        {{ ucfirst($cat) }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Listing Type --}}
        @php
            $selectedTypes = old('listing_type', $product->type ?? []);
            if (is_string($selectedTypes)) {
                $selectedTypes = json_decode($selectedTypes, true) ?? [$selectedTypes];
            }
        @endphp
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Listing Type</label>
            <div class="flex flex-wrap gap-3">
                @foreach (['sell' => 'Sell', 'rent' => 'Rent', 'swap' => 'Swap'] as $value => $label)
                    <label class="flex items-center space-x-1">
                        <input type="checkbox" name="listing_type[]" value="{{ $value }}"
                               {{ in_array($value, $selectedTypes) ? 'checked' : '' }}
                               class="rounded text-blue-600 focus:ring-blue-500">
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Quantity --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Quantity (Units Available)</label>
            <input type="number" name="quantity" min="1"
                   value="{{ old('quantity', $product->quantity ?? 1) }}"
                   class="w-full border-gray-300 rounded-lg text-sm p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
        </div>

        {{-- Sell Price --}}
        <div id="priceSection" class="transition-all duration-300 {{ in_array('sell', $selectedTypes) ? '' : 'hidden' }}">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Price (For Sale)</label>
            <input type="number" name="price" step="0.01"
                   value="{{ old('price', $product->price ?? '') }}"
                   class="w-full border-gray-300 rounded-lg text-sm p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
        </div>

        {{-- Rent Section --}}
        <div id="rentSection" class="transition-all duration-300 space-y-4 border p-4 rounded-xl bg-gray-50 {{ in_array('rent', $selectedTypes) ? '' : 'hidden' }}">
            <h3 class="text-sm font-semibold text-gray-800">Rental Details</h3>

            <div>
                <label class="block text-sm font-medium text-gray-700">Rent Deposit</label>
                <input type="number" step="0.01" name="rent_deposit"
                       value="{{ old('rent_deposit', $product->rentals->rent_deposit ?? '') }}"
                       class="w-full border-gray-300 rounded-lg text-sm p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Rent Fare</label>
                <input type="number" step="0.01" name="rent_fare"
                       value="{{ old('rent_fare', $product->rentals->rent_fare ?? '') }}"
                       class="w-full border-gray-300 rounded-lg text-sm p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Start Date</label>
                    <input type="date" name="available_from" id="startDate"
                           value="{{ old('start_date', $product->rentals->start_date ?? '') }}"
                           class="w-full border-gray-300 rounded-lg text-sm p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">End Date</label>
                    <input type="date" name="end_date" id="endDate"
                           value="{{ old('end_date', $product->rentals->end_date ?? '') }}"
                           class="w-full border-gray-300 rounded-lg text-sm p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Available Duration (days)</label>
                <input type="number" name="rent_duration" id="rentDuration"
                       value="{{ old('rent_duration', $product->rentals->available_duration ?? '') }}"
                       class="w-full border-gray-300 rounded-lg text-sm p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" readonly>
            </div>
        </div>

        {{-- Image --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Product Image</label>
            @if(!empty($product->image))
                <img src="{{ asset('storage/'.$product->image) }}" alt="Product image" class="w-24 h-24 mb-2 rounded-lg object-cover">
            @endif
            <input type="file" name="image"
                   class="w-full text-sm file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer">
        </div>

        {{-- Submit --}}
        <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg transition-colors">
            {{ $buttonText }}
        </button>
    </form>
</div>

<script>

    document.querySelectorAll('input[type="number"]').forEach(input => {
    input.addEventListener('input', () => {
        if (parseFloat(input.value) < 0) {
            input.value = '';
        }
    });
});

    const rentCheckbox = document.querySelector('input[value="rent"]');
    const sellCheckbox = document.querySelector('input[value="sell"]');
    const rentSection = document.getElementById('rentSection');
    const priceSection = document.getElementById('priceSection');
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    const rentDuration = document.getElementById('rentDuration');
    const swapCheckbox = document.querySelector('input[value="swap"]');


    // Set min dates to today
    const today = new Date().toISOString().split('T')[0];
    startDate.setAttribute('min', today);
    endDate.setAttribute('min', today);

    // Show/hide sections based on checkboxes
    function updateSections() {
        rentSection.classList.toggle('hidden', !rentCheckbox.checked);
        priceSection.classList.toggle('hidden', !(sellCheckbox.checked || swapCheckbox.checked));

    }
    rentCheckbox.addEventListener('change', updateSections);
    sellCheckbox.addEventListener('change', updateSections);
    swapCheckbox.addEventListener('change', updateSections);
    document.addEventListener('DOMContentLoaded', updateSections);

    // Duration auto-calc
    function updateDuration() {
        if (!startDate.value || !endDate.value) return;
        const start = new Date(startDate.value);
        const end = new Date(endDate.value);
        if (end < start) endDate.value = startDate.value;
        const diff = (end - start) / (1000 * 60 * 60 * 24) + 1;
        rentDuration.value = Math.max(1, Math.floor(diff));
    }
    startDate?.addEventListener('change', updateDuration);
    endDate?.addEventListener('change', updateDuration);

    // Run once on load for edit
    updateDuration();
</script>
